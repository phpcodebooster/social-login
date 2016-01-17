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

class FaceBook extends OAuthAPI {

	public function __construct($api_key=null, $api_secret=null, $callback=null) {
		
		$callback = $this->get_clean_url($callback);
		$this->api_settings = array(
				'api_key' 		=> $api_key,
				'api_secret'	=> $api_secret,
				'api_callback'	=> $callback,
				'api_endpoint'	=> 'https://www.facebook.com/',
				'api_endpoint2'	=> 'https://graph.facebook.com/v2.3/'
		);
	}
	
	public function get_user(&$session) {
		
		if ( !$session->get('fb_access_token', false) ) {
			 $response = $this->call($this->api_settings['api_endpoint2']. "oauth/access_token?", array(
				'code'			=> $_REQUEST['code'],
				'client_id'		=> $this->api_settings['api_key'],
				'client_secret'	=> $this->api_settings['api_secret'],
				'redirect_uri'	=> $this->api_settings['api_callback']
			 ));
			
			 if ( array_key_exists('access_token', $response) ) {
				  $session->set('fb_access_token', $response['access_token']);
			 }
		}
				
		return $this->call($this->api_settings['api_endpoint2']. "me?fields=id,email,first_name,last_name&access_token=" .$session->get('fb_access_token'));
	}
	
	public function is_authenticated() {
		return isset($_REQUEST['code']);
	}
	
	public function get_http_info() {
		return $this->http_info;
	}
	
	public function get_redirect_url() {		
		return $this->api_settings['api_endpoint']. "dialog/oauth?". http_build_query(array(
			'client_id'		=> $this->api_settings['api_key'],
			'redirect_uri'	=> $this->api_settings['api_callback']
		));
	}
}