<!doctype html>
<html class="no-js" lang="">

<head>
	<meta charset="utf-8">
	<title><?= first(URL::$route['page-title'], cfg('site/default_page_title')).' | '.cfg('site/name') ?></title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<meta property="og:title" content="">
	<meta property="og:type" content="">
	<meta property="og:url" content="">
	<meta property="og:image" content="">

	<link rel="manifest" href="site.webmanifest">
	<link rel="apple-touch-icon" href="icon.png">
	<!-- Place favicon.ico in the root directory -->

	<link rel="stylesheet" href="css/normalize.css">
	<link rel="stylesheet" href="themes/default/css/">
	<link rel="stylesheet" href="css/main.css">

	<meta name="theme-color" content="#fafafa">
</head>

<body
	hx-target="body"
	hx-ext="morph"
	hx-swap="morph:innerHTML">

	<script src="js/vendor/modernizr-3.11.2.min.js"></script>
	<script src="js/vendor/htmx.min.js"></script>
	<script src="js/vendor/ideomorph.min.js"></script>
	<script src="js/main.js"></script>
	<script src="js/plugins.js"></script>
	<script src="js/main.js"></script>

	<nav>
	<a href="<?= URL::Link('') ?>"><?= cfg('site/name') ?></a>
	<a href="<?= URL::Link('records/index') ?>">Records</a>
	</nav>

	<content>
		<?= $content ?>
		<?php Profiler::log('Page template done'); ?>
		<pre><?php UI::out([
			'GET' => $_GET,
			'POST' => $_POST,
			'URL::$route' => URL::$route,
			'Profiler' => Profiler::$log,
			'Header' => URL::get_request_header()]) ?></pre>
	</content>

	<footer>

	</footer>

</body>

</html>
