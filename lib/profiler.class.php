<?php

class Profiler
{

	static $log = array();
	static $start = 0;
	static $last = 0;
	static $current = 0;
	static $indent = '';

	# makes a commented profiler entry
	static function log($text, $backtrace = false)
	{
		if(substr($text, -3) == 'end')
			self::$indent = substr(self::$indent, 1);
		$thistime = microtime(true);
		$absoluteMS = $thistime - self::$start;
		self::$log[] =
			number_format($absoluteMS*1000, 3).'ms | '.
			number_format(1000*($thistime - self::$last), 3).'ms | '.
			ceil(memory_get_usage()/1024).' kB | '.self::$indent.$text;
		if($backtrace)
			self::$log[] = json_encode($backtrace).chr(10);
		self::$last = $thistime;
		self::$current = $absoluteMS;
		if(substr($text, -5) == 'start')
			self::$indent .= chr(9);
		return($thistime);
	}

	static function get_time()
	{
		return(1000*(microtime(true) - self::$start));
	}

	static function start()
	{
		self::$start = self::$last = microtime(true);
	}

}
