<?php

class ComproPago_Cash_Model_Observer
{
    /**
     * @param $observer
     * @throws Varien_Exception
     */
    public function setAdditionalInformation($observer)
    {
        $session = Mage::getSingleton('core/session');
        $info = unserialize($session->getComproPagoExtraData());
        $session->setComproPagoExtraData('');

        $order = $observer->payment->getOrder();
        $payment = $order->getPayment();


    }
}