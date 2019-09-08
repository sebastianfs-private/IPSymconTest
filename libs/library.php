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
	public function GetMowerData(string $type)
    {
		$getDataUrl = array(
			"status"  => "/json?cmd=battery",
			"ext"  => "/json?cmd=ext",
			"hour"  => "/json?cmd=hour",
			"motor"  => "/json?cmd=motor",
			"portal"  => "/json?cmd=portal",
			"push"  => "/json?cmd=push",
			"timer"  => "/json?cmd=timer",
			"wlan"  => "/json?cmd=wlan",
			"name"  => "/json?cmd=name",
			"weather"  => "/json?cmd=weather",
			"wire"  => "/json?cmd=wire",
			"gps"  => "/json?cmd=gps",
			"door"  => "/json?cmd=door",
			"remote"  => "/json?cmd=remote",
			"status"  => "/json?cmd=status",
			"version" => "/json?cmd=version",
			"error"   => "/json?cmd=error"
		);
			
		$content = $this->url_get_contents($getDataUrl[$type]);
		
		$status = json_decode($content, true);
		if($status['successful'] == true){
			$this->SendDebug(__FUNCTION__, 'Status: successful', 0);
			$this->SendDebug(__FUNCTION__, $status['name'], 0);
			
			return $status;
		}
		else {
			$this->SendDebug(__FUNCTION__, 'Status: failed', 0);

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
