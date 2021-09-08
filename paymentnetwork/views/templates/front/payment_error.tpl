{extends "$layout"}

{block name="content"}
<section id="main">
    <section id="content" class="page-content card card-block">
        <h2>Payment Error</h2>
        <br>
        <p>
            {l s='Unfortunately payment has failed for your order. Please contact shop administrator.'}
            <br>
            <br>
            {if isset($error_msg)}
                {l s='Additional error message : ' mod='paymentnetwork'}{$error_msg|escape:'htmlall':'UTF-8'}
            {/if}
        </p>
    </section>
</section>
{/block}
