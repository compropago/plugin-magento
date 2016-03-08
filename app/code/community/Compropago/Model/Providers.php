<?php
/**
 * @author Eduardo Aguilar <eduardo.aguilar@compropago.com>
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
        $privateKey = "sk_test_56e31883637446b1b";
        $publicKey = "pk_test_8781245a88240f9cf";

        $url = 'https://api.compropago.com/v1/providers/';
        $url.= 'true';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $privateKey . ":" . $publicKey);

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