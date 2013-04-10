<?php
/*
 * ajaxLogger.
 * v1.0
 * Server side PHP script to receive log statements from the slient side Javascript. 
 * License: LGPL
 * Author: Asbjorn Grandt
 */
require_once ('log4php/Logger.php');
Logger::configure('log4php.conf');
class ajaxLogger {
	var $logger;
	var $level;

	function parse($post) {
		$action = $post['action'];

		$this->logger = Logger::getLogger($post['name']);
		$this->level = $this->logger->getLevel();
		if ($this->level == NULL) {
			$this->level = LoggerLevel::getLevelInfo();
			$this->logger->setLevel($this->level);
		}
		if ($action == "init") {
			print '{"result": "ok","level": ' . $this->getLevel()->toInt() . '}';
		} else if ($action == "log") {
			$level = (int)$post['level'];
			$message = $post['msg'];
			$this->logger->log(LoggerLevel::toLevel($level), $message);
			print '{"result":"ok" }';
		} else {
			print '{"result":"error: unknown action: ' . $action . '"}';
		}
	}

	function init($name) {
	}
	
	function getLevel() {
		return $this->level;
	}
}

$aLog = new ajaxLogger();
$aLog->parse($_POST);
?>