<?php

spl_autoload_register(function ($class_name) {
	$classFile = 'lib/'.strtolower($class_name).'.class.php';
	if(file_exists($classFile))
	{
		include($classFile);
		return;
	}
});

Profiler::start();

# **************************** GENERAL UTILITY FUNCTIONS ******************************

function call_or_get($f, $p1 = null, $p2 = null, $p3 = null)
{
	if(is_callable($f))
		return($f($p1, $p2, $p3));
	return($f);
}

/**
	* Can have any number of arguments. Returns the first of its arguments that is not false, empty string, or null.
	*/
function first()
{
	$args = func_get_args();
	foreach($args as $v)
	{
		if(isset($v) && $v !== false && $v !== '' && $v !== null)
			return($v);
	}
	return('');
}

function alnum($s, $replace_with = '_', $trim = true)
{
	if(!$s) $s = '';
	if($trim) $s = trim(strtolower($s));
	return(preg_replace("/[^[:alnum:][:space:]]/u", $replace_with, $s));
}

/**
	* Append a string to the given file.
	*/
function write_to_file($filename, $content)
{
	if (is_array($content)) $content = json_encode($content);
	$open = fopen($filename, 'a+');
	fwrite($open, $content);
	fclose($open);
	@chmod($filename, 0777);
}

# **************************** ARRAY FUNCTIONS ******************************

/**
	* Iterates over the $list, calls $func($item, $key) on each entry,
	* returns a list with all the return values from calls to $func.
	*/
function map($list, $func)
{
	$result = array();
	if($list !== null && $list !== false)
	{
		if(!is_array($list)) $list = array($list);
		foreach($list as $key => $item)
		{
			$v = $func($item, $key);
			if($v !== null)
				$result[] = $v;
		}
	}
	return($result);
}


/**
	* Iterates over the $list, calls $func($item, $key) on each entry,
	* returns a list with all the non-null return values from calls to $func.
	*/
function reduce($list, $func)
{
	$result = null;
	if($list !== null && $list !== false)
	{
		if(!is_array($list)) $list = array($list);
		foreach($list as $key => $item)
		{
			$v = $func($result, $item, $key);
			if($v !== null)
				$result = $v;
		}
	}
	return($result);
}

/**
	* Returns a value from the $GLOBALS['config'] array identified by $key.
	* Sub-array values can be addressed by using the '/' character as a separator.
	*/
function cfg($key)
{
	$config = $GLOBALS['config'];
	$seg = explode('/', $key);
	$lastSeg = array_pop($seg);
	foreach($seg as $s)
	{
		if(is_array($config[$s]))
			$config = $config[$s];
		else
			$config = array();
	}
	return($config[$lastSeg]);
}


# **************************** STRING/FORMATTING FUNCTIONS ******************************

/**
	* Read a key-value text into a hash map.
	*/
function strings_to_array($text, $params = array())
{
	$result = array();
	$stringArray = explode("\n", $text);
	if (is_array($stringArray))
		foreach ($stringArray as $line)
		{
			$key = CutSegment('=', $line);
			$line = trim($line);
			if(substr($key, -1) == '+')
			{
				// add this to array by key
				$key = substr($key, 0, -1);
				$result[$key][] = $line;
			}
			else if(substr($line, 0, 1) == '[' && substr($line, -1) == ']')
			{
				foreach(explode(',', substr($line, 1, -1)) as $seg)
					$result[$key][] = $seg;
			}
			else if ($key != '') $result[$key] = $line;
		}
	return($result);
}

/**
	* Convert any base number into another number of another base system.
	*/
function base_convert_any($numberInput, $fromBaseInput, $toBaseInput)
{
	if ($fromBaseInput==$toBaseInput) return $numberInput;
	$fromBase = str_split($fromBaseInput,1);
	$toBase = str_split($toBaseInput,1);
	$number = str_split($numberInput,1);
	$fromLen=strlen($fromBaseInput);
	$toLen=strlen($toBaseInput);
	$numberLen=strlen($numberInput);
	$retval='';
	if ($toBaseInput == '0123456789')
	{
			$retval=0;
			for ($i = 1;$i <= $numberLen; $i++)
					$retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
			return $retval;
	}
	if ($fromBaseInput != '0123456789')
			$base10=base_convert_any($numberInput, $fromBaseInput, '0123456789');
	else
			$base10 = $numberInput;
	if ($base10<strlen($toBaseInput))
			return $toBase[$base10];
	while($base10 != '0')
	{
			$retval = $toBase[bcmod($base10,$toLen)].$retval;
			$base10 = bcdiv($base10,$toLen,0);
	}
	return $retval;
}

/**
	* Convert a Unix timestamp into a human-friendly short form.
	*/
function age_to_string($unixDate, $new = 'just now', $ago = 'ago')
{
	if($unixDate == 0) return('-');
	$result = '';
	$oneMinute = 60;
	$oneHour = $oneMinute*60;
	$oneDay = $oneHour*24;

	$difference = time() - $unixDate;

	if ($difference < $oneMinute)
		$result = $new;
	else if ($difference < $oneHour)
		$result = round($difference/$oneMinute).' min '.$ago;
	else if ($difference < $oneDay)
		$result = floor($difference/$oneHour).' h '.$ago;
	else if ($difference < $oneDay*5)
		$result = gmdate('D H:i', $unixDate);
	else if ($difference < $oneDay*365)
		$result = gmdate('M dS H:i', $unixDate);
	else
		$result = date('d. M Y H:i', $unixDate);
	return($result);
}

/**
	* Given the separator string $segdiv, cut a piece of &$cake off that precedes $segdiv,
	* and return that piece. If there are no instances of $segdiv in &$cake, nibble()
	* returns the entirety of &$cake and sets &$cake to an empty string.
	*/
function nibble($segdiv, &$cake, &$found = false)
{
	$p = strpos($cake, $segdiv);
	if ($p === false)
	{
		$result = $cake;
		$cake = '';
		$found = false;
	}
	else
	{
		$result = substr($cake, 0, $p);
		$cake = substr($cake, $p + strlen($segdiv));
		$found = true;
	}
	return $result;
}

function str_ends_width($s, $match)
{
	return(substr($s, -strlen($match)) == $match);
}

function truncate($s, $maxLength, $indicator = '')
{
	if(strlen($s) <= $maxLength)
		return($s);
	else
		return(substr($s, 0, $maxLength).$indicator);
}

function match_with($subject, $criteria)
{
	$result = true;
	foreach($criteria as $k => $v)
	{
		if($subject[$k] != $v) $result = false;
	}
	return($result);
}

function get_browser_info()
{
	return(array(
		'ip' => first($_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['REMOTE_ADDR']),
		'agent' => $_SERVER['HTTP_USER_AGENT'],
		'lang' => $_SERVER['HTTP_ACCEPT_LANGUAGE'],
		'cookie' => $_SERVER['HTTP_COOKIE'],
		));
}
