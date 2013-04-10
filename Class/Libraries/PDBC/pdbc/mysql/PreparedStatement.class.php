<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: PreparedStatement.class.php,v 0.1 2004/11/06 13:29:50
	 */

	require_once ('Object.class.php');
	require_once ('pdbc/mysql/Statement.class.php');

	class mysql_PreparedStatement extends mysql_Statement {
		/* 数据库连接实例 */
		var $conn = null;

		/* 本次会话的批量查询语句集合 */
		var $sqlBatch = array();

		/* 本次会话的批量查询结果集集合 */
		var $resultBatch = array();

		/* 当前批量查询结果集的游标 */
		var $current_result_offset = 0;

		/* 当前的查询结果集 */
		var $currentResult = null;

		/* mysql_PreparedStatement实例 */
		var $pstmt = null;

		/* mysql_ResultSet实例 */
		var $result = null;

		/* 由当前PreparedStatement对象创建的ResultSet对象集合 */
		var $openResults = null;

		/* 数据库连接是否可用标识 */
		var $isClosed = true;

		/* 一个会话期间内绑定的SQL参数数组 */
		var $bindParams = array();

		/* Document me */
		var $_bindInputArray = false;

		/* 要绑定的SQL语句参数个数 */
		var $bindParamCount = 0;

		/* 原始的查询语句 */
		var $original_sql = '';

		/* 预查询处理后的SQL语句 */
		var $prepared_sql = '';

		/* 数据库查询超时时间，默认5秒钟 */
		var $queryTimeout = 5;

		/**
		 *	构造函数
		 */
		function mysql_PreparedStatement(&$conn, $sql) {
			$this->checkDbh($conn);
			$this->checkNullOrEmptyQuery($sql);

			$this->conn = $conn;
			$this->original_sql = $sql;
			$this->isClosed = false;

			if ($GLOBALS['debug'] && (int)4 >= $GLOBALS['debug'])
				$this->debug('oringinal_sql: '.$this->original_sql);
		}

		/**
		 *	析构函数
		 */
		function __destruct() {
			$this->conn = null;
			$this->pstmt = null;
			$this->result = null;
			$this->bindParamCount = 0;
			$this->original_sql = null;
			#$this->sqlBatch = null;
			$this->resultBatch = null;
			$this->isClosed = true;
		}

		/*
		 *	将当前会话中的sql命令添加到当前Statement对象的sql命令列表中
		 *	@return void
		 *	@access private
		 */
		function addBatch() {
			$sql = $this->prepareSql($this->original_sql);

			if ($sql)
				$this->sqlBatch[sizeof($this->sqlBatch)] = $sql;

			// 清空当前绑定参数数组
			$this->bindParams = array();
		}

		/**
		 *	检查当前数据库连接是否已关闭
		 *	@return void
		 *	@access private
		 */
		function checkClosed() {
			if ($this->isClosed)
				$this->throws('No operations allowed after PreparedStatement closed.', null, EXCEPTION_DIE);
		}

		/**
		 *	检查dbh是否合法
		 */
		function checkDbh($dbh) {
			if (is_null($dbh)) 
				trigger_error('dbh is null', E_USER_ERROR);
			if (!is_object($dbh))
				trigger_error('dbh is not a object', E_USER_ERROR);
			if (!is_a($dbh, 'mysql_Connection'))
				trigger_error('dbh is not a mysql_Connection object', E_USER_ERROR);
		}

		/**
		 *	检查sql是否为空
		 *	@param	String	$sql	SQL语句
		 *	@return void
		 *	@access private
		 */
		function checkNullOrEmptyQuery($sql=null) {
			if (null === $sql)
				$this->throws('sql为空', null, EXCEPTION_DIE, null, __FILE__, __LINE__);

			if ((int)0 == strlen(trim($sql)))
				$this->throws('sql长度为0', null, EXCEPTION_DIE);
		}

		/**
		 *	释放当前的预查询
		 *	@return void
		 *	@access private
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
		 *	执行SQL语句
		 *	@see com.shine.pdbc.mysql.mysql_PreparedStatement.executeQuery() OR 
		 *	     com.shine.pdbc.mysql.mysql_PreparedStatement.executeUpdate()
		 *	@return com.shine.pdbc.mysql.mysql_ResultSet;
		 *			true成功，false失败;
		 */
		function execute($sql=null) {
			$this->checkClosed();
			$this->checkNullOrEmptyQuery($sql);

			$firstNonWsChar = StringUtils::firstNonWsChar($this->original_sql);

			$isSelect = true;
			if ('S' != $firstNonWsChar) {
				$isSelect = false;
				if ($this->conn->isReadOnly())
					return false;
					#trigger_error('Connection is in readonly mode', E_USER_ERROR);

				$this->executeUpdate();
			}
			return $this->executeQuery();
		}

		/*
		 *	执行批量查询
		 *	@return array<int>	update counts
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
						if (is_resource($this->result)) {
							$this->resultBatch[$num] = $this->getResultSet();
						} else {
							$this->resultBatch[$num] = $this->result;
						}
						$updateCounts[$num] = $this->getUpdateCount();
					}
				}
			}

			#print_r($this->resultBatch);
			return $updateCounts;
		}

		/**
		 *	初始化预查询绑定的参数数组
		 */
		function initBindParams() {
			if (!$this->bindParams)
				$this->bindParams = array();
		}

		/**
		 *	返回当前连接是否关闭的标识
		 */
		function isClosed() {
			return $this->isClosed;
		}

		/**
		 *	绑定预查询的参数
		 *	@param int		$parameterIndex
		 *	@param mixed	$parameter
		 *	@param string	$baseType
		 *	@return void
		 */
		function bindParam($parameterIndex, $parameter, $baseType) {
			if (!is_int($parameterIndex))
				trigger_error('parameterIndex('.$parameterIndex.') is not compitible with integer', E_USER_ERROR);

			$is_fun = 'is_'.$baseType;
			if (!$is_fun($parameter))
				trigger_error('parameter('.$parameter.') is not compitible with '.$baseType, E_USER_ERROR);

			$size = sizeof($this->bindParams);
			#print '<br/>'.$size.' : '.$parameterIndex.' : '.$parameter.' : '.$baseType.'<br/>';
			if ($parameterIndex != ($size + 1))
				$this->throws('index('.$parameterIndex.') is invalidate', null, EXCEPTION_DIE);

			$this->bindParams[--$parameterIndex] = $parameter;
			return true;
		}

		function getBindParamCount($sql) {
			$count = 0;
			for ($i = 0; $i < strlen($sql); $i++) {
				if ('?' == $sql[$i])
					$count++;
			}
			return $count;
		}

		/**
		 *	移动到当前Statement对象的下一个ResultSet对象
		 *	@param integer
		 *	@return boolean
		 */
		function getMoreResults($current=CLOSE_CURRENT_RESULT) {
			switch ($current) {
				case CLOSE_CURRENT_RESULT :
					break;
				case CLOSE_ALL_RESULTS :
					break;
				case KEEP_CURRENT_RESULT :
					break;
			}

			foreach ($this->resultBatch as $num => $result) {
				if (is_resource($result)) {
					$this->current_result_offset = $i;
					return true;
				} else {
					$this->current_result_offset = $i;
					return false;
				}
			}
		}

		/**
		 *	将原始预查询SQL语句解析为合法的SQL语句
		 *	@param String $sql
		 */
		function prepareSql($sql) {
			$this->bindParamCount = $this->getBindParamCount($sql);
			$bindParams =& $this->bindParams;

			if ($bindParams && ($this->bindParamCount > 0)) {
				if (!is_array($bindParams)) {
					$bindParams = array($bindParams);
				}
				
				$element0 = reset($bindParams);
				# is_object check because oci8 descriptors can be passed in
				$array_2d = is_array($element0) && !is_object(reset($element0));
				
				if (!is_array($sql) && !$this->_bindInputArray) {
					$sqlarr = explode('?', $sql);
						
					if (!$array_2d) {
						$bindParams = array($this->bindParams);
					}
					foreach ($bindParams as $arr) {
						$sql = '';
						$i = 0;
						foreach ($arr as $v) {
							$sql .= $sqlarr[$i];

							if (gettype($v) == 'string') {
								$sql .= $this->qstr($v);
							} else if ($v === null) {
								//$sql .= 'null';
								$sql .= '""';
							} else {
								$sql .= $v;
							}
							$i += 1;
						}
						$sql .= $sqlarr[$i];
						
						if ($i+1 != sizeof($sqlarr)) {	
							$errbuf = new StringBuffer();
							$errbuf->append("mysql_PreparedStatement->prepareSql(): 给定的参数数量与'?'的数量不相匹配");
							$errbuf->appendEnter();
							$errbuf->append($sql);
							$this->throws($errbuf->toString(), null, EXCEPTION_DIE);
						}
						#print '1:'.$sql;
						return $sql;
					}	
				} else {
					if ($array_2d) {
						foreach ($bindParams as $arr) {
							#print '2:'.$sql;
							return $sql;
						}
					} else {
						#print '3:'.$sql;
						return $sql;
					}
				}
			} else {
				#print '4:'.$sql;
				return $sql;
			}
		}
	
		/**
		 *	转换SQL语句中的特殊字符
		 *	@param String $str
		 *	@param boolean $magic_quotes
		 */
		function qstr($str, $magic_quotes=false) {	
			if (!$magic_quotes) {
				if ($this->replaceQuote[0] == '\\'){
					$str = str_replace("\0","\\\0", str_replace('\\', '\\\\', $str));
				}
				return  "'".str_replace("'", "\\'", $str)."'";
			}
			
			$str = str_replace('\\"', '"', $str);
			
			if ($this->replaceQuote == "\\'") {
				return "'$str'";
			} else {
				// change \' to '' for sybase/mssql
				$str = str_replace('\\\\', '\\', $str);
				return "'".str_replace("\\'", "\\'", $str)."'";
			}
		}

		/**
		 *	执行SELECT查询
		 *	@return mysql_ResultSet 
		 */
		function executeQuery() {
			$this->realExecuteSQL();
			return $this->getResultSet();
		}

		/**
		 *	执行INPUT, UPDATE, DELETE等SQL语句
		 *	@return boolean true/false 
		 */
		function executeUpdate() {
			return $this->realExecuteSQL();
		}

		/**
		 *	获取数据库连接句柄(mysql)
		 *	@return Object mysql
		 */
		function getConnection() {
			return $this->conn;
		}

		/**
		 *	Document me
		 */
		function getFetchSize() {
			//not implement yet
		}

		/**
		 *	获取查询超时时间
		 *	@return int queryTimeout
		 */
		function getQueryTimeout() {
			return $this->queryTimeout;
		}

		/**
		 *	获取本次会话的查询结果集
		 */
		function getResultSet() {
			return new mysql_ResultSet($this->result);
		}

		/**
		 *	Document me
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
		 *	关闭数据库连接
		 */
		function realClose($calledExplicitly=false) {
			if ($this->isClosed)
				return;

			$this->closeAllOpenResults();
			$this->openResults = null;

			$this->result  = null;
			$this->bindParams = array();
			$this->bindParamCount = 0;
			$this->conn->closePrepareStatement();
			$this->isClosed = true;
		}

		/**
		 *	执行会话期间内的查询
		 *	@return Object mysql_ResultSet
		 *	@return boolean true/false
		 */
		function realExecuteSQL() {
			$this->checkClosed();
			
			$firstNonWsChar = StringUtils::firstNonWsChar($this->original_sql);
			if ('S' != $firstNonWsChar) {
				$this->isSelect = false;
				if ($this->conn->isReadOnly())
					return false;
					#trigger_error('Connection is in readonly mode', E_USER_ERROR);
			}
			
			$this->prepared_sql = $this->prepareSql($this->original_sql);
			if ($GLOBALS['debug'] && (int)4 >= $GLOBALS['debug'])
				$this->debug('prepared_sql: '.$this->prepared_sql);

			$this->result = mysql_query($this->prepared_sql);
			
			if ($GLOBALS['debug'] && (int)3 >= $GLOBALS['debug'])
				$this->debug(var_dump($this->result));
			
			if ($this->result) {
				return true;
			} else {
				$errbuf = new StringBuffer();
				$errbuf->append('#ErrorNo '.mysql_errno($this->conn->getConnection()).': ');
				$errbuf->append(mysql_error($this->conn->getConnection()));
				trigger_error($errbuf->toString(), E_USER_ERROR);
			}

			return false;
		}

		/**
		 *	Document me
		 */
		function setFetchSize($rows) {
			//not implement yes
		}

		/**
		 *	设置预查询类型为Integer的绑定参数
		 */
		function setInt($parameterIndex, $int) {
			$ret = $this->bindParam($parameterIndex, $int, 'integer');

			if (!$ret)
				trigger_error('mysql_PreparedStatement->setInt(): bind param error', E_USER_ERROR);
		}

		/**
		 *	Document me
		 */
		function setFloat($parameterIndex, $float) {
			//not implement yes
		}

		/**
		 *	Document me
		 */
		function setDate($parameterIndex, $date) {
			//not implement yes
		}

		/**
		 *	Document me
		 */
		function setDouble($parameterIndex, $double) {
			//not implement yes
		}

		/**
		 *	Document me
		 */
		function setNull($parameterIndex, $null) {
			//not implement yes
		}

		/**
		 *	Document me
		 */
		function setObject($parameterIndex, $object) {
			//not implement yes
		}

		/**
		 *	设置预查询类型为String的绑定参数
		 */
		function setString($parameterIndex, $string) {
			$ret = $this->bindParam($parameterIndex, $string, 'string');

			if (!$ret)
				trigger_error('mysql_PreparedStatement->setString(): bind param error', E_USER_ERROR);
		}

		/**
		 *	Document me
		 */
		function setTime($parameterIndex, $time) {
			//not implement yes
		}

		/**
		 *	设置查询超时时长
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
		
		/**
		 *	Document me
		 */
		function setOriginalSQL($sql) {
			$this->original_sql = $sql;
		}
	}
?>