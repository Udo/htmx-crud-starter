<?php return(function($prop) {

	$data = $prop['data'];
	$context_name = first($prop['context'], 'edit_table_context');
	$schema = $prop['schema'];
	# if there is POST data pertaining to this context
	if($_POST['_context'] == $context_name)
	{
		# call 'validate_$fieldname()' if present
		if($_POST['_action'] == 'update' && $prop['validate_'.$_POST['_field']] && isset($schema[$_POST['_field']]))
			$_POST['_val'] = $prop['validate_'.$_POST['_field']]($_POST['_val'], $_POST['_rowid'], $prop);
		# call action handler 'on_$action' if present
		if($prop['on_'.$_POST['_action']])
		{
			$udata = $prop['on_'.$_POST['_action']]($_POST, $data, $prop);
			if(is_array($udata)) $data = $udata;
		}
		else
		{
			# if no action handler found, execute default handlers
			if($_POST['_action'] == 'update')
			{
				if(isset($schema[$_POST['_field']]))
					$data[$_POST['_rowid']][$_POST['_field']] = $_POST['_val'];
				else
					UI::banner('unknown field: '.htmlspecialchars($_POST['_field']));
			}
			else if($_POST['_action'] == 'delrow')
			{
				unset($data[$_POST['_rowid']]);
			}
			else if($_POST['_action'] == 'addrow')
			{
				$data[] = array();
				foreach($data as $idx => $row)
					$prop['focus_row'] = $idx;
			}
			else
			{
				UI::banner('Undefined action handler "on_'.htmlspecialchars($_POST['_action']).
					'" in context "'.htmlspecialchars($context_name).'"');
			}
		}
	}
	?><table>
	<thead>
	<?php foreach($schema as $k => $field_prop)
	{
		if(!$prop['focus_field'] && $field_prop['type'] != 'readonly')
			$prop['focus_field'] = $k;
		?><th><?= first($field_prop['caption'], $k) ?></th><?php
	} ?>
		<th style="padding:0;"><?= UI::url_button('', '➕', [
			'class' => 'edit',
			'hint' => 'Add row',
			'post' => ['_action' => 'addrow', '_context' => $context_name,]
		]) ?></th>
	</thead>
	<tbody>
	<?php foreach($data as $rowctr => $drow)
	{
		?><tr>
			<?php foreach($schema as $k => $field_prop)
			{
				$default_val = call_or_get($field_prop['default'], $rowctr);
				$val = first($drow[$k], $default_val, '');
				$is_changed = $prop['odata'] ? $val != $prop['odata'][$rowctr][$k] : $val != '';
				?><td style="padding:1px;">
					<?php
					UI::component('ui/std/edit/'.first($field_prop['type'], 'string'), [
						'value' => $val,
						'id' => 'f_'.$context_name.'_'.$rowctr.'_'.$k,
						'post' => [
							'_action' => 'update',
							'_context' => $context_name,
							'_rowid' => $rowctr,
							'_field' => $k],
						'edited' => $is_changed,
						'field' => $field_prop,
						'view' => $prop,
					]);
					?>
				</td><?php
			} ?>
			<td style="padding:0">
			<?= UI::url_button('', '🗑️', [
				'class' => 'edit',
				'hint' => 'Delete row',
				'post' => [
					'_action' => 'delrow',
					'_context' => $context_name,
					'_rowid' => $rowctr,
				] ]) ?>
			</td>
		</tr><?php
	} ?>
	</tbody>
	</table>
	<script>
	<?php
	if(isset($prop['focus_field']) && isset($prop['focus_row']))
	{
		?>document.getElementById(<?=
			json_encode('f_'.$context_name.'_'.$prop['focus_row'].'_'.$prop['focus_field']) ?>).focus();<?php
	}
	?>
	</script><?php
	#debug
	#print('POST: '); print_r($_POST);
	#print('<br/>GET: '); print_r($_GET);
	return($data);

});
