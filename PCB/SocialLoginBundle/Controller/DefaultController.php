<?php

namespace PCB\SocialLoginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($provider)
    {
    	try {
    		    		
    		// make sure we find the configuration
			if ( $this->container->hasParameter($provider)) {
			 	 
				 $loginProvider = $this->get('pcb_social_login.provider');
				 $loginProvider->authenticate($provider, $this->getParameter($provider), $this->getRequest()->headers->get('referer'));
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
