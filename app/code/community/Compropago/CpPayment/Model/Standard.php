<?php
/**
 * Copyright 2015 Compropago.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * Compropago $Library
 * @author Eduardo Aguilar <eduardo.aguilar@compropago.com>
 */
$libcp = Mage::getBaseDir('lib') . DS . 'Compropago' . DS . 'vendor' . DS . 'autoload.php';

require_once $libcp;

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
     * Asignacion inicial de informacion
     *
     * @param $data
     * @return $this
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
     * Generacion de la orden
     *
     * @param $paymentAction
     * @param $stateObject
     * @return $this
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
                'currency' => Mage::app()->getStore()->getCurrentCurrencyCode(),
                'image_url' => null,
                'app_client_name' => 'magento',
                'app_client_version' => Mage::getVersion(),
                'cp' => $shipping->getData('postcode')
            );

            if (isset($info['latitude'])) {
                $order_info['latitude'] = $info['latitude'];
            }

            if (isset($info['longitude'])) {
                $order_info['longitude'] = $info['longitude'];
            }

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
                'compropagoStatus' => $response->status,
                'storeCartId'      => $orderNumber,
                'storeOrderId'     => $orderNumber,
                'storeExtra'       => 'COMPROPAGO_PENDING',
                'ioIn'             => $ioin,
                'ioOut'            => $ioout
            );

            // DB insert ( prefix."compropago_orders",  dataInsert)

            /* TABLE compropago_transactions
             ------------------------------------------------------------------------*/

            $dataInsert = array(
                'orderId'              => $orderNumber,
                'date'                 => $date,
                'compropagoId'         => $response->id,
                'compropagoStatus'     => $response->status,
                'compropagoStatusLast' => $response->status,
                'ioIn'                 => $ioin,
                'ioOut'                => $ioout
            );

            // DB insert ( prefix."compropago_transactions",  dataInsert)
        } catch (Exception $error) {
            Mage::throwException($error->getMessage());
        }

        return $this;
    }

    /**
     * Envio de proveedores filtrados a la vista
     *
     * @return array
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
     * Esconde texto de titulo si se indico uso de logo
     *
     * @param $isInfo
     * @return mixed|string
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
     * verificacion de muestra de logos
     *
     * @return bool
     */
    public function showLogoProviders()
    {
        return (int)trim($this->getConfigData("compropago_showlogo")) == 1 ? true : false;
    }

    /**
     * Validate if have persion for obtain Glocation
     *
     * @return void
     */
    public function getGlocation() 
    {
        return (int)trim($this->getConfigData("compropago_gloaction")) == 1 ? true : false;
    }

    /**
     * Despliegue de retroalimentacion en el panel de administración
     *
     * @param bool   $enabled
     * @param string $publickey
     * @param string $privatekey
     * @param bool   $live
     * @return array
     */
    public function hookRetro($enabled, $publickey, $privatekey, $live)
    {
        $error = array(
            false,
            '',
            'yes'
        );

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
