<?php

	$data = $prop['data'];
	$columns = $prop['columns'];
	if(sizeof($data) == 0)
	{
		UI::banner('no data');
		return;
	}
	?><table class="<?= $prop['row_click'] ? 'row_clickable' : '' ?>"><?php
	$row_index = 0;
	foreach($data as $row)
	{
		if($row_index == 0)
		{
			if(!$columns)
			{
				$columns = array();
				foreach($row as $k => $v)
					$columns[$k] = array('caption' => $k);
			}
			?><thead><tr><?php
			foreach($columns as $col_name => $col_prop)
			{
				?><th><?= htmlspecialchars($col_prop['caption']) ?></th><?php
			}
			?></tr></thead><tbody><?php
		}
		$row_index += 1;
		$row_click = $prop['row_click'] ?
			'hx-push-url="true" hx-target="body" hx-get="'.$prop['row_click']($row).'"' : '';
		?><tr <?= $row_click ?>><?php
		foreach($columns as $col_name => $col_prop)
		{
			?><td><?= htmlspecialchars(first($row[$col_name])) ?></td><?php
		}
		?></tr><?php
	}
	?></tbody></table><?php
