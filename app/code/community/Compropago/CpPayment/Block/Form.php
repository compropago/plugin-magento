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


class Compropago_CpPayment_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('compropago/cppayment/cash.phtml');
    }

    public function getMethod()
    {
        return parent::getMethod();
    }


    /**
     * Render template for Payment Method Lebel in checkout
     *
     * @return mixed
     */
    public function getMethodLabelAfterHtml()
    {
        $model = Mage::getModel('cppayment/standard');

        $logo = (int)trim($model->getConfigData('compropago_show_title_logo')) == 1 ? true : false;

        $final = "";
        
        if($logo){
            $mark = Mage::getConfig()->getBlockClassName('core/template');
            $mark = new $mark;
            $mark->setTemplate('compropago/cppayment/mark.phtml');
            $final = $mark->toHtml();
        }

        return $final;
    }
}