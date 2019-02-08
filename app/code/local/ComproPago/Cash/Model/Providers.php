<?php

require_once Mage::getBaseDir('lib') . DS . 'ComproPago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Resources\Payments\Cash as sdkCash;


class ComproPago_Cash_Model_Providers
{
	/**
	 * Create the array options for the config page
	 * @return array
	 */
	public function toOptionArray()
	{
		$options = [];
		$providers = (new sdkCash)->getDefaultProviders();

		foreach ($providers as $provider)
		{
			$options[] = [
				'value' => $provider['internal_name'],
				'label' => $provider['name']
			];
		}

		return $options;
	}
}
