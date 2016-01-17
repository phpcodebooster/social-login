<?php

namespace PCB\SocialLoginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class DefaultController extends Controller
{
    public function indexAction(Request $request, $provider)
    {
    	try {
    		    		
    		// get previous url
			$session = $request->getSession();				 					 	
    		$loginUrl = $this->getRequest()->headers->get('referer');    		

    		// make sure we find the configuration
    		if ( $this->container->hasParameter($provider) && !$this->getUser() )
    		{
    			 // get current configuration for provider
    			 $configs = $this->container->getParameter($provider);
    			 
    			 if ( $provider == 'facebook')
    			 {
    			 	  $facebook = new \FaceBook($configs['api_key'], $configs['api_secret'], $this->getRequest()->getUri());
    			 	      			 	  
    			 	  if (!$facebook->is_authenticated()) {
    			 	  	  return $this->redirect($facebook->get_redirect_url());
    			 	  }
    			 	  else {
    			 	  	  $this->findUser($provider, $facebook->get_user($session));
    			 	  }    			 	  
    			 }    			     			
    		}
    	}
    	catch( \Exception $e ) {
    		die($e->getMessage());
    	}
		
        return $this->redirect($this->generateUrl($this->container->getParameter('login_path')));
    }
    
    private function findUser($provider, $data=array()) {
    	
    	$em = $this->getDoctrine()->getManager();
    	$qb = $em->createQueryBuilder();
    	
    	$model_alias = $this->container->getParameter('model_alias');
    	$model_namespace = '\\'. $this->container->getParameter('model_namespace'). '\\User';
    	
    	$user = $qb->select('u')
    	->from("{$model_alias}:User", 'u')
    	->where('u.authId = ?1 AND u.authProvider = ?2')
    	->setParameter(1, $data['id'])
    	->setParameter(2, $provider)
    	->setMaxResults(1)
    	->getQuery()
    	->getOneOrNullResult();
    	
    	if (!$user)
    	{
    		// create new user if not in the system
    		$user = new $model_namespace();
    			
    		$user->addRole('ROLE_USER');
    		$user->setAuthProvider($provider);
    		$user->setUsername($data['id']);
    		$user->setEmail($data['email']);
    		$user->setAuthId($data['id']);
    		$user->setLastName($data['last_name']);
    		$user->setFirstName($data['first_name']);
    			
    		// custom authentication
    		$factory = $this->container->get('security.encoder_factory');
    		$encoder = $factory->getEncoder($user);
    			
    		// get encoded password
    		$password = $encoder->encodePassword(md5(uniqid()), $user->getSalt());
    		$user->setPassword($password);
    			
    		$em = $this->getDoctrine()->getManager();
    		$em->persist($user);
    		$em->flush();
    	}
    	
    	// authenticate user manually
    	$token = new UsernamePasswordToken($user, null, "your_firewall_name", $user->getRoles());
    	$this->get("security.context")->setToken($token); //now the user is logged in
    	
    	// now dispatch the login event
    	$request = $this->get("request");
    	$event = new InteractiveLoginEvent($request, $token);
    	$this->get("event_dispatcher")->dispatch("security.interactive_login", $event);    	 
    }
}
