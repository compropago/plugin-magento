<?php

require_once Mage::getBaseDir('lib') . DS . 'ComproPago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Factory\Factory;
use CompropagoSdk\Client;
use CompropagoSdk\Tools\Validations;

class ComproPago_Webhook_WebhookController extends Mage_Core_Controller_Front_Action
{
    private $publicKey;
    private $privateKey;
    private $mode;

    /**
     * Load ComproPago base configuration
     */
    private function initConfig()
    {
        $this->publicKey = Mage::getStoreConfig('payment/base/publickey');
        $this->privateKey = Mage::getStoreConfig('payment/base/privatekey');
        $this->mode = Mage::getStoreConfig('payment/base/mode');
    }

    /**
     * Main webhook action
     * @throws Exception
     */
    public function indexAction()
    {
        header('Content-Type: application/json');
        $request = @file_get_contents('php://input');

        if (empty($request) || !$respWebhook = Factory::getInstanceOf('CpOrderInfo', $request)) {
            die(json_encode([
                'status' => 'error',
                'message' => 'Invalid request',
                'short_id' => null,
                'reference' => null
            ]));
        }

        $this->initConfig();

        if (empty($this->publicKey) || empty($this->privateKey)) {
            die(json_encode([
                'status' => 'error',
                'message' => 'Invalid plugin keys',
                'short_id' => null,
                'reference' => null
            ]));
        }
        try {
            $client = new Client($this->publicKey, $this->privateKey, $this->mode);
            Validations::validateGateway($client);

            if ($respWebhook->short_id == "000000") {
                die(json_encode([
                    'status' => 'success',
                    'message' => 'OK - TEST',
                    'short_id' => $respWebhook->short_id,
                    'reference' => null
                ]));
            }

            $response = $client->api->verifyOrder($respWebhook->id);

            if ($response->type == 'error') {
                die(json_encode([
                    'status' => 'error',
                    'message' => 'error verifying order',
                    'short_id' => null,
                    'reference' => null
                ]));
            }

            /* ************************************************************************
            *                        RUTINAS DE BASE DE DATOS                         *
            ************************************************************************ */

            // TODO: Verify charge in transactions

            /* Rutinas de aprovación
             ------------------------------------------------------------------------*/

            // TODO: Load order

            switch ($response->type) {
                case 'charge.pending':
                    // TODO: pending process
                    break;
                case 'charge.success':
                    // TODO: success process
                    break;
                case 'charge.expired':
                    // TODO: expired process
                    break;
                default:
                    die(json_encode([
                        'status' => 'error',
                        'message' => 'invalid status',
                        'short_id' => null,
                        'reference' => null
                    ]));
            }

            // TODO: process transacrions

            die(json_encode([
                'status' => 'succeess',
                'message' => 'OK - ' . $response->type,
                'short_id' => $response->short_id,
                'reference' => $response->order_info->order_id
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'short_id' => null,
                'reference' => null
            ]));
        }
    }
}