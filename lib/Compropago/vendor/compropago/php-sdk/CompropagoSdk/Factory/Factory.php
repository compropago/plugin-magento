<?php

namespace CompropagoSdk\Factory;

use CompropagoSdk\Factory\Models\CpOrderInfo;
use CompropagoSdk\Factory\Models\Customer;
use CompropagoSdk\Factory\Models\EvalAuthInfo;
use CompropagoSdk\Factory\Models\FeeDetails;
use CompropagoSdk\Factory\Models\InstructionDetails;
use CompropagoSdk\Factory\Models\Instructions;
use CompropagoSdk\Factory\Models\NewOrderInfo;
use CompropagoSdk\Factory\Models\OrderInfo;
use CompropagoSdk\Factory\Models\PlaceOrderInfo;
use CompropagoSdk\Factory\Models\Provider;
use CompropagoSdk\Factory\Models\SmsData;
use CompropagoSdk\Factory\Models\SmsInfo;
use CompropagoSdk\Factory\Models\SmsObject;
use CompropagoSdk\Factory\Models\Webhook;

class Factory
{
    /**
     * @param $class
     * @param array $data
     * @return array|CpOrderInfo|Customer|EvalAuthInfo|FeeDetails|InstructionDetails|Instructions|NewOrderInfo|OrderInfo|PlaceOrderInfo|Provider|SmsData|SmsInfo|SmsObject|Webhook
     * @throws \Exception
     */
    public static function getInstanceOf($class, $data=array())
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        } else if (!is_array($data)) {
            throw new \Exception('Data format is not factored');
        }

        switch ($class) {
            case 'CpOrderInfo':
                return Serialize::cpOrderInfo($data);
            case 'Customer':
                return Serialize::customer($data);
            case 'EvalAuthInfo':
                return Serialize::evalAuthInfo($data);
            case 'FeeDetails':
                return Serialize::feeDetails($data);
            case 'InstructionDetails':
                return Serialize::instructionDetails($data);
            case 'Instructions':
                return Serialize::instructions($data);
            case 'NewOrderInfo':
                return Serialize::newOrderInfo($data);
            case 'OrderInfo':
                return Serialize::orderInfo($data);
            case 'PlaceOrderInfo':
                return Serialize::placeOrderInfo($data);
            case 'Provider':
                return Serialize::provider($data);
            case 'ListProviders':
                $aux = [];
                foreach ($data as $prov) {
                    $aux[] = Serialize::provider($prov);
                }
                return $aux;
            case 'SmsData':
                return Serialize::smsData($data);
            case 'SmsInfo':
                return Serialize::smsInfo($data);
            case 'SmsObject':
                return Serialize::smsObject($data);
            case 'Webhook':
                return Serialize::webhook($data);
            case 'ListWebhooks':
                $aux = [];
                foreach ($data as $web) {
                    $aux[] = Serialize::webhook($web);
                }
                return $aux;
            default:
                throw new \Exception('Object not in factory');
        }
    }
}