<?php

class Record
{

	static $error = false;

	function __construct($type_name)
	{
		$type_name = alnum($type_name);
		$this->type = alnum($type_name);
	}

	static function create_type($type_name)
	{
		$type_name = alnum($type_name);
		if(DB::table_exists($type_name))
		{
			self::$error = 'type already exists ';
			return(false);
		}
		$queries[] = 'CREATE TABLE `'.$type_name.'` (
			`'.$type_name.'_key` bigint(20) NOT NULL,
			`'.$type_name.'_created` double NOT NULL DEFAULT 0,
			`'.$type_name.'_updated` double NOT NULL DEFAULT 0,
			`'.$type_name.'_state` varchar(16) NOT NULL DEFAULT "new",
			`'.$type_name.'_data` longtext NOT NULL DEFAULT "",
			`'.$type_name.'_title` varchar(32) NOT NULL DEFAULT ""
			) ENGINE=Aria DEFAULT CHARSET=utf8mb4';
		$queries[] = 'ALTER TABLE `'.$type_name.'`
			ADD PRIMARY KEY (`'.$type_name.'_key`),
			ADD KEY `'.$type_name.'_created` (`'.$type_name.'_created`),
			ADD KEY `'.$type_name.'_updated` (`'.$type_name.'_updated`),
			ADD KEY `'.$type_name.'_state` (`'.$type_name.'_state`)';
		$queries[] = 'ALTER TABLE `'.$type_name.'`
			MODIFY `'.$type_name.'_key` bigint(20) NOT NULL AUTO_INCREMENT';
		foreach($queries as $q)
		{
			DB::query($q);
			if(DB::$lastError)
			{
				self::$error = DB::$lastError;
				return(false);
			}
		}
		return(true);
	}

	static function delete_type($type_name)
	{
		DB::query('DROP TABLE `'.alnum($type_name).'`');
	}

	static function get_types()
	{
		return(DB::get_tables());
	}

}
