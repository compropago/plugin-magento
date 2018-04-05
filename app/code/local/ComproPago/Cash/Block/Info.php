<?php

class ComproPago_Cash_Block_Info extends Mage_Payment_Block_Info
{
    private $template = 'compropago/cash/info.phtml';

    /**
     * ComproPago_Cash_Block_Info constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate($this->template);
    }

    /**
     * Return the payment method title
     * @return string
     */
    public function getTitle()
    {
        return Mage::getStoreConfig('payment/cash/title');
    }

    public function getExtraData()
    {
        $info = parent::getInfo();
        $data = $info->getAdditionalInformation();
        var_dump($data);
        return $data;
    }
}