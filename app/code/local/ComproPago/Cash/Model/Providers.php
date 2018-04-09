<?php

require_once Mage::getBaseDir('lib') . DS . 'ComproPago' . DS . 'vendor' . DS . 'autoload.php';

use CompropagoSdk\Client;

class ComproPago_Cash_Model_Providers
{
    /**
     * Create the array options for the config page
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();

        $client = new Client('', '', false);

        foreach ($client->api->listDefaultProviders() as $provider){
            $options[] = array(
                'value' => $provider->internal_name,
                'label' => $provider->name
            );
        }

        return $options;
    }
}