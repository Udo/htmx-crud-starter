<?php return(function($prop) {
	?>
	<input id="<?= $prop['id'] ?>"
		disabled
		style="opacity:0.6;<?= first(call_or_get($prop['field']['style'])) ?>"
		class="borderless"
		<?= $prop['field']['size'] ? 'size="'.$prop['field']['size'].'"' : '' ?>
		type="text" value="<?= htmlspecialchars($prop['value']) ?>"/>
<?php });
