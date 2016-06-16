<?php
/**
 * Description of Standard
 *
 * @author ivelazquex <isai.velazquez@gmail.com>
 */

class Compropago_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'compropago';
    protected $_formBlockType = 'compropago/form';

    protected $_canUseForMultiShipping = false;
    protected $_canUseInternal         = false;
    protected $_isInitializeNeeded     = true;

    /**
     * Asignacion de informacion inicial
     *
     * @param $data
     * @return $this
     */
    public function assignData($data)
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        if (!($data instanceof Varien_Object)){
            $data = new Varien_Object($data);
        }

        if ($data->getStoreCode() != ''){
            $store_code = $data->getStoreCode();
        } else {
            $store_code = 'OXXO';
        }
        //Verificamos si existe el customer
        if($customer->getFirstname()){
            $info = array(
                "payment_type" => $store_code,
                "customer_name" => htmlentities($customer->getFirstname()),
                "customer_email" => htmlentities($customer->getEmail()),
                "customer_phone" => $data->getCustomerPhone()
            );
        } else {
            $sessionCheckout = Mage::getSingleton('checkout/session');
            $quote = $sessionCheckout->getQuote();
            $billingAddress = $quote->getBillingAddress();
            $billing = $billingAddress->getData();
            $info = array(
                "payment_type" => $store_code,
                "customer_name" => htmlentities($billing['firstname']),
                "customer_email" => htmlentities($billing['email']),
                "customer_phone" => $data->getCustomerPhone()
            );
        }

        $infoInstance = $this->getInfoInstance();
        $infoInstance->setAdditionalData(serialize($info));

        return $this;
    }

    /**
     * Inicializacion del objeto principal
     *
     * @param $paymentAction
     * @param $stateObject
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        parent::initialize($paymentAction, $stateObject);

        if($paymentAction != 'sale'){
            return $this;
        }

        // Set the default state of the new order.
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT; // state now = 'pending_payment'
        $default_status = 'pending';

        $stateObject->setState($state);
        $stateObject->setStatus($default_status);
        $stateObject->setIsNotified(false);

        $sessionCheckout = Mage::getSingleton('checkout/session');
        $quoteId = $sessionCheckout->getQuoteId();
        
        $quote = Mage::getModel("sales/quote")->load($quoteId);
        $grandTotal = $quote->getData('grand_total');
        $subTotal = $quote->getSubtotal();
        $shippingHandling = ($grandTotal-$subTotal);

        $convertQuote = Mage::getSingleton('sales/convert_quote');
        $order = $convertQuote->toOrder($quote);
        $orderNumber = $order->getIncrementId();
        $order1 = Mage::getModel('sales/order')->loadByIncrementId($orderNumber);
        
        $name = "";
        foreach ($order1->getAllItems() as $item) {
            $name .= $item->getName();
        }

        $infoIntance = $this->getInfoInstance();
        $info = unserialize($infoIntance->getAdditionalData());
        $info['order_id'] = $orderNumber;
        $info['order_price'] = $grandTotal;
        $info['order_name'] = $name;
        $info['client_secret'] = trim($this->getConfigData('private_key'));
        $info['client_id'] = trim($this->getConfigData('public_key'));
        
        try
        {
            $Api = new Compropago_Model_Api();
            $response = $Api->payment($info);

            if ($response == null) {
                Mage::throwException("El servicio de Compropago no se encuentra disponible.");
            }

            if ($response['type'] == "error") {
                $errorMessage = $response['message'] . "\n";
                Mage::throwException($errorMessage.">>".json_encode($info));
            }

            $id = null;
            if($response['api_version'] == '1.0'){
                $id = $response['payment_id'];
            } elseif ($response['api_version'] == '1.1' || $response['api_version'] == '1.2') {
                $id = $response['id'];
            }else{
                Mage::throwException("Payment Id not defined");
            }

            Mage::getSingleton('core/session')->setCompropagoId($id);
        }catch (Exception $error){
            Mage::throwException($error->getMessage().">>".json_encode($info));
        }
        
        return $this;
    }

    /**
     * Obtiene listado filtrado de proveedores
     * 
     * @return array
     * @throws Mage_Core_Exception
     */
    public function getProviders()
    {
        if (trim($this->getConfigData('private_key')) == '' || trim($this->getConfigData('public_key')) == '') {
            Mage::throwException("Datos incompletos del servicio, contacte al administrador del sitio");
        }
        
        $url = 'https://api.compropago.com/v1/providers/true';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, ":");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->_response = curl_exec($ch);
        curl_close($ch);
            
        $response = json_decode($this->_response,true);

        if ($response['type'] == "error"){
            $errorMessage = $response['message'] . "\n";
            Mage::throwException($errorMessage);
        }

        $filter = explode(",",$this->getConfigData('provider_available'));

        $hash = array();

        foreach($response as $record){
            foreach($filter as $value){
                if($record['internal_name'] == $value){
                    $hash[$record['rank']] = $record;
                }
            }
        }

        ksort($hash);
        $records = array();

        foreach($hash as $record){
            $records []= $record;
        }

        return $records;
    }

    /**
     * Determina si se mostraran los logos para la seleccion de proveedores
     * 
     * @return bool
     */
    public function showLogoProviders()
    {
        return ( (int)trim($this->getConfigData("provider")) == 1 ? true : false );
    }
}
