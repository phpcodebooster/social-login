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

class LinkedIn extends OAuthAPI {

	public function __construct($api_key=null, $api_secret=null, $callback=null) {
		
		$callback = $this->get_clean_url($callback);
		$this->api_settings = array(
				'api_key' 		=> $api_key,
				'api_secret'	=> $api_secret,
				'api_callback'	=> $callback,
				'api_response_type' => 'code',
				'api_state' 		=> md5(uniqid()),
				'api_grant_type'	=> 'authorization_code',
				'api_endpoint'		=> 'https://www.linkedin.com/',
				'api_scope' 		=> 'r_basicprofile,r_emailaddress'
		);
	}
	
	public function get_user(&$session) {
		
		if ( !$session->get('ln_access_token', false) ) {
			 $response = $this->call($this->api_settings['api_endpoint']. "uas/oauth2/accessToken?", array(
				'code'			=> $_REQUEST['code'],
				'grant_type'	=> $this->api_settings['api_grant_type'],
				'client_id'		=> $this->api_settings['api_key'],
				'client_secret'	=> $this->api_settings['api_secret'],
				'redirect_uri'	=> $this->api_settings['api_callback']
			 ));
			
	  		 if ( array_key_exists('access_token', $response) ) {
				  $session->set('ln_access_token', $response['access_token']);
			 }
		}
				
		$user = (array)$this->get_xml($this->api_settings['api_endpoint']. "v1/people/~:(id,first-name,last-name,email-address)?oauth2_access_token=" .$session->get('ln_access_token'));
		return array(
			'id' 		 => (string)$user['id'],
			'first_name' => (string)$user['first-name'],
			'last_name'  => (string)$user['last-name'],
			'email' 	 => (string)$user['id']. '@linkedin.com'
		);
	}
	
	public function is_authenticated() {
		return isset($_REQUEST['code']) && isset($_REQUEST['state']);
	}
	
	public function get_http_info() {
		return $this->http_info;
	}
	
	public function get_redirect_url() {		
		return $this->api_settings['api_endpoint']. "uas/oauth2/authorization?". http_build_query(array(
			'client_id'		=> $this->api_settings['api_key'],
			'state'			=> $this->api_settings['api_state'],
			'scope'			=> $this->api_settings['api_scope'],
			'redirect_uri'	=> $this->api_settings['api_callback'],
			'response_type'	=> $this->api_settings['api_response_type'],
		));
	}
}