<?php

	$type = alnum($_REQUEST['id']);
	Record::delete_type($type);

	URL::redirect('records/index');
