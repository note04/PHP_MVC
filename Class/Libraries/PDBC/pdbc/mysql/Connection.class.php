<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: Connection.class.php,v 0.1 2004/11/03 18:58:07
	 */

	#require_once ('Object.class.php');
	require_once ('util/StringBuffer.class.php');

	class mysql_Connection extends Object {
		/* mysql数据库连接 */
		var $conn = null;

		/* 数据库驱动对象 */
		var $driver = null;

		/* 默认数据库连接超时秒数, 默认3秒, 可通过setConnectionconntimeout()方法设置 */
		var $connconntimeout = 3;

		/* � �识数据库连接是否以关闭，ture为已关闭，false为为关闭 */
		var $isClosed = true;

		/* � �识数据库连接是否为只读 */
		var $readOnly = false;

		/* 设置当前是否为aotucommit状态 */
		var $autoCommit = true;

		/* 由当前Connection对象创建的查询对象(Statement) */
		var $stmt = null;

		/* 由当前Connection对象创建的预查询对象(PreparedStatement) */
		var $pstmt = null;

		/* 设置是否支持事务 */
		var $transactionsSupported = false;

		/* 事务操作 */
		var $transaction_opcount = 0;

		/* 当前Connection对象创建的Statement对象集合 */
		var $openStatements = null;

		/**
		 *	构� 函数
		 *	@param 数据库连接
		 */
		function &mysql_Connection(&$driver) {
			$this->driver = $driver;

			$this->realConnect($this->driver->getUrlProps());
		}

		/**
		 *	析构函数
		 *	释放mysql连接以及driver对象
		 */
		function __destruct() {
			if (@version_compare('5.0.0', phpversion(), '>='))
				$this->close();
			$this->driver = null;
		}

		/**
		 *	Singleton
		 */
		function getInstance(&$driver) {
			if (null === $this->conn) {
				return new mysql_Connection($driver);
			}
			return $this;
		}

		/**
		 *	获取当前mysql连接
		 *	@return mysql_connect
		 */
		function getConnection() {
			return $this->conn;
		}

		/**
		 *	判断当前连接是否已关闭
		 *	@return void
		 */
		function checkClosed() {
			$this->ping();

			if ($this->isClosed) {
				$msgbuf = new StringBuffer($lang['CONNECTION_ALREADY_CLOSE']);

				$this->throws($msgbuf->toString());
			}
		}

		/**
		 *	ping MySQL服务器，以判断当前连接是否可用
		 *	@return true可用，false不可用
		 */
		function ping() {
			$this->isClosed = !mysql_ping($this->conn);
		}

		/**
		 *	关闭当前数据库连接
		 *	@return void
		 */
		function close() {
			$this->realClose(true, true);
		}

		/**
		 *	关闭当前Connection对象创建的所有Statement对象
		 *	@access private
		 */
		function closeAllOpenStatements() {
			if (null !== $this->openStatements) {
				for ($i = 0; $i < $this->openStatements->size(); $i++) {
					$currentOpenedStatement = $this->openStatements->get($i);
					
					if ($GLOBALS['debug'] && (int)3 >= $GLOBALS['debug']) {
						$this->debug('closeStatement '.$i.': ('.$currentOpenedStatement.')');
					}
					$ret = $currentOpenedStatement->realClose();

					if (is_a($ret, 'EXCEPTIONS'))
						$exp = $ret; //先关闭所有活动的Statement对象，稍后再抛出异常
				}

				if (isset($exp) && null !== $exp)
					$this->throws($exp->getMessage(), $exp->getCode(), $exp->getMode());
			}
		}

		/**
		 *	关闭当前PreparedStatment对象
		 *	@return void
		 */
		function closePrepareStatement() {
			$this->pstmt = null;
		}

		/**
		 *	开始一组事务
		 *	@return void
		 */
		function startTransaction() {
			$this->checkClosed();
			
			#$stmt =& $this->createStatement();
			#$ret = $stmt->executeUpdate('START TRANSACTION;');
			$ret = mysql_query('START TRANSACTION;', $this->conn);

			if (!$ret)
				$this->throws(mysql_errno($this->conn) .': '.mysql_error($this->conn), null, EXCEPTION_DIE);

			$this->transaction_opcount++;
		}

		/**
		 *	提交当前事务
		 *	@return void
		 */
		function commit() { 
			if ($this->transaction_opcount > 0) {
				$this->checkClosed();

				//$stmt =& $this->createStatement();
				//$ret = $stmt->exexuteUpdate('COMMIT;');
				//$ret = $stmt->exexuteUpdate('SET AUTOCOMMIT=1;');
			
				$ret = mysql_query('COMMIT;', $this->conn) && mysql_query('SET AUTOCOMMIT=1', $this->conn);

				if ($GLOBALS['debug'] && (int) 3 < $GLOBALS['debug']) {
					$this->debug('commit');
				}
				
				if (!$ret)
					$this->throws(mysql_errno($this->conn) .': '.mysql_error($this->conn), null, EXCEPTION_DIE);

				$this->transaction_opcount = 0;
			}
		}

		/**
		 *	回滚当前事务
		 *	@accedd pubic
		 *	@see rollback()
		 *	@return void
		 */
		function rollback() {
			if ($this->transaction_opcount > 0) {
				$this->checkClosed();

				if (null !== $this->openStatements) {
					$lastNum = $this->openStatements->size() - 1;

					if (is_object($lastStatement = $this->openStatements->get($lastNum)))
						$ok = !$lastStatement->isClosed();
				}

				if ($ok) {
					$stmt =& $this->createStatement();
					$ret = $stmt->executeUpdate('ROLLBACK;');
					$ret = $stmt->executeUpdate('SET AUTOCOMMIT=1;');
				} else {
					$ret = mysql_query('ROLLBACK;', $this->conn) && mysql_query('SET AUTOCOMMIT=1;', $this->conn);
				}

				$this->transaction_opcount = 0;

				if ($GLOBALS['debug'] && (int)3 <= $GLOBALS['debug']) {
					$this->debug('rollback');
				}

				if (!$ret) {
					$errbuf = new StringBuffer('#ErrorNo '.mysql_errno($this->conn).': ');
					$errbuf->append(mysql_error($this->conn));
					$this->throws($errbuf->toString(), null, EXCEPTION_DIE, null, __FILE__, __LINE__);
				}
			}
		}

		/**
		 *	返回mysql_Statement对象
		 *	@return mysql_Statement
		 *	@access public
		 *	@see createPrepareStatement()
		 */
		function &createStatement() {
			$this->checkClosed();

			require_once ('pdbc/mysql/Statement.class.php');

			//if (!$this->stmt && !is_object($this->pstmt) && !is_a($this->stmt, 'mysql_Statement')) {
				$stmt =& new mysql_Statement($this);
				if (null === $this->openStatements || !is_a($this->openStatements, 'Vector')) {
					require_once ('util/Vector.class.php');
					$this->openStatements = new Vector();
				}
				$this->openStatements->add($stmt);
			//}
			
			//return $this->stmt;
			return $stmt;
		}

		/**
		 *	返回当前Connection对象的commit模式
		 *	@return boolean
		 *	@access public
		 */
		function getAutoCommit() {
			return $this->autoCommit;
		}

		function getRelaxAutoCommit() {
			if ((int)32315 < $this->getServerVersionInt())
				return true;
			return false;
		}

		function getDriverProperty($property_key) {
			if ((bool)$property_key) {
				return $this->driver->getDriverProperty();
			} else {
				return $this->driver->getDriverProperty($property_key);
			}
		}

		/**
		 *	获取当前MySQL服务器数字版本号
		 *	@return int
		 */
		function getServerVersionInt() {
			$numerics = array('.', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
			$fullversion = $this->getServerVersionString();

			for ($i = 0; $i < strlen($fullversion); $i++) {
				if (!in_array($fullversion[$i], $numerics)) {
					break;
				}
			}

			$version = substr($fullversion, 0, $i);
			$version = explode('.', $version);

			foreach ($version as $key => $val) {
				if ((int)1 == $key || (int)2 == $key) {
					$val = ((int)1 == strlen($version[$key])) ? '0'.$version[$key]: $version[$key];
				}
				$vid .= $val;
			}
			return $vid;
		}

		/**
		 *	获取当前MySQL服务器版本全称
		 *	@return String
		 */
		function getServerVersionString() {
			return @mysql_get_server_info();
		}

		/**
		 *	获取系统警告
		 */
		function getWarnings() {
			//not impelment yet
		}

		/**
		 *	判断当前连接是否已关闭
		 *	@return true关闭，false未关闭
		 */
		function isClosed() {
			return $this->isClosed;
		}

		/**
		 *	@retrun 如果是该连接为只读，返回true，否则返回false
		 */
		function isReadOnly() {
			return $this->readOnly;
		}

		/**
		 *	@param $sql
		 */
		function prepareCall($sql) {
			//not implement yet
		}

		/**
		 *	@param $sql
		 */
		function prepareStatement($sql) {
			$this->checkClosed();

			require_once ('pdbc/mysql/PreparedStatement.class.php');
			
			if ($this->pstmt && is_object($this->pstmt)) {
				$this->pstmt->setOriginalSQL($sql);
			} else {
				$this->pstmt = new mysql_PreparedStatement($this, $sql);
			}
			#print '$conn->prepareStatement: <br/>';
			#var_dump($this->pstmt);
			print_r($this->pstmt->sqlBatch);
			print '<br/>';
			return $this->pstmt;
		}

		/** 
		 *	� �据$urlProps中的信息打开数据库连接
		 *	@param Array $urlProps
		 *	@return void
		 *	@access private
		 */
		function realConnect($urlProps) {
			$this->conn = @mysql_connect($urlProps['dbhost'].':'
											.$urlProps['dbport'], 
										 $urlProps['dbuser'], 
										 $urlProps['dbpass']
						  );

			if (!$this->conn) {
				$msgbuf = new StringBuffer();
				$msgbuf->append('#ErrorNo '.mysql_errno().': ');
				$msgbuf->append(mysql_error());
				
				$this->throws($msgbuf->toString(), null, EXCEPTION_DIE, null, __FILE__, __LINE__);
			}

			$ret = mysql_select_db($urlProps['dbname'], 
								   $this->conn
				   );

			if (!$ret) {
				$msgbuf = new StringBuffer();
				$msgbuf->append('#ErrorNo '.mysql_errno($this->conn).': ');
				$msgbuf->append(mysql_error($this->conn));
				mysql_close($this->conn);
				return $this->throws($msgbuf->toString(), null, EXCEPTION_DIE, NULL, 'Exceptions', __FILE__, __LINE__);
			}

			$this->isClosed = false;
		}

		/**
		 *	设置当前连接是否为自动提交模式，true为自动提交，false反之
		 *	@param $autoCommit
		 *	@return void
		 *	@access public
		 */
		function setAutoCommit($autoCommit=false) {
			$this->checkClosed();

			if ($this->transactionsSupported) {
				$this->autoCommit = $autoCommit;
			} else {
				if ($autoCommit && !$this->getRelaxAutoCommit()) {
					$this->throws('MySQL Versions Older than 3.23.15, do not support transactions', null, EXCEPTION_DIE);
				}
			}
			$this->autocommit($autoCommit);
		}

		function autocommit($autoCommit) {
			$sql = $autoCommit ? 'SET autocommit = 1' : 'SET autocommit = 0';
			return mysql_query($sql, $this->conn);
		}

		function exeSQL($sql) {
			//not in Connection interface
			//not implement yet
		}

		/**
		 *	关闭当前Connection对象
		 *	@param boolean $calledExplicitly
		 *	@param boolean $issueRollback
		 *	@return void
		 *	@accept protected
		 *	@see close()
		 */
		function realClose($calledExplicitly, $issueRollback) {
			if (!$this->isClosed()) {
				if (!$this->getAutoCommit() && $issueRollback) {
					$this->rollback();
				}
			}

			$this->closeAllOpenStatements();
			$this->openStatements = null;

			mysql_close($this->conn);

			$this->isClosed = true;
		}

		/**
		 *	设置数据库连接是否为只读模式
		 *	@param boolean	$readOnly
		 */
		function setReadOnly($readOnly=true) {
			if (!is_bool($readOnly)) {
				$readOnly = StringUtils::isNumeric($readOnly);

				if ((int)0 != $readOnly || (float)0.0 != $readOnly)
					$this->readOnly = true;
				else
					$this->readOnly = false;
			} else {
				$this->readOnly = $readOnly;
			}
		}

		/**
		 *	设置数据库连接超时时间
		 *	@param int	$conntimeout
		 */
		function setConnectionconntimeout($conntimeout) {
			$this->conntimeout = $conntimeout;
		}
	}
?>