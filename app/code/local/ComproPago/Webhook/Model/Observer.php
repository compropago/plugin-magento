<?php
require_once Mage::getBaseDir('lib') . DS . 'ComproPago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Client;

class ComproPago_Webhook_Model_Observer
{
    /**
     * Ovserver callback to register webhook and set Retro hook
     * @param $observer
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     */
    public function create($observer)
    {
        $webhook = Mage::getBaseUrl() . "compropago/webhook";
        $session = Mage::getSingleton('adminhtml/session');

        $publicKey = Mage::getStoreConfig('payment/base/publickey');
        $privateKey = Mage::getStoreConfig('payment/base/privatekey');
        $mode = intval(Mage::getStoreConfig('payment/base/mode')) == 1;

        try {
            $client = new Client($publicKey, $privateKey, $mode);

            $client->api->createWebhook($webhook);
            $session->addSuccess('ComproPago Webhook was registered correctly');
        } catch (Exception $e) {
            if ($e->getMessage() == 'Request error: 409') {
                $session->addSuccess('ComproPago Webhook was registered correctly');
                return;
            } else {
                Mage::throwException($e->getMessage());
            }
        }
    }
}