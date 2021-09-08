{extends "$layout"}

{block name="content"}

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, '')|escape:'htmlall':'UTF-8'}" rel="nofollow" title="{l s='Go back to the Checkout'}">
	{l s='Checkout'}
{/capture}

{assign var='current_step' value='payment'}

{if isset($api_errors)}
	<div class="errors">
	{foreach $api_error as $error}
		<div class='alert alert-danger error'>{$error}</div>
	{/foreach}
	</div>
{/if}

<iframe id="paymentgatewayframe" name="paymentgatewayframe" frameBorder="0" seamless="seamless" style="width:699px;height: 1100px;margin: 0 auto;display:block;"></iframe>

<form id="paymentgatewaymoduleform" action="{$url}" method="post" target="paymentgatewayframe">
	<div class="box cheque-box">
		<h3 class="page-subheading">
			{l s= $frontend|cat:' Payment'}
		</h3>

		<p class="cheque-indent">
			<strong class="dark">
				{l s='Clicking "I confirm my order" will take you to the secure payment website'}
			</strong>
		</p>
	</div>

	{foreach $form as $k=>$v}
		<input type="hidden" name="{$k|escape:'html':'UTF-8'}" value="{$v|escape:'html':'UTF-8'}"/>
	{/foreach}

	<p class="cart_navigation clearfix" id="cart_navigation">
	<a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL)|escape:'html':'UTF-8'}">
		<i class="icon-chevron-left"></i>{l s='Other payment methods'}
		</a>
		<button class="button btn btn-default button-medium" type="submit">
			<span>{l s='I confirm my order'}<i class="icon-chevron-right right"></i></span>
		</button>
	</p>
</form>

<script>
	if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
		var frame = document.getElementById('paymentgatewayframe');
		frame.style.height = '1500px';
		frame.style.width = '100%';
	}
	document.getElementById('paymentgatewaymoduleform').submit();
	document.getElementById('paymentgatewaymoduleform').remove();
</script>
{/block}

