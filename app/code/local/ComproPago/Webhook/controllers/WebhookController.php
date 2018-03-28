<?php

require_once Mage::getBaseDir('lib') . DS . 'ComproPago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Factory\Factory;
use CompropagoSdk\Client;
use CompropagoSdk\Tools\Validations;

class ComproPago_Webhook_WebhookController extends Mage_Core_Controller_Front_Action
{
    protected $_model = null;

    /**
     * ComproPago_Cash_IndexController Constructor
     */
    public function _construct()
    {
        $this->_model = Mage::getModel('ComproPago_Cash_Model_Cash');
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
            die(json_encode([
                'status' => 'error',
                'message' => 'Invalid request',
                'short_id' => null,
                'reference' => null
            ]));
        }

        $publickey     = $this->_model->getConfigData('compropago_publickey');
        $privatekey    = $this->_model->getConfigData('compropago_privatekey');
        $live          = (int)trim($this->_model->getConfigData('compropago_mode')) == 1 ? true : false;

        if (empty($publickey) || empty($privatekey)) {
            die(json_encode([
                'status' => 'error',
                'message' => 'invalid plugin keys',
                'short_id' => null,
                'reference' => null
            ]));
        }

        try {
            $client = new Client($publickey, $privatekey, $live);
            Validations::validateGateway($client);

            if ($respWebhook->short_id == "000000") {
                die(json_encode([
                    'status' => 'success',
                    'message' => 'test OK',
                    'short_id' => $respWebhook->short_id,
                    'reference' => null
                ]));
            }

            $response = $client->api->verifyOrder($respWebhook->id);
            if ($response->type == 'error') {
                die(json_encode([
                    'status' => 'error',
                    'message' => 'error verifying order',
                    'short_id' => null,
                    'reference' => null
                ]));
            }

            /* ************************************************************************
            *                        RUTINAS DE BASE DE DATOS                         *
            ************************************************************************ */

            $DBread  = Mage::getSingleton('core/resource')->getConnection('core_read');
            $DBwrite = Mage::getSingleton('core/resource')->getConnection('core_write');
            $prefix  = Mage::getConfig()->getTablePrefix();

            $ioin  = base64_encode(serialize($respWebhook));
            $ioout = base64_encode(serialize($response));
            $date  = Mage::getModel('core/date')->timestamp();

            $sql = "SELECT * FROM " . $prefix . "compropago_orders where compropagoId = '{$response->id}'";
            $res = $DBread->fetchAll($sql);

            $storedId = $res[0]['storeOrderId'];

            if (empty($storedId)) {
                die(json_encode([
                    'status' => 'error',
                    'message' => 'charge not found in store',
                    'short_id' => null,
                    'reference' => null
                ]));
            }

            /* Rutinas de aprovación
             ------------------------------------------------------------------------*/

            $_order = Mage::getModel('sales/order')->loadByIncrementId($response->order_info->order_id);

            switch ($response->type) {
                case 'charge.pending':
                    $state = Mage_Sales_Model_Order::STATE_NEW;
                    $status = "pending";
                    $_order->setData('state', $state);
                    $_order->setStatus($status);
                    $_order->save();
                    $nomestatus = 'COMPROPAGO_PENDING';
                    break;
                case 'charge.success':
                    $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                    $status = "processing";
                    $_order->setData('state', $state);
                    $_order->setStatus($status);
                    $message = 'ComproPago automatically confirmed payment for this order.';
                    $history = $_order->addStatusHistoryComment($message);
                    $history->setIsCustomerNotified(true);
                    $_order->save();
                    $nomestatus = 'COMPROPAGO_SUCCESS';
                    break;
                case 'charge.expired':
                    $state = Mage_Sales_Model_Order::STATE_CANCELED;
                    $status = "canceled";
                    $_order->setData('state', $state);

                    $_order->setStatus($status);

                    $message = 'The user has not completed the payment and the order was cancelled.';
                    $history = $_order->addStatusHistoryComment($message);

                    $history->setIsCustomerNotified(false);

                    $_order->save();
                    $nomestatus = 'COMPROPAGO_EXPIRED';
                    break;
                default:
                    $_order->save();
                    die(json_encode([
                        'status' => 'error',
                        'message' => 'invalid status',
                        'short_id' => null,
                        'reference' => null
                    ]));
            }

            /* TABLE compropago_orders
             ------------------------------------------------------------------------*/

            $updateData = array(
                'modified'         => $date,
                'compropagoStatus' => $response->type,
                'storeExtra'       => $nomestatus,
            );

            $DBwrite->update($prefix."compropago_orders",  $updateData, 'id='. $res[0]['id']);

            /* TABLE compropago_transactions
             ------------------------------------------------------------------------*/

            $dataInsert = array(
                'orderId'              => $storedId,
                'date'                 => $date,
                'compropagoId'         => $response->id,
                'compropagoStatus'     => $response->type,
                'compropagoStatusLast' => $res[0]['compropagoStatus'],
                'ioIn'                 => $ioin,
                'ioOut'                => $ioout
            );

            $DBwrite->insert($prefix."compropago_transactions", $dataInsert);

            die(json_encode([
                'status' => 'succeess',
                'message' => 'OK - ' . $response->type,
                'short_id' => $response->short_id,
                'reference' => $response->order_info->order_id
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'short_id' => null,
                'reference' => null
            ]));
        }
    }
}