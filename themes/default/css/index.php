<?
	header('content-type: text/css; charset=utf-8');

	$cssList = explode(chr(10), trim(shell_exec('ls -1 *css')));

	$mList = array();
	$lastModified = time()-60*60*24*30;

	foreach($cssList as $cssFile)
	{
		$modified = filemtime($cssFile);
		$mList[] = $modified;
		if($modified > $lastModified)
			$lastModified = $modified+2;
	}

	$etagFile = md5(implode('/* */', $mList));

	$ifModifiedSince=(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
	$etagHeader=(isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

	header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");
	header("Etag: $etagFile");
	header('Cache-Control: public');

	if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])==$lastModified || $etagHeader == $etagFile)
	{
		 header("HTTP/1.1 304 Not Modified");
		 die();
	}

	print("\n/****** ETAG: ".$etagFile." ******/\n\n");

	foreach($cssList as $fle)
	{
		print("\n/****** ".$fle." ******/\n\n");
		include($fle);
	}
