<?php

class CReturn {
	const FAIL = false;
	const SUCCESS = true;
	public static function error($error_msg){
		return array(self::FAIL, $error_msg);
	}
	public static function success($value){
		return array(self::SUCCESS, $value);
	}
}

class initDb {
	private static $host = "localhost";
	private static $user = "root";
	private static $pass = "mysql";
	private static $db = "db_test1";	
	
	public static function connect(){
		$db_conn = mysql_pconnect(self::$host, self::$user, self::$pass) or die(mysql_error());
		mysql_select_db(self::$db, $db_conn) or die(mysql_error());
		mysql_query("SET NAMES UTF8;");
		return $db_conn;
	}
}

class DbAbstract {
	protected $db_conn;
	protected $table_name;
	public $_data;
	public function __construct($db_conn){
		$this->db_conn = $db_conn;
	}
	protected function _get($sql){
		$this->_data = null;		
		$result = mysql_query($sql);
		if(mysql_errno($this->db_conn) !== 0) 
			return CReturn::error("Mysql error [".mysql_errno($this->db_conn)."] : ".$sql."<br />".mysql_error($this->db_conn));
		while($res = mysql_fetch_assoc($result)) {
			$this->_data[] = $res;
		}
		return CReturn::success(mysql_num_rows($result));
	}
	protected function getTableName(){
		return $this->table_name;
	}
	public function getAll(){
		return $this->_get("SELECT * FROM {$this->getTableName()}");
	}
}

class DbTable1 extends DbAbstract {
	protected $table_name = "table1";
	public function getById($id){
		return $this->_get("SELECT *  FROM {$this->getTableName()} WHERE user_id = {$id}");
	}
}

print "<pre>";
$db_conn = initDb::connect();

// ??? error
$oTb1 = new DbTable1($db_conn);
list($result, $value) = $oTb1->getById("id_is_not_string");
print "<b>Query result</b> = ".var_export($result,true)."<br />";
print "<b>Num rows/Error msg</b> = '".$value."'<br />";
print "<b>data</b> = ".var_export($oTb1->_data,true)."<br />";
print "<br />";

// ??? ?????????????????????????? db
$oTb1 = new DbTable1($db_conn);
list($result, $value) = $oTb1->getById(5);
print "<b>Query result</b> = ".var_export($result,true)."<br />";
print "<b>Num rows/Error msg</b> = ".var_export($value,true)."<br />";
print "<b>data</b> = ".var_export($oTb1->_data,true)."<br />";
print "<br />";

// ??????????????? 1 record
$oTb1 = new DbTable1($db_conn);
list($result, $value) = $oTb1->getById(1);
print "<b>Query result</b> = ".var_export($result,true)."<br />";
print "<b>Num rows/Error msg</b> = ".var_export($value,true)."<br />";
print "<b>data</b> = ".var_export($oTb1->_data,true)."<br />";
print "<br />";

// ??????????????????? records
list($result, $value) = $oTb1->getAll();
print "<b>Query result</b> = ".var_export($result,true)."<br />";
print "<b>Num rows/Error msg</b> = ".var_export($value,true)."<br />";
print "<b>data</b> = ".var_export($oTb1->_data,true)."<br />";
print "<br />";

?>
