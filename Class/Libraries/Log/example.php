<?php
/*

============================================
FileName	: example.php
Author		: Hatem Mohamed (http://www.itmideast.com)
Mail		: developer-php@hotmail.com
Country		: Egypt
Class Name	: Log Class
Date		: May 2010
============================================
 */



try
{
    /*** a new logger instance ***/
    $log = logger::getInstance();
    /*** the file to write to ***/
    $log->logfile = '/tmp/errors.log';
    /*** write an error message with filename and line number ***/
    $log->write('An error has occured', __FILE__, __LINE__);
}
catch(Exception $e)
{
    echo $e->getMessage();
}

?>
