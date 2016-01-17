<?php

/**
 * ---------------------------------
 * PHP CODE BOOSTER
 * ---------------------------------
 *
 * @Author: Sandip Patel
 * @package pcb/social-login
 * @version 5.0
 * @copyright (c) 2016, Sandip Patel
 **/

abstract class OAuthAPI {
	
	protected $http_info = array();
	protected $api_settings = array(
		'api_key' 		=> '',
		'api_secret'	=> '',
		'api_callback'	=> ''
	);
		
	abstract public function get_user(&$session);
	abstract public function is_authenticated();
	abstract public function get_redirect_url();
	abstract public function get_http_info();
	
	protected function call($url, $params = array(), $headers=array()) {
		
		try {
				
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url . http_build_query($params));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			
			if ( !empty($headers) ) {
				 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}
			else {
				 curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
			}
				
		    $response = curl_exec($ch);
		    $this->http_info['error'] = curl_error($ch);
		    $this->http_info['code']  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		    $this->http_info['info']  = array_merge($this->http_info, curl_getinfo($ch));
		    curl_close ($ch);
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
		    $this->http_info['error'] = curl_error($ch);
		    $this->http_info['code']  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		    $this->http_info['info']  = array_merge($this->http_info, curl_getinfo($ch));
		    curl_close ($ch);
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