<?php

namespace PCB\SocialLoginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Facebook\Facebook;
use Abraham\TwitterOAuth\TwitterOAuth;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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
				 else if ( $provider == 'twitter') {
				 	
				 	$connection = new TwitterOAuth($configs['api_key'], $configs['api_secret']);
				 	$content = $connection->get("account/verify_credentials");
				 	die(var_dump($content));
				 }
			}		
			else {
				 throw new \Exception("Provider not found check your config.yml file.");
			}
    	}
    	catch( \Exception $e ) {
    		echo $e->getMessage();
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
