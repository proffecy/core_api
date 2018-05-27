<?php
namespace App\Services;

use Doctrine\ORM\EntityManager;
use App\Entity\User;
use App\Entity\Client;
use App\Services\PRFCYCreateClientId;

class PRFCYRegisterUser
{

	private $em;
	
	public function __construct(EntityManager $entityManager)
	{	
	    $this->em = $entityManager;
	}


   /**
	*
	* @param string $email 
	* @param string $username 
	* @param string $roles
	* @param string $roles
	*
	*/
    public function registerUser( $email,$username,$password, $roles ) {    
            
        $usersRepository = $this->em->getRepository("App:User");

        $email_exist = $usersRepository->findOneBy(array('email' => $email));

        if($email_exist) {

            return array('response'=>$username. ' exist');
        }
        
        $user = new User();
        
        switch($roles) {
            
            case '0' : $user->setRoles( array('ROLE_USER')); break;

            case '1' : $user->setRoles( array('ROLE_ADMIN')); break;
            
            case '2' :$user->setRoles( array('ROLE_ADMIN', 'ROLE_SUPER_ADMIN')); break;
            
            default:break;
        }

        $user->setUsername($username);

        $user->setEmailCanonical($email);
        
        $user->setEmail($email);

        $user->setPlainPassword($password);
        
        $user->setEnabled(1);

        $this->em->persist($user);

        $this->em->flush();

        $user_id = $user->getId();

        $service = new PRFCYCreateClientId($this->em);

        $createClientIdresponse = $service->createClientId($user);

        $arrayinfo =  array('id' => $user_id,  'user'=>$username,'email'=>$email, 'registred'=>true, 'client_reponse'=>$createClientIdresponse);

        return $arrayinfo;

    }
}