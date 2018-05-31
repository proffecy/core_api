<?php
namespace App\Services;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PRFCYUserCheck
{

	private $em;
	
	private $container;

	public function __construct(EntityManager $entityManager, ContainerInterface $container)
	{	
	    $this->em = $entityManager;

	    $this->container = $container;
	}


   /**
	*
	* @param string $email 
	* @param string $username 
	* @param string $password
	*
	*/
    public function userCheck($email, $username, $password)
    {
       
        $user_manager = $this->container->get('fos_user.user_manager');
        
        $factory = $this->container->get('security.encoder_factory');
        
        # Check 1: on mail ---

        $user = $user_manager->findUserByEmail($email);
        
        if($user) {
            
            $encoder = $factory->getEncoder($user);
        
            $salt = $user->getSalt();

            # Check 2: on password ---



            if( $encoder->isPasswordValid($user->getPassword(), $password, $salt) ) {

                $userid = $user->getId();

                return $userid;
            }

            return false;
        }

        return false;
    }


    public function decodePass64($password) {
    	
	    $obj = base64_decode($password);
	    $password = (array)json_decode($obj);
	    return $password['password'];
	}

}
   