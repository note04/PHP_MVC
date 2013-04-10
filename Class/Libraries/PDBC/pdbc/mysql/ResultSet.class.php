<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: ResultSet.class.php,v 0.1 2004/11/02 19:39:38
	 */

	//require_once ('pdbc/interface/ResultSet.php');
	require_once ('pdbc/mysql/ResultSetMetaData.class.php');

	class mysql_ResultSet extends Object {
		var $pstmt  = null;
		var $result = null;
		var $isAvailable = false;

		/* 游标 */
		var $cursor = 0;

		/* 结果集大小 */
		var $size = 0;

		/* 结果集元数据 */
		var $metaData = array();

		/* 结果集中有效字段的名称集 */
		var $finfo = array();

		var $absop = false;
		var $isResult = false;
		var $isPstmt  = false;

		/* 标识当前ResultSet对象是否可用 */
		var $isAvalable = false;

		/* 标识当前ResultSet对象是否已关闭 */
		var $isClosed = true;

		/* 当前ResultSet对象的元数据 */
		var $meta = null;

		/* 当前row */
		var $currentRow = null;

		/**
		 *	构造函数
		 *	@param resource
		 */
		function &mysql_ResultSet(&$resource) {
			if (is_resource($resource)) {
				$this->result = $resource;
				$this->isResult = true;
			} else {
				trigger_error('<br/>resource is not available', E_USER_ERROR);
			}
			
			$this->isClosed = false;
			$this->isAvalable = true;
			$this->size = $this->getRow();
		}

		/**
		 *	析构函数
		 */
		function __destruct() {
			$this->cursor = 0;
			$this->size = 0;
			$isClosed = true;
			$rs = null;
			$this->metaData = null;
			$this->result = null;
			$this->currentRow = null;
		}

		/**
		 *	将当前游标移动至指定的位置
		 *	@param $offset
		 */
		function absolute($offset) {
			$this->checkClosed();

			if (0 >= $offset || $offset > $this->size)
				return false;

			mysql_data_seek($this->result, ($offset-1));
			$row = mysql_fetch_object($this->result);

			if (!is_object($row) || !$row) {
				return false;
			}
			
			$this->cursor = $offset;
			$this->absop = true;
			$this->currentRow = $row;
			return true;
		}

		/**
		 *	检查当前ResultSet对象是否可用
		 *	return void
		 */
		function checkClosed() {
			if ($this->isClosed) {
				require_once ('util/StringBuffer.class.php');
				$msgbuf = new StringBuffer('No operations allowed after resultset closed.');

				$this->throws($msgbuf->toString(), null, EXCEPTION_DIE);
			}
		}

		/**
		 *	关闭当前ResultSet对象，并释放当前结果集
		 *	return void
		 */
		function close() {
			$this->realClose(true);
		}

		function getFetchSize() {
			//not implement
		}

		/**
		 *	获取当前记录指定列的布尔值
		 *	@param $column
		 *	@return boolean
		 *	@access public
		 */
		function getBoolean($column) {
			return $this->getInternalValue($column, 'boolean');
		}

		/**
		 *	获取当前记录指定列的Blob值
		 *	@param $column
		 */
		function getBlob($column) {
			return $this->getInternalValue($column, 'blob');
		}

		/**
		 *	检查给定的参数(列)的值是否合法
		 *	return void
		 */
		function checkInputColumn($column) {
			if ('' == $column || null === $column)
				trigger_error('<br/>mysql_ResultSet->checkInputColumn(): param \'column\' is null', E_USER_ERROR);
		}

		/**
		 *	查看当前ResultSet对象是否可用
		 *	return void
		 */
		function checkResource() {
			if (!$this->isAvalable)
				trigger_error('<br/>mysql_ResultSet->getRow(): resource is invalidate', E_USER_ERROR);
		}

		/**
		 *	将光标移动至当前ResultSet对象的第一条记录
		 *	return boolean
		 */
		function first() {
			return $this->absolute(1);
		}

		/**
		 *	返回指定DateTime类型字段的值
		 *	@param $column
		 *	@return Datetime
		 *	@access public
		 */
		function getDate($column) {
			return $this->getInternalValue($column, 'datetime');
		}

		/**
		 *	返回指定Double类型字段的值
		 *	@param String $column
		 *	@return Double
		 *	@access public
		 */
		function getDouble($column) {
			return $this->getInternalValue($column, 'double');
		}

		/**
		 *	返回指定float类型字段的值
		 *	@param string $column
		 *	@return float
		 *	@access public
		 */
		function getFloat($column) {
			return $this->getInternalValue($column, 'float');
		}

		/**
		 *	获取当前查询结果集的元数据
		 *	@return mysql_ResultSetMetaData
		 *	@access private
		 */
		function getMetaData() {
			if (!is_resource($this->result))
				return null;
				#trigger_error('<br/>Result is not an mysql_result', E_USER_ERROR);

			return new mysql_ResultSetMetaData($this->result);
		}

		/**
		 *	返回指定object类型字段的值
		 *	@param string $column
		 *	@return object
		 *	@access public
		 */
		function getObject($column) {
			return $this->getInternalValue($column, 'object');
		}

		/**
		 *	获取当前结果集记录数目
		 *	@return int
		 *	@access private
		 */
		function getResultRow() {
			$this->checkResource();

			return mysql_num_rows($this->result);
		}
		
		/**
		 *	返回当前结果集记录数目
		 *	@return int
		 *	@access public
		 */
		function getRow() {
			if ($this->isResult)
				return $this->getResultRow();
			else
				return (int)0;
		}

		/**
		 *	检查字段类型是否与基类相符合
		 *	@param	String	$column		字段名
		 *	@param	String	$baseType	基类
		 *	@return void
		 *	@access private
		 */
		function checkColumnType($column, $baseType) {
			$this->metaData = $this->getMetaData();

			$found = false;
			for ($i = 0; $i < $this->metaData->getColumnCount(); $i++) {
				$name = $this->metaData->getColumnName($i);
				if ($column == $name) {
					$type = $this->metaData->getColumnType($i);
					$found = true;
					break;
				}
			}

			if (!$found)
				$this->throws('mysql_ResultSet->getString(): column('.$column.') not in resultset', null, EXCEPTION_DIE);

			if ($baseType != $type)
				$this->throws('mysql_ResultSet->getString(): type of column ['.$column.'] is not a '.$baseType, null, EXCEPTION_DIE);
		}

		/**
		 *	获取元数据的结构
		 *	@return String[]
		 *	@access public
		 */
		function getMetaStructure() {
			if (!is_object($this->metaData) && !is_a($this->metaData, 'mysql_ResultSetMetaData'))
				$this->metaData = $this->getMetaData();
			return $this->metaData->getMetadata();
		}

		/**
		 *	返回指定string类型字段的值
		 *	@param $column
		 *	@return String
		 *	@access public
		 */
		function getString($column) {
			return $this->getInternalValue($column, 'string');
		}

		/**
		 *	返回当前查询涉及的字段数
		 *	@return int
		 *	@access private
		 */
		function getTableFiledNums() {
			return mysql_num_fields($this->result);
		}

		/**
		 *	返回当前查询结果的字段信息
		 *	@param	int	$offset
		 *	@return Object
		 *	@access private
		 */
		function fetchField($offset=0) {
			return mysql_fetch_field($this->result, $offset);
		}

		/**
		 *	获取当前查询结果集中的涉及的字段名信息
		 *	@return string[]
		 *	@access public
		 */
		function getFetchFieldInfo() {
			if (!is_resource($this->result))
				return false;
				#trigger_error('<br/>Result is not an mysql_result', E_USER_ERROR);

			$i = 0;
			while ($i < $this->getTableFiledNums()) {
				$fields = $this->fetchField($i);

				if (!$fields) {
					return false;
					#trigger_error("DB_mysql->getTableField(): mysql_fetch_field failed", E_USER_ERROR);
				}

				$finfo[$i++] = $fields->name;
			}
			
			if (!is_array($finfo) && !is_null($finfo[0])) {
				return false;
			}

			return $finfo;
		}

		/**
		 *	打印当前查询结果集中的涉及的字段名信息
		 *	@return void
		 *	@access public
		 */
		function pGetFetchFieldInfo() {
			print_r($this->getFetchFieldInfo());
		}

		/**
		 *	返回指定time类型字段的值
		 *	@param string	$column
		 *	@return String
		 *	@access public
		 */
		function getTime($column) {
			//not implement yet
		}

		/**
		 *	获取当前对象中查询返回的错误信息
		 *	@access public
		 */
		function getWarnings() {
			//not implement yet
		}

		/**
		 *	返回指定int类型字段的值
		 *	@param string	$column
		 *	@return String
		 *	@access public
		 */
		function getInt($column) {
			return $this->getInternalValue($column, 'int');
		}

		/**
		 *	返回指定类型字段的值
		 *	@param string	$column
		 *	@param string	$baseType
		 *	@return String
		 *	@access private
		 */
		function getInternalValue($column, $baseType) {
			$this->checkInputColumn($column);

			$this->checkColumnType($column, $baseType);

			if ($this->isResult) {
				return $this->currentRow->$column;
			} else if ($this->isPstmt) {
				return $this->pstmt->$column;
			}
		}

		/**
		 *	返回当前对象是否可用的标识
		 *	@return String
		 *	@access public
		 */
		function isAvailable() {
			return $this->isAvailable;
		}

		/**
		 *	返回当前结果集是否已经关闭
		 *	@return boolean
		 ×	@access public
		 */
		function isClosed() {
			return $this->isClosed;
		}

		/**
		 *	返回当前记录是否是第一条
		 *	@return boolean
		 */
		function isFirst() {
			return ((int)1 == $this->cursor);
		}

		/**
		 *	返回当前记录是否是最后一条
		 *	@return boolean
		 */
		function isLast() {
			return ($this->size == $this->cursor);
		}

		/**
		 *	将游标指向结果集中的下一条记录
		 *	@return boolean
		 */
		function next() {
			$this->checkResource();

			if (0 == $this->size)
				return false;
			if ($this->size < ($this->cursor + 1))
				return false;

			if ($this->isResult) {
				$this->currentRow = mysql_fetch_object($this->result);

				if (!$this->currentRow)
					return false;

				if (!is_object($this->currentRow)) {
					trigger_error('<br/>mysql_ResultSet->next(): Result of fetch_object method is not a object', E_USER_ERROR);
				}

				$this->cursor++;
				$this->absop = false;

				if (!$this->metaData)
					$this->getMetaData();

				return true;
			} else {
				trigger_error('<br/>internal error', E_USER_ERROR);
			}
		}

		/**
		 *	将游标移动至当前ResultSet对象的最后一条记录
		 *	return boolean
		 */
		function last() {
			$this->cursor = $this->size;
			return $this->absolute(($this->size));
		}

		/**
		 *	将游标在当前ResultSet对象前移一条记录
		 *	return boolean
		 */
		function previous() {
			if (0 >= $this->cursor) 
				return false;
			
			$cursor = $this->cursor - 1;
			if (0 > $cursor)
				return false;

			return $this->absolute($cursor);
		}

		/**
		 *	关闭当前对象，并释放资源
		 *	@param boolean $calledExplicitly
		 *	@return void
		 *	@access protected
		 */
		function realClose($calledExplicitly=false) {
			if ($this->isResult)
				mysql_free_result($this->result);

			$this->__destruct();
		}

		/**
		 *	Document me
		 */
		function refreshRow() {
			//not implement yet
		}

		/**
		 *	@param $rows
		 */
		function setFetchSize($rows) {
			//not implement yet
		}

		/**
		 *	返回当前的游标
		 *	@return integer	$this->cursor
		 */
		function getCursor() {
			return $this->cursor;
		}

		/**
		 *	打印当前的游标
		 *	@return void
		 */
		function pGetCursor() {
			printf("Current Cursor: (%s)", $this->getCursor());
		}
	}
?>