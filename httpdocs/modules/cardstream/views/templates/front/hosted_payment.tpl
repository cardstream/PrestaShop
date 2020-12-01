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
*
*  @author Matthew James <support@cardstream.com>
*  @copyright  2018 Cardstream Ltd
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{extends "$layout"}

{block name="content"}

  {capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, '')|escape:'htmlall':'UTF-8'}" rel="nofollow" title="{l s='Go back to the Checkout' mod='cardstream'}">
    {l s='Checkout' mod='cardstream'}
    {l s='Pay using Cardstream' mod='Cardstream'}
  {/capture}

  {assign var='current_step' value='payment'}

  {if isset($api_errors)}
    <div class="errors">
    {foreach $api_error as $error}
      <div class='alert alert-danger error'>{$error}</div>
    {/foreach}
    </div>
  {/if}

  <form id="paymentgatewaymoduleform" action="{$url}" method="post">

    <div class="box cheque-box">
      <h3 class="page-subheading">
        {l s= $frontend|cat:' Payment' mod='cardstream'}
      </h3>

      <p class="cheque-indent">
        <strong class="dark">
          {l s='Clicking "I confirm my order" will take you to the Cardstream secure payment website' mod='cardstream'}
        </strong>
      </p>
    </div>

    {foreach $form as $k=>$v}
      <input type="hidden" name="{$k|escape:'html':'UTF-8'}" value="{$v|escape:'html':'UTF-8'}"/>
    {/foreach}

    <p class="cart_navigation clearfix" id="cart_navigation">
    <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL)|escape:'html':'UTF-8'}">
      <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='cardstream'}
      </a>
      <button class="button btn btn-default button-medium" type="submit">
        <span>{l s='I confirm my order' mod='cardstream'}<i class="icon-chevron-right right"></i></span>
      </button>
    </p>
  </form>

  <script type="text/javascript">
    window.onload = function () {
      // document.getElementById('paymentgatewaymoduleform').submit();
    };
  </script>
{/block}

