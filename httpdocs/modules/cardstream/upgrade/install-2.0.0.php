<?php
/**
 * 2015 Cardstream
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
 *  @author    Paul Lashbrook <support@cardstream.com>
 *  @copyright 2015 Cardstream Ltd
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * upgrades version 1.2 to 1.3
 *
 * no longer requires users to manually adjust hooks
 * adds header hook for css file
 *
 * @param $object
 *
 * @return bool
 */
function upgrade_module_2_0_0($object)
{
    if (!defined('_PS_VERSION_')) {
        exit;
    }

    return ( $object->registerHook('displayPayment') && $object->registerHook('displayPaymentReturn') &&
             $object->registerHook('header') );
}
