<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: ResultSetMetaData.class.php,v 0.2 2004/11/02 16:38:19
	 *	@since 0.2
	 */

	require_once ('Object.class.php');
	
	define ('columnNoNulls', 0);
	define ('columnNullable', 1);
	define ('columnNullableUnknown', 2);

	class mysql_ResultSetMetaData extends Object {
		var $result = null;
		var $metaData = null;

		function mysql_ResultSetMetaData(&$result) {
			$this->result = $result;

			$i = 0;
			while ($i < mysql_num_fields($result)) {
				$fields = mysql_fetch_field($result, $i);

				if (!$fields) {
					return null;
					#$this->throws("DB_mysql->getTableField(): mysql_fetch_field failed", null, EXCEPTION_DIE);
				}

				$meta = array();
				$meta['blob']			= $fields->blob;
				$meta['max_length']		= $fields->max_length;
				$meta['multiple_key']	= $fields->multiple_key;
				$meta['name']			= $fields->name;
				$meta['not_null']		= $fields->not_null;
				$meta['numeric']		= $fields->numeric;
				$meta['primary_key']	= $fields->primary_key;
				$meta['table']			= $fields->table;
				$meta['type']			= $fields->type;
				$meta['unique_key']		= $fields->unique_key;
				$meta['unsigned']		= $fields->unsigned;
				$meta['zerofill']		= $fields->zerofill;
				
				$this->metaData[$i++] = $meta;
			}
			
			if (!is_array($this->metaData) && !is_null($this->metaData[0])) {
				return null;
			}

			return $this->metaData;
		}

		function __destruct() {
			$this->result = null;
			$this->metaData = null;
			$this->null;
		}

		function getCatalogName($column) {}

		function getColumnClassName($column) {}

		/**
		 *	返回当前结果集中字段的个数
		 *	@return integer
		 */
		function getColumnCount() {
			return (null !== $this->metaData) ? sizeof($this->metaData): 0;
		}

		function getColumnDisplaySize($column) {}

		function getColumnLabel($column) {}

		/**
		 *	返回指定字段的名称
		 *	@return String
		 */
		function getColumnName($column) {
			#print '('.$column.')'.$this->metaData[$column]['name'].'<br/>';
			return $this->metaData[$column]['name'];
		}

		/**
		 *	返回指定字段的类型
		 *	@return String
		 */
		function getColumnType($column) {
			return $this->metaData[$column]['type'];
		}

		function getColumnTypeName($column) {}

		function getMetadata() {
			return $this->metaData;
		}

		function getPrecision($column) {}

		function getScale($column) {}

		function getSchemaName($column) {}

		function getTableName($column) {
			return $this->metaData[$column]['table'];
		}

		/**
		 *	返回该字段是否为auto_increment
		 *	@return boolean
		 */
		function isAutoIncrement($column) {
			return ('int' == $this->metaData[$column]['type'] &&
					(int)1 == (int)$this->metaData[$column]['primary_key'])
					? true: false;
		}

		function isCaseSensitive($column) {
			$type = strtoupper($this->getColumnType($column));

			switch ($type) {
				case 'BIT' :
				case 'TINYINT' :
				case 'SMALLINT' :
				case 'INTEGER' :
				case 'BIGINT' :
				case 'FLOAT' :
				case 'REAL' :
				case 'DOUBLE' :
				case 'DATE' :
				case 'TIME' :
				case 'TIMESTAMP' :
					return false;

				case 'CHAR' :
				case 'VARCHAR' :
				case 'LONGVARCHAR' :

				default:
					return true;
			}
		}

		function getCollation($field) {
			
		}

		function isCurrency($column) {}

		function isDefinitelyWritable($column) {}

		function isNullable($column) {
			return ((int)1 == (int)$this->metaData[$column]['not_null'])
					? true: false;
		}

		function isReadOnly($column) {}

		function isSearchable($column) {}

		function isSigned($column) {}

		function isWritable($column) {}
	}
?>