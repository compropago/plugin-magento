<?php

require_once Mage::getBaseDir('lib') . '/ComproPago/vendor/autoload.php';

use CompropagoSdk\Factory\Factory;
use CompropagoSdk\Client;
use CompropagoSdk\Tools\Request;
use CompropagoSdk\Tools\Validations;


class ComproPago_Webhook_WebhookController extends Mage_Core_Controller_Front_Action
{
    private $publicKey;
    private $privateKey;
    private $mode;

    /**
     * Load ComproPago base configuration
     */
    private function initConfig()
    {
        $this->publicKey = Mage::getStoreConfig('payment/base/publickey');
        $this->privateKey = Mage::getStoreConfig('payment/base/privatekey');
        $this->mode = intval(Mage::getStoreConfig('payment/base/mode')) == 1;
    }

    /**
     * Main webhook action
     * @throws Exception
     */
    public function indexAction()
    {
        header('Content-Type: application/json');
        $request = @file_get_contents('php://input');

        if (empty($request) || !$respWebhook = Factory::getInstanceOf('CpOrderInfo', $request)) {
            $message = 'Invalid request';
            throw new \Exception($message);
        }

        $this->initConfig();

        if (empty($this->publicKey) || empty($this->privateKey)) {
            $message = 'Invalid plugin keys';
            throw new \Exception($message);
        }
        try {
            $client = new Client($this->publicKey, $this->privateKey, $this->mode);
            Validations::validateGateway($client);

            if ($respWebhook->short_id == "000000") {
                die(json_encode([
                    'status' => 'success',
                    'message' => 'OK - TEST',
                    'short_id' => $respWebhook->short_id,
                    'reference' => null
                ]));
            }

            /**
             * Get te extra information to validate the payment
            ------------------------------------------------------------------------*/

            $order = new Mage_Sales_Model_Order();
            $order->loadByIncrementId($respWebhook->order_info->order_id);

            if (empty($order->getId())) {
                $message = 'Not found order: ' . $respWebhook->order_info->order_id;
                throw new \Exception($message);
            }

            $payment = $order->getPayment();
            $extraInfo = $payment->getAdditionalInformation();

            if (empty($extraInfo)) {
                $message = 'Error verifying order: ' . $order->getId();
                throw new \Exception($message);
            }

            if ($extraInfo['id'] != $respWebhook->id) {
                $message = 'The order is not from this store' .
                    $extraInfo['id'] . ':' . $respWebhook->id;
                throw new \Exception($message);
            }

            switch ($payment->getMethod()) {
                case 'spei':
                    $this->processSpei($order, $payment, $extraInfo, $respWebhook);
                    break;
                case 'cash':
                    $this->processCash($order, $payment, $extraInfo, $respWebhook);
                    break;
                default:
                    $message = 'Payment method ' . $payment->getMethod() . ' not allowed';
                    throw new \Exception($message);
            }
        } catch (\Exception $e) {
            die(json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'short_id' => null,
                'reference' => null
            ]));
        }
    }

    /**
     * Process Cash payments flow
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param array $extraInfo
     * @param Object $request
     * @throws Exception
     */
    private function processCash(&$order, &$payment, $extraInfo, $request)
    {
        $client = new Client($this->publicKey, $this->privateKey, $this->mode);

        $verify = $client->api->verifyOrder($request->id);
        $status = $verify->type;

        $this->updateStatus($order, $status);
        $this->save($order, $payment, $extraInfo);

        die(json_encode([
            'status' => 'success',
            'message' => 'OK - ' . $status . ' - CASH',
            'short_id' => $extraInfo['short_id'],
            'reference' => $order->getIncrementId()
        ]));
    }

    /**
     * Process Spei payments flow
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param array $extraInfo
     * @param Object $request
     * @throws Exception
     */
    private function processSpei(&$order, &$payment, $extraInfo, $request)
    {
        $url = 'https://api.compropago.com/v2/orders/' . $request->id;
        $auth = [
            "user" => $this->privateKey,
            "pass" => $this->publicKey
        ];

        $response = Request::get($url, [], $auth);

        if ($response->statusCode != 200) {
            $message = "Can't verify order: {$response->body}";
            throw new \Exception($message);
        }

        $body = json_decode($response->body);

        $verify = $body->data;
        $status = '';

        switch ($verify->status) {
            case 'PENDING':
                $status = 'charge.pending';
                break;
            case 'ACCEPTED':
                $status = 'charge.success';
                break;
            case 'EXPIRED':
                $status = 'charge.expired';
                break;
        }

        $this->updateStatus($order, $status);
        $this->save($order, $payment, $extraInfo);

        die(json_encode([
            'status' => 'success',
            'message' => 'OK - ' . $status . ' - SPEI',
            'short_id' => $extraInfo['short_id'],
            'reference' => $order->getIncrementId()
        ]));
    }

    /**
     * Change Order Status
     * @param Mage_Sales_Model_Order $order
     * @param string $status
     * @throws Exception
     */
    private function updateStatus(&$order, $status)
    {
        switch ($status) {
            case 'charge.pending':
                $state = Mage_Sales_Model_Order::STATE_NEW;
                $status = "pending";
                $order->setData('state', $state);
                $order->setStatus($status);
                break;
            case 'charge.success':
                $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                $status = "processing";
                $order->setData('state', $state);
                $order->setStatus($status);

                $message = 'ComproPago automatically confirmed payment for this order.';

                $history = $order->addStatusHistoryComment($message);
                $history->setIsCustomerNotified(true);
                $history->save();
                break;
            case 'charge.expired':
                $state = Mage_Sales_Model_Order::STATE_CANCELED;
                $status = "canceled";
                $order->setData('state', $state);
                $order->setStatus($status);

                $message = 'The user has not completed the payment and the order was cancelled.';

                $history = $order->addStatusHistoryComment($message);
                $history->setIsCustomerNotified(false);
                $history->save();
                break;
            default:
                $message = 'Status ' . $status . 'not allowed';
                throw new \Exception($message);
        }
    }

    /**
     * Save Information
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param array $extraInfo
     * @throws Exception
     */
    private function save(&$order, $payment, $extraInfo)
    {
        $orderId = $order->getId();

        $payment->save();
        $order->save();

        $resource = Mage::getSingleton('core/resource');
        $dbWrite = $resource->getConnection('core_write');

        $table = $resource->getTableName('sales_flat_order_payment');
        $info = serialize($extraInfo);

        $query = "UPDATE $table SET additional_information = '$info' WHERE parent_id = $orderId";
        $dbWrite->query($query);
    }
}