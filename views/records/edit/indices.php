<?php

	$type = alnum($_REQUEST['id']);
	$r = new Record($type);

	$schema = array(
		'seq' => ['type' => 'readonly', 'size' => 4, 'style' => 'text-align:right;',
			'default' => function($rowidx) { return($rowidx); }],
		'field' => [],
		'key' => ['type' => 'readonly', 'size' => 4],
		'type' => [],
		'length' => [],
		'caption' => [],
		'default' => [],
	);

	$context_name = 'rtype_'.$type.'_idx';
	$odata = DB::columns($type);
	$data = first($_SESSION[$context_name], $odata);

?>
<div hx-target="#edit-area">
<?php

	URL::$route['self-path'] = '--records/edit/indices';
	$new_data = UI::component('ui/std/edit/grid', [
		'context' => $context_name,
		'schema' => $schema,
		'data' => $data,
		'odata' => $odata,
		'on_cancel' => function() use($context_name, $odata, &$data) {
			unset($_SESSION[$context_name]);
			$data = $odata;
			return($odata);
		},
		'on_save' => function() use($context_name, $odata, &$data) {
			unset($_SESSION[$context_name]);
			$data = $odata;
			// todo: actually save data
			return($odata);
		},
	]);

	$was_updated = md5(json_encode($new_data)) != md5(json_encode($data));
	if($was_updated || isset($_SESSION[$context_name]))
	{
		if($was_updated)
			$_SESSION['rtype_'.$type.'_idx'] = $new_data;
		?><div>
			<?= UI::url_button('', 'Save', ['post' =>
				['_context' => $context_name, '_action' => 'save']]) ?>
			<?= UI::url_button('', 'Cancel', ['class' => 'cancel', 'post' =>
				['_context' => $context_name, '_action' => 'cancel']]) ?>
		</div><?php
	}

?>
</div>
