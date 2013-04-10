<?php
/*
This class provides simple logging functionality but logging error messages to a log file.
 * The name of the log file can be set if multiple log files need to be written for different scripts.
 * This form of logging is a great debugging tool for those using ajax in their scripts as errors are often hidden.

============================================
FileName	: log.class.php
Author		: Hatem Mohamed (http://www.itmideast.com)
Mail		: developer-php@hotmail.com
Country		: Egypt
Class Name	: Log Class
Date		: May 2010
============================================
 */



class logger
{
    /*** Declare instance ***/
    private static $instance = NULL;

    /**
     *
     * @Constructor is set to private to stop instantion
     *
     */
    private function __construct()
    {
    }

    /**
     *
     * @settor
     *
     * @access public
     *
     * @param string $name
     *
     * @param mixed $value
     *
     */
    public function __set($name, $value)
    {
        switch($name)
        {
            case 'logfile':
            if(!file_exists($value) || !is_writeable($value))
            {
                throw new Exception("$value is not a valid file path");
            }
            $this->logfile = $value;
            break;

            default:
            throw new Exception("$name cannot be set");
        }
    }

    /**
     *
     * @write to the logfile
     *
     * @access public
     *
     * @param string $message
     *
     * @param string $file The filename that caused the error
     *
     * @param int $line The line that the error occurred on
     *
     * @return number of bytes written, false other wise
     *
     */
    public function write($message, $file=null, $line=null)
    {
        $message = time() .' - '.$message;
        $message .= is_null($file) ? '' : " in $file";
        $message .= is_null($line) ? '' : " on line $line";
        $message .= "\n";
        return file_put_contents( $this->logfile, $message, FILE_APPEND );
    }

    /**
    *
    * Return logger instance or create new instance
    *
    * @return object (PDO)
    *
    * @access public
    *
    */
    public static function getInstance()
    {
        if (!self::$instance)
        {
            self::$instance = new logger;
        }
        return self::$instance;
    }


    /**
     * Clone is set to private to stop cloning
     *
     */
    private function __clone()
    {
    }

} /*** end of log class ***/

?>