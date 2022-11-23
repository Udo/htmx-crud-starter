<?php

class URL
{

	static $locator = '';
	static $route = array();
	static $error = '';
	static $title = 'TITLE';
	static $page_type = 'html';

	# extracts the locator string from parameters or the URI
	static function ParseRequestURI()
	{
		$uri = first($_SERVER['REQUEST_URI']);
		$loc = nibble('?', $uri);
		$seg_count = 0;

		if($uri != '')
		{
			while($uri != '')
			{
				$seg_count += 1;
				$seg = nibble('&', $uri);
				if(stristr($seg, '=') === false && $seg_count == 1)
				{
					$loc .= $seg;
				}
				else
				{
					$k = nibble('=', $seg);
					$_REQUEST[$k] = $seg;
				}
			}
		}

		self::$locator = $loc;
		return($loc);
	}

	static function NotFound($message = 'resource not found')
	{
		header("HTTP/1.0 404 Not Found");
		self::$error = $message;
	}

	static function render_list_from_path($path)
	{
		$render_list = array();
		if(is_array($path))
			$seg = $path;
		else
			$seg = explode('/', $path);
		$spath = '';
		foreach($seg as $s)
		{
			$spath .= ($spath == '' ? '' : '/').$s;
			if(file_exists('views/'.$spath.'/_all.php'))
				$render_list[] = $spath.'/_all';
		}
		if(file_exists('views/'.$spath.'.php'))
			$render_list[] = $spath;
		return($render_list);
	}

	# determines which view to show given a locator string
	static function MakeRoute($lc = false)
	{
		$route = $GLOBALS['config']['url'];
		$route['render_list'] = array();
		if(!$lc)
			$lc = URL::ParseRequestURI();
		if(str_starts_with($lc, $GLOBALS['config']['url']['root']))
			$lc = substr($lc, strlen($GLOBALS['config']['url']['root']));
		if(str_contains($lc, '--'))
		{
			$route['content-type'] = 'blank';
			$route['render_list'] = false;
			if(substr($lc, 0, 2) == '--')
				$lc = substr($lc, 2);
		}
		$seg = array();
		foreach(explode('/', $lc) as $s)
			if(substr($s, 0, 1) != '.' && $s != '') # strip unnecessary prefixes
				$seg[] = $s;
		if($route['render_list'] !== false)
			$route['render_list'] = URL::render_list_from_path($seg);
		$route['l-path'] = implode('/', $seg);
		if(!file_exists('views/'.$route['l-path'].'.php'))
			# this enables you to disable the 404 error inside _all.php files which
			# is a cool way to allow for "pretty" URLs containing dynamic IDs
			$route['primary-view-not-found'] = true;
		if(sizeof(self::$route) == 0) self::$route = $route;
		foreach($_GET as $k => $v) if($v === '') unset($_GET[$k]);
		foreach($_REQUEST as $k => $v) if($v === '') unset($_REQUEST[$k]);
		Profiler::log('URL::MakeRoute('.$lc.') done');
		return($route);
	}

	static function Link($path, $params = false)
	{
		if(cfg('url/pretty'))
		{
			return($GLOBALS['config']['url']['root'].$path.($params ? '?'.http_build_query($params) : ''));
		}
		else
		{
			return($GLOBALS['config']['url']['root'].'?'.$path.($params ? '&'.http_build_query($params) : ''));
		}
	}

	static function self_link()
	{
		return(self::Link(first(URL::$route['self-path'], self::$route['l-path']), $_GET));
	}

	# redirect to URL and quit
	static function Redirect($url = '', $params = array())
	{
		header('location: '.self::Link($url, $params));
		die();
	}

	static $request_header = array();

	static function get_request_header()
	{
		if(sizeof(URL::$request_header) == 0)
		{
			foreach($_SERVER as $k => $v)
			{
				if(substr($k, 0, 5) == 'HTTP_')
					URL::$request_header[substr($k, 5)] = $v;
			}
			ksort(URL::$request_header);
		}
		return(URL::$request_header);
	}

}
