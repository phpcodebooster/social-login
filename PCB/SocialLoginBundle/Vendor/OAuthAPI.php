<?php

abstract class OAuthAPI {
	
	protected $api_settings = array(
		'api_key' 		=> '',
		'api_secret'	=> '',
		'api_callback'	=> ''
	);
		
	abstract public function get_user(&$session);
	abstract public function is_authenticated();
	abstract public function get_redirect_url();
	protected function call($url, $params = array()) {
		
		try {
				
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url . http_build_query($params));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
				
			$response = curl_exec($ch);
			curl_close($ch);
		}
		catch( \System\Libraries\Core\PCBRouterException $e ) {
			$e->display_error();
		}
			
		return json_decode($response, true);
	}	
	protected function get_xml($url, $params = array()) {
	
		try {
	
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url . http_build_query($params));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/xml"));
	
			$response = curl_exec($ch);
			curl_close($ch);
		}
		catch( \System\Libraries\Core\PCBRouterException $e ) {
			$e->display_error();
		}
			
		return new SimpleXMLElement($response);
	}
	protected function get_clean_url($callback) {
		
		if ( strpos($callback, "?") !== false ) {
			 return substr($callback, 0, strpos($callback, "?"));
		}
		
		return $callback;
	}
}