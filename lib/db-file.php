<?php

define('STORAGE_DIR', 'data/');

/* SIMPLE UNIX-STYLE FILE STORAGE LAYER
 *
 * PREREQUISITES:
 * shell_exec() Linux/Unix with access to
 * - tail, grep, ls, rm, cd
 *
 * The general layout is
 * storage_function($class,			$bucket,		$item_name)
 * 					   ^			   ^			   1
 * STORAGE_DIR	.	ITEM_CLASS	.	BUCKET_PATH	.	ITEM_NAME	.	'.json'/'.log'
 */

# convert a number into another base
function make_hash($s)
{
	$s = strtolower(substr(trim($s), 0, 64));
	return(substr(base_convert_ex(
		sha1(sha1('qw0e983124o521öl34u9087'.$s)),
		'0123456789abcdef',
		'0123456789abcdefghijklmnopqrstuvwxyz'
	), -10));
}

function base_convert_ex($numberInput, $fromBaseInput, $toBaseInput)
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
		$base10=base_convert_ex($numberInput, $fromBaseInput, '0123456789');
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

function make_bucket_path($p)
{
	if(stristr($p, '/') !== false)
	{
		$seg = explode('/', $p);
		$p = array_shift($seg);
		return(substr($p, -2).'/'.$p.'/'.implode('/', $seg));
	}
	return(substr($p, -2).'/'.$p);
}

function write_file($file_name, $content)
{
	$csum = md5($content);
	if($GLOBALS['ccache'][$file_name] == $csum)
		return;
	profiler_log('write_file start '.$file_name);
	cache()->set($file_name, $content);
	if(str_ends_with($file_name, 'presence.json'))
		return;
	WriteToFile('log/debug.table.log', first($_REQUEST['session']['room'], '?').chr(9).
		first($_REQUEST['session']['nick'], '?').chr(9).
		'write_file'.chr(9).$file_name.chr(9).strlen($content).chr(10));
	file_put_contents($file_name, $content, LOCK_EX);
	profiler_log('write_file end '.$file_name);
}

function delete_file($file_name)
{
	cache()->delete($file_name);
	unlink($file_name);
}

function read_file($file_name)
{
	$content = cache()->get($file_name);
	if($content)
	{
		$GLOBALS['ccache'][$file_name] = md5($content);
		profiler_log('read_file cached '.$file_name);
		#WriteToFile('log/debug.table.log', 'read_file:cached'.chr(9).$file_name.chr(9).strlen($content).chr(10));
		return($content);
	}
	if(str_ends_with($file_name, 'presence.json'))
		return;
	$tmp = fopen($file_name, 'rb');
	@flock($tmp, LOCK_SH);
	$content = file_get_contents($file_name);
	@flock($tmp, LOCK_UN);
	fclose($tmp);
	cache()->set($file_name, $content);
	$GLOBALS['ccache'][$file_name] = md5($content);
	profiler_log('read_file cold '.$file_name);
	return($content);
}

function write_data($class, $bucket, $type, $data)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	if(!file_exists($storage_path)) @mkdir($storage_path, 0774, true);
	$fn = $storage_path.'/'.$type.'.json';
	write_file($fn, json_encode($data));
	$GLOBALS['write_data'] = $storage_path.'/'.$type.'.json';
}

function read_data($class, $bucket, $type)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	$fn = $storage_path.'/'.$type.'.json';
	return(json_decode(read_file($fn), true));
}

function delete_data($class, $bucket, $type)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	return(delete_file($storage_path.'/'.$type.'.json'));
}

function get_data_filename($class, $bucket, $type)
{
	return('data/'.$class.'/'.make_bucket_path($bucket).'/'.$type.'.json');
}

function list_bucket($class, $bucket)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	foreach(explode(chr(10), trim(shell_exec('ls -1 '.escapeshellarg($storage_path)))) as $name)
	{
		if(substr($name, 0, 1) != '_' && trim($name) != '')
			$items[] = $name;
	}
	return($items);
}

function search_bucket($class, $bucket, $q)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	foreach(explode(chr(10), trim(shell_exec('grep -irlF '.escapeshellarg($q).' '.escapeshellarg($storage_path)))) as $l)
	{
		$name = substr($l, strlen($storage_path)+1, -5);
		if(substr($name, 0, 1) != '_' && trim($name) != '')
			$items[] = $name;
	}
	return($items);
}

