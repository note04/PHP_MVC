<?php
require_once "login.php";
$login = new Login("hoheckell.info@gmail.com","123456");
if($login){
	echo "OK";
}else{
	echo "ERROR";
}
?>