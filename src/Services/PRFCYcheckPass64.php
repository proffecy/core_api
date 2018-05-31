<?php

namespace App\Services;

class PRFCYcheckPass64
{

   /**
	* Passwords have to pass thru that function
	* 
	* @param string $password
	*
	*/
	public function decodePass64($password) {
	    
	    $obj = base64_decode($password);
	    
	    $password = (array)json_decode($obj);
	    
	    return $password['password'];
	}

}