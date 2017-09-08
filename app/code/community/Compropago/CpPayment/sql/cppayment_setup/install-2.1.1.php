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
 * Get Drop tables querys
 *
 * @param null|string $prefix
 * @return array
 *
 * @author Eduardo Aguilar <dante.aguilar41@gmail.com>
 */
function sqlDropTables($prefix = null) {
    return array(
        'DROP TABLE IF EXISTS `' . $prefix . 'compropago_orders`;',
        'DROP TABLE IF EXISTS `' . $prefix . 'compropago_transactions`;',
        'DROP TABLE IF EXISTS `' . $prefix . 'compropago_webhook_transactions`;'
    );
}

/**
 * Get query array for create tables
 *
 * @param null $prefix
 * @return array
 *
 * @author Eduardo Aguilar <dante.aguilar41@gmail.com>
 */
function sqlCreateTables($prefix = null) {
    return array(
        'CREATE TABLE `' . $prefix . 'compropago_orders` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` int(11) NOT NULL,
          `modified` int(11) NOT NULL,
          `compropagoId` varchar(50) NOT NULL,
          `compropagoStatus`varchar(50) NOT NULL,
          `storeCartId` varchar(255) NOT NULL,
          `storeOrderId` varchar(255) NOT NULL,
          `storeExtra` varchar(255) NOT NULL,
          `ioIn` mediumtext,
          `ioOut` mediumtext,
          PRIMARY KEY (`id`), UNIQUE KEY (`compropagoId`)
          );',
        'CREATE TABLE `' . $prefix . 'compropago_transactions` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `orderId` int(11) NOT NULL,
          `date` int(11) NOT NULL,
          `compropagoId` varchar(50) NOT NULL,
          `compropagoStatus` varchar(50) NOT NULL,
          `compropagoStatusLast` varchar(50) NOT NULL,
          `ioIn` mediumtext,
          `ioOut` mediumtext,
          PRIMARY KEY (`id`)
          );',
        'CREATE TABLE `' . $prefix . 'compropago_webhook_transactions` (
          `id` integer not null auto_increment,
          `webhookId` varchar(50) not null,
          `updated` integer not null,
          `status` varchar(50) not null,
          `url` text not null,
          primary key(id)
          );'
    );
}

$installer = $this;
$installer->startSetup();

$prefix = Mage::getConfig()->getTablePrefix();

foreach (sqlDropTables($prefix) as $table) {
    $installer->run($table);
}

foreach (sqlCreateTables($prefix) as $table) {
    $installer->run($table);
}

$installer->endSetup();