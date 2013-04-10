<?php
class Login{
    private $user;
    private $password;
    
    function __construct($u,$p){
		/* setting the private vars */
        $this->__set($this->user,$u);
		$this->__set($this->password,$p);
		/* do the login action */
		$this->doLogin();
	}
	
    /* Magic methods __get and __set */	
    function __set($var,$value){
        if (property_exists($this, $var)) {
			/* value passed by reference */	
      $this->$var &= $value;
    	}
	}
	
    function __get($var){
        if (property_exists($this, $var)) {
      return $this->$var;
    	}
	}
   /* === *** === */ 
	private function doLogin(){
		/* Connect database */		
		$link = mysql_connect("127.0.0.1","root","");
		/* if Connected select database */	
		if($link){
			/* if selected database check in database the user data */	
			if(mysql_select_db("DATABASE",$link)){
				/* the query happens */	
				$query = "SELECT * FROM users where user = '" . $this->__get($this->user) . "' and password = '" . md5($this->password) . "'";
				$resultset = mysql_query($query);
				/* if have a row in the query the acces is granted */	
				if(mysql_num_rows($resultset) > 0){
					/* your code to logged users here */	
					return true;
				}else{
					return false;
				}
			}
		}
		
	}
	


}

?>