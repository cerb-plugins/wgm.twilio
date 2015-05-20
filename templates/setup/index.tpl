<h2>{'wgm.twilio.common'|devblocks_translate}</h2>

<form action="javascript:;" method="post" id="frmSetupTwilio" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="twilio">
<input type="hidden" name="action" value="saveJson">

<fieldset>
	<legend>API Credentials</legend>
	
	<b>Account SID:</b><br>
	<input type="text" name="api_sid" value="{$params.api_sid}" size="50"><br>
	<br>
	
	<b>Auth Token:</b><br>
	<input type="password" name="api_token" value="{$params.api_token}" size="45"><br>
	<br>
	
	<b>Default Caller ID:</b><br>
	<input type="text" name="default_caller_id" value="{$params.default_caller_id}" size="45"><br>
	<br>

	<div class="status"></div>	

	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
</fieldset>

</form>

<script type="text/javascript">
$('#frmSetupTwilio BUTTON.submit')
	.click(function(e) {
		genericAjaxPost('frmSetupTwilio','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#frmSetupTwilio div.status',$o.error);
			} else {
				Devblocks.showSuccess('#frmSetupTwilio div.status',$o.message);
			}
		});
	})
;
</script>