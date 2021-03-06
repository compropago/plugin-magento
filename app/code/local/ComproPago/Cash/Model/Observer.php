<?php

class ComproPago_Cash_Model_Observer
{
    /**
     * Add extra information of the payment when a cash order was placed
     * @param $observer
     * @throws Varien_Exception
     */
    public function setAdditionalInformation($observer)
    {
        $order = $observer->payment->getOrder();
        $method = $order->getPayment()->getMethodInstance();

        if ($method->getCode() == 'cash') {
            $session = Mage::getSingleton('core/session');
            $resource = Mage::getSingleton('core/resource');

            $writeConnection = $resource->getConnection('core_write');
            $table = $resource->getTableName('sales_flat_order_payment');

            $info = $session->getComproPagoExtraData();
            $session->setComproPagoExtraData('');

            $query = "UPDATE $table SET additional_information = '$info' WHERE parent_id = {$order->getId()}";
            $writeConnection->query($query);
        }
    }
}