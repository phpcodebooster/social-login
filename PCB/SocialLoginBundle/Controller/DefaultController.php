<?php

namespace PCB\SocialLoginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Facebook\Facebook;

class DefaultController extends Controller
{
    public function indexAction($provider)
    {
    	try {
    		
    		// get previous url
    		$loginUrl = $this->getRequest()->headers->get('referer');
    		
    		// make sure we find the configuration
			if ( $this->container->hasParameter($provider)) 
			{	
				 // get current configuration for provider
				 $configs = $this->container->getParameter($provider);
				
				 // initialize the app
				 $fb = new Facebook([
 				 	 'app_id' => $configs['app_id'],
 				 	 'app_secret' => $configs['secret_id'],
 				 	 'default_graph_version' => 'v2.5'
 				 ]);
				 				 
				 // login helper with redirect url
				 $helper = $fb->getRedirectLoginHelper();
				 $accessToken = $helper->getAccessToken();

				 // check to see if user
				 // authenticated or send
				 // him back to facebook				 
				 if ( $accessToken !== null ) 
				 {
				 	  // user logged on
				 	  $oResponse = $fb->get('/me', $accessToken);
    				  print_r($oResponse->getGraphUser());
				 }
				 else 
				 {
				 	  return $this->redirect( $helper->getLoginUrl($this->getRequest()->getUri(), ['email', 'user_likes']) );
				 }
			}		
			else {
				throw new \Exception("Provider not found check your config.yml file.");
			}
    	}
    	catch( \Exception $e ) {
    		echo $e->getMessage();
    	}
		
        return $this->render('PCBSocialLoginBundle:Default:index.html.twig');
    }
}
