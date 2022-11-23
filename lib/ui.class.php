<?php

class UI
{

	static function url_button($url, $caption, $opt = array())
	{
		if(!isset($opt['get'])) $opt['get'] = $_GET;
		if($url == '') $url = first(URL::$route['self-path'], URL::$route['l-path']);
		$push_url = stristr($url, '--') !== false ? false : true;
		?><button <?= $opt['class'] ? 'class="'.$opt['class'].'"' : '' ?>
			<?= $opt['hint'] ? 'title="'.htmlspecialchars($opt['hint']).'"' : '' ?>
			<?= $opt['class'] == 'red' || $opt['confirm'] ?
				'hx-confirm="'.first($opt['confirm'], 'Are you sure?').'"' : '' ?>
			<?= $push_url ? 'hx-push-url="true"' : '' ?>
			<?= $opt['post'] ? 'hx-vals=\''.json_encode($opt['post']).'\'' : '' ?>
			hx-post="<?= URL::Link($url, first($opt['get'], [])) ?>">
			<?= $caption ?>
		</button><?php
	}

	static function banner($text, $opt = array())
	{
		?><div class="banner <?= $opt['class'] ?>" style="<?= $opt['style'] ?>">
			<?= $text ?>
		</div><?php
	}

	static function spinner($text)
	{
		?><div class="htmx-indicator banner blue">
			<?= htmlspecialchars($text) ?>
		</div><?php
	}

	static function redirect($url, $params = array(), $delay = 250)
	{
		?><script>
			setTimeout(() => {
				document.location.href = <?= json_encode(URL::Link($url, $params)) ?>
				}, 250);
		</script><?php
	}

	static function render($render_list, $prop = array())
	{
		if(!is_array($render_list))
			$render_list = URL::render_list_from_path($render_list);
		$url = URL::$route['render_child'] = array_shift($render_list);
		Profiler::log('UI::render('.$url.') start');
		if($url)
		{
			$fn = 'views/'.$url.'.php';
			if(file_exists($fn))
				$res = include($fn);
			else
				$res = UI::banner('UI::render("'.$url.'") element not found');
		}
		Profiler::log('UI::render('.$url.') end');
		return($res);
	}

	static $component_cache = array();

	static function component($url, $prop = array())
	{
		if(UI::$component_cache[$url])
		{
			return(UI::$component_cache[$url]($prop));
		}
		$fn = 'views/'.$url.'.php';
		if(file_exists($fn))
		{
			$res = include($fn);
			if(is_callable($res))
			{
				# This is a speed optimization: if the component returns a function,
				# cache and then execute it. On subsequent invokations of this component,
				# it can be executed from cache instead of going through include() again
				UI::$component_cache[$url] = $res;
				$res = $res($prop);
			}
		}
		else
			UI::banner('UI::component("'.$url.'") element not found');
		Profiler::log('UI::component('.$url.') done');
		return($res);
	}

	static function out($v, $indent = '')
	{
		foreach($v as $kk => $vv)
		{
			if(is_array($vv))
			{
				print($indent.$kk.': '.(sizeof($vv) == 0 ? '[]' : '').chr(10));
				UI::out($vv, $indent.chr(9));
			}
			else if(is_bool($vv))
			{
				print($indent.$kk.': '.($vv ? 'true' : 'false').chr(10));
			}
			else if(is_string($vv))
			{
				print($indent.$kk.': "'.htmlspecialchars($vv).'"'.chr(10));
			}
			else
			{
				print($indent.$kk.': '.($vv).chr(10));
			}
		}
	}

}
