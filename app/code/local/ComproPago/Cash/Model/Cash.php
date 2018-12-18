<?php

require_once Mage::getBaseDir('lib') . DS . 'ComproPago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Resources\Payments\Cash as sdkCash;


class ComproPago_Cash_Model_Cash extends Mage_Payment_Model_Method_Abstract
{
	protected $_code					= 'cash';
	protected $_formBlockType			= 'cash/form';
	protected $_infoBlockType			= 'cash/info';
	protected $_isInitializeNeeded		= true;
	protected $_canUseInternal			= true;
	protected $_canUseCheckout			= true;
	protected $_canUseForMultishipping	= true;

	/**
	 * Assing data to the order process
	 * @param mixed $data
	 * @return $this|Mage_Payment_Model_Info
	 * @throws Varien_Exception
	 */
	public function assignData($data)
	{
		$customer = Mage::getSingleton('customer/session')->getCustomer();

		if (!($data instanceof Varien_Object)) $data = new Varien_Object($data);

		$storeCode = ($data->getStoreCode() != '')
			? $data->getStoreCode()
			: null;

		if ($customer->getFirstname())
		{
			$info = [
				"payment_type"		=> $storeCode,
				"customer_name"		=> htmlentities($customer->getFirstname()),
				"customer_email"	=> htmlentities($customer->getEmail()),
				"customer_phone"	=> $data->getCustomerPhone()
			];
		}
		else
		{
			$sessionCheckout	= Mage::getSingleton('checkout/session');
			$quote				= $sessionCheckout->getQuote();
			$billingAddress		= $quote->getBillingAddress();
			$billing			= $billingAddress->getData();
			$info = [
				"payment_type"		=> $storeCode,
				"customer_name"		=> htmlentities($billing['firstname']),
				"customer_email"	=> htmlentities($billing['email']),
				"customer_phone"	=> $data->getCustomerPhone()
			];
		}

		$infoInstance = $this->getInfoInstance();
		$infoInstance->setAdditionalData(serialize($info));

		return $this;
	}

	/**
	 * Return the providers array in checkout
	 * @return array
	 * @throws Mage_Core_Model_Store_Exception
	 * @throws Varien_Exception
	 */
	public function getProviders()
	{
		$publicKey	= Mage::getStoreConfig('payment/base/publickey');
		$privateKey	= Mage::getStoreConfig('payment/base/privatekey');
		$mode		= intval(Mage::getStoreConfig('payment/base/mode')) == 1;
		$quote		= Mage::getModel('checkout/session')->getQuote();
		$quoteData	= $quote->getData();

		$client		= (new sdkCash)->withKeys($publicKey, $privateKey);
		$providers	= $client->getProviders(
			$quoteData['grand_total'],
			Mage::app()->getStore()->getCurrentCurrencyCode()
		);

		$filter = explode(',', $this->getConfigData('providers_available'));
		$record = [];
		foreach ($providers as $provider)
		{
			foreach ($filter as $value)
			{
				if ($provider['internal_name'] == $value)
				{
					$record[] = $provider;
				}
			}
		}

		return $record;
	}

	/**
	 * Main process to create order
	 * @param string $paymentAction
	 * @param object $stateObject
	 * @return $this|Mage_Payment_Model_Abstract
	 * @throws Mage_Core_Exception
	 * @throws Varien_Exception
	 */
	public function initialize($paymentAction, $stateObject)
	{
		parent::initialize($paymentAction, $stateObject);

		if ($paymentAction != 'sale') return $this;

		$publicKey		= Mage::getStoreConfig('payment/base/publickey');
		$privateKey		= Mage::getStoreConfig('payment/base/privatekey');
		$mode			= intval(Mage::getStoreConfig('payment/base/mode')) == 1;
		$session		= Mage::getSingleton('checkout/session');
		$coreSession	= Mage::getSingleton('core/session');
		$orderModel		= Mage::getModel('sales/order');
		$convertQuote	= Mage::getSingleton('sales/convert_quote');
		$customer		= Mage::getModel('customer/customer');
		$state			= Mage_Sales_Model_Order::STATE_NEW;
		$defaultStatus	= 'pending';

		$stateObject->setState($state);
		$stateObject->setStatus($defaultStatus);
		$stateObject->setIsNotified(true);
		
		$quoteId		= $session->getQuoteId();
		$quote			= $session->getQuote($quoteId);
		$orderId		= $quote->getReservedOrderId();
		$order			= $orderModel->loadByIncrementId($orderId);
		$grandTotal		= (float) $order->getBaseGrandTotal();
		$order			= $convertQuote->toOrder($quote);
		$orderNumber	= $order->getIncrementId();
		$orderOne		= $orderModel->loadByIncrementId($orderNumber);

		$orderOne->setVisibleOnFront(1);

		$name = "";
		foreach ($orderOne->getAllItems() as $item)
		{
			$name .= $item->getName();
		}

		$infoIntance	= $this->getInfoInstance();
		$info			= unserialize($infoIntance->getAdditionalData());
		$currency		= Mage::app()->getStore()->getCurrentCurrencyCode();

		$orderInfo = [
			'order_id'				=> $orderNumber,
			'order_name'			=> $name,
			'order_price'			=> $grandTotal,
			'customer_name'			=> $info['customer_name'],
			'customer_email'		=> $info['customer_email'],
			'payment_type'			=> $info['payment_type'],
			'app_client_name'		=> 'magento',
			'app_client_version'	=> Mage::getVersion(),
			'currency'				=> $currency
		];

		try
		{
			$client		= (new sdkCash)->withKeys( $publicKey, $privateKey );
			$response	= $client->createOrder( $orderInfo );
			$coreSession->setComproPagoId( $response['id'] );

			/**
			 * Asignar compra al usuario
			 * ------------------------------------------------------------------------- */

			$message = 'The user has not completed the payment process yet.';

			$customer->setWebsiteId(1);
			$customer->loadByEmail($info['customer_email']);
			$orderbyid = $orderModel->loadByIncrementId($orderNumber);
			$orderbyid->setCustomerId($customer->getId());
			$orderbyid->setCustomerFirstname($customer->getFirstname());
			$orderbyid->setCustomerLastname($customer->getLastname());
			$orderbyid->setCustomerEmail($customer->getEmail());
			$history = $orderbyid->addStatusHistoryComment($message);
			$history->setIsCustomerNotified(true);
			$orderbyid->save();

			/**
			 * Add Additional Information
			 * ------------------------------------------------------------------------- */

			$additional = [
				'id'		=> $response['id'],
				'short_id'	=> $response['short_id'],
				'store'		=> $info['payment_type']
			];

			$coreSession->setComproPagoExtraData(serialize($additional));
		}
		catch (Exception $error)
		{
			Mage::throwException($error->getMessage());
		}

		return $this;
	}
}
