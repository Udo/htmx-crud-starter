<h1>Create New Record Type</h1>
<div>

	<form hx-post="<?= URL::self_link(['x' => 'y']) ?>">

		<?php

			$record_name = alnum($_POST['record_name']);

			if(strlen($record_name) > 2 && $_POST['record_name'] == $record_name)
			{
				if(!Record::create_type($record_name))
				{
					UI::banner('Error: '.Record::$error);
				}
				else
				{
					UI::banner('Success', ['class' => 'green']);
					UI::redirect('records/edit/index', array('id' => $record_name));
				}
			}

			UI::component('ui/std/edit/string', [ 'name' => 'record_name', 'value' => $record_name ]);
			?><input type="submit" value="Create"/><?
			UI::url_button('records/index', 'Cancel', [ 'class' => 'cancel' ]);
			UI::spinner('working...');

		?>

	</form>

</div>
