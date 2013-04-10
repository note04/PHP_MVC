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

		/* �α� */
		var $cursor = 0;

		/* �������С */
		var $size = 0;

		/* �����Ԫ���� */
		var $metaData = array();

		/* ���������Ч�ֶε����Ƽ� */
		var $finfo = array();

		var $absop = false;
		var $isResult = false;
		var $isPstmt  = false;

		/* ��ʶ��ǰResultSet�����Ƿ���� */
		var $isAvalable = false;

		/* ��ʶ��ǰResultSet�����Ƿ��ѹر� */
		var $isClosed = true;

		/* ��ǰResultSet�����Ԫ���� */
		var $meta = null;

		/* ��ǰrow */
		var $currentRow = null;

		/**
		 *	���캯��
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
		 *	��������
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
		 *	����ǰ�α��ƶ���ָ����λ��
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
		 *	��鵱ǰResultSet�����Ƿ����
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
		 *	�رյ�ǰResultSet���󣬲��ͷŵ�ǰ�����
		 *	return void
		 */
		function close() {
			$this->realClose(true);
		}

		function getFetchSize() {
			//not implement
		}

		/**
		 *	��ȡ��ǰ��¼ָ���еĲ���ֵ
		 *	@param $column
		 *	@return boolean
		 *	@access public
		 */
		function getBoolean($column) {
			return $this->getInternalValue($column, 'boolean');
		}

		/**
		 *	��ȡ��ǰ��¼ָ���е�Blobֵ
		 *	@param $column
		 */
		function getBlob($column) {
			return $this->getInternalValue($column, 'blob');
		}

		/**
		 *	�������Ĳ���(��)��ֵ�Ƿ�Ϸ�
		 *	return void
		 */
		function checkInputColumn($column) {
			if ('' == $column || null === $column)
				trigger_error('<br/>mysql_ResultSet->checkInputColumn(): param \'column\' is null', E_USER_ERROR);
		}

		/**
		 *	�鿴��ǰResultSet�����Ƿ����
		 *	return void
		 */
		function checkResource() {
			if (!$this->isAvalable)
				trigger_error('<br/>mysql_ResultSet->getRow(): resource is invalidate', E_USER_ERROR);
		}

		/**
		 *	������ƶ�����ǰResultSet����ĵ�һ����¼
		 *	return boolean
		 */
		function first() {
			return $this->absolute(1);
		}

		/**
		 *	����ָ��DateTime�����ֶε�ֵ
		 *	@param $column
		 *	@return Datetime
		 *	@access public
		 */
		function getDate($column) {
			return $this->getInternalValue($column, 'datetime');
		}

		/**
		 *	����ָ��Double�����ֶε�ֵ
		 *	@param String $column
		 *	@return Double
		 *	@access public
		 */
		function getDouble($column) {
			return $this->getInternalValue($column, 'double');
		}

		/**
		 *	����ָ��float�����ֶε�ֵ
		 *	@param string $column
		 *	@return float
		 *	@access public
		 */
		function getFloat($column) {
			return $this->getInternalValue($column, 'float');
		}

		/**
		 *	��ȡ��ǰ��ѯ�������Ԫ����
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
		 *	����ָ��object�����ֶε�ֵ
		 *	@param string $column
		 *	@return object
		 *	@access public
		 */
		function getObject($column) {
			return $this->getInternalValue($column, 'object');
		}

		/**
		 *	��ȡ��ǰ�������¼��Ŀ
		 *	@return int
		 *	@access private
		 */
		function getResultRow() {
			$this->checkResource();

			return mysql_num_rows($this->result);
		}
		
		/**
		 *	���ص�ǰ�������¼��Ŀ
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
		 *	����ֶ������Ƿ�����������
		 *	@param	String	$column		�ֶ���
		 *	@param	String	$baseType	����
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
		 *	��ȡԪ���ݵĽṹ
		 *	@return String[]
		 *	@access public
		 */
		function getMetaStructure() {
			if (!is_object($this->metaData) && !is_a($this->metaData, 'mysql_ResultSetMetaData'))
				$this->metaData = $this->getMetaData();
			return $this->metaData->getMetadata();
		}

		/**
		 *	����ָ��string�����ֶε�ֵ
		 *	@param $column
		 *	@return String
		 *	@access public
		 */
		function getString($column) {
			return $this->getInternalValue($column, 'string');
		}

		/**
		 *	���ص�ǰ��ѯ�漰���ֶ���
		 *	@return int
		 *	@access private
		 */
		function getTableFiledNums() {
			return mysql_num_fields($this->result);
		}

		/**
		 *	���ص�ǰ��ѯ������ֶ���Ϣ
		 *	@param	int	$offset
		 *	@return Object
		 *	@access private
		 */
		function fetchField($offset=0) {
			return mysql_fetch_field($this->result, $offset);
		}

		/**
		 *	��ȡ��ǰ��ѯ������е��漰���ֶ�����Ϣ
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
		 *	��ӡ��ǰ��ѯ������е��漰���ֶ�����Ϣ
		 *	@return void
		 *	@access public
		 */
		function pGetFetchFieldInfo() {
			print_r($this->getFetchFieldInfo());
		}

		/**
		 *	����ָ��time�����ֶε�ֵ
		 *	@param string	$column
		 *	@return String
		 *	@access public
		 */
		function getTime($column) {
			//not implement yet
		}

		/**
		 *	��ȡ��ǰ�����в�ѯ���صĴ�����Ϣ
		 *	@access public
		 */
		function getWarnings() {
			//not implement yet
		}

		/**
		 *	����ָ��int�����ֶε�ֵ
		 *	@param string	$column
		 *	@return String
		 *	@access public
		 */
		function getInt($column) {
			return $this->getInternalValue($column, 'int');
		}

		/**
		 *	����ָ�������ֶε�ֵ
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
		 *	���ص�ǰ�����Ƿ���õı�ʶ
		 *	@return String
		 *	@access public
		 */
		function isAvailable() {
			return $this->isAvailable;
		}

		/**
		 *	���ص�ǰ������Ƿ��Ѿ��ر�
		 *	@return boolean
		 ��	@access public
		 */
		function isClosed() {
			return $this->isClosed;
		}

		/**
		 *	���ص�ǰ��¼�Ƿ��ǵ�һ��
		 *	@return boolean
		 */
		function isFirst() {
			return ((int)1 == $this->cursor);
		}

		/**
		 *	���ص�ǰ��¼�Ƿ������һ��
		 *	@return boolean
		 */
		function isLast() {
			return ($this->size == $this->cursor);
		}

		/**
		 *	���α�ָ�������е���һ����¼
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
		 *	���α��ƶ�����ǰResultSet��������һ����¼
		 *	return boolean
		 */
		function last() {
			$this->cursor = $this->size;
			return $this->absolute(($this->size));
		}

		/**
		 *	���α��ڵ�ǰResultSet����ǰ��һ����¼
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
		 *	�رյ�ǰ���󣬲��ͷ���Դ
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
		 *	���ص�ǰ���α�
		 *	@return integer	$this->cursor
		 */
		function getCursor() {
			return $this->cursor;
		}

		/**
		 *	��ӡ��ǰ���α�
		 *	@return void
		 */
		function pGetCursor() {
			printf("Current Cursor: (%s)", $this->getCursor());
		}
	}
?>