<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationExpiredException;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use App\Entity\User;
use App\Entity\Client;


class UserController extends FOSRestController
{
    /**
     * @Get(
     *     path = "/users/new/{mail}/{username}/{pass}",
     *     name = "new_users",
     *     requirements = {"mail", "username", "pass"}
     * )
     * @View(serializerGroups={"new"})
     */
    public function newUsersAction(Request $request)
    {
        $array['mail'] = $request->get('mail');
        
        $array['username'] = $request->get('username');
        
        $array['pass'] = $request->get('pass');

        $succesfullyRegistered = $this->registerUser($array['mail'], $array['username'], $array['pass'] );
        
        return $succesfullyRegistered;
    }
    

    /**
     * @Get(
     *     path = "/users/auth/{email}/{pass}",
     *     name = "users_auth",
     *     requirements = {"email", "pass"}
     * )
     * @View(serializerGroups={"auth"})
     */
    public function usersAuthAction(Request $request)
    {   
        $email = $request->get('email');
        
        $password = $request->get('pass');

        # Checking user by fos

        $user_manager = $this->get('fos_user.user_manager');
        
        $factory = $this->get('security.encoder_factory');

        # Check 1: on email

        $user = $user_manager->findUserByEmail($email);
        
        $encoder = $factory->getEncoder($user);
        
        $salt = $user->getSalt();

        # Check 2: on password

        if( $encoder->isPasswordValid($user->getPassword(), $password, $salt) ) {
            
            $userid = $user->getId();

            $username = $user->getUsername();


            # Check 3: on client_id

            $em = $this->getDoctrine()->getManager();

            $client = $em->getRepository("App:Client")->createQueryBuilder('c')
               ->Where('c.randomId LIKE :rid')
               ->setParameter('rid', $userid.'prfcy%')
               ->getQuery()
               ->getResult();

            if( $client ) {
                
                # Get Secret

                $id_table_client = $client[0]->getId();
                
                $secret = $client[0]->getSecret();

                $randomid = $client[0]->getRandomId();
                
                $cid = $id_table_client.'_'.$randomid;

                $pass = $request->get('pass');

                # Check 4: Check on request to check clientId & secret then get token from Oauth
                
                $url = "http://s.wbrm/core_api/public/oauth/v2/token?";
                
                $url .= "client_id=".$cid."&client_secret=".$secret."&";
                
                $url .= "grant_type=password&password=".$pass."&username=".$username; 
                
                $options = array(
                      
                      CURLOPT_URL            => $url, 
                      
                      CURLOPT_RETURNTRANSFER => true,
                      
                      CURLOPT_HEADER         => false
                );
                
                # Send request and get response

                $CURL = curl_init();

                curl_setopt_array($CURL,$options);

                # Get response

                $content = curl_exec($CURL);
                 
                curl_close($CURL);

                # \O/ Return response token informations \O/

                $view = $this->view($content);

                return $this->handleView($view);
            }

            $view = $this->view(array("error"=>"no client"));

            return $this->handleView($view);

        } else {

            $response = array(
              
              'message'=>'Username or Password not valid.',
              
              'response'=> Response::HTTP_UNAUTHORIZED,
              
              'Content-type'=>'application/json'
            );

            $view = $this->view( $response );

            return $this->handleView($view);
        }
    }
    
    

    private function registerUser($email,$username,$password) {    
        
        $em = $this->getDoctrine()->getManager();
            
        $usersRepository = $em->getRepository("App:User");

        $email_exist = $usersRepository->findOneBy(array('email' => $email));

        if($email_exist) {

            $view = $this->view(array('response'=>$username. ' exist'));

            return $this->handleView($view);
        }
        
        $user = new User();

        $roles=[];
        
        $roles[] = 'ROLE_USER';

        $user->setRoles($roles);

        $user->setUsername($username);

        $user->setEmailCanonical($email);
        
        $user->setEmail($email);

        $user->setPlainPassword($password);
        
        $user->setEnabled(1);

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->persist($user);

        $entityManager->flush();

        $user_id = $user->getId();

        $createClientIdresponse = $this->createClientId($user);

        $arrayinfo =  array( 'user'=>$username,'email'=>$email, 'registred'=>true, 'client_reponse'=>$createClientIdresponse);

        $view = $this->view($arrayinfo);

        return $this->handleView($view);

    }


    private function createClientId (User $user) {

        $client = new Client();

        $bytes = random_bytes(32);
        
        $user_id = $user->getId();

        # client id  & secret id

        $random_id =  hash( 'tiger192,4', $user->getUsername() .  $bytes ); 

        $random_id =  $user_id . 'prfcy' . $random_id;

        $bytesecret = random_bytes(32);
        
        $random_secret = hash('tiger192,4', $user->getUsername() . $bytesecret);
        
        # insert new client id and secret in client base

        $em = $this->getDoctrine()->getManager();
        
        $connection = $em->getConnection();
        
        $statement = $connection->prepare("INSERT INTO oauth2_clients SET random_id = '".$random_id."', redirect_uris = '".serialize(array("/users/".$user_id))."', secret = '".$random_secret."',  allowed_grant_types= '".serialize(array("password"))."'");

        if($statement->execute())
        { 
            $lastid = $connection->lastInsertId();

            return array("client_created"=>true);
        }

        return array("client_created"=>false);
    }


    private function checkAuthAndGetErrorResponse(Request $request)
    {
        $tokenManager = $this->get('fos_oauth_server.access_token_manager.default');

        $bearerToken = $this->get('fos_oauth_server.server')->getBearerToken($request);
        
        if (!$bearerToken) {

            return new JsonResponse(['status' => 400, 'message' => 'Bearer token not supplied'], 400);
        }

        $accessToken = $tokenManager->findTokenByToken($bearerToken);

        if (!$accessToken) {

            return new JsonResponse(['status' => 400, 'message' => 'Bearer token not valid'], 400);
        }

        if ($accessToken->hasExpired()) {

            return new JsonResponse(['status' => 400, 'message' => 'Access token has expired'], 400);
        }
        // may want to validate something else about the client, but that is beyond OAuth2 scope
        
        # $client = $accessToken->getClient();
        
        return null;
    }


    


    /**
     * @Get(
     *     path = "/users/{id}",
     *     name = "get_user",
     *     requirements = {"id"="\d+"}
     * )
     */
    public function getUserAction($id, Request $request)
    {
        $authenticationErrorResponse = $this->checkAuthAndGetErrorResponse($request);
        
        if ($authenticationErrorResponse) {
        
            return $authenticationErrorResponse;
        }

        $users = $this->getDoctrine()->getRepository('App:User')->findById($id);

        $data = array(

            "id" => $users[0]->getId(),

            "username" => $users[0]->getUsername(),

            "mail" => $users[0]->getEmail(),
        );

        $view = $this->view( $data );

        return $this->handleView($view);
    }


}
