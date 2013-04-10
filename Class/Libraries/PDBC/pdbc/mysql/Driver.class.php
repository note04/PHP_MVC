<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: mysqli_Driver.class.php,v 0.1 2004/11/02 17:37:50
	 */

	require_once ('util/StringUtils.class.php');
	require_once ('util/String.class.php');

	class mysql_Driver extends Object {
		/* 需要解析的url */
		var $url = '';

		/* 解析后的url信息，包括主机名、端口、用户名、密� �、数据库等信息 */
		var $urlProps = array();

		/* 默认数据库类型，用来创建数据库连接实例 */
		var $dbtype = 'mysql';

		/* $urlProps中数据库类型索引名 */
		var $dbtype_key	= 'dbtype';

		/* $urlProps中数据库主机名索引名 */
		var $dbhost_key	= 'dbhost';

		/* $urlProps中数据库端口索引名 */
		var $dbport_key	= 'dbport';

		/* $urlProps中数据库用户名索引名 */
		var $dbuser_key	= 'dbuser';

		/* $urlProps中数据库用户密� �索引名 */
		var $dbpass_key	= 'dbpass';

		/* $urlProps中数据库名索引名 */
		var $dbname_key	= 'dbname';

		/* 默认数据库主机名 */
		var $DEFAULT_HOST= 'localhost';

		/* 默认数据端口 */
		var $DEFAULT_PORT= '3306';

		/**
		 *	构� 函数
		 *	$param String $url
		 */
		function &mysql_Driver(&$url) {
			if ($this->acceptsURL($url))
				$this->connect($url);
			return null;
		}
		
		/**
		 *	析构函数
		 */
		function __destruct() {
			$this->url = '';
			$this->urlProps = array();
		}

		/**
		 *	@param $url
		 *	@return 如果$url合法返回true，否则返回false
		 */
		function acceptsURL(&$url) {
			return ($urlProps != null && is_array($urlProps));
		}

		/**
		 *	获取mysqli_Connection的实例
		 *	@param String $url
		 *	@param $info
		 *	@return Object mysqli_Connection
		 */
		function connect(&$url, $info=NULL) {
			if (($this->urlProps = $this->parseURL($url)) == NULL)
				return NULL;

			require_once ('pdbc/mysql/Connection.class.php');
			/* singleton get unique instance of mysql_Connection */
			return mysql_Connection::getInstance($this);
		}

		/**
		 *	获取主版本号(Major Version)
		 *	@return int (Major Version)
		 */
		function getMajorVersion() {
			return (int)0;
		}

		/**
		 *	获取Minor Version
		 *	@return int (Minor Version)
		 */
		function getMinorVersion() {
			return (int)1;
		}

		/**
		 *	获取解析后的url信息
		 *	@return Array urlProps
		 */
		function getUrlProps() {
			return $this->urlProps;
		}

		/**
		 *	获取版本
		 *	@return String
		 */
		function getVersion() {
			return $this->getMajorVersion().'.'.$this->getMinorVersion();
		}
		
		function getDriverProperty($property_key=NULL) {
			if ((bool)$property_key)
				return $this->urlProps;
			else
				return $this->urlProps[$property_key];
		}

		/**
		 *	Document me!
		 */
		function getPropertyInfo(&$url, $info=NULL) {
			//not implement yet
		}

		/**
		 *	检查数据库服务器是否符合pdbc规范
		 *	@return boolean
		 */
		function pdbcCompliant() {
			//mysql is not compliant with pdbc specification
			return false;
		}

		/**
		 *	解析� 入的url，结果存入urlProps
		 *	@param String $url
		 *	@return String[] $urlProps
		 */
		function parseURL(&$url) {
			if (NULL === $url || !(bool)$url)
				return NULL;

			$surl = new String($url);
			#if (!StringUtils::startsWith($url, 'pdbc:', true)) { //StringUtils model
			if (!$surl->startsWith('pdbc:mysql')) {
				if ((int)5 == $debug)
					print 'url??';
				$this->throws('url??');
				//return null;
			}

			$pdbc = strpos($url, ':');
			$dslash = strpos($url, '://');

			if ($pdbc !== false && $dslash !== false) {
				$dbtype = substr($url, ($pdbc + 1), ($dslash - strlen('pdbc:')));
				$dbtype[$dbtype_key] = $dbtype;
			}

			$pos = strpos($url, '?');
			if (false !== $pos) {
				$userPassPair = substr($url, ($pos + 1));
				$url = substr($url, 0, $pos);
			}

			$userPass = $this->parseUserPass($userPassPair);

			$url = substr($url, 13);
			
			$slash = strpos($url, '/');
			if ($slash !== false) {
				$hostPortPair = substr($url, 0, $slash);
				$hostPort = $this->parseHostPort($hostPortPair);
			} 

			$dbname = array();
			$dbname[$this->dbname_key] = substr($url, ($slash + 1));

			$urlProps = array_merge($dbname, $hostPort, $userPass);

			return $urlProps;
		}

		/**
		 *	解析主机名、端口片段
		 *	@param String $hostPortPair
		 *	@return String[] $hostPort
		 */
		function parseHostPort(&$hostPortPair) {
			$dbhost = 'localhost';
			$hostPort = array();

			$pos = strpos($hostPortPair, ':');
			if ($pos !== false) {
				$dbhost = substr($hostPortPair, 0, $pos);
				$dbport = substr($hostPortPair, ($pos + 1));
			} else {
				if (is_string($hostPortPair) && !$hostPortPair) {
					$dbhost = $hostPortPair;
					$dbport = $this->DEFAULT_PORT;
				} else if (is_int($hostPortPair)) {
					$dbhost = $this->DEFAULT_HOST;
					$dbport = $hostPortPair;
				} else {
					$dbhost = $this->DEFAULT_HOST;
					$dbport = $this->DEFAULT_PORT;
				}
			}

			$hostPort[$this->dbhost_key] = $dbhost;
			$hostPort[$this->dbport_key] = $dbport;

			return $hostPort;
		}

		/**
		 *	解析用户名、密� �片段
		 *	@param String $userPassPair
		 *	@return String[] $userPass
		 */
		function parseUserPass(&$userPassPair) {
			$userPass = array();

			if ((bool)$userPassPair) {
				require_once ('util/StringTokenizer.class.php');
				$userPassToken = new StringTokenizer($userPassPair, '&');
				$i = 0;
				while ($userPassToken->hasMoreTokens()) {
					$up = new StringTokenizer($userPassToken->nextToken(), '=');

					if ($up->hasMoreTokens()) {
						$param = $up->nextToken();
					}

					if ($up->hasMoreTokens()) {
						$value = $up->nextToken();
					}

					$userPass[$param] = $value;
				}
			} else {
				$userPass[$this->dbuser_key] = '';
				$userPass[$this->dbpass_key] = '';
			}

			return $userPass;
		}
	}
?>