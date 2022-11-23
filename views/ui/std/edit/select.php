<?php return(function($prop) {
	?>
	<select id="<?= $prop['id'] ?>"
		class="borderless <?= $prop['edited'] ? 'edited' : '' ?>"
		<?= $prop['post'] ? 'hx-post="'.first($prop['update_url'], URL::self_link($_GET)).'"' : '' ?>
		<?= $prop['post'] ? 'hx-vals=\''.json_encode($prop['post']).'\'' : '' ?>
		style="<?= first(call_or_get($prop['field']['style'])) ?>"
		name="<?= first($prop['name'], '_val') ?>">
		<?php
		if(is_callable($prop['field']['options']))
			$options = $prop['field']['options']($prop, $opt, $view);
		else if(is_array($prop['field']['options']))
			$options = $prop['field']['options'];
		else if(is_array($prop['field']['list']))
			foreach($prop['field']['list'] as $item)
				$options[$item] = $item;
		?><option value="">-</option><?php
		foreach($options as $k => $v)
		{
			?><option
				<?= $prop['value'] == $k ? 'selected' : '' ?>
				value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($v) ?></option><?php
		}
		?>
	</select>
<?php });
