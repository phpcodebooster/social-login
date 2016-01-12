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
 *
 **/
 namespace PCB\SocialLoginBundle\Services;

 use Symfony\Component\DependencyInjection\ContainerInterface as Container;
 
 class Provider {
 	 	
 	private $container;
 	
 	public function __construct(Container $container) 
 	{
 		$this->container = $container;
 	}
 	
 	public function authenticate($provider, $configs=array(), $redirectUrl) 
 	{	
 		try {
 			
 				 if ($provider == 'facebook') {
 				 	 
 				 	 
 				 }
	 			 else {
	 				 throw new \Exception("Provider is not supportable.");
	 			 }
 		}
 		catch (\Exception $e) {
 			echo $e->getMessage();
 		}
 	}
 	

 }