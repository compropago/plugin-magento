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
    protected $_model = null;
    public function _construct()
    {
        $this->_model = Mage::getModel('cppayment/Standard');
    }
    public function indexAction()
    {
        $request = @file_get_contents('php://input');
        if (!$respWebhook = Factory::getInstanceOf('CpOrderInfo', $request)) {
            die(json_encode([
                'status' => 'error',
                'message' => 'invalid request',
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
            if ($respWebhook->id == "ch_00000-000-0000-000000") {
                die(json_encode([
                    'status' => 'success',
                    'message' => 'test OK',
                    'short_id' => $respWebhook->id,
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
            $date  = Mage::getModel('core/date')->timestamp(); // time standart function
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
                'short_id' => $response->id,
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