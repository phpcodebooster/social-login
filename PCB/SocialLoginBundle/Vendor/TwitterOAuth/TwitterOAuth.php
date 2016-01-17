<?php

/**
 * ---------------------------------
 * PHP CODE BOOSTER
 * ---------------------------------
 *
 * @Author: Sandip Patel
 * @package PHPCodebooster
 * @version 5.0
 * @copyright (c) 2014, Sandip Patel
 **/
namespace TwitterOAuth;

class TwitterOAuth {

	private $http_info = array();
	private $settings  = array();
	
	public function __construct($configs=array()) 
	{
		$dirname = dirname(__FILE__);
		$this->settings = array_merge(array(
	        'user_agent'                 => 'SignIn with Twitter',
	        'host'                       => 'api.twitter.com',
	        'method'                     => 'POST',
	        'token'                      => '',
	        'secret'                     => '',
	        'bearer'                     => '',
	        'oauth_version'              => '1.0',
	        'oauth_signature_method'     => 'HMAC-SHA1',
			'oauth_timestamp'			 => time(),
	        'oauth_consumer_key'         => '',
	        'oauth_consumer_secret'      => '',
			'oauth_callback'			 => '',
			'oauth_signature'			 => '',
	        'curl_encoding'              => '',    
			'curl_http_version'          => CURL_HTTP_VERSION_1_1,
	        'curl_connecttimeout'        => 30,
	        'curl_timeout'               => 10,
	        'curl_ssl_verifyhost'        => 2,
	        'curl_ssl_verifypeer'        => TRUE,
	        'use_ssl'                    => TRUE,
	        'curl_capath'                => $dirname,
	        'curl_cainfo'                => $dirname . DIRECTORY_SEPARATOR . 'cacert.pem',
			'curl_sslversion'            => FALSE,
	        'curl_followlocation'        => FALSE, 
	        'curl_proxy'                 => FALSE, 
	        'curl_proxyuserpwd'          => FALSE, 
	        'is_streaming'               => FALSE,
	        'as_header'                  => TRUE,
	        'force_nonce'                => FALSE, 
	        'force_timestamp'            => FALSE, 
	        'streaming_metrics_interval' => 10,
	        'streaming_eol'              => "\r\n"
		), $configs );
	}
	
	public function get_request_token() 
	{
		$content = $this->call('oauth/request_token', array(
				'oauth_callback'	 	 => $this->settings['oauth_callback'],
				'oauth_consumer_key' 	 => $this->settings['oauth_consumer_key'],
				'oauth_nonce'		 	 => $this->get_nonce(),
				'oauth_signature'    	 => $this->settings['oauth_consumer_secret'],
				'oauth_signature_method' => $this->settings['oauth_signature_method'],
				'oauth_timestamp'        => $this->settings['oauth_timestamp'],
				'oauth_version'          => $this->settings['oauth_version']
		));
		
		echo "<pre>";
		var_dump($content, $this->http_info);
	}
	
	public function verify_token() 
	{
		
	}
	
	private function get_nonce()
	{
		return md5(uniqid());
	}
	
	private function setCurlHeader($ch, $header) 
	{
		list($key, $value) = array_pad(explode(':', $header, 2), 2, null);
		
		$key = trim($key);
		$value = trim($value);
		
		if ( ! isset($this->response['headers'][$key])) 
		{
			 $this->http_info['headers'][$key] = $value;
		} 
		else 
		{
			if (!is_array($this->http_info['headers'][$key])) 
			{
				$this->http_info['headers'][$key] = array($this->http_info['headers'][$key]);
			}
			$this->http_info['headers'][$key][] = $value;
		}
		
		return strlen($header);
	}
	
	private function call($url, $params = array())
	{
		try {
							
			$response  = NULL;
			$headers   = array();
			$targetURL = "https://{$this->settings['host']}/{$url}";
			
			$ci = curl_init();		
			curl_setopt_array($ci, array(
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLINFO_HEADER_OUT    => TRUE,
				CURLOPT_HEADER         => FALSE,
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_POSTFIELDS	   => $params,
				CURLOPT_URL            => $targetURL,
				CURLOPT_USERAGENT      => $this->settings['user_agent'],
				CURLOPT_CAINFO		   => $this->settings['curl_cainfo'],
				CURLOPT_PROXY          => $this->settings['curl_proxy'],
				CURLOPT_TIMEOUT        => $this->settings['curl_timeout'],
				CURLOPT_ENCODING       => $this->settings['curl_encoding'],
				CURLOPT_HTTP_VERSION   => $this->settings['curl_http_version'],
				CURLOPT_CONNECTTIMEOUT => $this->settings['curl_connecttimeout'],
				CURLOPT_SSL_VERIFYPEER => $this->settings['curl_ssl_verifypeer'],
				CURLOPT_SSL_VERIFYHOST => $this->settings['curl_ssl_verifyhost'],
				CURLOPT_FOLLOWLOCATION => $this->settings['curl_followlocation'],
				CURLOPT_HEADERFUNCTION => array($this, 'setCurlHeader')
			));
						
		    $response = curl_exec($ci);
		    $this->http_info['error'] = curl_error($ci);
		    $this->http_info['code']  = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		    $this->http_info['info']  = array_merge($this->http_info, curl_getinfo($ci));
		    curl_close ($ci);
		}
		catch( \Exception $e ) {
			echo $e->getMessage();
		}
			
		return $response;
	}
}