<?php
namespace App\Services;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PRFCYcheckRoles
{

	private $em;

	private $container;

	public function __construct(EntityManager $entityManager, ContainerInterface $container) {	

	    $this->em = $entityManager;

	    $this->container = $container;
	}


    public function userRolesChecking($request)
    {
        $roles = $this->getUserRoles($request);

        if(in_array('ROLE_SUPER_ADMIN', $roles)) {

            return 'superadmin';

        } elseif(in_array('ROLE_ADMIN', $roles)) {

            return 'admin';

        } elseif(in_array('ROLE_USER', $roles)) {

            return 'user';

        } else {

            return 'anonym';
        }
    }


    public function getUserRoles($request) {

        $bearer_token = $this->container->get('fos_oauth_server.server')->getBearerToken($request);
        
        // select user_id from oauth2_access_tokens where token='$bearer_token'

        $userid = $this->em->getRepository("App:AccessToken")->createQueryBuilder('at')
        
               ->Where('at.token LIKE :token')
        
               ->setParameter('token', $bearer_token)
        
               ->getQuery()
        
               ->getResult();

        $roles = $userid[0]->getUser()->getRoles();

        return $roles;
    }


}