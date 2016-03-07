<?php
/**
 * Created by PhpStorm.
 * User: Arthur
 * Date: 04/03/16
 * Time: 13:51
 */

class Compropago_Model_Providers
{
    public function toOptionArray()
    {
        $options =  array();

        foreach($this->getProviders() as $provider){
            $options[] = array(
                'value' => $provider['internal_name'],
                'label' => $provider['name']
            );
        }

        return $options;
    }

    private function getProviders()
    {
        if (trim(Mage::getStoreConfig("payment/compropago/client_secret")) == ''
            || trim(Mage::getStoreConfig("payment/compropago/client_id")) == ''
        ) {
            Mage::throwException("Datos incompletos del servicio, contacte al administrador del sitio");
        }

        $url = 'https://api.compropago.com/v1/providers/';
        $url.= 'true';
        $username = trim(Mage::getStoreConfig("payment/compropago/client_secret"));
        $password = trim(Mage::getStoreConfig("payment/compropago/client_id"));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);

        // Blindly accept the certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        // tratamiento de la respuesta del servicio
        $response = json_decode($response,true);

        // respuesta del servicio
        if ($response['type'] == "error")
        {
            $errorMessage = $response['message'] . "\n";
            Mage::throwException($errorMessage);
        }


        $hash = array();

        foreach($response as $record)
        {
            $hash[$record['rank']] = $record;
        }

        ksort($hash);

        $records = array();

        foreach($hash as $record)
        {
            $records []= $record;
        }

        return $records;
    }
}