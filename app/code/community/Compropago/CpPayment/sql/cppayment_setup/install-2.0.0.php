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
$libcp = Mage::getBaseDir('lib') . DS . 'Compropago' . DS . 'vendor' . DS . 'autoload.php';

require_once $libcp;

use CompropagoSdk\Extern\TransactTables;

$installer = $this;
$installer->startSetup();

foreach (TransactTables::sqlDropTables(Mage::getConfig()->getTablePrefix()) as $table){
    $installer->run($table);
}

foreach (TransactTables::sqlCreateTables(Mage::getConfig()->getTablePrefix()) as $table){
    $installer->run($table);
}

$installer->endSetup();