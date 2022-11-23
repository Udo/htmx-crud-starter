<?php

class DB
{

	static $dataCache = array();
	static $link = false;
	static $lastQuery = '';
	static $lastError = false;
	static $keyDef = array(

		);
	static $affectedRows = 0;
	static $writeOps = 0;
	static $readOps = 0;
	static $track_changes_in_tables = array();

	static function is_connected()
	{
		return(is_resource(self::$link));
	}

	static function connect()
	{
		if(self::$link) return;
		if(!cfg('db/user')) die('database not configured');
		Profiler::Log('DB::Connect() start');
		self::$link = mysqli_connect(cfg('db/host'), cfg('db/user'), cfg('db/password'), cfg('db/database'), ini_get("mysqli.default_port"), cfg('db/socket')) or
			critical('The database connection to server '.cfg('db/user').'@'.cfg('db/host').
				' could not be established (code: '.@mysqli_connect_errno(self::$link).': '.@mysqli_connect_error(self::$link).')');
		mysqli_set_charset(self::$link, 'utf8mb4');
		self::$track_changes_in_tables = cfg('track-changes');
		Profiler::Log('DB::Connect() end');
	}

	static function audit_log($table, $key, $data, $reason = '')
	{
		if(array_search($table, self::$track_changes_in_tables))
		{
			Log::AuditEvent('db-update', $table.':'.$key.' '.$reason, $data);
			$audit = array(
				'h_time' => time(),
				'h_user' => 0+User::$uid,
				'h_dstable' => $table,
				'h_dskey' => 0+$key,
				'h_action' => 'U',
				'h_gzdata' => gzcompress(json_encode($data)),
				'h_reason' => $reason,
			);
			DB::Commit('history', $audit);
		}
	}

