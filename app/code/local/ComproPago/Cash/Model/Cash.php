<?php

require_once Mage::getBaseDir('lib') . DS . 'ComproPago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Client;
use CompropagoSdk\Factory\Factory;

class ComproPago_Cash_Model_Cash extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'cash';
    protected $_formBlockType = 'cash/form';
    protected $_infoBlockType = 'cash/info';
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = true; //This can use in admin
    protected $_canUseCheckout = true; //This can use in onepage checkout
    protected $_canUseForMultishipping = true; //This can use in multishipping

    /**
     * Assing data to the order process
     * @param mixed $data
     * @return $this|Mage_Payment_Model_Info
     * @throws Varien_Exception
     */
    public function assignData($data)
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        if ($data->getStoreCode() != '') {
            $storeCode = $data->getStoreCode();
        } else {
            $storeCode = null;
        }

        if ($customer->getFirstname()) {
            $info = array(
                "payment_type" => $storeCode,
                "customer_name" => htmlentities($customer->getFirstname()),
                "customer_email" => htmlentities($customer->getEmail()),
                "customer_phone" => $data->getCustomerPhone()
            );
        } else {
            $sessionCheckout = Mage::getSingleton('checkout/session');

            $quote = $sessionCheckout->getQuote();
            $billingAddress = $quote->getBillingAddress();
            $billing = $billingAddress->getData();

            $info = array(
                "payment_type" => $storeCode,
                "customer_name" => htmlentities($billing['firstname']),
                "customer_email" => htmlentities($billing['email']),
                "customer_phone" => $data->getCustomerPhone()
            );
        }

        $infoInstance = $this->getInfoInstance();
        $infoInstance->setAdditionalData(serialize($info));
        return $this;
    }

    /**
     * Return the providers array in checkout
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     * @throws Varien_Exception
     */
    public function getProviders()
    {
        $publicKey = Mage::getStoreConfig('payment/base/publickey');
        $privateKey = Mage::getStoreConfig('payment/base/privatekey');
        $mode = intval(Mage::getStoreConfig('payment/base/mode')) == 1;

        $client = new Client($publicKey, $privateKey, $mode);

        $quote = Mage::getModel('checkout/session')->getQuote();

        $quoteData = $quote->getData();
        $grandTotal = $quoteData['grand_total'];

        $providers = $client->api->listProviders(
            $grandTotal,
            Mage::app()->getStore()->getCurrentCurrencyCode()
        );

        $filter = explode(',', $this->getConfigData('providers_available'));
        $record = [];

        foreach ($providers as $provider) {
            foreach ($filter as $value) {
                if ($provider->internal_name == $value) {
                    $record[] = $provider;
                }
            }
        }

        return $record;
    }

    /**
     * Return the title method according with the context
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    /**
     * Main process to create order
     * @param string $paymentAction
     * @param object $stateObject
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        parent::initialize($paymentAction, $stateObject);

        if ($paymentAction != 'sale') {
            return $this;
        }

        $publicKey = Mage::getStoreConfig('payment/base/publickey');
        $privateKey = Mage::getStoreConfig('payment/base/privatekey');
        $mode = intval(Mage::getStoreConfig('payment/base/mode')) == 1;

        // Set the default state of the new order.
        $state = Mage_Sales_Model_Order::STATE_NEW;
        $defaultStatus = 'pending';

        $stateObject->setState($state);
        $stateObject->setStatus($defaultStatus);
        $stateObject->setIsNotified(true);

        $session = Mage::getSingleton('checkout/session');
        $coreSession = Mage::getSingleton('core/session');
        $orderModel = Mage::getModel('sales/order');
        $convertQuote = Mage::getSingleton('sales/convert_quote');
        $customer = Mage::getModel('customer/customer');

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

        try {
            $details = array(
                'order_id' => $orderNumber,
                'order_name' => $name,
                'order_price' => $grandTotal,
                'customer_name' => $info['customer_name'],
                'customer_email' => $info['customer_email'],
                'payment_type' => $info['payment_type'],
                'app_client_name' => 'magento',
                'app_client_version' => Mage::getVersion(),
                'currency' => Mage::app()->getStore()->getCurrentCurrencyCode()
            );

            $orderInfo = Factory::getInstanceOf('PlaceOrderInfo', $details);

            $client = new Client($publicKey, $privateKey, $mode);

            $response = $client->api->placeOrder($orderInfo);

            if (empty($response->id)) {
                Mage::throwException("El servicio de ComproPago no se encuentra disponible.");
            }

            $coreSession->setComproPagoId($response->id);
            $coreSession->setComproPagoShortId($response->short_id);
            $coreSession->setComproPagoStore($info['payment_type']);

            /* ************************************************************************
                                    ASIGNAR COMPRA AL USUARIO
            ************************************************************************ */

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
                'short_id' => $response->short_id,
                'store' => $info['payment_type'],
                'order_id' => $order->getId()
            ];

            $coreSession->setComproPagoExtraData(serialize($additional));
        } catch (Exception $error) {
            Mage::throwException($error->getMessage());
        }
        return $this;
    }
}
