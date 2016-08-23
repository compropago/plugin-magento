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
require_once(Mage::getBaseDir('lib') . DS . 'Compropago' . DS . 'vendor' . DS . 'autoload.php');

use CompropagoSdk\Client;
use CompropagoSdk\Models\PlaceOrderInfo;
use CompropagoSdk\Tools\Validations;

class Compropago_CpPayment_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_code                   = 'cppayment';
    protected $_formBlockType          = 'cppayment/form';

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

        if (!($data instanceof Varien_Object)){
            $data = new Varien_Object($data);
        }

        if ($data->getStoreCode() != ''){
            $store_code = $data->getStoreCode();
        } else {
            $store_code = null;
        }

        if($customer->getFirstname()){
            $info = array(
                "payment_type" => $store_code,
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
                "payment_type" => $store_code,
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

        if($paymentAction != 'sale'){
            return $this;
        }

        // Set the default state of the new order.
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT; // state now = 'pending_payment'
        $default_status = 'pending';

        $stateObject->setState($state);
        $stateObject->setStatus($default_status);
        $stateObject->setIsNotified(false);

        $sessionCheckout = Mage::getSingleton('checkout/session');
        $quoteId         = $sessionCheckout->getQuoteId();
        $quote           = Mage::getSingleton('checkout/session')->getQuote($quoteId);
        $orderId         = $quote->getReservedOrderId();
        $order           = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $grandTotal      = (float)$order->getBaseGrandTotal();
        $convertQuote    = Mage::getSingleton('sales/convert_quote');
        $order           = $convertQuote->toOrder($quote);
        $orderNumber     = $order->getIncrementId();
        $order1          = Mage::getModel('sales/order')->loadByIncrementId($orderNumber);


        $name = "";
        foreach ($order1->getAllItems() as $item) {
            $name .= $item->getName();
        }


        $infoIntance = $this->getInfoInstance();
        $info = unserialize($infoIntance->getAdditionalData());


        $order = new PlaceOrderInfo(
            $orderNumber,
            $name,
            $grandTotal,
            $info['customer_name'],
            $info['customer_email'],
            $info['payment_type'],
            null,
            'magento',
            Mage::getVersion()
        );


        try
        {
            $client = new Client(
                $this->getConfigData('compropago_publickey'),
                $this->getConfigData('compropago_privatekey'),
                (int)trim($this->getConfigData('compropago_mode')) == 1 ? true : false
            );

            $response = $client->api->placeOrder($order);

            if (empty($response->getId())) {
                Mage::throwException("El servicio de ComproPago no se encuentra disponible.");
            }

            Mage::getSingleton('core/session')->setCompropagoId($response->getId());



            /* ************************************************************************
                                    RUTINAS DE BASE DE DATOS
            ************************************************************************ */



            $DB = Mage::getSingleton('core/resource')->getConnection('core_write');
            $prefix = Mage::getConfig()->getTablePrefix();

            $date = time();
            $ioin = base64_encode(serialize($order));
            $ioout = base64_encode(serialize($response));



            /* TABLE compropago_orders
             ------------------------------------------------------------------------*/


            $DB->insert($prefix."compropago_orders", array(
                'date'             => $date,
                'modified'         => $date,
                'compropagoId'     => $response->getId(),
                'compropagoStatus' => $response->getStatus(),
                'storeCartId'      => $orderNumber,
                'storeOrderId'     => $orderNumber,
                'storeExtra'       => 'COMPROPAGO_PENDING',
                'ioIn'             => $ioin,
                'ioOut'            => $ioout
            ));


            /* TABLE compropago_transactions
             ------------------------------------------------------------------------*/

            $DB->insert($prefix."compropago_transactions", array(
                'orderId'              => $orderNumber,
                'date'                 => $date,
                'compropagoId'         => $response->getId(),
                'compropagoStatus'     => $response->getStatus(),
                'compropagoStatusLast' => $response->getStatus(),
                'ioIn'                 => $ioin,
                'ioOut'                => $ioout
            ));


        }catch (Exception $error){
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

        $sessionCheckout = Mage::getSingleton('checkout/session');
        $quoteId         = $sessionCheckout->getQuoteId();
        $quote           = Mage::getSingleton('checkout/session')->getQuote($quoteId);
        $orderId         = $quote->getReservedOrderId();
        $order           = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $grandTotal      = (float)$order->getBaseGrandTotal();

        $providers = $client->api->listProviders(false, $grandTotal);
        $filter    = explode(',', $this->getConfigData('compropago_provider_available'));

        $record = array();
        foreach ($providers as $provider){
            foreach ($filter as $value){
                if($provider->internal_name == $value){
                    $record[] = $provider;
                }
            }
        }

        return $record;
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

        if($enabled){
            if(!empty($publickey) && !empty($privatekey) ){
                try{
                    $client = new Client(
                        $publickey,
                        $privatekey,
                        $live
                    );
                    $compropagoResponse = Validations::evalAuth($client);
                    //eval keys
                    if(!Validations::validateGateway($client)){
                        $error[1] = 'Invalid Keys, The Public Key and Private Key must be valid before using this module.';
                        $error[0] = true;
                    }else{
                        if($compropagoResponse->mode_key != $compropagoResponse->livemode){
                            // compropagoKey vs compropago Mode
                            $error[1] = 'Your Keys and Your ComproPago account are set to different Modes.';
                            $error[0] = true;
                        }else{
                            if($live != $compropagoResponse->livemode){
                                // store Mode vs compropago Mode
                                $error[1] = 'Your Store and Your ComproPago account are set to different Modes.';
                                $error[0] = true;
                            }else{
                                if($live != $compropagoResponse->mode_key){
                                    // store Mode vs compropago Keys
                                    $error[1] = 'ComproPago ALERT:Your Keys are for a different Mode.';
                                    $error[0] = true;
                                }else{
                                    if(!$compropagoResponse->mode_key && !$compropagoResponse->livemode){
                                        //can process orders but watch out, NOT live operations just testing
                                        $error[1] = 'WARNING: ComproPago account is Running in TEST Mode, NO REAL OPERATIONS';
                                        $error[0] = true;
                                    }
                                }
                            }
                        }
                    }
                }catch (Exception $e) {
                    //something went wrong on the SDK side
                    $error[2] = 'no';
                    $error[1] = $e->getMessage(); //may not be show or translated
                    $error[0] = true;
                }
            }else{
                $error[1] = 'The Public Key and Private Key must be set before using ComproPago';
                $error[2] = 'no';
                $error[0] = true;
            }
        }else{
            $error[1] = 'ComproPago is not Enabled';
            $error[2] = 'no';
            $error[0] = true;
        }
        return $error;
    }
}