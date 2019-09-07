<?php

if (!defined('IS_UNAUTHORIZED')) {
    define('IS_UNAUTHORIZED', IS_EBASE + 1);
    define('IS_SERVERERROR', IS_EBASE + 2);
    define('IS_HTTPERROR', IS_EBASE + 3);
    define('IS_INVALIDDATA', IS_EBASE + 4);
    define('IS_DEVICE_MISSING', IS_EBASE + 5);
}

trait RobonectLibrary
{
	public function GetMowerStatus()
    {
		$getDataUrl = array(
			"status"  => "/json?cmd=status",
			"version" => "/json?cmd=version",
			"error"   => "/json?cmd=error"
		);
			
		$content = $this->url_get_contents($getDataUrl['status'], $debug);

		$status = json_decode($content, true);

		if($status['successful'] == true){
			$name = $status['name'];
			
			return $name;
		}
		else {
			return false;
		}
    }

    public function url_get_contents($url, $debug = false)
	{	
		$ip = $this->ReadPropertyString('ip');
		$user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');

        if($debug == true) echo $ip;
        if($debug == true) echo $user;
        if($debug == true) echo $password;

		if($url !== ""){
            
            if($debug == true) echo $url;

			$options = array(
				'http' => array(
					'method' => "GET",
					'header' => "Connection: close\r\n". 
						"Authorization: Basic ".base64_encode($user.":".$password)."\r\n",
					'timeout' => 3
				)
            );
            
            if($debug == true) echo "http://".$ip.$url;
			
			$context = stream_context_create( $options );
			$content = @file_get_contents("http://".$ip.$url, false, $context);

			if(!$content){
				if($debug == true) echo "Meldung von \"url_get_contents\": ".error_get_last()['message']."\n";
				return false;
			}
			else{
				if($debug == true) echo $http_response_header[0]."\n\n";
				if($debug == true) echo $content;
				
				if(substr($http_response_header[0], 9, -3) == 200) return $content;
				else return false;
			}
		}
		else{
			if($debug == true) echo "Meldung von \"url_get_contents\": Logindaten falsch - Daten in keinem Array -> array(\"user\"=> \"username\", \"pass\"=> \"passwort\")\n";
			return false;
		}
	}
}
