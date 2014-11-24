<p class="payment_module">
	<a href="javascript:$('#ipay88_form').submit();" title="{l s='Pay with iPay88' mod='ipay88'}">
		<img src="{$module_template_dir}ipay88.gif" alt="{l s='Pay with iPay88' mod='ipay88'}" />
		{l s='Pay with iPay88' mod='ipay88'}
	</a>
</p>

<form action="{$ipay88Url}" method="post" id="ipay88_form">

	<input type="hidden" name="MerchantCode" value="{$MerchantCode}" />
	<input type="hidden" name="PaymentId" value="" />
	<input type="hidden" name="RefNo" value="{$RefNo}" />
	<input type="hidden" name="Amount" value="{$Amount}" />
	<input type="hidden" name="Currency" value="{$Currency}" />
	<input type="hidden" name="ProdDesc" value="{$ProdDesc}" />
	<input type="hidden" name="UserName" value="{$UserName}" />
	<input type="hidden" name="UserEmail" value="{$UserEmail}" />
	<input type="hidden" name="UserContact" value="{$UserContact}" />
	<input type="hidden" name="Remark" value="{$Remark}" />
	<input type="hidden" name="Lang" value="UTF-8" />
	<input type="hidden" name="ResponseURL" value="{$returnUrl}" />	
	<input type="hidden" name="Signature" value="{$Signature}" />

</form>