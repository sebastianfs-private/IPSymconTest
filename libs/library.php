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
			
		$content = $this->url_get_contents($getDataUrl['status']);

		$status = json_decode($content, true);

		$this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);

		if($status['successful'] == true){
			$name = $status['name'];
			
			return $name;
		}
		else {
			return false;
		}
    }

    public function url_get_contents(string $url)
	{	
		$ip = $this->ReadPropertyString('ip');
		$user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');

        if($url !== ""){
            
            $options = array(
				'http' => array(
					'method' => "GET",
					'header' => "Connection: close\r\n". 
						"Authorization: Basic ".base64_encode($user.":".$password)."\r\n",
					'timeout' => 3
				)
            );
            
            $context = stream_context_create( $options );
			$content = @file_get_contents("http://".$ip.$url, false, $context);

			if(!$content){
				return false;
			}
			else{
				if(substr($http_response_header[0], 9, -3) == 200) return $content;
				else return false;
			}
		}
		else{
			return false;
		}
	}
}
