{*
* 2018 Cardstream
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
*
*  @author Matthew James <support@cardstream.com>
*  @copyright  2018 Cardstream Ltd
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{if $status == 'ok'}
<h2>Payment Success</h2>
<p>{l s='Your order on' mod='cardstream'}&nbsp;<span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}&nbsp;</span>{l s='is complete.' mod='cardstream'}
<br/><br/><span class="bold">{l s='Your order will be sent as soon as possible.' mod='cardstream'}</span>
<br/><br/>{l s='For any questions or for further information, please contact our' mod='cardstream'}
	{l s='customer support' mod='cardstream'}.
</p>
{else}
<h2>Payment Error</h2>
<p class="warning">
	{l s='Unfortunately payment has failed for your order. Please recomplete the checkout process.' mod='cardstream'}
</p>
{/if}