	static function update($table, $searchCriteria, $updateFields)
	{
		self::$writeOps++;
		DB::Query('UPDATE #'.DB::Safe($table).'
			SET '.DB::_make_set_list($updateFields).'
			WHERE '.DB::_make_set_list($searchCriteria, ' AND '));
	}

	# get a list of datasets matching the $query
	static function get($query, $parameters = null)
	{
		self::$readOps++;
		$result = array();

		$query = self::_parse_query_params($query, $parameters);

		try {
			$lines = mysqli_query(self::$link, $query) or die(mysqli_error(self::$link).' {query: '.$query.' }');
		} catch(Exception $ee) {
			die(mysqli_error(self::$link).' {query: '.$query.' }');
		}

		while ($line = mysqli_fetch_array($lines, MYSQLI_ASSOC))
		{
			if (isset($keyByField))
				$result[$line[$keyByField]] = $line;
			else
				$result[] = $line;
		}
		mysqli_free_result($lines);

		Profiler::Log('DB::Get('.substr($query, 0, 40).'...)');
		return $result;
	}

	# gets a list of keys for the table
	static function keys($otablename)
	{
		$tablename = self::prefixize_tablename($otablename);

		if(isset(self::$keyDef[$otablename]))
			return(self::$keyDef[$otablename]);

		self::$readOps++;
		$result = array();
		$sql = 'SHOW KEYS FROM `'.$tablename.'`';
		$res = mysqli_query(self::$link, $sql) or critical('Cannot get keys // '.mysqli_error(self::$link));

		while ($row = @mysqli_fetch_assoc($res))
		{
			if ($row['Key_name']=='PRIMARY')
				array_push($result, $row['Column_name']);
		}

		Profiler::Log('DB::Keys('.$tablename.') REBUILD KEY CACHE');
		self::$keyDef[$otablename] = $result;
		return($result);
	}

	static function table_exists($table)
	{
		$count = self::get_ds_with_query("SELECT COUNT(TABLE_NAME) as cnt
			FROM
			   information_schema.TABLES
			WHERE
			   TABLE_SCHEMA LIKE '".cfg('db/database')."' AND
				TABLE_NAME = '".$table."'");
		return($count['cnt'] > 0);
	}

	# get column info for $table
	static function columns($table)
	{
		self::$readOps++;
		$result = array();
		foreach(self::Get('SHOW FULL COLUMNS FROM #'.$table.'') as $fld)
		{
			$ds = array();
			foreach($fld as $k => $v)
			{
				$k = strtolower($k);
				if($k == 'type')
				{
					$s = $v;
					$ds['type'] = nibble('(', $s);
					if($s != '')
						$ds['length'] = nibble(')', $s);
				}
				else if($v)
					$ds[$k] = $v;
			}
			$result[$ds['field']] = $ds;
		}
		return($result);
	}

	# updates/creates the $dataset in the $tablename
	static function insert($otablename, $dataset)
	{
		self::$writeOps++;
		$tablename = self::prefixize_tablename($otablename);
		$cache_entry = $tablename.':'.$keyname.':'.$keyvalue;

		$query='INSERT INTO '.$tablename.' ('.DB::_make_names_list($dataset).
				') VALUES('.DB::_make_values_list($dataset).')';

		mysqli_query(self::$link, $query) or critical(mysqli_error(self::$link).'{ '.$query.' }');
		self::$affectedRows += mysqli_affected_rows(self::$link);
		return(mysqli_insert_id(self::$link));
	}

	# updates/creates the $dataset in the $tablename
	static function commit($otablename, &$dataset)
	{
		self::$writeOps++;
		$tablename = self::prefixize_tablename($otablename);
		$keynames = self::Keys($tablename);
		$keyname = $keynames[0];
		$keyvalue = $dataset[$keyname];
		Profiler::Log('DB::Commit('.$tablename.', '.$dataset[$keyname].') start');

		$cache_entry = $tablename.':'.$keyname.':'.$keyvalue;
		$oldData = self::$dataCache[$cache_entry];
		unset(self::$dataCache[$cache_entry]);

		$query='REPLACE INTO '.$tablename.' ('.DB::_make_names_list($dataset).
				') VALUES('.DB::_make_values_list($dataset).');';

		# keeping this around just in case, but performance seems the same:
		# $query='INSERT INTO '.$tablename.' ('.DB::_make_names_list($dataset).
		#		') VALUES('.DB::_make_values_list($dataset).')
		#		ON DUPLICATE KEY UPDATE '.DB::_make_set_list($dataset).';';

		mysqli_query(self::$link, $query) or critical(mysqli_error(self::$link).' { '.$query.' }');
		$dataset[$keyname] = first($dataset[$keyname], mysqli_insert_id(self::$link));
		self::$dataCache[$cache_entry] = $dataset;

		Profiler::Log('DB::Commit('.$tablename.', '.$dataset[$keyname].') done');
		return($dataset[$keyname]);
	}

	static function get_ds_match($table, $matchOptions, $fillIfEmpty = true)
	{
		self::$writeOps++;
		$where = array('1');
		foreach($matchOptions as $k => $v)
			$where[] = '('.$k.'="'.DB::Safe($v).'")';
		$iwhere = implode(' AND ', $where);
		$query = 'SELECT * FROM '.self::prefixize_tablename($table).
			' WHERE '.$iwhere;
		$resultDS = self::get_ds_with_query($query);
		if ($fillIfEmpty && sizeof($resultDS) == 0)
			foreach($matchOptions as $k => $v)
				$resultDS[$k] = $v;
		Profiler::Log('DB::get_ds_match('.$table.') done');
		return($resultDS);
	}

	# from table $tablename, get dataset with key $keyvalue
	static function get_ds($tablename, $keyvalue, $keyname = '', $options = array())
	{
		if($keyvalue == '0') return(array());
		$fields = @$options['fields'];
		$fields = first($fields, '*');
		if (!self::$link) return(array());

		self::prefixize_tablename($tablename);
		if ($keyname == '')
		{
			$keynames = self::Keys($tablename);
			$keyname = $keynames[0];
		}

		$cache_entry = $tablename.':'.$keyname.':'.$keyvalue;
		if(isset(self::$dataCache[$cache_entry])) return(self::$dataCache[$cache_entry]);

		$query = 'SELECT '.$fields.' FROM '.$tablename.' '.$options['join'].' WHERE '.$keyname.'="'.DB::Safe($keyvalue).'";';
		$queryResult = mysqli_query(self::$link, $query) or critical(mysqli_error(self::$link).' { Query: "'.$query.'" }');

		if ($line = @mysqli_fetch_array($queryResult, MYSQLI_ASSOC))
		{
			mysqli_free_result($queryResult);
			self::$dataCache[$cache_entry] = $line;
			Profiler::Log('DB::get_ds('.$tablename.', '.$keyvalue.')');
			return($line);
		}
		else
			$result = array();

		Profiler::Log('DB::get_ds('.$tablename.', '.$keyvalue.') #fail');
		self::$readOps++;
		return($result);
	}

	static function remove_ds($tablename, $keyvalue, $keyname = null)
	{
		self::prefixize_tablename($tablename);
		if ($keyname == null)
		{
			$keynames = self::Keys($tablename);
			$keyname = $keynames[0];
		}
		$res = (mysqli_query(self::$link, 'DELETE FROM '.$tablename.' WHERE '.$keyname.'="'.
			DB::Safe($keyvalue).'";')
				or critical(' Cannot remove dataset // '.mysqli_error(self::$link)));
		Profiler::Log('DB::remove_ds('.$tablename.', '.$keyvalue.') done');
		self::$affectedRows += mysqli_affected_rows(self::$link);
		self::$writeOps++;
		return($res);
	}

	static function get_tables()
	{
		$result = array();
		foreach(self::get('SHOW TABLE STATUS') as $ds)
		{
			$result[$ds['Name']] = $ds;
		}
		return($result);
	}

	// retrieve dataset identified by SQL $query
	static function get_ds_with_query($query, $parameters = null)
	{
		$query = self::_parse_query_params($query, $parameters);

		$queryResult = mysqli_query(self::$link, $query);

		if(!$queryResult)
			return(critical('Error getting data // '.mysqli_error(self::$link).'{ '.$query.' }'));

		if ($line = mysqli_fetch_array($queryResult, MYSQLI_ASSOC))
		{
			$result = $line;
			mysqli_free_result($queryResult);
		}
		else
			$result = array();

		Profiler::Log('DB::get_ds_with_query('.$query.')');
		self::$readOps++;
		return($result);
	}

	# execute a simple update $query
	static function query($query, $parameters = null)
	{
		$query = self::_parse_query_params($query, $parameters);
		if (substr($query, -1, 1) == ';')
			$query = substr($query, 0, -1);
		$result = (mysqli_query(self::$link, $query)
			or critical(' Query error // '.mysqli_error(self::$link)));
		Profiler::Log('DB::Query('.$query.') done');
		self::$affectedRows += mysqli_affected_rows(self::$link);
		self::$writeOps++;
		return($result);
	}

	# create a comma-separated list of keys in $dataset
	static function _make_names_list(&$dataset)
	{
		$result = '';
		if (sizeof($dataset) > 0)
			foreach (array_keys($dataset) as $k)
			{
				if ($k!='')
					$result = $result.','.$k;
			}
		return(substr($result, 1));
	}

	# make a name-value list for UPDATE-queries
	static function _make_values_list(&$dataset)
	{
		$result = '';
		if (sizeof($dataset) > 0)
			foreach ($dataset as $k => $v)
			{
				if ($k!='')
					$result = $result.',"'.DB::safe($v).'"';
			}
		return(substr($result, 1));
	}

	static function _make_set_list(&$dataset, $concat = ', ')
	{
		$result = array();
		if (sizeof($dataset) > 0) foreach ($dataset as $k => $v)
		{
			if(substr($k, -1) == '+' || substr($k, -1) == '-')
			{
				$op = substr($k, -1);
				$k = substr($k, 0, -1);
				$result[] = $k.' = '.$k.' '.$op.' "'.DB::safe($v).'"';
			}
			else
			{
				$result[] = $k.' = "'.DB::safe($v).'"';
			}
		}
		return(implode($concat, $result));
	}

	static function _parse_query_params($query, $parameters = null)
	{
		if ($parameters != null)
		{
			$pctr = 0;
			$result = '';
			for($a = 0; $a < strlen($query); $a++)
			{
				$chr = substr($query, $a, 1);
				if ($chr == '?')
				{
					$result .= '"'.DB::Safe($parameters[$pctr]).'"';
					$pctr++;
				}
				else if ($chr == '&')
				{
					$result .= ''.intval($parameters[$pctr]).'';
					$pctr++;
				}
				else if ($chr == ':')
				{
					$paramName = '';
					$a += 1;
					$pFormat = 'string';
					if($query[$a] == ':')
					{
						$pFormat = 'number';
						$a += 1;
					}
					while(!ctype_space($chr = substr($query, $a, 1)) && $a < strlen($query))
					{
						$paramName .= $chr;
						$a += 1;
					}
					if($pFormat == 'number')
						$result .= ' '.($parameters[$paramName]+0).' ';
					else
						$result .= ' "'.DB::Safe($parameters[$paramName]).'" ';
				}
				else
					$result .= $chr;
			}
		}
		else
			$result = $query;
		$q = str_replace('#', cfg('db/prefix'), $result);
		self::$lastQuery = $q;
		return($q);
	}

	static function safe($raw)
	{
		if(!isset(self::$link))
			return(addslashes($raw));
		else
			return(mysqli_real_escape_string(self::$link, $raw));
	}

	static function prefixize_tablename(&$table, $makeSafe = true)
	{
		$prefix = cfg('db/prefix');
		$len = strlen($prefix);
		if (substr($table, 0, $len) != $prefix)
			$table = $prefix.$table;
		if($makeSafe)
			$table = mysqli_real_escape_string(self::$link, $table);
		return($table);
	}


}

DB::connect();

