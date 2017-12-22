<b>{'common.connected_account'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<button type="button" class="chooser-abstract" data-field-name="{$namePrefix}[connected_account_id]" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-single="true" data-query="service:twilio"><span class="glyphicons glyphicons-search"></span></button>
	<ul class="bubbles chooser-container">
		{if $connected_account}
		<li>
			<input type="hidden" name="{$namePrefix}[connected_account_id]" value="{$connected_account->id}">
			<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="{$connected_account->id}">{$connected_account->name}</a>
		</li>
		{/if}
	</ul>
</div>

<b>{'message.header.from'|devblocks_translate|capitalize}:</b> (e.g. +17145551234)<br>
<input type="text" name="{$namePrefix}[from]" value="{$params.from}" size="45" style="width:100%;" class="placeholders"><br>
<br>

<b>{'message.header.to'|devblocks_translate|capitalize}:</b> (e.g. +17145551234)<br>
<input type="text" name="{$namePrefix}[phone]" value="{$params.phone}" size="45" style="width:100%;" class="placeholders"><br>
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div>
	<textarea name="{$namePrefix}[content]" rows="10" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	
	$action.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	$action.find('.chooser-abstract')
		.cerbChooserTrigger()
		;
});
</script>