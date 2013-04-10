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
		/* 当前对象id */
		var $id = null;

		/* 数据库连接 */
		var $conn = null;

		/* 结果集 */
		var $result = false;

		/* 本次会话的批量查询语句集合 */
		var $sqlBatch = null;

		/* 本次会话的批量查询结果集集合 */
		var $resultBatch = null;

		/* 当前批量查询结果集的游标 */
		var $current_result_offset = 0;

		/* 当前的查询结果集 */
		var $currentResult = null;

		/* 由当前Statement对象创建的ResultSet对象集合 */
		var $openResults = null;

		/* 当前会话的查询结果集 */
		var $resultSet = null;

		/* 本次会话的查询语句是否是SELECT语句的标识 */
		var $isSelect = true;

		/* 当前Statement对象是否已关闭的标识 */
		var $isClosed = false;

		/* 查询结果集的fetch大小 */
		var $fetchSize = 30;

		/* 查询超时时间 */
		var $queryTimeout = 30;

		/* 最后一次查询的SQL语句 */
		var $lastSql = null;

		/**
		 *	构造函数
		 */
		function &mysql_Statement(&$conn) {
			$this->id = $this->genUniqueId();
			$this->conn = $conn;
		}

		/**
		 *	析构函数
		 */
		function __destruct() {
			$this->conn = null;
			$this->resultSet = null;
			$this->isClosed = true;
		}

		/*
		 *	将给定的sql命令添加到当前Statement对象的sql命令列表中
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
		 *	检查当前数据库连接是否已关闭
		 */
		function checkClosed() {
			if ($this->isClosed())
				$this->throws('No operations allowed after Statement closed.', E_USER_ERROR);
		}
		
		/**
		 *	@param	SQL语句
		 *	检查sql是否为空
		 */
		function checkNullOrEmptyQuery($sql=null) {
			if (null === $sql)
				trigger_error('sql is null', E_USER_ERROR);
			if ((int)0 == strlen($sql))
				trigger_error('sql length is 0', E_USER_ERROR);
		}

		/**
		 *	清空当前批量查询的所有信息
		 *	return void
		 *	@access	public
		 */
		function clearBatch() {
			$this->clearBatchResult();
			$this->clearBatchSQL();
		}

		/**
		 *	清空批量查询的结果集以及批量查询SQL语句(可选)
		 *	@param	boolean	$clearSQL	是否清空批量查询SQL语句
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
		 *	清空批量查询SQL语句
		 *	@param	boolean	$clear	是否清空批量查询SQL语句
		 *	@return	void
		 *	@access	private
		 */
		function clearBatchSQL($clear=false) {
			if ($clear)
				$this->sqlBatch = null;
		}

		/**
		 *	释放当前的查询
		 */
		function close() {
			$this->realClose(true);
		}

		/**
		 *	关闭当前所有活动的ResultSet对象
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
		 *	执行SQL语句
		 *	@see com.shine.pdbc.mysql.mysql_PreparedStatement.executeQuery() OR 
		 *	     com.shine.pdbc.mysql.mysql_PreparedStatement.executeUpdate()
		 *	@return com.shine.pdbc.mysql.mysql_ResultSet;
		 *			true成功，false失败;
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
		 *	执行批量查询
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
		 *	执行SELECT查询
		 *	@return mysql_ResultSet
		 */
		function executeQuery($sql) {
			if ($GLOBALS['debug'] && (int)4 >= $GLOBALS['debug'])
				$this->debug($sql);

			$this->realExecuteSQL($sql);
			return $this->getResultSet();
		}

		/**
		 *	执行INPUT, UPDATE, DELETE等SQL语句
		 *	@return boolean true/false 
		 */
		function executeUpdate($sql) {
			if ($GLOBALS['debug'] && (int)4 >= $GLOBALS['debug'])
				$this->debug($sql);

			return $this->realExecuteSQL($sql);
		}

		/**
		 *	返回当前对象的id
		 *	@access public
		 *	@see Object->genUniqueId()
		 */
		function getId() {
			return $this->id;
		}

		/**
		 *	执行会话期间内的查询
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
		 *	获取批量执行的SQL列表
		 *	@return Array	$sqlBatch
		 */
		function getBatchSQL() {
			return $this->sqlBatch;
		}

		/**
		 *	获取数据库连接
		 *	@return resource $conn
		 */
		function getConnection() {
			return $this->conn->getConnection();
		}

		/**
		 *	获取fetchsize
		 *	@return void
		 */
		function getFetchSize() {
			return $this->fetchSize;
		}

		/**
		 *	打印当前Statement对象的fetchsize
		 *	@return String
		 */
		function pGgetFetchSize() {
			printf ('Fetch Size: %s<br/>', $this->fetchSize);
		}

		/**
		 *	返回查询超时时间
		 *	@return int queryTimeout
		 */
		function getQueryTimeout() {
			return $this->queryTimeout;
		}

		/**
		 *	移动到当前Statement对象的下一个ResultSet对象
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
		 *	获取当前对象中的下一个结果集
		 *	@return	mysql_ResultSet
		 *	@access private
		 */
		function getNextResult() {
			return $this->resultBatch[$this->current_result_offset++];
		}

		/**
		 *	获取本次会话的查询结果集
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
		 *	返回受影响的记录数
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
		 *	返回当前Statement对象是否已关闭的标识
		 *	@return boolean
		 */
		function isClosed() {
			return $this->isClosed;
		}

		/**
		 *	关闭当前Statement对象
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
		 *	设置当前查询的结果集size
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
		 *	设置查询超时时长
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