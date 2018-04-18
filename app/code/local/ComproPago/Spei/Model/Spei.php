<?php

require_once Mage::getBaseDir('lib') . DS . 'ComproPago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Tools\Request;

class ComproPago_Spei_Model_Spei extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'spei';
    protected $_formBlockType = 'spei/form';
    protected $_infoBlockType = 'spei/info';
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;

    /**
     * Assing user information to precess the order
     * @param mixed $data
     * @return $this|Mage_Payment_Model_Info
     * @throws Varien_Exception
     */
    public function assignData($data)
    {
        $customerSession = Mage::getSingleton('customer/session');
        $checkoutSession = Mage::getSingleton('checkout/session');

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        if ($customerSession->getFirstname()) {
            $info = [
                "customer_name" => htmlentities($customerSession->getFirstname()),
                "customer_email" => htmlentities($customerSession->getEmail()),
                "customer_phone" => $data->getCustomerPhone()
            ];
        } else {
            $quote = $checkoutSession->getQuote();
            $billingAddress = $quote->getBillingAddress();
            $billing = $billingAddress->getData();

            $info = [
                "customer_name" => htmlentities($billing['firstname']),
                "customer_email" => htmlentities($billing['email']),
                "customer_phone" => $data->getCustomerPhone()
            ];
        }

        $infoInstance = $this->getInfoInstance();
        $infoInstance->setAdditionalData(serialize($info));

        return $this;
    }

    /**
     * Main process to create order
     * @param string $paymentAction
     * @param object $stateObject
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        parent::initialize($paymentAction, $stateObject);

        if ($paymentAction != 'sale') {
            return $this;
        }

        $session = Mage::getSingleton('checkout/session');
        $coreSession = Mage::getSingleton('core/session');
        $orderModel = Mage::getModel('sales/order');
        $convertQuote = Mage::getSingleton('sales/convert_quote');
        $customer = Mage::getModel('customer/customer');

        $state = Mage_Sales_Model_Order::STATE_NEW;
        $defaultStatus = 'pending';

        $stateObject->setState($state);
        $stateObject->setStatus($defaultStatus);
        $stateObject->setIsNotified(true);

        try {
            $quoteId = $session->getQuoteId();
            $quote = $session->getQuote($quoteId);
            $orderId = $quote->getReservedOrderId();
            $order = $orderModel->loadByIncrementId($orderId);
            $grandTotal = (float)$order->getBaseGrandTotal();
            $order = $convertQuote->toOrder($quote);
            $orderNumber = $order->getIncrementId();
            $orderOne = $orderModel->loadByIncrementId($orderNumber);
            $orderOne->setVisibleOnFront(1);

            $name = "";

            foreach ($orderOne->getAllItems() as $item) {
                $name .= $item->getName();
            }

            $infoIntance = $this->getInfoInstance();
            $info = unserialize($infoIntance->getAdditionalData());
            $currency = Mage::app()->getStore()->getCurrentCurrencyCode();

            $orderInfo = [
                "product" => [
                    "id" => "$orderNumber",
                    "url" => "",
                    "name" => $name,
                    "price" => floatval($grandTotal),
                    "currency" => $currency
                ],
                "customer" => [
                    "name" => $info['customer_name'],
                    "email" => $info['customer_email'],
                    "phone" => empty($info['customer_phone']) ? '' : $info['customer_phone']
                ],
                "payment" =>  [
                    "type" => "SPEI"
                ]
            ];

            $response = $this->speiRequest($orderInfo);

            $coreSession->setComproPagoId($response->id);


            /**
             * Asignar compra al usuario
            ------------------------------------------------------------------------- */

            $message = 'The user has not completed the payment process yet.';

            $customer->setWebsiteId(1);
            $customer->loadByEmail($info['customer_email']);
            $orderbyid = $orderModel->loadByIncrementId($orderNumber);
            $orderbyid->setCustomerId($customer->getId());
            $orderbyid->setCustomerFirstname($customer->getFirstname());
            $orderbyid->setCustomerLastname($customer->getLastname());
            $orderbyid->setCustomerEmail($customer->getEmail());
            $history = $orderbyid->addStatusHistoryComment($message);
            $history->setIsCustomerNotified(true);
            $orderbyid->save();

            /**
             * Add Additional Information
            ------------------------------------------------------------------------- */

            $additional = [
                'id' => $response->id,
                'short_id' => $response->shortId,
                'store' => 'SPEI'
            ];

            $coreSession->setComproPagoExtraData(serialize($additional));
        } catch (\Exception $e) {
            Mage::throwException($e->getMessage());
        }

        return $this;
    }

    /**
     * Create SPEI order
     * @param $data
     * @return mixed
     * @throws Mage_Core_Exception
     */
    private function speiRequest($data)
    {
        $url = 'https:/api.compropago.com/v2/orders';

        $publicKey = Mage::getStoreConfig('payment/base/publickey');
        $privateKey = Mage::getStoreConfig('payment/base/privatekey');

        $auth = [
            "user" => $privateKey,
            "pass" => $publicKey
        ];

        try {
            $response = Request::post($url, $data, array(), $auth);

            if ($response->statusCode != 200) {
                Mage::throwException("SPEI Error #: {$response->body}");
            }
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

        $body = json_decode($response->body);

        return $body->data;
    }
}