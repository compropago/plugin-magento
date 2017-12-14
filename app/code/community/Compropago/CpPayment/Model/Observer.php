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

class Compropago_CpPayment_Model_Observer
{
    public function checkWebhook($observer)
    {
        $webhook = Mage::getBaseUrl() . "cpwebhook";
        $model = Mage::getModel('cppayment/Standard');

        try{
            $client = new Client(
                $model->getConfigData('compropago_publickey'),
                $model->getConfigData('compropago_privatekey'),
                (int)trim($model->getConfigData('compropago_mode')) == 1 ? true : false
            );

            $response = $client->api->createWebhook($webhook);
            $time = Mage::getModel('core/date')->timestamp(); // time standart function

            $DB = Mage::getSingleton('core/resource')->getConnection('core_write');
            $prefix = Mage::getConfig()->getTablePrefix();

            $dataInsert = array(
                'webhookId' => $response->id,
                'updated'   => $time,
                'status'    => $response->status,
                'url'       => $webhook
            );

            // DB insert( prefix."compropago_webhook_transactions",  dataInsert)


            /* Retroalimentación en el panel de administración
             ------------------------------------------------------------------------*/
            
            $retro = $model->hookRetro(
                (int)trim($model->getConfigData('active')) == 1 ? true : false,
                $model->getConfigData('compropago_publickey'),
                $model->getConfigData('compropago_privatekey'),
                (int)trim($model->getConfigData('compropago_mode')) == 1 ? true : false
            );

            if ($retro[0]) {
                Mage::getSingleton('adminhtml/session')->addWarning($retro[1]);
            }
        } catch (Exception $e) {
            if ($e->getMessage() == 'Error: conflict.urls.create') {
                return;
            } else {
                Mage::throwException($e->getMessage());
            }
        }
    }
}
