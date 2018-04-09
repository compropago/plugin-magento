<?php

class ComproPago_Spei_Block_Info extends Mage_Payment_Block_Info
{
    private $template = 'compropago/spei/info.phtml';

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
        return Mage::getStoreConfig('payment/spei/title');
    }

    /**
     * Return extra data from the current order
     * @return array|mixed|null
     */
    public function getExtraData()
    {
        $info = parent::getInfo();
        $data = $info->getAdditionalInformation();
        return $data;
    }
}