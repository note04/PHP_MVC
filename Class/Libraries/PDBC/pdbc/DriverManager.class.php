<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: DriverManager.class.php,v 0.1 2004/11/02 17:36:00
	 */

	require_once ('util/Vector.class.php');
	require_once ('util/String.class.php');

	class DriverManager extends Object {
		var $conn				= null;
		var $timeout			= null;
		var $logger				= null;
		var $urlProps			= null;
		var $initialized		= false;
		var $driverClassName	= null;
		var $drivers			= null;
		var $dbtype				= 'mysql';
		var $dbtype_key			= 'dbtype';
		var $dbhost_key			= 'dbhost';
		var $dbport_key			= 'dbport';
		var $dbuser_key			= 'dbuser';
		var $dbpass_key			= 'dbpass';
		var $dbname_key			= 'dbname';
		var $DEFAULT_HOST		= 'localhost';
		var $DEFAULT_PORT		= '3306';

		/**
		 *	Construction
		 *	@aceess private
		 */
		function DriverManager() {;}

		/**
		 *	Destruction
		 */
		function __destruct() {;}

		/**
		 *	Get an logical connection from db
		 *	@param String $url
		 *	@return Connection
		 *	@access public
		 */
		function getConnection($url) {
			if (null === $url || '' == trim($url)) {
				return Object::throws('The url cannot be null');
			}

			$dbtype = DriverManager::parseURL($url);

			require_once ('pdbc/'.$dbtype.'/Driver.class.php');
			$driverclass = $dbtype.'_Driver';
			$driver =& new $driverclass($url);

			$conn = $driver->connect($url);

			if (($conn != null) && (is_object($conn)))
				return $conn;
			
			return $conn;
		}

		/**
		 *	Deregister the driver that specified
		 *	@param String $driver
		 *	@return void
		 *	@access public
		 */
		function deregisterDriver($driver) {}

		/**
		 *	Register the driver that specified
		 *	@param String $driver
		 *	@return void
		 *	@access public
		 */
		function registerDriver($driver) {}

		/**
		 *	Parse url into array
		 *	@param String $url
		 *	@return void
		 *	@access public
		 */
		function parseURL(&$url) {
			if (null === $url || !(bool)$url)
				return null;

			$surl = new String($url);
			if (!$surl->startsWith('pdbc:')) {
				if ((int)5 == $debug)
					print 'url??';
				trigger_error('url??');exit();
			}

			$pdbc = strpos($url, ':');
			$dslash = strpos($url, '://');

			if ($pdbc !== false && $dslash !== false) {
				$dbtype = substr($url, ($pdbc + 1), ($dslash - strlen('pdbc:')));
				return $dbtype;
			}

			return null;
		}

		/**
		 *	Set up timeout of connection to db server
		 *	@param int $timeout
		 *	@return void
		 *	@access public
		 */
		function setLoginTimeout($timeout) {
			$this->timeout = $timeout;
		}

		/**
		 *	Get timeout of connection to db server
		 *	@return int
		 *	@access public
		 */
		function getLoginTimeout() {
			return $this->timeout;
		}

		/**
		 *	Set up logger class and initial logger class
		 *	@return void
		 *	@access public
		 */
		function setLogger($logger) {
			if (file_exists($logger.'class.php')) {
				require_once ($logger.'class.php');

				$this->logger = new $logger;
			}
		}
		
		/**
		 *	Get logger class name of this
		 *	@return String
		 *	@access public
		 */
		function getLogger() {
			return $this->$logger;
		}
	}

	class DriverInfo extends Object {
		var $driver;
		var $driverClassName;

		/**
		 *	Set up driver class name and url
		 *	@param String $driverClassName
		 *	@param String $url
		 *	@return void
		 *	@access public
		 */	
		function setDriver(&$driverClassName, $url) {
			if (is_Object($driverClassName)) {
				$this->driver = $driverClassName;
			} else if (is_string($driverClassName)) {
				$this->driver = new $driverClassName($url);
				$this->driverClassName = $driverClassName;
			} else {}
		}

		/**
		 *	Get current driver object
		 *	@return Driver
		 *	@access public
		 */
		function getDriver() {
			return $this->driver;
		}

		/**
		 *	Return a string description
		 *	@return String
		 *	@access public
		 */
		function toString() {
			return sprintf('driver[className=%s, %s]', $this->driverClassName, $this->driver);
		}
	}
?>