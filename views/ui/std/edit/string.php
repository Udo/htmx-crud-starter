<?php return(function($prop) {
	?>
	<input id="<?= $prop['id'] ?>"
		placeholder="<?= htmlspecialchars(first($prop['field']['placeholder'])) ?>"
		class="borderless <?= $prop['edited'] ? 'edited' : '' ?>"
		<?= $prop['post'] ? 'hx-post="'.first($prop['update_url'], URL::self_link($_GET)).'"' : '' ?>
		<?= $prop['post'] ? 'hx-vals=\''.json_encode($prop['post']).'\'' : '' ?>
		<?= $prop['field']['size'] ? 'size="'.$prop['field']['size'].'"' : '' ?>
		style="<?= first(call_or_get($prop['field']['style'])) ?>"
		type="text" name="<?= first($prop['name'], '_val') ?>" value="<?= htmlspecialchars($prop['value']) ?>"/>
	<?php
});
