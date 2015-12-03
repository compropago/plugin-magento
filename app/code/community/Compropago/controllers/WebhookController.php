<?php
/** 
*  Webhook para notificaciones de pagos.
*
*  @author waldix (waldix86@gmail.com)
*/
class Compropago_WebhookController extends Mage_Core_Controller_Front_Action{
    protected $_model = null;
    
    public function _construct() {
        $this->_model = Mage::getModel('compropago/Standard');
    }

    public function indexAction(){
        $params = $this->getRequest()->getParams();
        $body = @file_get_contents('php://input');
        $event_json = json_decode($body);

        if(isset($event_json)){
            if ($event_json->{'api_version'} === '1.1') {
                if ($event_json->{'id'}){
                    $order = $this->verifyOrder($event_json->{'id'}); 
                    $type = $order['type'];

                    if (isset($order['id'])){
                        if ($order['id'] === $event_json->{'id'}) {
                            $order_id = $order['order_info']['order_id'];
                            $this->changeStatus($order_id, $type);                          
                        } else {
                            echo 'Order not valid';
                        }
                    } else {
                        echo 'Order not valid';
                    }
                }
            } else {
                if ($event_json->data->object->{'id'}){
                    $order = $this->verifyOrder($event_json->data->object->{'id'}); 
                    $type = $order['type'];

                    if (isset($order['data']['object']['id'])){
                        if ($order['data']['object']['id'] === $event_json->data->object->{'id'}) {
                            $order_id = $order['data']['object']['payment_details']['product_id'];  
                            $this->changeStatus($order_id, $type);
                        } else {
                            echo 'Order not valid';
                        }
                    } else {
                        echo 'Order not valid';
                    }
                              
                }
            }              
        } else {
            echo 'Order not valid';
        }                           
    }

    public function changeStatus($order_id, $type){     
        $_order = Mage::getModel('sales/order')->loadByIncrementId($order_id);         

        switch ($type) {    
            case 'charge.pending':
                $status = $this->_model->getConfigData('order_status_in_process');
                $message = 'The user has not completed the payment process yet.';
                $_order->addStatusToHistory($status, $message);
                break;
            case 'charge.success':
                $message = 'ComproPago automatically confirmed payment for this order.';
                $status = $this->_model->getConfigData('order_status_approved');
                $_order->addStatusToHistory($status,$message,true);
                break;
            case 'charge.declined':    
                $status = $this->_model->getConfigData('order_status_in_process');
                $message = 'The user has not completed the payment process yet.';
                $_order->addStatusToHistory($status, $message);
                break;
            case 'charge.deleted':
                $status = $this->_model->getConfigData('order_status_cancelled');
                $message = 'The user has not completed the payment and the order was cancelled.';   
                $_order->addStatusToHistory($status, $message,true);
                break;
            case 'charge.expired':
                $status = $this->_model->getConfigData('order_status_cancelled');
                $message = 'The user has not completed the payment and the order was cancelled.';   
                $_order->addStatusToHistory($status, $message,true);     
                break;     
            default:
                $status = $this->_model->getConfigData('order_status_in_process');
                $message = "";    
                $_order->addStatusToHistory($status, $message,true);         
        }

        $_order->save();
    }

    public function verifyOrder($id){
        $url = 'https://api.compropago.com/v1/charges/';
        $url .=  $id;   
        $username = trim($this->_model->getConfigData('client_secret'));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
        $this->_response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($this->_response,true);
        return $response;
      }         
}