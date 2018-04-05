<?php

class ComproPago_Cash_Model_Oberver
{
    /**
     * Set ComproPago retro to config panel
     * @param $observer
     * @throws Varien_Exception
     */
    public function retro($observer)
    {
        $session = Mage::getSingleton('adminhtml/session');

        $config = [
            "mode" => Mage::getStoreConfig('payment/base/mode'),
            "public_key" => Mage::getStoreConfig('payment/base/publickey'),
            "private_key" => Mage::getStoreConfig('payment/base/privatekey'),
            "cash_enable" => Mage::getStoreConfig('payment/cash/active')
        ];

        try {
            $this->validate($config);
        } catch (\Exception $e) {
           $session->addWarning($e->getMessage());
        }
    }

    /**
     * Validate configuration
     * @param $config
     */
    private function validate($config)
    {
        return;
    }
}