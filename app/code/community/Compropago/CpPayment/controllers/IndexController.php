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
$libcp =  Mage::getBaseDir('lib') . DS . 'Compropago' . DS . 'vendor' . DS . 'autoload.php';

require_once $libcp;


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
        if (!$respWebhook = Factory::getInstanceOf('CpOrderInfo', $request)) {
            echo 'Tipo de Request no Valido';
        }

        /**
         * Gurdamos la informacion necesaria para el Cliente
         * las llaves de compropago y el modo de ejecucion de la tienda
         */
        $publickey     = $this->_model->getConfigData('compropago_publickey');
        $privatekey    = $this->_model->getConfigData('compropago_privatekey');
        $live          = (int)trim($this->_model->getConfigData('compropago_mode')) == 1 ? true : false;

        /**
         * Se valida que las llaves no esten vacias (No es obligatorio pero si recomendado)
         */
        if (empty($publickey) || empty($privatekey)) {
            echo "Se requieren las llaves de compropago";
        }

        try {
            $client = new Client($publickey, $privatekey, $live);

            Validations::validateGateway($client);

            /**
             * Verificamos si recivimos una peticion de prueba
             */
            if ($respWebhook->id == "ch_00000-000-0000-000000") {
                echo "Probando el WebHook?, Ruta correcta.";
            }

            /**
             * Verificamos la informacion del Webhook recivido
             */
            $response = $client->api->verifyOrder($respWebhook->id);

            /**
             * Comprovamos que la verificacion fue exitosa
             */
            if ($response->type == 'error') {
                echo 'Error procesando el número de orden';
            }

            /* ************************************************************************
                                    RUTINAS DE BASE DE DATOS
            ************************************************************************ */

            $DBread  = Mage::getSingleton('core/resource')->getConnection('core_read');
            $DBwrite = Mage::getSingleton('core/resource')->getConnection('core_write');
            $prefix  = Mage::getConfig()->getTablePrefix();

            $ioin  = base64_encode(serialize($respWebhook));
            $ioout = base64_encode(serialize($response));
            $date  = Mage::getModel('core/date')->timestamp(); // time standart function


            $sql = "SELECT * FROM " . $prefix . "compropago_orders where compropagoId = '{$response->id}'";
            $res = $DBread->fetchAll($sql);

            $storedId = $res[0]['storeOrderId'];

            if (empty($storedId)) {
               throw new Exception('El pago no corresponde a esta tienda.');
            }

            /* Rutinas de aprovación
             ------------------------------------------------------------------------*/
            $_order = Mage::getModel('sales/order')->loadByIncrementId($response->order_info->order_id);

            /**
             * Generamos las rutinas correspondientes para cada uno de los casos posible del webhook
             */
            switch ($response->type) {
                case 'charge.pending':
                    $state = Mage_Sales_Model_Order::STATE_NEW;
                    $status = "pending";

                    $message = 'The user has not completed the payment process yet.';
                    $_order->setData('state', $state);

                    $_order->setStatus($status);

                    $_order->save();

                    $nomestatus = 'COMPROPAGO_PENDING';
                    echo $nomestatus;
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
                    echo $nomestatus;
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
                    echo $nomestatus;
                    break;
                default:
                    $_order->save();
                    echo 'Invalid Response type';
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
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
