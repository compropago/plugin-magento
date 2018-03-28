<?php

require_once Mage::getBaseDir('lib') . DS . 'ComproPago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Client;

class ComproPago_Webhook_Model_Observer
{
    /**
     * Ovserver callback to register webhook and set Retro hook
     * @param $observer
     * @throws Mage_Core_Exception
     */
    public function create($observer)
    {
        $webhook = Mage::getBaseUrl() . "compropago/webhook";
        $session = Mage::getSingleton('adminhtml/session');

        $model = Mage::getModel('ComproPago_Cash_Model_Cash');
        $mode = (int)trim($model->getConfigData('compropago_mode')) == 1 ? true : false;

        try {
            $client = new Client(
                $model->getConfigData('compropago_publickey'),
                $model->getConfigData('compropago_privatekey'),
                $mode
            );

            $client->api->createWebhook($webhook);

            $session->addSuccess('ComproPago Webhook was registered correctly');
        } catch (Exception $e) {
            if ($e->getMessage() == 'Error: conflict.urls.create') {
                $session->addSuccess('ComproPago Webhook was registered correctly');
                return;
            } else {
                Mage::throwException($e->getMessage());
            }
        }
    }
}