<?php
/**
 * API para el consumo de los servicios de ComproPago
 * 
 * @author Eduardo Aguilar <eduardo.aguilar@compropago.com>
 * @author ivelazquex <isai.velazquez@gmail.com>
 */


class Compropago_Model_Api
{
    /**
     * URL del servicio de PagoFacil en ambiente de produccion
     * 
     * @var string
     */
    protected $_url = 'https://api.compropago.com/v1/charges';
    
    /**
     * respuesta sin parsear del servicio
     * 
     * @var string
     */
    protected $_response = NULL;

    public function __construct()
    {

    }

    /**
     * Consume el servicio de pago de ComproPago
     * 
     * @param string[] vector con la informacion de la peticion
     * @return mixed respuesta del consumo del servicio
     * @throws Exception
     */
    public function payment($info)
    {
        $response = null;

        if (!is_array($info)){
            throw new Exception('parameter is not an array');
        }

        $info['url'] = $this->_url;

        $data = array(
            'order_id'        => $info['order_id'],
            'order_price'        => $info['order_price'],
            'order_name'         => $info['order_name'],
            'image_url'            => $info['image_url'],
            'customer_name'         => $info['customer_name'],
            'customer_email'     => $info['customer_email'],
            'customer_phone'     => $info['customer_phone'],
            'payment_type'               => $info['payment_type']
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $info['client_secret'] . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $this->_response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($this->_response,true);

        if ($response == null){
            Mage::throwException("El servicio de Compropago no se encuentra disponible.");
        }

        if ($response['type'] == "error") {
            $errorMessage = $response['message'] . "\n";
            Mage::throwException($errorMessage);
        }

        return $response;
    }

    /**
     * obtiene la respuesta del servicio
     * @return string
     */
    public function getResponse()
    {
        return $this->_response;
    }
}