<?php

class ComproPago_Cash_Model_Oberver
{
    /**
     * Set ComproPago retro to config panel
     * @param $observer
     */
    public function retro($observer)
    {
        $model = Mage::getModel('ComproPago_Cash_Model_Cash');

        $retro = $model->hookRetro(
            (int)trim($model->getConfigData('active')) == 1 ? true : false,
            $model->getConfigData('compropago_publickey'),
            $model->getConfigData('compropago_privatekey'),
            (int)trim($model->getConfigData('compropago_mode')) == 1 ? true : false
        );

        if ($retro[0]) {
            Mage::getSingleton('adminhtml/session')->addWarning($retro[1]);
        }
    }
}