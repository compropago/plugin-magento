<?php

class Compropago_Block_OnepageSuccess extends Mage_Checkout_Block_Onepage_Success
{
    /**
     * Regresa el recibo de compra
     *
     * @return mixed 
     */
    protected function _beforeToHtml()
    {
        $outHtml = parent::_beforeToHtml();
        $this->setTemplate('compropago/onepage_success.phtml');
        return $outHtml;
    }
}