<?php
/*
This class was developed by Dhruv Jain aka Hack Archives
You may use this freely until this notice remains intact
Author Website: http://hackarchives.org
*/


//Initialization of a class
class db
{

//Function for mysql_query
	function exec($arg1)
	{
		return mysql_query($arg1);
	}
	
//MySQL Select Function
//Sample Usage: $this->select("*","users","username='dhruv'","LIMIT 10");
//* is the fields to select
//users is the table to select from
// username='dhruv' is the condition for selection
//LIMIT 10 is for limiting results to 10

	function select($fields="*", $table, $conditions="", $options=array())
	{
		$do = "SELECT ".$fields." FROM ". $table;
		if($conditions != "")
		{
			$do .= " WHERE ".$conditions;
		}
		if(isset($options['order_by']))
		{
			$do .= " ORDER BY ".$options['order_by'];
			if(isset($options['order_dir']))
			{
				$do .= " ".my_strtoupper($options['order_dir']);
			}
		}
		if(isset($options['limit_start']) && isset($options['limit']))
		{
			$do .= " LIMIT ".$options['limit_start'].", ".$options['limit'];
		}
		elseif(isset($options['limit']))
		{
			$do .= " LIMIT ".$options['limit'];
		}
		return $this->exec($do);
	}

//Function for mysql_num_rows
//Gets number of rows matching a mysql query
	function num_rows($arg1)
	{
		return mysql_num_rows($arg1);		 	
	}

//Function for fetching array of result i.e. mysql_fetch_array	
	function fetch_array($arg1)
	{
		return mysql_fetch_array($arg1);
	}
	
//Function for inserting data into a table
	function insert($arg1,$arg2,$arg3)
	{
		return $this->exec("INSERT INTO ".$arg1."(".$arg2.") VALUES (".$arg3.")");
	}

//Function to select database
	function select_db($arg1)
	{
		return mysql_select_db($arg1);
	}               

//Closing a MySQL Connection	
	function close()
	{
	mysql_close();
	}
	
//Show fields from specific table	
	function show_fields_from($table)
	{
		$do = "SHOW FIELDS FROM ". $table;
		$query = $this->exec($do);
		while($field = $this->fetch_array($query))
		{
			$field_info[] = $field;
		}
		return $field_info; 
	}

//Escaping the data to prevent MySQL Injection

