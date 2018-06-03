<?php
namespace App\Services;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PRFCYAuthenticate
{

	private $em;

	private $container;

	public function __construct(EntityManager $entityManager, ContainerInterface $container) {	

	    $this->em = $entityManager;

	    $this->container = $container;
	}


	/**
	 * Do authentification using PRFCYGetTokenService and ContainerInterface, 
	 * check mail, password and client_id then secret, and return jwtoken  
	 *
	 * @param string email
	 * @param string password
	 */
	public function authenticate($email, $password)
    {
        $user_manager = $this->container->get('fos_user.user_manager');
        
        $factory = $this->container->get('security.encoder_factory');
        
        # Check 1: on mail ---

        $user = $user_manager->findUserByEmail($email);
        
        $service = new PRFCYGetTokenService();

        if($user) {
        	
        	$encoder = $factory->getEncoder($user);
        
        	$salt = $user->getSalt();

        	# Check 2: on password ---

        	if( $encoder->isPasswordValid($user->getPassword(), $password, $salt) ) {

        		$userid = $user->getId();

	            $username = $user->getUsername();

	            # Check 3: on client_id ---

	            $client = $this->em->getRepository("App:Client")->createQueryBuilder('c')
	               
	               ->Where('c.randomId LIKE :rid')
	               
	               ->setParameter('rid', $userid.'prfcy%')
	               
	               ->getQuery()
	               
	               ->getResult()
	            ;
	            
	            if( $client ) {

	            	$id_table_client = $client[0]->getId();
                
	                $secret = $client[0]->getSecret();

	                $randomid = $client[0]->getRandomId();
	                
	                $cid = $id_table_client.'_'.$randomid;

	                $pass = $password;

	                $oauth_route = 'http://s.wbrm/core_api/public/oauth/v2/token';

	                $token = $service->getToken(
	                    
	                    $oauth_route,
	                    
	                    $cid,
	                    
	                    $secret,
	                    
	                    $pass,
	                    
	                    $username

                    );
	            	return $token;
	            }
	            
	            $response =  array("error"=>"no client_id");

            	return $response;
        	}

        } else {

            $response = array(
              
              'message'=>'Email or Password is not valid.',
              
              'response'=> Response::HTTP_UNAUTHORIZED,
              
              'Content-type'=>'application/json'
            );

            return $response;
        } 
    }
}



