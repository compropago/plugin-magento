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
}