<!DOCTYPE html>
<html>
<head>
<title>Log test</title>
<script type="text/javascript" src="jquery.min.js"></script>
<script type="text/javascript" src="ajaxLogger.js"></script>
<script type="text/javascript">
var logger;
var count = 0;

function println(message) {
	$('#test').append(message + "\n");
}

$(document).ready(function() {
	println("Initialize logger.");
	logger = Logger.getLogger("loggerTest");

	println("Setup 'logme' button");
	$('#logme').click(logme);

	println("\n\nFlooding the log.");
	logger.log(LoggerLevel.INFO, "Testing logger client side  1");
	logger.log(LoggerLevel.INFO, "Testing logger client side  2");
	logger.log(LoggerLevel.INFO, "Testing logger client side  3");
	logger.log(LoggerLevel.INFO, "Testing logger client side  4");
	logger.log(LoggerLevel.INFO, "Testing logger client side  5");
	logger.log(LoggerLevel.INFO, "Testing logger client side  6");
	logger.log(LoggerLevel.INFO, "Testing logger client side  7");
	logger.log(LoggerLevel.INFO, "Testing logger client side  8");
	logger.log(LoggerLevel.INFO, "Testing logger client side  9");
	logger.log(LoggerLevel.INFO, "Testing logger client side 10");
	logger.log(LoggerLevel.INFO, "Testing logger client side 11");
	logger.log(LoggerLevel.INFO, "Testing logger client side 12");

	println("if for instance the log level is 'INFO', we won't see DEBUG and TRACE in the log.");
	println("Logging: FATAL");
	logger.fatal("FATAL");
	println("Logging: ERROR");
	logger.error("ERROR");
	println("Logging: WARN");
	logger.warn("WARN");
	println("Logging: INFO");
	logger.info("INFO");
	println("Logging: DEBUG");
	logger.debug("DEBUG");
	println("Logging: TRACE");
	logger.trace("TRACE");
	println("\nDone.");
});

function logme() {
	count++;
	println("Button pressed: " + count);
	logger.info("Button pressed: " + count);
}
</script>
</head>
<body>
	<h1>Log test</h1>
<?php
require_once ('log4php/Logger.php');
class MyApp {
	private $logger;

	public function __construct() {
		print "<p>MyApp::_construct</p>\n";
		$this->logger = Logger::getLogger('MyApp');
		$this->logger->debug('Hello!');
	}
	 
	public function doSomething() {
		print "<p>MyApp::doSomething</p>\n";
		$this->logger->info("Entering application.");
		$bar = new Bar();
		$bar->doIt();
		print "<p>" . $this->logger->getLevel() . "</p>\n";
		$this->logger->info("Exiting application.");
	}
}

class Bar {
	public function __construct() {
		print "<p>Bar::_construct</p>\n";
		$this->logger = Logger::getLogger('Bar');
		$this->logger->debug('Hello Bar!');
	}
	
	public function doIt() {
		print "<p>Bar::doIt</p>\n";
		$this->logger->info("Entering application bar->doIt.");
		$a = 5;
		$b = 10;
		$c = $a * $b + $a; 
		$this->logger->info("$a * $b + $a = " . $c);
		$this->logger->info("Exiting application  bar->doIt.");
	}
}

// Set up a simple configuration that logs on the console.
Logger::configure('log4php.conf');
$myapp = new MyApp();
$myapp->doSomething();
?>
<button id="logme">Press me!</button>
<pre id="test"></pre>
</body>
</html>
