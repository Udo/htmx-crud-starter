<?php

	$type = alnum($_REQUEST['id']);

	unset($_SESSION['changeset']);

?>
<div style="background:none;float:right">
	<?= UI::url_button('records/edit/indices', 'Indices', ['get' => ['id' => $type]]) ?>
	<?= UI::url_button('records/edit/forms', 'Forms', ['get' => ['id' => $type]]) ?>
	<?= UI::url_button('records/delete', 'Delete', ['class' => 'red', 'get' => ['id' => $type]]) ?>
</div>
<h1>
	Record Type: <?= htmlspecialchars($type) ?>
</h1>
<div id="edit-area">
	<?php UI::render($render_list) ?>
</div>
