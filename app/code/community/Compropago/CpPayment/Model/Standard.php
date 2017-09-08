<?php

require_once Mage::getBaseDir('lib') . DS . 'Compropago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Client;
use CompropagoSdk\Factory\Factory;
use CompropagoSdk\Tools\Validations;

class Compropago_CpPayment_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_code                   = 'cppayment';
    protected $_formBlockType          = 'cppayment/form';
    protected $_infoBlockType          = 'cppayment/info';

    protected $_canUseForMultiShipping = false;
    protected $_canUseInternal         = false;
    protected $_isInitializeNeeded     = true;

    /**
     * Assing init data
     *
     * @param $data
     * @return $this
     *
     * @author Eduardo Aguilar <dante.aguilar41@gmail.com>
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
     * Generate order
     *
     * @param $paymentAction
     * @param $stateObject
     * @return $this
     *
     * @author Eduardo Aguilar <dante.aguilar41@gmail.com>
     */
    public function initialize($paymentAction, $stateObject)
    {
        parent::initialize($paymentAction, $stateObject);

        if ($paymentAction != 'sale') {
            return $this;
        }

        // Set the default state of the new order.
        $state = Mage_Sales_Model_Order::STATE_NEW; // state now = 'pending_payment'
        $defaultStatus = 'pending';

        $stateObject->setState($state);
        $stateObject->setStatus($defaultStatus);
        $stateObject->setIsNotified(false);

        $sessionCheckout = Mage::getSingleton('checkout/session');
        $quoteId         = $sessionCheckout->getQuoteId();

        $quote           = Mage::getSingleton('checkout/session')->getQuote($quoteId);
        $orderId         = $quote->getReservedOrderId();
        $shipping        = $quote->getShippingAddress();

        $order           = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $grandTotal      = (float)$order->getBaseGrandTotal();

        $convertQuote    = Mage::getSingleton('sales/convert_quote');
        $order           = $convertQuote->toOrder($quote);
        $orderNumber     = $order->getIncrementId();

        $orderOne          = Mage::getModel('sales/order')->loadByIncrementId($orderNumber);
        $orderOne->setVisibleOnFront(1);


        $name = "";
        foreach ($orderOne->getAllItems() as $item) {
            $name .= $item->getName();
        }


        $infoIntance = $this->getInfoInstance();
        $info = unserialize($infoIntance->getAdditionalData());

        try {
            $order_info = array(
                'order_id' => $orderNumber,
                'order_name' => $name,
                'order_price' => $grandTotal,
                'customer_name' => $info['customer_name'],
                'customer_email' => $info['customer_email'],
                'payment_type' => $info['payment_type'],
                'currency' => Mage::app()->getStore()->getCurrentCurrencyCode()
            );

            $order = Factory::getInstanceOf('PlaceOrderInfo', $order_info);

            $client = new Client(
                $this->getConfigData('compropago_publickey'),
                $this->getConfigData('compropago_privatekey'),
                (int)trim($this->getConfigData('compropago_mode')) == 1 ? true : false
            );

            $response = $client->api->placeOrder($order);

            if (empty($response->id)) {
                Mage::throwException("El servicio de ComproPago no se encuentra disponible.");
            }

            Mage::getSingleton('core/session')->setCompropagoId($response->id);

            /* ************************************************************************
                                    ASIGNAR COMPRA AL USUARIO
            ************************************************************************ */

            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId(1);
            $customer->loadByEmail($info['customer_email']);
            $message = 'The user has not completed the payment process yet.';
            $orderbyid = Mage::getModel('sales/order')->loadByIncrementId($orderNumber);
            $orderbyid->setCustomerId($customer->getId());
            $orderbyid->setCustomerFirstname($customer->getFirstname());
            $orderbyid->setCustomerLastname($customer->getLastname());
            $orderbyid->setCustomerEmail($customer->getEmail());
            $history = $orderbyid->addStatusHistoryComment($message);
            $history->setIsCustomerNotified(true);
            $orderbyid->save();

            /* ************************************************************************
                                    RUTINAS DE BASE DE DATOS
            ************************************************************************ */

            $DB = Mage::getSingleton('core/resource')->getConnection('core_write');
            $prefix = Mage::getConfig()->getTablePrefix();

            $date  = Mage::getModel('core/date')->timestamp(); // time standart function
            $ioin  = base64_encode(serialize($order));
            $ioout = base64_encode(serialize($response));

            /* TABLE compropago_orders
             ------------------------------------------------------------------------*/
            $dataInsert = array(
                'date'             => $date,
                'modified'         => $date,
                'compropagoId'     => $response->id,
                'compropagoStatus' => $response->type,
                'storeCartId'      => $orderNumber,
                'storeOrderId'     => $orderNumber,
                'storeExtra'       => 'COMPROPAGO_PENDING',
                'ioIn'             => $ioin,
                'ioOut'            => $ioout
            );

            $DB->insert($prefix."compropago_orders", $dataInsert);

            /* TABLE compropago_transactions
             ------------------------------------------------------------------------*/

            $dataInsert = array(
                'orderId'              => $orderNumber,
                'date'                 => $date,
                'compropagoId'         => $response->id,
                'compropagoStatus'     => $response->type,
                'compropagoStatusLast' => $response->type,
                'ioIn'                 => $ioin,
                'ioOut'                => $ioout
            );

            $DB->insert($prefix."compropago_transactions", $dataInsert);
        } catch (Exception $error) {
            Mage::throwException($error->getMessage());
        }

        return $this;
    }

    /**
     * Obtain available providers
     *
     * @return array
     *
     * @author Eduardo Aguilar <dante.aguilar41@gmail.com>
     */
    public function getProviders()
    {
        $client = new Client(
            $this->getConfigData('compropago_publickey'),
            $this->getConfigData('compropago_privatekey'),
            (int)trim($this->getConfigData('compropago_mode')) == 1 ? true : false
        );

        $quote      = Mage::getModel('checkout/session')->getQuote();
        $quoteData  = $quote->getData();
        $grandTotal = $quoteData['grand_total'];

        $providers = $client->api->listProviders($grandTotal, Mage::app()->getStore()->getCurrentCurrencyCode());
        $filter    = explode(',', $this->getConfigData('compropago_provider_available'));

        $record = array();
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
     * Get payment method title or image banner
     *
     * @param bool $isInfo
     * @return string
     *
     * @author Eduardo Aguilar <dante.aguilar41@gmail.com>
     */
    public function getTitle($isInfo = false)
    {
        if ($isInfo) {
            return $this->getConfigData('title');
        } else {
            $logo = (int)trim($this->getConfigData('compropago_show_title_logo')) == 1 ? true : false;
            return $logo ? "" : $this->getConfigData('title');
        }
    }

    /**
     * Verify if the plugin is allow to show image buttons for providers
     *
     * @return bool
     *
     * @author Eduardo Aguilar <dante.aguilar41@gmail.com>
     */
    public function showLogoProviders()
    {
        return (int)trim($this->getConfigData("compropago_showlogo")) == 1 ? true : false;
    }


    /**
     * Return warning messages after save configuration
     *
     * @param $enabled
     * @param $publickey
     * @param $privatekey
     * @param $live
     * @return array
     *
     * @author Eduardo Aguilar <dante.aguilar41@gmail.com>
     */
    public function hookRetro($enabled, $publickey, $privatekey, $live)
    {
        if (!$enabled) {
            return array(
                true,
                'ComproPago is not Enabled',
                'no'
            );
        }

        if (empty($publickey) || empty($privatekey)) {
            return array(
                true,
                'The Public Key and Private Key must be set before using ComproPago',
                'no'
            );
        }

        try {
            $client = new Client(
                $publickey,
                $privatekey,
                $live
            );

            $compropagoResponse = Validations::evalAuth($client);
            //eval keys
            if (!Validations::validateGateway($client)) {
                return array(
                    true,
                    'Invalid Keys, The Public Key and Private Key must be valid before using this module.',
                    'no'
                );
            }

            if ($compropagoResponse->mode_key != $compropagoResponse->livemode) {
                return array(
                    true,
                    'Your Keys and Your ComproPago account are set to different Modes.',
                    'no'
                );
            }

            if ($live != $compropagoResponse->livemode) {
                return array(
                    true,
                    'Your Store and Your ComproPago account are set to different Modes.',
                    'no'
                );
            }

            if ($live != $compropagoResponse->mode_key) {
                return array(
                    true,
                    'Your ComproPago Keys are for a different Mode.',
                    'no'
                );
            }

            if (!$compropagoResponse->mode_key && !$compropagoResponse->livemode) {
                return array(
                    true,
                    'ComproPago account is Running in TEST Mode, NO REAL OPERATIONS',
                    'no'
                );
            }
        } catch (Exception $e) {
            return array(
                true,
                $e->getMessage(),
                'no'
            );
        } 

        return array(
            false,
            '',
            'yes'
        );
    }
}
