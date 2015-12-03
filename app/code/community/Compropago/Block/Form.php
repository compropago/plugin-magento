<?php
/**
 * Description of Form
 *
 * @author waldix <waldix86@gmail.com>
 */
class Compropago_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {        
        parent::_construct();
        $this->setTemplate('compropago/cash.phtml');      
    }
    
    public function getMethod()
    {        
        return parent::getMethod();
    }
    
}