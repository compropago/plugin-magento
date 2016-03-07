
<?php
	class Compropago_Block_OnepageSuccess extends Mage_Checkout_Block_Onepage_Success
	{
	// Write your custom methods
	// All parent’s methods also will work
		protected function _beforeToHtml() 
	    {	    	
	        $outHtml = parent::_beforeToHtml();
	        $this->setTemplate('compropago/onepage_success.phtml');
	        return $outHtml;
	    }
	}