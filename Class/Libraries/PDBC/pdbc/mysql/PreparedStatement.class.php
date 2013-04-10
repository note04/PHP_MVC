<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: PreparedStatement.class.php,v 0.1 2004/11/06 13:29:50
	 */

	require_once ('Object.class.php');
	require_once ('pdbc/mysql/Statement.class.php');

	class mysql_PreparedStatement extends mysql_Statement {
		/* ���ݿ�����ʵ�� */
		var $conn = null;

		/* ���λỰ��������ѯ��伯�� */
		var $sqlBatch = array();

		/* ���λỰ��������ѯ��������� */
		var $resultBatch = array();

		/* ��ǰ������ѯ��������α� */
		var $current_result_offset = 0;

		/* ��ǰ�Ĳ�ѯ����� */
		var $currentResult = null;

		/* mysql_PreparedStatementʵ�� */
		var $pstmt = null;

		/* mysql_ResultSetʵ�� */
		var $result = null;

		/* �ɵ�ǰPreparedStatement���󴴽���ResultSet���󼯺� */
		var $openResults = null;

		/* ���ݿ������Ƿ���ñ�ʶ */
		var $isClosed = true;

		/* һ���Ự�ڼ��ڰ󶨵�SQL�������� */
		var $bindParams = array();

		/* Document me */
		var $_bindInputArray = false;

		/* Ҫ�󶨵�SQL���������� */
		var $bindParamCount = 0;

		/* ԭʼ�Ĳ�ѯ��� */
		var $original_sql = '';

		/* Ԥ��ѯ������SQL��� */
		var $prepared_sql = '';

		/* ���ݿ��ѯ��ʱʱ�䣬Ĭ��5���� */
		var $queryTimeout = 5;

		/**
		 *	���캯��
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
		 *	��������
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
		 *	����ǰ�Ự�е�sql������ӵ���ǰStatement�����sql�����б���
		 *	@return void
		 *	@access private
		 */
		function addBatch() {
			$sql = $this->prepareSql($this->original_sql);

			if ($sql)
				$this->sqlBatch[sizeof($this->sqlBatch)] = $sql;

			// ��յ�ǰ�󶨲�������
			$this->bindParams = array();
		}

		/**
		 *	��鵱ǰ���ݿ������Ƿ��ѹر�
		 *	@return void
		 *	@access private
		 */
		function checkClosed() {
			if ($this->isClosed)
				$this->throws('No operations allowed after PreparedStatement closed.', null, EXCEPTION_DIE);
		}

		/**
		 *	���dbh�Ƿ�Ϸ�
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
		 *	���sql�Ƿ�Ϊ��
		 *	@param	String	$sql	SQL���
		 *	@return void
		 *	@access private
		 */
		function checkNullOrEmptyQuery($sql=null) {
			if (null === $sql)
				$this->throws('sqlΪ��', null, EXCEPTION_DIE, null, __FILE__, __LINE__);

			if ((int)0 == strlen(trim($sql)))
				$this->throws('sql����Ϊ0', null, EXCEPTION_DIE);
		}

		/**
		 *	�ͷŵ�ǰ��Ԥ��ѯ
		 *	@return void
		 *	@access private
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
		 *	ִ��SQL���
		 *	@see com.shine.pdbc.mysql.mysql_PreparedStatement.executeQuery() OR 
		 *	     com.shine.pdbc.mysql.mysql_PreparedStatement.executeUpdate()
		 *	@return com.shine.pdbc.mysql.mysql_ResultSet;
		 *			true�ɹ���falseʧ��;
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
		 *	ִ��������ѯ
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
		 *	��ʼ��Ԥ��ѯ�󶨵Ĳ�������
		 */
		function initBindParams() {
			if (!$this->bindParams)
				$this->bindParams = array();
		}

		/**
		 *	���ص�ǰ�����Ƿ�رյı�ʶ
		 */
		function isClosed() {
			return $this->isClosed;
		}

		/**
		 *	��Ԥ��ѯ�Ĳ���
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
		 *	�ƶ�����ǰStatement�������һ��ResultSet����
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
		 *	��ԭʼԤ��ѯSQL������Ϊ�Ϸ���SQL���
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
							$errbuf->append("mysql_PreparedStatement->prepareSql(): �����Ĳ���������'?'����������ƥ��");
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
		 *	ת��SQL����е������ַ�
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
		 *	ִ��SELECT��ѯ
		 *	@return mysql_ResultSet 
		 */
		function executeQuery() {
			$this->realExecuteSQL();
			return $this->getResultSet();
		}

		/**
		 *	ִ��INPUT, UPDATE, DELETE��SQL���
		 *	@return boolean true/false 
		 */
		function executeUpdate() {
			return $this->realExecuteSQL();
		}

		/**
		 *	��ȡ���ݿ����Ӿ��(mysql)
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
		 *	��ȡ��ѯ��ʱʱ��
		 *	@return int queryTimeout
		 */
		function getQueryTimeout() {
			return $this->queryTimeout;
		}

		/**
		 *	��ȡ���λỰ�Ĳ�ѯ�����
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
		 *	�ر����ݿ�����
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
		 *	ִ�лỰ�ڼ��ڵĲ�ѯ
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
		 *	����Ԥ��ѯ����ΪInteger�İ󶨲���
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
		 *	����Ԥ��ѯ����ΪString�İ󶨲���
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
		 *	���ò�ѯ��ʱʱ��
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