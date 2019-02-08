<?php

require_once Mage::getBaseDir('lib') . '/ComproPago/vendor/autoload.php';

use CompropagoSdk\Resources\Payments\Cash as sdkCash;
use CompropagoSdk\Resources\Payments\Spei as sdkSpei;


class ComproPago_Webhook_WebhookController extends Mage_Core_Controller_Front_Action
{
	const TEST_SHORT_ID  = "000000";

	private $client;
	private $publicKey;
	private $privateKey;
	private $mode;

	/**
	 * Load ComproPago base configuration
	 */
	private function initConfig()
	{
		$this->publicKey	= Mage::getStoreConfig('payment/base/publickey');
		$this->privateKey	= Mage::getStoreConfig('payment/base/privatekey');
		$this->mode			= intval(Mage::getStoreConfig('payment/base/mode')) == 1;
	}

	/**
	 * Main webhook action
	 * @throws Exception
	 */
	public function indexAction()
	{
		header('Content-Type: application/json');
		$request = json_decode( @file_get_contents('php://input'), true );

		if ( empty($request) || !isset($request['short_id']) )
		{
			throw new \Exception('Invalid request');
		}
		elseif ($request['short_id'] == self::TEST_SHORT_ID)
		{
			die(json_encode([
				'status'	=> 'success',
				'message'	=> 'OK - TEST',
				'short_id'	=> $request['short_id'],
				'reference'	=> null
			]));
		}

		$this->initConfig();

		if (empty($this->publicKey) || empty($this->privateKey))
		{
			throw new \Exception('Invalid plugin keys');
		}

		try
		{
			/**
			 * Get te extra information to validate the payment
			 * ------------------------------------------------------------------------*/

			$order = new Mage_Sales_Model_Order();
			$order->loadByIncrementId($request['order_info']['order_id']);

			if (empty($order->getId()))
			{
				$message = "Not found order: {$request['order_info']['order_id']}";
				throw new \Exception($message);
			}

			$payment = $order->getPayment();
			$extraInfo = $payment->getAdditionalInformation();

			if (empty($extraInfo))
			{
				$message = 'Error verifying order: ' . $order->getId();
				throw new \Exception($message);
			}

			if ($extraInfo['id'] != $request['id'])
			{
				$message = "The order is not from this store {$extraInfo['id']}: {$request['id']}";
				throw new \Exception($message);
			}

			switch ($payment->getMethod())
			{
				case 'spei':
					$this->client = (new sdkSpei)->withKeys( $this->publicKey, $this->privateKey );
					$this->processSpei($order, $payment, $extraInfo, $request);
					break;
				case 'cash':
					$this->client = (new sdkCash)->withKeys( $this->publicKey, $this->privateKey );
					$this->processCash($order, $payment, $extraInfo, $request);
					break;
				default:
					$message = 'Payment method ' . $payment->getMethod() . ' not allowed';
					throw new \Exception($message);
			}
		}
		catch (\Exception $e)
		{
			die(json_encode([
				'status'	=> 'error',
				'message'	=> $e->getMessage(),
				'short_id'	=> null,
				'reference'	=> null
			]));
		}
	}

	/**
	 * Process Cash payments flow
	 * @param Mage_Sales_Model_Order $order
	 * @param Mage_Sales_Model_Order_Payment $payment
	 * @param array $extraInfo
	 * @param Object $request
	 * @throws Exception
	 */
	private function processCash(&$order, &$payment, $extraInfo, $request)
	{
		$response = $this->client->verifyOrder( $request['id'] );
		$status = $response['type'];

		$this->updateStatus($order, $status);
		$this->save($order, $payment, $extraInfo);

		die(json_encode([
			'status'	=> 'success',
			'message'	=> "OK - $status - CASH",
			'short_id'	=> $extraInfo['short_id'],
			'reference'	=> $order->getIncrementId()
		]));
	}

	/**
	 * Process Spei payments flow
	 * @param Mage_Sales_Model_Order $order
	 * @param Mage_Sales_Model_Order_Payment $payment
	 * @param array $extraInfo
	 * @param Object $request
	 * @throws Exception
	 */
	private function processSpei(&$order, &$payment, $extraInfo, $request)
	{
		$response = $this->client->verifyOrder( $request['id'] );

		switch ( $response['data']['status'] )
		{
			case 'PENDING':
				$status = 'charge.pending';
				break;
			case 'ACCEPTED':
				$status = 'charge.success';
				break;
			case 'EXPIRED':
				$status = 'charge.expired';
				break;
		}

		$this->updateStatus($order, $status);
		$this->save($order, $payment, $extraInfo);

		die(json_encode([
			'status'	=> 'success',
			'message'	=> "OK - $status - SPEI",
			'short_id'	=> $extraInfo['short_id'],
			'reference'	=> $order->getIncrementId()
		]));
	}

	/**
	 * Change Order Status
	 * @param Mage_Sales_Model_Order $order
	 * @param string $status
	 * @throws Exception
	 */
	private function updateStatus(&$order, $status)
	{
		switch ($status)
		{
			case 'charge.pending':
				$state = Mage_Sales_Model_Order::STATE_NEW;
				$status = "pending";
				$order->setData('state', $state);
				$order->setStatus($status);
				break;
			case 'charge.success':
				$state = Mage_Sales_Model_Order::STATE_PROCESSING;
				$status = "processing";
				$order->setData('state', $state);
				$order->setStatus($status);

				$message = 'ComproPago automatically confirmed payment for this order.';

				$history = $order->addStatusHistoryComment($message);
				$history->setIsCustomerNotified(true);
				$history->save();
				break;
			case 'charge.expired':
				$state = Mage_Sales_Model_Order::STATE_CANCELED;
				$status = "canceled";
				$order->setData('state', $state);
				$order->setStatus($status);

				$message = 'The user has not completed the payment and the order was cancelled.';

				$history = $order->addStatusHistoryComment($message);
				$history->setIsCustomerNotified(false);
				$history->save();
				break;
			default:
				throw new \Exception("Status {$status} not allowed");
		}
	}

	/**
	 * Save Information
	 * @param Mage_Sales_Model_Order $order
	 * @param Mage_Sales_Model_Order_Payment $payment
	 * @param array $extraInfo
	 * @throws Exception
	 */
	private function save(&$order, $payment, $extraInfo)
	{
		$orderId = $order->getId();

		$payment->save();
		$order->save();

		$resource = Mage::getSingleton('core/resource');
		$dbWrite = $resource->getConnection('core_write');

		$table = $resource->getTableName('sales_flat_order_payment');
		$info = serialize($extraInfo);

		$query = "UPDATE $table
		SET additional_information = '$info'
		WHERE parent_id = $orderId";
		$dbWrite->query($query);
	}
}
