<?php

namespace PCB\SocialLoginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Facebook\Facebook;
use LinkedIn\LinkedIn;
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
			if ( $this->container->hasParameter($provider)) 
			{	
				 // get current configuration for provider
				 $configs = $this->container->getParameter($provider);
				
				 if ( $provider == 'facebook')
				 {
				 	// initialize the app
				 	$fb = new Facebook([
				 		'app_id' => $configs['api_key'],
				 		'app_secret' => $configs['api_secret'],
				 		'default_graph_version' => 'v2.5'
				 	]);
				 	
				 	// login helper with redirect url
				 	$helper = $fb->getRedirectLoginHelper();
				 	$accessToken = $helper->getAccessToken();
				 	
				 	// check to see if user
				 	// authenticated or send him back to facebook
				 	if ( $accessToken !== null && !$this->getUser() )
				 	{
				 		 $fbResponse = $fb->get('/me?fields=id,email,first_name,last_name', $accessToken);
				 		 $fbUser = $fbResponse->getGraphUser();
				 		 $this->findUser($provider, $fbUser);
				 	}
				 	
				 	return $this->redirect( $helper->getLoginUrl($this->getRequest()->getUri(), ['email', 'user_likes']) );				 	
				 }
				 else if ( $provider == 'linkedin') {
				 	
				 	$linkedin = new LinkedIn($configs['api_key'], $configs['api_secret'], $this->getRequest()->getUri());
				 	
				 	if (!$session->get('request_token', false)) 
				 	{
				 		$linkedin->getRequestToken();
				 		$session->set('request_token', serialize($linkedin->request_token));
				 		return $this->redirect($linkedin->generateAuthorizeUrl());
				 	}
				 	elseif ( $session->get('access_token', false) && $session->get('oauth_verifier', false) && $session->get('request_token', false) )
				 	{
        				$linkedin->oauth_verifier = $session->get('oauth_verifier');
				 		$linkedin->request_token  = unserialize($session->get('request_token'));
        				$linkedin->access_token   = unserialize($session->get('access_token'));
				 	
				 		$xml_response = $linkedin->getProfile("~:(id,first-name,last-name,email;secure=true)");
				 		$this->findUser($provider, array(
				 			'id' => $xml_response['id'],
				 			'first_name' => $xml_response['first_name'],
				 			'last_name' => $xml_response['last_name'],
				 			'email' => $xml_response['email']
				 		));
				 	}
				 	elseif ( $request->get('oauth_token', false) && $request->get('oauth_verifier', false) )
				 	{

				 		$linkedin->request_token  = unserialize($session->get('request_token'));
				 		$linkedin->oauth_verifier = $request->get('oauth_verifier');				 		
				 		$linkedin->getAccessToken($request->get('oauth_verifier'));
				 		
				 		$session->set('oauth_verifier', $request->get('oauth_verifier'));
				 		$session->set('access_token', serialize($linkedin->access_token));	
				 		
				 		return $this->redirect($this->getRequest()->getUri());
				 	}
				 	
				 	exit;
				 }
			}		
			else {
				 throw new \Exception("Provider not found check your config.yml file.");
			}
    	}
    	catch( \Exception $e ) {
    		die($e->getMessage());
    	}
		
        return $this->redirect($this->generateUrl($this->container->getParameter('login_path')));
    }
    
    private function findUser($provider, $data) {
    	
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
