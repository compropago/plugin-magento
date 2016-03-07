
<?php
/**
 * API para el consumo de los servicios
 * de PagoFacil
 * @author ivelazquex <isai.velazquez@gmail.com>
 */
class Compropago_Model_Api
{
    // --- ATTRIBUTES ---
    /**
     * URL del servicio de PagoFacil en ambiente de produccion
     * @var string 
     */
    protected $_url = 'https://api.compropago.com/v1/charges';
    /**
     * respuesta sin parsear del servicio
     * @var string
     */
    protected $_response = NULL;
    
    
    // --- OPERATIONS ---
    
    public function __construct()
    {
        
    }
    
    /**
     * consume el servicio de pago de PagoFacil
     * @param string[] vector con la informacion de la peticion
     * @return mixed respuesta del consumo del servicio
     * @throws Exception
     */
    public function payment($info)
    {
        $response = null;
        // NOTA la url a la cual s edebe conectar en produccion no viene 
        // la direccion de produccion en el documento 
        
        if (!is_array($info))
        {
            throw new Exception('parameter is not an array');
        }
        $info['url'] = $this->_url;
        // datos para la peticion del servicio
        $data = array(
            'order_id'        => $info['order_id'],
            'order_price'        => $info['order_price'],
            'order_name'         => $info['order_name'],
            'image_url'            => $info['image_url'],
            'customer_name'         => $info['customer_name'],
            'customer_email'     => $info['customer_email'],
            'payment_type'               => $info['payment_type']
        );
        $username = $info['client_secret'];
        $password = $info['client_id'];
        // consumo del servicio
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        // Blindly accept the certificate
        $this->_response = curl_exec($ch);
        curl_close($ch);
        // tratamiento de la respuesta del servicio
        $response = json_decode($this->_response,true);
        // respuesta del servicio
        if ($response == null)
        {            
            Mage::throwException("El servicio de Compropago no se encuentra disponible.");
        }
        
        if ($response['type'] == "error")
        {            
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