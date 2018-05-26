<?php
namespace App\Services;

use Doctrine\ORM\EntityManager;
use App\Entity\User;
use App\Entity\Client;

class PRFCYCreateClientId 
{
	
	public function __construct(EntityManager $entityManager)
	{
	    $this->em = $entityManager;
	}


	/**
     *
     * @var User 
     */
	public function createClientId (User $user) {

        $client = new Client();

        $bytes = random_bytes(32);
        
        $user_id = $user->getId();

        # client id  & secret id

        $random_id =  hash( 'tiger192,4', $user->getUsername() .  $bytes ); 

        $random_id =  $user_id . 'prfcy' . $random_id;

        $bytesecret = random_bytes(32);
        
        $random_secret = hash('tiger192,4', $user->getUsername() . $bytesecret);
        
        # insert new client id and secret in client base

        //$em = $this->getDoctrine()->getManager();
        
        $connection = $this->em->getConnection();
        
        $statement = $connection->prepare("INSERT INTO oauth2_clients SET random_id = '".$random_id."', redirect_uris = '".serialize(array("/users/".$user_id))."', secret = '".$random_secret."',  allowed_grant_types= '".serialize(array("password"))."'");

        if($statement->execute())
        { 
            $lastid = $connection->lastInsertId();

            return array("client_created"=>true);
        }

        return array("client_created"=>false);
    }

}