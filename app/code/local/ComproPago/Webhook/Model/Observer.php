<?php
require_once Mage::getBaseDir('lib') . DS . 'ComproPago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Resources\Webhook as sdkWebhook;


class ComproPago_Webhook_Model_Observer
{
	/**
	 * Ovserver callback to register webhook and set Retro hook
	 * @param $observer
	 * @throws Mage_Core_Exception
	 * @throws Varien_Exception
	 */
	public function create($observer)
	{
		$webhook	= Mage::getBaseUrl() . "compropago/webhook";
		$session	= Mage::getSingleton('adminhtml/session');
		$publicKey	= Mage::getStoreConfig('payment/base/publickey');
		$privateKey	= Mage::getStoreConfig('payment/base/privatekey');
		$mode		= intval(Mage::getStoreConfig('payment/base/mode')) == 1;

		try
		{
			$objWebhook = (new sdkWebhook)->withKeys($publicKey, $privateKey);

			# Create webhook on ComproPago Panel
			$response = $objWebhook->create($webhook);

			$session->addSuccess('ComproPago Webhook was registered correctly.');
		}
		catch (Exception $e)
		{
			$errors = [
				'Request Error [409]: ',
			];
			$message = json_decode(str_replace($errors, '', $e->getMessage()), true);

			# Ignore Webhook registered
			if ( isset($message['code']) && $message['code']==409 )
			{
				$session->addSuccess('ComproPago Webhook is registered.');
				return;
			}
			elseif( isset($message['message']) )
			{
				Mage::throwException( $message['message'] );
			}
			else
			{
				Mage::throwException('$e->getMessage()');
			}
		}
	}
}
