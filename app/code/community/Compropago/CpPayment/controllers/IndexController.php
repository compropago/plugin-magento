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
 * Compropago plugin-magento
 * @author Eduardo Aguilar <eduardo.aguilar@compropago.com>
 */
require_once(Mage::getBaseDir('lib') . DS . 'Compropago' . DS . 'vendor' . DS . 'autoload.php');


use CompropagoSdk\Factory\Factory;
use CompropagoSdk\Client;
use CompropagoSdk\Tools\Validations;


class Compropago_CpPayment_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Variable que alojara el modelo
     * @var null
     */
    protected $_model = null;



    public function _construct()
    {
        $this->_model = Mage::getModel('cppayment/Standard');
    }


    public function indexAction()
    {
        /**
         * Se captura la informacion enviada desde compropago
         */
        $request = @file_get_contents('php://input');


        /**
         * Se valida el request y se transforma con la cadena a un objeto de tipo CpOrderInfo con el Factory
         */
        if(!$resp_webhook = Factory::cpOrderInfo($request)){
            die('Tipo de Request no Valido');
        }


        /**
         * Gurdamos la informacion necesaria para el Cliente
         * las llaves de compropago y el modo de ejecucion de la tienda
         */
        $publickey     = $this->_model->getConfigData('compropago_publickey');
        $privatekey    = $this->_model->getConfigData('compropago_privatekey');;
        $live          = (int)trim($this->_model->getConfigData('compropago_mode')) == 1 ? true : false;


        /**
         * Se valida que las llaves no esten vacias (No es obligatorio pero si recomendado)
         */
        if (empty($publickey) || empty($privatekey)){
            die("Se requieren las llaves de compropago");
        }


        try{
            $client = new Client(
                $publickey,
                $privatekey,
                $live
            );

            Validations::validateGateway($client);
        }catch (Exception $e) {
            die($e->getMessage());
        }


        /**
         * Verificamos si recivimos una peticion de prueba
         */
        if($resp_webhook->getId()=="ch_00000-000-0000-000000"){
            die("Probando el WebHook?, <b>Ruta correcta.</b>");
        }


        try{
            /**
             * Verificamos la informacion del Webhook recivido
             */
            $response = $client->api->verifyOrder($resp_webhook->getId());


            /**
             * Comprovamos que la verificacion fue exitosa
             */
            if($response->getType() == 'error'){
                die('Error procesando el número de orden');
            }



            /* ************************************************************************
                                    RUTINAS DE BASE DE DATOS
            ************************************************************************ */


            $DBread  = Mage::getSingleton('core/resource')->getConnection('core_read');
            $DBwrite = Mage::getSingleton('core/resource')->getConnection('core_write');
            $prefix  = Mage::getConfig()->getTablePrefix();

            $ioin  = base64_encode(serialize($resp_webhook));
            $ioout = base64_encode(serialize($response));
            $date  = time();


            $sql = "SELECT * FROM " . $prefix . "compropago_orders where compropagoId = '{$response->getId()}'";
            $res = $DBread->fetchAll($sql);

            $storedId = $res[0]['storeOrderId'];

            if(empty($storedId)){
               throw new Exception('El pago no corresponde a esta tienda.');
            }



            /* Rutinas de aprovación
             ------------------------------------------------------------------------*/

            $_order = Mage::getModel('sales/order')->loadByIncrementId($response->getOrderInfo()->getOrderId());


            /**
             * Generamos las rutinas correspondientes para cada uno de los casos posible del webhook
             */
            switch ($response->getType()){
                case 'charge.pending':
                    $status = $this->_model->getConfigData('compropago_order_status_new');
                    $message = 'The user has not completed the payment process yet.';
                    $_order->setData('state',$status);
                    $_order->setStatus($status);
                    $history = $_order->addStatusHistoryComment($message);
                    $history->setIsCustomerNotified(false);
                    $_order->save();
                    $nomestatus = 'COMPROPAGO_PENDING';
                    break;
                case 'charge.success':
                    $status = $this->_model->getConfigData('compropago_order_status_approved');
                    $message = 'ComproPago automatically confirmed payment for this order.';
                    $_order->setData('state',$status);
                    $_order->setStatus($status);
                    $history = $_order->addStatusHistoryComment($message);
                    $history->setIsCustomerNotified(true);
                    $_order->save();
                    $nomestatus = 'COMPROPAGO_SUCCESS';
                    break;
                case 'charge.declined':
                    $status = $this->_model->getConfigData('compropago_order_status_in_process');
                    $message = 'The user has not completed the payment process yet.';
                    $_order->setData('state',$status);
                    $_order->setStatus($status);
                    $history = $_order->addStatusHistoryComment($message);
                    $history->setIsCustomerNotified(false);
                    $_order->save();
                    $nomestatus = 'COMPROPAGO_DECLINED';
                    break;
                case 'charge.deleted':
                    $status = $this->_model->getConfigData('compropago_order_status_cancelled');
                    $message = 'The user has not completed the payment and the order was cancelled.';
                    $_order->setData('state',$status);
                    $_order->setStatus($status);
                    $history = $_order->addStatusHistoryComment($message);
                    $history->setIsCustomerNotified(false);
                    $_order->save();
                    $nomestatus = 'COMPROPAGO_DELETED';
                    break;
                case 'charge.expired':
                    $status = $this->_model->getConfigData('compropago_order_status_cancelled');
                    $message = 'The user has not completed the payment and the order was cancelled.';
                    $_order->setData('state',$status);
                    $_order->setStatus($status);
                    $history = $_order->addStatusHistoryComment($message);
                    $history->setIsCustomerNotified(false);
                    $_order->save();
                    $nomestatus = 'COMPROPAGO_EXPIRED';
                    break;
                default:
                    $_order->save();
                    die('Invalid Response type');
            }


            /* TABLE compropago_orders
             ------------------------------------------------------------------------*/


            $DBwrite->update($prefix."compropago_orders",array(
                'modified'         => $date,
                'compropagoStatus' => $response->getType(),
                'storeExtra'       => $nomestatus,
            ), 'id='.$res[0]['id']);


            /* TABLE compropago_transactions
             ------------------------------------------------------------------------*/

            $DBwrite->insert($prefix."compropago_transactions", array(
                'orderId'              => $storedId,
                'date'                 => $date,
                'compropagoId'         => $response->getId(),
                'compropagoStatus'     => $response->getStatus(),
                'compropagoStatusLast' => $response->getStatus(),
                'ioIn'                 => $ioin,
                'ioOut'                => $ioout
            ));


        }catch (Exception $e){
            //something went wrong at sdk lvl
            die($e->getMessage());
        }
    }
}