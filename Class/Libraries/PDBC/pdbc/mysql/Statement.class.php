<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: Statement.class.php,v 0.1 2004/11/04 11:34:03
	 */

	require_once ('Object.class.php');
	require_once ('pdbc/mysql/ResultSet.class.php');
	require_once ('util/StringBuffer.class.php');
	require_once ('util/StringUtils.class.php');

	define ('CLOSE_ALL_RESULTS',	0);
	define ('KEEP_CURRENT_RESULT',	1);
	define ('CLOSE_CURRENT_RESULT', 2);

	class mysql_Statement extends Object {
		/* ��ǰ����id */
		var $id = null;

		/* ���ݿ����� */
		var $conn = null;

		/* ����� */
		var $result = false;

		/* ���λỰ��������ѯ��伯�� */
		var $sqlBatch = null;

		/* ���λỰ��������ѯ��������� */
		var $resultBatch = null;

		/* ��ǰ������ѯ��������α� */
		var $current_result_offset = 0;

		/* ��ǰ�Ĳ�ѯ����� */
		var $currentResult = null;

		/* �ɵ�ǰStatement���󴴽���ResultSet���󼯺� */
		var $openResults = null;

		/* ��ǰ�Ự�Ĳ�ѯ����� */
		var $resultSet = null;

		/* ���λỰ�Ĳ�ѯ����Ƿ���SELECT���ı�ʶ */
		var $isSelect = true;

		/* ��ǰStatement�����Ƿ��ѹرյı�ʶ */
		var $isClosed = false;

		/* ��ѯ�������fetch��С */
		var $fetchSize = 30;

		/* ��ѯ��ʱʱ�� */
		var $queryTimeout = 30;

		/* ���һ�β�ѯ��SQL��� */
		var $lastSql = null;

		/**
		 *	���캯��
		 */
		function &mysql_Statement(&$conn) {
			$this->id = $this->genUniqueId();
			$this->conn = $conn;
		}

		/**
		 *	��������
		 */
		function __destruct() {
			$this->conn = null;
			$this->resultSet = null;
			$this->isClosed = true;
		}

		/*
		 *	��������sql������ӵ���ǰStatement�����sql�����б���
		 *	@return void
		 */
		function addBatch($sql) {
			if (!$this->sqlBatch) {
				$this->sqlBatch = array();
			}

			if (is_array($sql)) {
				foreach ($sql as $num => $s) {
					if ($sql)
						$this->sqlBatch[sizeof($this->sqlBatch)] = $sql[$num];
				}
			} else {
				if ($sql)
					$this->sqlBatch[sizeof($this->sqlBatch)] = $sql;
			}
		}

		function cancel() {
			//not implement
		}

		/**
		 *	��鵱ǰ���ݿ������Ƿ��ѹر�
		 */
		function checkClosed() {
			if ($this->isClosed())
				$this->throws('No operations allowed after Statement closed.', E_USER_ERROR);
		}
		
		/**
		 *	@param	SQL���
		 *	���sql�Ƿ�Ϊ��
		 */
		function checkNullOrEmptyQuery($sql=null) {
			if (null === $sql)
				trigger_error('sql is null', E_USER_ERROR);
			if ((int)0 == strlen($sql))
				trigger_error('sql length is 0', E_USER_ERROR);
		}

		/**
		 *	��յ�ǰ������ѯ��������Ϣ
		 *	return void
		 *	@access	public
		 */
		function clearBatch() {
			$this->clearBatchResult();
			$this->clearBatchSQL();
		}

		/**
		 *	���������ѯ�Ľ�����Լ�������ѯSQL���(��ѡ)
		 *	@param	boolean	$clearSQL	�Ƿ����������ѯSQL���
		 *	@return	void
		 *	@access	private
		 */
		function clearBatchResult($offset=0) {
			if ($offset >= sizeof($this->resultBatch)) {
				$this->throws($offset.' is overflow of sizeof($this->resultBatch)', null);
				return;
			}

			for ($i = $offset; $i < sizeof($this->resultBatch); $i++) {
				if (is_resource($this->resultBatch[$i]))
					mysql_free_result($this->resultBatch[$i]);
			}

			$this->resultBatch = null;
		}

		/**
		 *	���������ѯSQL���
		 *	@param	boolean	$clear	�Ƿ����������ѯSQL���
		 *	@return	void
		 *	@access	private
		 */
		function clearBatchSQL($clear=false) {
			if ($clear)
				$this->sqlBatch = null;
		}

		/**
		 *	�ͷŵ�ǰ�Ĳ�ѯ
		 */
		function close() {
			$this->realClose(true);
		}

		/**
		 *	�رյ�ǰ���л��ResultSet����
		 *	@return void
		 *	@access private
		 */
		function closeAllOpenResults() {
			if (null !== $this->openResults) {
				for ($i = 0; $i < $this->openResults->size(); $i++) {
					$currentOpenResult = $this->openResults->get($i);
					$ret = $currentOpenResult->realClose();

					if (is_a($ret, 'EXCEPTION'))
						$exp = $ret;
				}

				if (isset($exp))
					$this->throws($exp->getMessage(), $exp->getCode(), $exp->getMode());
			}
		}

		/**
		 *	Document me
		 *	@return void
		 *	@access	private
		 */
		function closeAllOpenResult() {
			if (null !== $this->resultBatch) {
				foreach ($this->resultBatch as $num => $rs) {
					if (is_object($rs) && is_a($rs, 'mysql_ResultSet')) {
						$rs->close();
					}
				}
			}

			$this->resultBatch = null;
		}
		
		/**
		 *	ִ��SQL���
		 *	@see com.shine.pdbc.mysql.mysql_PreparedStatement.executeQuery() OR 
		 *	     com.shine.pdbc.mysql.mysql_PreparedStatement.executeUpdate()
		 *	@return com.shine.pdbc.mysql.mysql_ResultSet;
		 *			true�ɹ���falseʧ��;
		 */
		function execute($sql) {
			$this->checkNullOrEmptyQuery($sql);
			$this->checkClosed();

			$firstNonWsChar = StringUtils::firstNonWsChar($sql);

			if ('S' != $firstNonWsChar) {
				$this->isSelect = false;
				if ($this->conn->isReadOnly())
					return false;
					#trigger_error('Connection is in readonly mode', E_USER_ERROR);

				$this->executeUpdate($sql);
			}

			return $this->executeQuery($sql);
		}

		/*
		 *	ִ��������ѯ
		 *	@return int[]	update counts
		 */
		function executeBatch() {
			if (!is_array($this->sqlBatch) || (int)0 == sizeof($this->sqlBatch)) {
				$errbuf = new StringBuffer('no sql in Batch');
				$this->throws($errbuf->toString(), null, EXCEPTION_DIE, NULL, 'Exceptions', __FILE__, __LINE__);
			}

			$updateCounts = array();
			foreach ($this->sqlBatch as $num => $sql) {
				if ($sql) {
					if (!$this->realExecuteSQL($sql)) {
						$updateCounts[$num] = -1;
						$this->resultBatch[$num] = false;
					} else {
						/*
						print $num.': ';
						var_dump($this->result);
						print '<br/>';
						*/
						if (is_resource($this->result)) {
							$this->resultBatch[$num] = $this->getResultSet();
							/*
							print $num.': ';
							var_dump($this->resultBatch[$num]);
							print '<br/>';
							*/
						} else {
							$this->resultBatch[$num] = $this->result;
						}
						$updateCounts[$num] = $this->getUpdateCount();
					}
				}
			}

			return $updateCounts;
		}

		/**
		 *	ִ��SELECT��ѯ
		 *	@return mysql_ResultSet
		 */
		function executeQuery($sql) {
			if ($GLOBALS['debug'] && (int)4 >= $GLOBALS['debug'])
				$this->debug($sql);

			$this->realExecuteSQL($sql);
			return $this->getResultSet();
		}

		/**
		 *	ִ��INPUT, UPDATE, DELETE��SQL���
		 *	@return boolean true/false 
		 */
		function executeUpdate($sql) {
			if ($GLOBALS['debug'] && (int)4 >= $GLOBALS['debug'])
				$this->debug($sql);

			return $this->realExecuteSQL($sql);
		}

		/**
		 *	���ص�ǰ�����id
		 *	@access public
		 *	@see Object->genUniqueId()
		 */
		function getId() {
			return $this->id;
		}

		/**
		 *	ִ�лỰ�ڼ��ڵĲ�ѯ
		 *	@return Object mysqlh_ResultSet
		 *	@return boolean true/false
		 */
		function realExecuteSQL($sql) {
			$firstNonWsChar = StringUtils::firstNonWsChar($sql);
			if ('S' != $firstNonWsChar) {
				$this->isSelect = false;
				if ($this->conn->isReadOnly())
					#trigger_error('Connection is in readonly mode', E_USER_WARNING);
					return false;
			}
			
			$this->lastSql = $sql;
			$result = mysql_query($sql);

			if ($GLOBALS['debug'] && (int)3 >= $GLOBALS['debug'])
				var_dump($result);

			if ($result) {
				$this->result = $result;
				return true;
			} 
			/*
			else {
				$errbuf = new StringBuffer();
				$errbuf->append('Error - SQLSTATE '.mysql_errno($this->conn->getConnection()));
				$errbuf->append(': ');
				$errbuf->append(mysql_error($this->conn->getConnection()));

				return new Error($errbuf->toString(), null, ERROR_DIE);
			}
			*/

			return false;
		}

		/**
		 *	��ȡ����ִ�е�SQL�б�
		 *	@return Array	$sqlBatch
		 */
		function getBatchSQL() {
			return $this->sqlBatch;
		}

		/**
		 *	��ȡ���ݿ�����
		 *	@return resource $conn
		 */
		function getConnection() {
			return $this->conn->getConnection();
		}

		/**
		 *	��ȡfetchsize
		 *	@return void
		 */
		function getFetchSize() {
			return $this->fetchSize;
		}

		/**
		 *	��ӡ��ǰStatement�����fetchsize
		 *	@return String
		 */
		function pGgetFetchSize() {
			printf ('Fetch Size: %s<br/>', $this->fetchSize);
		}

		/**
		 *	���ز�ѯ��ʱʱ��
		 *	@return int queryTimeout
		 */
		function getQueryTimeout() {
			return $this->queryTimeout;
		}

		/**
		 *	�ƶ�����ǰStatement�������һ��ResultSet����
		 *	@param integer
		 *	@return boolean
		 */
		function getMoreResults($current=CLOSE_CURRENT_RESULT) {
			switch ($current) {
				case CLOSE_CURRENT_RESULT :
					if (null !== $this->currentResult && is_object($this->currentReuslt))
						$this->currentResult->close();
					break;
				case CLOSE_ALL_RESULTS :
					if (null !== $this->currentResult && is_object($this->currentReuslt))
						$this->currentResult->close();

					$this->closeAllOpenResult();
					break;
				case KEEP_CURRENT_RESULT :
					//do nothing
					break;
			}
			
			$this->resultSet = $this->getNextResult();
			return (is_object($rs) && is_a($rs, 'mysql_ResultSet')) ? true: false;

			/*
			foreach ($this->resultBatch as $num => $rs) {
				if (is_object($rs) && is_a($rs, 'mysql_ResultSet')) {
					$this->resultSet = $rs;
					$this->current_result_offset = $i;
					return true;
				} else {
					$this->current_result_offset = $i;
					return false;
				}
			}
			*/
		}

		/**
		 *	��ȡ��ǰ�����е���һ�������
		 *	@return	mysql_ResultSet
		 *	@access private
		 */
		function getNextResult() {
			return $this->resultBatch[$this->current_result_offset++];
		}

		/**
		 *	��ȡ���λỰ�Ĳ�ѯ�����
		 *	@return mysql_ResultSet
		 *	@access public
		 */
		function getResultSet() {
			if (is_object($this->resultSet) && is_a($this->resultSet, 'mysql_ResultSet')) {
				return $this->resultSet;
			} else if (is_resource($this->result)) {
				return new mysql_ResultSet($this->result);
			} else {
				return null;
			}
		}

		/**
		 *	������Ӱ��ļ�¼��
		 *	@return int
		 *	@access public
		 */
		function getUpdateCount() {
			if (!$this->isSelect && is_bool($this->result))
				return mysql_affected_rows($this->conn->getConnection());
			return -1;
		}
		
		/**
		 *	Document me
		 */
		function getWarnings() {
			//not implement yet
		}

		/**
		 *	���ص�ǰStatement�����Ƿ��ѹرյı�ʶ
		 *	@return boolean
		 */
		function isClosed() {
			return $this->isClosed;
		}

		/**
		 *	�رյ�ǰStatement����
		 *	@param	boolean	$calledExplicitly
		 *	@return	void
		 */
		function realClose($calledExplicitly=false) {
			if ($this->isClosed)
				return;

			$this->closeAllOpenResults();
			$this->openResults = null;
			$this->result  = null;
			$this->resultSet = null;
			$this->isClosed = true;
		}

		/**
		 *	���õ�ǰ��ѯ�Ľ����size
		 *	@param	numeric	$fetchSize
		 *	@return	void
		 */
		function setFetchSize($fetchSize) {
			$fetchSize = StringUtils::isNumeric($fetchSize);
			if (!$fetchSize)
				trigger_error($fetchSize.' is not a numeric', E_USER_ERROR);

			$this->fetchSize = $fetchSize;
		}

		/**
		 *	���ò�ѯ��ʱʱ��
		 *	@param	numeric	$timeout
		 *	@return void
		 */
		function setQueryTimeout($timeout) {
			$timeout = StringUtils::isNumeric($timeout);
			
			if ($timeout) {
				$this->queryTimeout = $timeout;
				ini_set('mysql.connect_timeout', $timeout);
			} else {
				$this->queryTimeout = 60;
			}
		}
	}
?>