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
    			 
    			 if ( $provider == 'twitter')
    			 {
    			 	define('TWITTER_CONSUMER_KEY',    $configs['api_key']);
    			 	define('TWITTER_CONSUMER_SECRET', $configs['api_secret']);
    			 	define('TWITTER_OAUTH_CALLBACK',  $this->getRequest()->getUri());
    			 		
    			 	if (!$session->get('oauth_token')) {
    			 		
    			 		$connection = new \TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
    			 		$request_token = $connection->getRequestToken(TWITTER_OAUTH_CALLBACK);
    			 		
    			 		$session->set('oauth_token', $request_token['oauth_token']);
    			 		$session->set('oauth_token_secret', $request_token['oauth_token_secret']);
    			 		
    			 		switch ($connection->http_code) {
    			 			case 200:
    			 				return $this->redirect( $connection->getAuthorizeURL($request_token['oauth_token']) );
    			 				break;
    			 			default:
    			 				throw new \Exception('Could not connect to Twitter. Refresh the page or try again later.');
    			 				break;
    			 		}
    			 	}
    			 	else {
    			 		
    			 		$connection = new \TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $session->get('oauth_token'), $session->get('oauth_token_secret'));
    			 		$access_token = $connection->getAccessToken($request->get('oauth_verifier'));
    			 		
    			 		$session->set('access_token', $access_token);
    			 		$content = $connection->get('account/verify_credentials');
    			 		
    			 		$this->findUser($provider, array(
							'id' 		 => (string)$content->id,
							'first_name' => (string)$content->name,
							'last_name'  => (string)$content->name,
							'email' 	 => (string)$content->id. '@twitter.com'
						));
    			 	}
    			 }
    			 elseif ( $provider == 'google')
    			 {
    			 	// include google library
    			 	include dirname(__DIR__). '/Vendor/Google/autoload.php';
    			 	
					$client = new \Google_Client();
					$client->addScope("email");
					$client->addScope("profile");
					$client->setClientId($configs['api_key']);
					$client->setClientSecret($configs['api_secret']);
					$client->setRedirectUri( $this->get_clean_url($this->getRequest()->getUri()) );
    			 	
					if ($request->get('code', false)) {
						$client->authenticate($request->get('code'));						
						$service = new \Google_Service_Oauth2($client);
						$user = $service->userinfo->get(); //get user info
						$this->findUser($provider, array(
								'id' 		 => (string)$user->id,
								'first_name' => (string)$user->givenName,
								'last_name'  => (string)$user->familyName,
								'email' 	 => (string)$user->email
						));
					}
    			 	else {
    			 		return $this->redirect($client->createAuthUrl());
    			 	}
    			 }
    			 elseif ( $provider == 'facebook')
    			 {
    			 	$facebook = new \FaceBook($configs['api_key'], $configs['api_secret'], $this->getRequest()->getUri());
    			 
    			 	if (!$facebook->is_authenticated()) {
    			 		return $this->redirect($facebook->get_redirect_url());
    			 	}
    			 	else {
    			 		$this->findUser($provider, $facebook->get_user($session));
    			 	}
    			 }
    			 elseif ( $provider == 'linkedin')
    			 {
    			 	$linkedin = new \LinkedIn($configs['api_key'], $configs['api_secret'], $this->getRequest()->getUri());
    			 		
    			 	if (!$linkedin->is_authenticated()) {
    			 		return $this->redirect($linkedin->get_redirect_url());
    			 	}
    			 	else {
    			 		$this->findUser($provider, $linkedin->get_user($session));
    			 	}
    			 }
    		}
    	}
    	catch( \Exception $e ) {
    		$this->get('session')->getFlashBag()->add('error', $e->getMessage());
    	}
		
        return $this->redirect($this->generateUrl($this->container->getParameter('login_path')));
    }
    
    private function get_clean_url($callback) {
    
    	if ( strpos($callback, "?") !== false ) {
    		return substr($callback, 0, strpos($callback, "?"));
    	}
    
    	return $callback;
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
