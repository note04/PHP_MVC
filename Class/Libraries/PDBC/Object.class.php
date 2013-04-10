<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: Object.class.php,v 0.1 2004/11/02 17:34:00
	 *	@since 0.1
	 */

	define ('PHP_CLASS_SUFFIX', '.class.php');
	define ('ENTER', '<br/>');

	class Object {
		/**
		 *	Construction
		 */
		function &Object() {;}
		
		/**
		 *	Destruction
		 */
		function __destruct() {
			printf ('%s', 'Object->__destruct');
		}

		/**
		 *	Indicates whether the given parameter is instance of Exceptions
		 *	@param String $exp
		 *	@return boolean
		 *	@access public
		 */
		function isException($exp) {
			if (is_a($exp, 'Exceptions') || !$exp)
				return true;
			return false;
		}

		/**
		 *	Retrieves a cloned object
		 *	@return object
		 *	@access public	
		 */
		function __clone() {
			#return clone $this;  //php5 style
			return $obj = $this;
		}

		/**
		 *	Print debug message
		 *	@return void
		 *	@access protected
		 */
		function debug($message) {
			printf ('%s%s', $message, $GLOBALS['enter']);
		}

		/**
		 *	@params $obj
		 *	@return boolean
		 *	@access public
		 */
		function equals($obj) {;}

		/**
		 *	Garbage collection
		 *	@return void
		 *	@access protected
		 */
		function finalize() {
			$this->__destruct();
		}

		/**
		 *	Generate unqiue key for object
		 *	@param String $prefix
		 *	@param boolean $lcg
		 *	@access public
		 */
		function genUniqueId($prefix=null, $lcg=true) {
			if (null === $prefix)
				$prefix = microtime();
			return md5(uniqid($prefix, $lcg));
		}

		/**
		 *	Document me!
		 *	@return float
		 *	@access public
		 */
		function getmicrotime() {
			list($usec, $sec) = explode(" ",microtime());
			return ((float)$usec + (float)$sec);
		}

		/**
		 *	Get the name of this class (e.g. Object)
		 *	@return String
		 *	@access public
		 */
		function getClass() {
			//return get_class($this);
			return __CLASS__;
		}

		/**
		 *	Get current version (e.g. 0.3-dev)
		 *	@return String
		 *	@access public
		 */
		function getVersion() {
			return sprintf('%s.%s%s', $this->getMajorVersion(), $this->getMinorVersion(), $this->getVersionStatus());
		}
		
		/**
		 *	Get major version (e.g. 0)
		 *	@return int
		 *	@access public
		 */
		function getMajorVersion() {
			return (int)0;
		}
		
		/**
		 *	Get major version (e.g. 3)
		 *	@return int
		 *	@access public
		 */
		function getMinorVersion() {
			return (int)3;
		}

		/**
		 *	Get major version (e.g. 'stable')
		 *	@return String
		 *	@access public
		 */
		function getVersionStatus() {
			return '-dev';
		}

		/**
		 *	Print current version informaction
		 *	@return void
		 *	@access public
		 */
		function pGetVersion() {
			printf ('%s%s', $this->getVersion(), ENTER);
		}

		/**
		 *	Load specified php extension
		 *	@return boolean
		 *	@access public
		 */
		function loadExtension($ext) {
			if (!extension_loaded($ext)) {
				if ((ini_get('enable_dl') != 1) || (ini_get('safe_mode') == 1)) {
					return false;
				}
				if (OS_WINDOWS) {
					$suffix = '.dll';
				} elseif (PHP_OS == 'HP-UX') {
					$suffix = '.sl';
				} elseif (PHP_OS == 'AIX') {
					$suffix = '.a';
				} elseif (PHP_OS == 'OSX') {
					$suffix = '.bundle';
				} else {
					$suffix = '.so';
				}
				return @dl('php_'.$ext.$suffix) || @dl($ext.$suffix);
			}
			return true;
		}

		/**
		 *	Throw new eception in specified mode
		 *	@param String $message
		 *	@param String $code
		 *	@param String $mode
		 *	@param String[] $option
		 *	@param String $file
		 *	@param int $line
		 *	@param boolean $skipMsg
		 *	@param Object $ex
		 *	@param boolean $debug
		 *	@return Object
		 *	@access public
		 */
		function throws($message  = null,
						$code = null,
						$mode = null,
						$option = null,
						$file = __FILE__,
						$line = __LINE__,
						$skipMsg = false,
						$ex = null,
						$debug = false) {
			if (is_object($msg) && is_a($msg, 'Exceptions')) {
				$code = $message->getCode();
				$mode = $message->getMode();
				$option = $message->getOption();
				$ex = $ex->instanceOfClass();
				$message = $message->getMessage();
			}

			if (null !== $ex) {
				$ex_class = $ex;
				if (!class_exists($ex))
					return new Exceptions("Class '$ex' not found", $code, EXCEPTION_DIE, null, __FILE__, __LINE__);
			} else {
				$ex_class = 'Exceptions';
			}

			if ($skipMsg) {
				return new $ex_class(null, $code, $mode, $option, $file, $line, $debug);
			} else {
				return new $ex_class($message, $code, $mode, $option, $file, $line, $debug);
			}
		}

		/**
		 *	Pause current process and wait for specified seconds, then continue
		 *	@paran long $timeout
		 *	@param int $nanos
		 *	@return void
		 *	@access public
		 */
		function wait($timeout, $nanos) {
			$timeout = is_int($timeout) ? $timeout: (int)$timeout;
			sleep($timeout);
		}
	}

	define ('NO_EXCEPTION',			0);
	define ('EXCEPTION_DIE',		1);
	define ('EXCEPTION_PRINT',		2);
	define ('EXCEPTION_RETURN',		3);
	define ('EXCEPTION_TRIGGER',	4);
	define ('EXCEPTION_CALL_BACK',	5);

	class Exceptions extends Object {
		/* Mode of error */
		var $mode		= EXCEPTION_RETURN;

		/* Trigger_error level */
		var $level		= E_USER_NOTICE;

		/* Error code */
		var $code		= -1;

		/* Error message */
		var $message	= '';

		/* Debug backtrace */
		var $backtrace	= null;

		/* Callback method */
		var $callback	= null;

		/* File info */
		var $file		= __FILE__;

		/* Line info */
		var $line		= __LINE__;

		/**
		 *	Construction
		 *	@param	String	$message
		 *	@param	int		$code
		 *	@param	int		$mode
		 *	@param	mixed	$option
		 */
		function Exceptions($message = 'Unkown Exception',
						    $code = null,
						    $mode = null,
						    $option = null,
							$file = __FILE__,
							$line = __LINE__,
							$debug = false) {

			if (null === $mode) {
				$level = E_USER_ERROR;
			} else {
				$this->mode = $mode;
				if ($mode & EXCEPTION_CALL_BACK) {
					$this->callback = $option['callback'];
				} else if ($option != null) {
					$level = $option['level'];
				}
			}

			$this->code = (null !== $code) ? $code: -1;
			$this->level = $level;
			$this->message = $message;
			$this->file = (!$file) ? '\'unknow file\'': $file;
			$this->line = (!$line) ? (int)0: $line;
			$this->debug = $debug;

			$this->debug();

			switch ($this->mode) {
				/* Return mode: return an new Exceptions object */
				case EXCEPTION_RETURN :
					return new $this($message);
					break;
				
				/* Error print mode: print error message, not stop running */
				case EXCEPTION_PRINT :
					printf ($this->getFormat($option), $this->getMessage());
					break;

				/* Error die mode: print error message and stop program */
				case EXCEPTION_DIE :
					die(sprintf($this->getFormat($option), $this->getMessage()));
					break;

				/* Trigger_error mode: using build in php trigger_error mode */
				case EXCEPTION_TRIGGER :
					trigger_error($this->getMessage(), $this->level);
					break;
				
				/* Callback mode: invoke callback function that user setup */
				case EXCEPTION_CALL_BACK :
					if (is_callable($this->callback)) {
						call_user_func($this->callback, $this);
					}
					break;

				/* Exception mode: throw new exception */
				case EXCEPTION_EXCEPTION :
					// Need php5.0.0 or above
					if (version_compare('5.0.0', phpversion(), '<=')) {
						eval("throw new EXCEPTION($this->getMessage();)");
					} else {
						$message = 'Your php version ('.phpversion().') < 5.0.0';
						return new $this($message, null, EXCEPTION_NOTICE);
					}
					break;
			}
		}

		/**
		 *	Destruction
		 */
		function __destruct() {;}

		/**
		 *	Print debug message
		 *	@return void
		 */
		function debug() {
			if ($GLOBALS['debug'] >= 5)
				Object::debug(sprintf('Exception in %s at line %s<br/>', $this->file, $this->line));

			$this->backtrace = debug_backtrace();
		}

		/**
		 *	Get buildin php debug_backtrace infomation
		 *	@param String $param
		 *	@return String
		 *	@access public
		 */
		function getDebugBacktrace($param=null) {
			if (!$param || null === $param)
				return $this->backtrace;
			return $this->backtrace[$param];
		}
		
		/**
		 *	Get print mode of EXCEPTION_PRINT
		 *	@param	mixed	$option
		 *	@return String
		 *	@access public
		 */
		function getFormat(&$option) {
			if (is_null($option) || is_int($option))
				return '%s';
			else
				return $option;
		}
	
		/**
		 *	Get mode of current Exceptions object
		 *	@return int
		 *	@access public
		 */
		function getMode() {
			return $this->mode;
		}

		/**
		 *	Get message of current Exceptions object
		 *	@return String
		 *	@access public
		 */
		function getMessage() {
			return $this->message;
		}

		/**
		 *	Get mode of EXCEPTION_TRIGGER and error level
		 *	@return int
		 *	@access public
		 */
		function getLevel() {
			return $this->level;
		}
	
		/**
		 *	Get error code of current Exceptions object
		 *	@return int
		 *	@access public
		 */
		function getCode() {
			return $this->code;
		}
	
		/**
		 *	Get callback function of current Exceptions object
		 *	@return int
		 *	@access public
		 */
		function getCallback() {
			return $this->callback;
		}

		/**
		 *	Get instance name of current Exceptions object
		 *	@return String
		 *	@access public
		 */
		function instanceOfClass() {
			return get_class($this);
		}

		/**
		 *	Set debug level
		 *	@return void
		 *	@access public
		 */
		function setDebug($debug) {
			$this->debug = $debug;
		}

		/**
		 *	Return a string description of this object
		 *	@return String
		 *	@access public
		 */
		function toString() {
			$levels = array(E_USER_NOTICE => 'e_user_notice',
							E_USER_WARNING => 'e_user_warning',
							E_USER_ERROR => 'e_user_error'
					  );
			$modes = array(EXCEPTION_RETURN => 'return',
						   EXCEPTION_PRINT => 'print',
						   EXCEPTION_DIE => 'die',
						   EXCEPTION_TRIGGER => 'trigger',
						   EXCEPTION_EXCEPTION => 'exception',
						   EXCEPTION_CALL_BACK => 'callback'
					 );

			return sprintf('class: "%s"<br/> 
							message: "%s"<br/> 
							code: "%s"<br/> 
							mode: "%s"<br/> 
							level: "%s"<br/> 
							callback: "%s"<br/>', 		
							$this->instanceOfClass(), 
							$this->message, 
							$this->code ? $this->code: 'null', 
							$modes[$this->mode], 
							$levels[$this->level], 
							$this->callback ? $this->callback: 'null');
		}
	} // End class


	// LicenseManager class
	class LicenseManager extends Object {
		var $license = 'license/license';

		/**
		 *	Constructor
		 */
		function LicenseManager() {;}

		/**
		 *	Destructor
		 */
		function __destruct() {;}

		/**
		 *	Validate given license object
		 *	@param License $license
		 *	@return boolean
		 *	@access public static
		 */
		function validate($license=null) {
			if (!$license || !is_object($license))
				$license = new License();

			$type = $license->getType();

			if ('Evaluation' == $type) {
				$expire = str_replace('-', '', $license->getExpireDate());
				$now = Date('Ymd');
				if (!$expire || !$now || $now > $expire)
					Object::throws('license already expired, please buy new license', null, EXCEPTION_DIE);
			} else if ('Enterprise' == $type) {
				// Improve me
			} else {
				Object::throws('license invalidate, please buy new license', null, EXCEPTION_RETURN);
			}

			if (LicenseManager::verify($type, $license->getSignature()))
				return true;

			Object::throws('license invalidate, please buy new license', null, EXCEPTION_RETURN);
		}

		/**
		 *	Verify given signature is validate
		 *	@param String $type
		 *	@param String $signature
		 *	@return boolean
		 *	@access public
		 */
		function verify($type, $signature) { // Improve me
			if (!((bool)$signature))
				return false;

			// verify signature
			return true;
		}

		/**
		 *	Return string as description of this object
		 *	@return String
		 *	@access public
		 */
		function toString() {
			sprintf('%s', 'Shine media licenseManager');
		}
	} // End class


	// License class
	class License extends Object {
		var $license = '/license/license';
		var $options = array('product' => '',
							 'version' => '',
							 'company' => '',
							 'type' => '',
							 'createDate' => '',
							 'expiresDate' => '',
							 'signature' => '');

		function License() {
			$this->load();
		}

		function __destruct() {
		}

		/**
		 *	Load license file
		 *	@return void
		 *	@access public
		 */
		function load() {
			$license_file = $_SERVER['DOCUMENT_ROOT'].$this->license;

			if (!file_exists($license_file)) {
				$this->throws('License file not found!', null, EXCEPTION_DIE);
			}

			$fp = @fopen($license_file, 'r');
			if (!$fp) {
				$this->throws('open license file failed', null, EXCEPTION_DIE);
			}

			while (!feof($fp)) {
				$license_string .= fgets($fp, 4096);
			}
			fclose($fp);

			$this->parseLicnese($license_string);
		}

		/**
		 *	Parse license file information
		 *	@return void
		 *	@access private
		 */
		function parseLicnese($license_string) { // Improve me
			global $tags, $contents;

			$license_xml = base64_decode($license_string);

			if (!$license_string || !$license_xml)
				return;
			
			if (function_exists('simplexml_load_string')) { // Use simplexml functions
				$xml = simplexml_load_string($license_xml);
				$this->options['product']		= $xml->product;
				$this->options['version']		= $xml->version;
				$this->options['company']		= $xml->company;
				$this->options['type']			= $xml->type;
				$this->options['createDate']	= $xml->createDate;
				$this->options['expiresDate']	= $xml->expiresDate;
				$this->options['signature']		= $xml->signature;
			} else if (function_exists('xml_parser_create')) { // Use expat xml functions
				// Inner function, parse start element tag
				function startElement($parser, $name, $attrs) {
					global $tags;

					if ('license' != $name)
						$tags[] = $name;
				}

				// Inner function, parse end element tag
				function endElement($parser, $name) {
					// Do nothing now...
				}

				// Inner function, parse element content
				function characterData($parser, $data) {
					global $contents;
					
					if (trim($data))
						$contents[] = $data;
				}

				// Create new expat xml parser
				$xml_parser = xml_parser_create();
				// Set expat case folding, if true uppercase, false keep;
				xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
				xml_set_element_handler($xml_parser, "startElement", "endElement");
				xml_set_character_data_handler($xml_parser, "characterData");

				if (!xml_parse($xml_parser, $license_xml)) {
					die(sprintf("XML parse error: %s at line %d",
								xml_error_string(xml_get_error_code($xml_parser)),
								xml_get_current_line_number($xml_parser)));
				}

				// Do not forget free parser resource, ;)
				xml_parser_free($xml_parser);
				
				if (function_exists('array_combine')) { // This function supported in php5
					$details = array_combine($tags, $contents);
				} else {
					$details = array();
					foreach($tags as $idx => $val) {
						$this->options[$val] = $contents[$idx];
					}
				}
			} else if ($GLOBALS['php4']) { // Use php4 domxml function
				$dom = domxml_open_mem($license_xml);
				$root = $dom->document_element();
				$children = $root->child_nodes();
				foreach ($children as $idx => $node) {
					if (is_a($node, 'domelement')) {
						$this->options[$node->tagname()] = $node->get_content();
					}
				}
			} else if ($GLOBALS['php5']) { // Use php5 domxml function
			} else {;} // Do nothing
		}

		/**
		 *	Get lincese information
		 *	@return String[]
		 *	@access public
		 */
		function getInfo() {
			return $this->options;
		}

		/**
		 *	Get company name that presented in license information
		 *	@return String
		 *	@access public
		 */
		function getCompany() {
			return $this->options['company'];
		}

		/**
		 *	Get license file creation date that presented in license information
		 *	@return String
		 *	@access public
		 */
		function getCreateDate() {
			return $this->options['createDate'];
		}

		/**
		 *	Get exppire date that presented in license information
		 *	@return String
		 *	@access public
		 */
		function getExpireDate() {
			return $this->options['expiresDate'];
		}

		/**
		 *	Get product's name that presented in license information
		 *	@return String
		 *	@access public
		 */
		function getProduct() {
			return $this->options['product'];
		}

		/**
		 *	Get type of product that presented in license information
		 *	@return String
		 *	@access public
		 */
		function getType() {
			return $this->options['type'];
		}

		/**
		 *	Get version that presented in license information
		 *	@return String
		 *	@access public
		 */
		function getVersion() {
			return $this->options['version'];
		}

		/**
		 *	Get signature string
		 *	@return String
		 *	@access public
		 */
		function getSignature() {
			return $this->options['signature'];
		}
	} // End class
?>