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