		function escape($string)
	{
		if(function_exists("mysql_real_escape_string"))
		{
			$string = mysql_real_escape_string($string);
		}
		else
		{
			$string = addslashes($string);
		}
		return $string;
	}

//Deleting a row/rows from a table
	function delete($table, $where="", $limit="")
	{
		$do = "";
		if(!empty($where))
		{
			$do .= " WHERE $where";
		}
		
		if(!empty($limit))
		{
			$do .= " LIMIT $limit";
		}
		
		return $this->exec("
			DELETE 
			FROM $table 
			$do
		");
	}
	
//Updating a row	
	function update($table, $array, $where="", $limit="", $no_quote=false)
	{
		if(!is_array($array))
		{
			return false;
		}
		
		$comma = "";
		$do = "";
		$quote = "'";
		
		if($no_quote == true)
		{
			$quote = "";
		}
		
		foreach($array as $field => $value)
		{
			$do .= $comma."`".$field."`={$quote}{$value}{$quote}";
			$comma = ', ';
		}
		
		if(!empty($where))
		{
			$do .= " WHERE $where";
		}
		
		if(!empty($limit))
		{
			$do .= " LIMIT $limit";
		}
		
		return $this->exec("
			UPDATE $table 
			SET $do
		");
	}
	
//Escape a string used within a like command.	
	function escape_string_like($string)
	{
		return $this->escape(str_replace(array('%', '_') , array('\\%' , '\\_') , $string));
	}
	
//OPTIMIZING Table	
	function optimize_table($table)
	{
		$do = "OPTIMIZE TABLE " . $table;
		$this->exec($do);
	}

//Analysing Table	
	function analyze_table($table)
	{
		$do = "ANALYZE TABLE ". $table;
		$this->exec($do);
	}

//Show the "create table" command for a specific table.	
	function show_create_table($table)
	{
		$do1 = "SHOW CREATE TABLE ".$table;
		$do = $this->exec($do1);
		$structure = $this->fetch_array($do);
		return $structure['Create Table'];
	}
	
//Dropping index	
	function drop_index($table, $name)
	{
		$this->do("
			ALTER TABLE $table 
			DROP INDEX $name
		");
	}
	
//Drop a column	
	function drop_column($table, $column)
	{
		$do = "ALTER TABLE ".$table." DROP ".$column;
		return $this->exec($do);
	}
	
//Adding a column	
	function add_column($table, $column, $definition)
	{
		$do = "ALTER TABLE " . $table . " ADD ".$column." ".$definition;
		return $this->exec($do);
	}
	
//Modifies a column	
	function modify_column($table, $column, $new_definition)
	{
		$do = "ALTER TABLE ".$table." MODIFY ".$column." " . $new_defination;
		return $this->exec($do
		);
	}
	
//Rename a Column from a table	
	function rename_column($table, $old_column, $new_column, $new_definition)
	{
		$do = "ALTER TABLE ".$table." CHANGE ".$old_column." ".$new_column." ".$new_definition;
		return $this->exec($do);
	}

//Get execution time	
	function get_execution_time()
	{
		static $time_start;
		$time = microtime(true);
		if(!$time_start)
		{
			$time_start = $time;
			return;
		}
		else
		{
			$total = $time-$time_start;
			$time_start = 0;
			if($total < 0) $total = 0;
			return $total;
		}
	}
	function fetch_charset_collation($charset)
	{
		$collations = array(
			'big5' => 'big5_chinese_ci',
			'dec8' => 'dec8_swedish_ci',
			'cp850' => 'cp850_general_ci',
			'hp8' => 'hp8_english_ci',
			'koi8r' => 'koi8r_general_ci',
			'latin1' => 'latin1_swedish_ci',
			'latin2' => 'latin2_general_ci',
			'swe7' => 'swe7_swedish_ci',
			'ascii' => 'ascii_general_ci',
			'ujis' => 'ujis_japanese_ci',
			'sjis' => 'sjis_japanese_ci',
			'hebrew' => 'hebrew_general_ci',
			'tis620' => 'tis620_thai_ci',
			'euckr' => 'euckr_korean_ci',
			'koi8u' => 'koi8u_general_ci',
			'gb2312' => 'gb2312_chinese_ci',
			'greek' => 'greek_general_ci',
			'cp1250' => 'cp1250_general_ci',
			'gbk' => 'gbk_chinese_ci',
			'latin5' => 'latin5_turkish_ci',
			'armscii8' => 'armscii8_general_ci',
			'utf8' => 'utf8_general_ci',
			'ucs2' => 'ucs2_general_ci',
			'cp866' => 'cp866_general_ci',
			'keybcs2' => 'keybcs2_general_ci',
			'macce' => 'macce_general_ci',
			'macroman' => 'macroman_general_ci',
			'cp852' => 'cp852_general_ci',
			'latin7' => 'latin7_general_ci',
			'cp1251' => 'cp1251_general_ci',
			'cp1256' => 'cp1256_general_ci',
			'cp1257' => 'cp1257_general_ci',
			'binary' => 'binary',
			'geostd8' => 'geostd8_general_ci',
			'cp932' => 'cp932_japanese_ci',
			'eucjpms' => 'eucjpms_japanese_ci',
		);
		if($collations[$charset])
		{
			return $collations[$charset];
		}
		return false;
	}

//This function drops a MySQL table	
	function drop_table($arg1)
	{
		$do = "DROP TABLE ".$arg1;
		$result = $this->exec($do);
		return $result;
	}

//Information regarding most recent query	
	function info()
	{
	return mysql_info();
	}
	
//Information regarding number of affected rows in most recent MySQL Query
	function affect_rows()
	{
	return mysql_affected_rows();
	}

//Returns the name of the character set
//$arg1 should be the mysql connection ..like $arg1 = mysql_connect('localhost','user','pass');
	function client_encod($arg1)
	{
	return mysql_client_encoding($arg1);
	}
	
//Creating a database
//This won't work for Cpanle hosting.You will need to do it via cpanel only
	function create_db($arg1)
	{
		$do = "CREATE DATABASE ".$arg1;
		return $this->exec($do);
	}

//Getting name of current Database	
	function mysql_current_db() {
    return $this->exec("SELECT DATABASE()");
	
//Dropping a Database	
//This won't work for Cpanle hosting.You will need to do it via cpanel only
	function drop_db($arg1)
	{
	$do = "DROP DATABASE ".$arg1;
	return $this->exec($do);
	}
	
//Returns the length of the requested field
//Note that for some reason the length of fields is 3 times the actual value if you are using UTF8 encoding.. So a varchar(10) field returns 30 here. This renders this function almost useless.
	function field_len($arg1)
	{
		return mysql_field_len($arg1);
	}

//Shows MySQL client info
	function clien_info()
	{
		return mysql_get_client_info();
	}

//Shows MySQL Host information	
	function host_info()
	{
		return mysql_get_host_info();
	}
	
//Shows MySQL Server Information
	function server_info()
	{
		return mysql_get_server_info(): 
	}
	
//Retrieves number of fields in a result
	function num_fields($arg1)
	{
		return mysql_num_fields($arg1);
	}
}
//Class End

/*
This class was developed by Dhruv Jain aka Hack Archives
You may use this freely until this notice remains intact
Author Website: http://hackarchives.org
*/
?>