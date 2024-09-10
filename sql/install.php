<?php
/**
 * 2007-2024 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2024 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'fm_feature_group` (
    `id_feature_group` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`id_feature_group`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'fm_feature` (
    `id_feature` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_feature_group` INT(11) UNSIGNED NOT NULL,
    `name` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`id_feature`),
    KEY `idx_feature_group` (`id_feature_group`),
    CONSTRAINT `fk_feature_group` FOREIGN KEY (`id_feature_group`) 
    REFERENCES `' . _DB_PREFIX_ . 'fm_feature_group` (`id_feature_group`) ON DELETE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'fm_feature_category` (
    `id_feature_category` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_feature` INT(11) UNSIGNED NOT NULL,
    `id_category` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`id_feature_category`),
    KEY `idx_feature` (`id_feature`),
    KEY `idx_category` (`id_category`),
    CONSTRAINT `fk_feature_category` FOREIGN KEY (`id_feature`) 
    REFERENCES `' . _DB_PREFIX_ . 'fm_feature` (`id_feature`) ON DELETE CASCADE,
    CONSTRAINT `fk_category` FOREIGN KEY (`id_category`) 
    REFERENCES `' . _DB_PREFIX_ . 'category` (`id_category`) ON DELETE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'fm_feature_product` (
    `id_feature_product` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_feature` INT(11) UNSIGNED NOT NULL,
    `id_product` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`id_feature_product`),
    KEY `idx_feature` (`id_feature`),
    KEY `idx_product` (`id_product`),
    CONSTRAINT `fk_feature` FOREIGN KEY (`id_feature`) 
    REFERENCES `' . _DB_PREFIX_ . 'fm_feature` (`id_feature`) ON DELETE CASCADE,
    CONSTRAINT `fk_product` FOREIGN KEY (`id_product`) 
    REFERENCES `' . _DB_PREFIX_ . 'product` (`id_product`) ON DELETE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}