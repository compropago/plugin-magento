<?php

class ComproPago_Spei_Block_Form extends Mage_Payment_Block_Form
{
    private $template = 'compropago/spei/form.phtml';

    /**
     * ComproPago_Cash_Block_Form Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate($this->template);
    }

    /**
     * Return the payment method
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function getMethod()
    {
        return parent::getMethod();
    }

    /**
     * Render template for Payment Method Lebel in checkout
     * @return string
     */
    public function getMethodLabelAfterHtml()
    {
        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('compropago/spei/mark.phtml');
        return $mark->toHtml();
    }
}