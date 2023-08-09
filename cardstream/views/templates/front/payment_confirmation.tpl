{if $status == 'ok'}
<h2>Payment Success</h2>
<p>{l s='Your order on'}&nbsp;<span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}&nbsp;</span>{l s='is complete.'}
<br/><br/><span class="bold">{l s='Your order will be sent as soon as possible.'}</span>
<br/><br/>{l s='For any questions or for further information, please contact our'}
	{l s='customer support'}.
</p>
{else}
<h2>Payment Error</h2>
<p class="warning">
	{l s='Unfortunately payment has failed for your order. Please recomplete the checkout process.'}
</p>
{/if}
