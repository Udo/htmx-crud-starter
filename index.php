<?php

	$GLOBALS['start_time'] = hrtime(true);

	include('lib/ulib.php');
	include('config/settings.php');
	Profiler::log('Request setup complete');

	URL::MakeRoute();
	User::init();

	ob_start();
	$view_file = 'views/'.first(URL::$route['l-path'], 'index').'.php';
	if(URL::$route['render_list'] && sizeof(URL::$route['render_list']) > 0)
		UI::render(URL::$route['render_list']);
	else if(file_exists($view_file))
	{
		URL::$route['primary-view-not-found'] = false;
		include($view_file);
	}
	else
		URL::$route['content-type'] = '404';
	if(URL::$route['primary-view-not-found'])
		URL::$route['content-type'] = '404';
	$content = ob_get_clean();

	include(cfg('theme/path').'/page.'.URL::$route['content-type'].'.php');

	Log::audit('page:'.URL::$route['page'], URL::$route['l-path']);