function delete_bucket($class, $bucket)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	if(stristr($storage_path, '*') !== false) return;
	if(stristr($storage_path, '?') !== false) return;
	$result = trim(shell_exec('rm -r '.escapeshellarg($storage_path).' 2>&1'));
	return($result);
}

function list_storage($class, $bucket, $crit = false)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	$result = array();
	if($crit)
		$crit_arg = '-iname '.escapeshellarg($crit);
	foreach(explode(chr(10), trim(shell_exec('cd '.escapeshellarg($storage_path).' && find . '.$crit_arg))) as $item)
	{
		nibble('/', $item);
		$file = $storage_path.'/'.$item;
		if(is_file($file))
		{
			if(stristr($item, '/') !== false)
			{
				$dir_parts = explode('/', $item);
				$item = array_pop($dir_parts);
				$sbucket = '/'.implode('/', $dir_parts);
			}
			else
			{
				$sbucket = '';
			}
			$format = 'none';
			if(substr($item, -4, 1) == '.')
			{
				$format = substr($item, -3);
				$item = substr($item, 0, -4);
			}
			$result[] = array(
				'bucket' => $bucket.$sbucket,
				'item' => $item,
				'format' => $format,
				'file' => $file,
			);
		}
	}
	return($result);
}

function write_log($class, $bucket, $type, $data)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	if(!file_exists($storage_path)) @mkdir($storage_path, 0774, true);
	WriteToFile($storage_path.'/'.$type.'.log', json_encode($data).chr(10));
}

function read_log($class, $bucket, $type, $line_count = 8, $offset = false)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	return(get_json_tail($storage_path.'/'.$type.'.log', $line_count, $offset));
}

function read_log_complete($class, $bucket, $type)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	return(json_lines(read_file($storage_path.'/'.$type.'.log')));
}

function search_log($class, $bucket, $type, $q, $max_lines = false)
{
	$storage_path = escapeshellarg('data/'.$class.'/'.make_bucket_path($bucket).'/').$type.'.log';
	$filter = '';
	if($max_lines > 0)
		$filter .= ' | tail -n '.$max_lines.' ';
	return(json_lines(trim(shell_exec('grep -Fhi '.escapeshellarg($q).' '.($storage_path).$filter))));
}

function line_count($class, $bucket, $type)
{
	$storage_path = 'data/'.$class.'/'.make_bucket_path($bucket);
	return(1*trim(shell_exec('wc -l '.escapeshellarg($storage_path.'/'.$type.'.log'))));
}

function nvSet($key, $data)
{
	$file_name = 'data/'.trim(strtolower($key)).'.json';
	if(!file_exists($file_name))
	{
		if(!file_exists($dn = dirname($file_name))) @mkdir($dn, 0774, true);
	}
	write_file($file_name, json_encode($data));
}

function get_json_tail($from_file, $line_count = 8, $offset = false)
{
	if($offset >  0)
	{
		$lines = trim(shell_exec(
			'tail -n '.escapeshellarg($offset+$line_count).' '.escapeshellarg($from_file).' | head -n '.escapeshellarg($line_count)));
	}
	else
	{
		$lines = trim(shell_exec(
			'tail -n '.escapeshellarg($line_count).' '.escapeshellarg($from_file)));
	}
	return(json_lines($lines));
}

function get_tail($from_file, $line_count = 8, $offset = false)
{
	if($offset >  0)
	{
		$lines = trim(shell_exec(
			'tail -n '.escapeshellarg($offset+$line_count).' '.escapeshellarg($from_file).' | head -n '.escapeshellarg($line_count)));
	}
	else
	{
		$lines = trim(shell_exec(
			'tail -n '.escapeshellarg($line_count).' '.escapeshellarg($from_file)));
	}
	return(explode(chr(10), $lines));
}

function json_lines($lines)
{
	if($lines == '')
	{
		return(array());
	}
	else
	{
		$result = array();
		foreach(explode(chr(10), $lines) as $line)
			$result[] = json_decode($line, true);
		return($result);
	}
}
