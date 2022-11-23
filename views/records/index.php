<h1>Record Types</h1>
<div>
	<?php UI::url_button('records/create', 'Create New') ?>
</div>
<div>
	<?php UI::component('ui/std/table', [
			'data' => Record::get_types(),
			'row_click' => function($row) { return(URL::Link('records/edit/index', ['id' => $row['Name']])); },
	]); ?>
</div>
