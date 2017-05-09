<?php
/**
 * Copyright 2015 Compropago.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * Compropago $Library
 * @author Eduardo Aguilar <eduardo.aguilar@compropago.com>
 */

require_once(Mage::getBaseDir('lib') . DS . 'Compropago' . DS . 'vendor' . DS . 'autoload.php');

use CompropagoSdk\Client;

class Compropago_CpPayment_Model_Providers
{
    public function toOptionArray()
        {
            $options = array();
            $client = new Client('', '', false);
            $flag = false;
            foreach ($client->api->listDefaultProviders() as $provider){
                $options[] = array(
                    'value' => $provider->internal_name,
                    'label' => $provider->name
                );
                if ($provider->internal_name == "OXXO") { $flag = true; }
            }
            if (!$flag) {
                $OXXO[] = [
                    'value' => "OXXO",
                    'label' => "Oxxo"
                ];
                $options = array_merge($OXXO,$options);
            }
            return $options;
        }
}