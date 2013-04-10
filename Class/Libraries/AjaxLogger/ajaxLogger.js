/**
 * ajaxLogger.
 * v1.0
 * Client side Javascript to send log statements to the server side logger. 
 * License: LGPL
 * Author: Asbjorn Grandt
 */
var LoggerLevel = {
		OFF:2147483647,
		FATAL:50000,
		ERROR:40000,
		WARN:30000,
		INFO:20000,
		DEBUG:10000,
		TRACE:5000,
		ALL:-2147483647
};

var LoggerSettings = {
		path:'ajaxLogger.php'
};

var Logger = {
		name:"ajaxLogger",
		loglevel: LoggerLevel.OFF,
		isInitialized: false,
		isIdle: true,
		queue: new Array(),
		
		/**
		 * @param appname Name of the logger.
		 * @returns an instance of the logger, though it's not really used, in JS the logger is sadly a singleton.
		 */
		getLogger:function(appname) {
			$.ajax({
				type: 'POST',
				url: LoggerSettings.path,
				data: {
					'action':'init',
					'name':appname
				},
				dataType: 'json',
				success: function(msg) {
					if (msg.result == "ok") {
						Logger.setLevel(msg.level);
						Logger.setName(appname);
						Logger.isInitialized = true;
						Logger.spoolQueue();
					}
				}
			});
			return Logger;
		},

		/**
		 * Send a log message
		 * @param level
		 * @param message
		 */
		log:function(level, message) {
			if (Logger.isInitialized && Logger.isIdle) {
				if (Logger.isEnabledFor(level)) {
					Logger.isIdle = false;
					$.ajax({
						type: 'POST',
						url: LoggerSettings.path,
						data: {
							'action':'log',
							'name':Logger.getName(),
							'level':level,
							'msg':message
						},
						dataType: 'json',
						success: function(msg) {
							Logger.isIdle = true;
							Logger.spoolQueue();
						}
					});
				}
			} else {
				Logger.queue[Logger.queue.length] = {"level":level,"message":message};
			}
		},

		spoolQueue:function(level, message) {
			if (Logger.queue.length > 0) {
				var m = Logger.queue.shift();
				Logger.log(m.level, m.message);
			}
		},

		setName:function(appName) {
			Logger.name = appName;
		},

		getName:function() {
			return Logger.name;
		},

		setLevel:function(level) {
			Logger.loglevel = level;
		},

		getLevel:function() {
			return Logger.loglevel;
		},

		fatal:function(message) {
			Logger.log(LoggerLevel.FATAL, message);
		},

		error:function(message) {
			Logger.log(LoggerLevel.ERROR, message);
		},

		warn:function(message) {
			Logger.log(LoggerLevel.WARN, message);
		},

		info:function(message) {
			Logger.log(LoggerLevel.INFO, message);
		},

		debug:function(message) {
			Logger.log(LoggerLevel.DEBUG, message);
		},

		trace:function(message) {
			Logger.log(LoggerLevel.TRACE, message);
		},

		isEnabledFor:function(level) {
			return level >= Logger.getLevel();
		}
};

