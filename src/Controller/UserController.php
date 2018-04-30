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
     *     path = "/home/{id}",
     *     name = "app_home",
     *     requirements = {"id"="\d+"}
     * )
     * 
     * @View(serializerGroups={"home"})
     */
    public function indexAction(Request $request)
    {
        //Authenticate 

        $authenticationErrorResponse = $this->checkAuthAndGetErrorResponse($request);
        
        if ($authenticationErrorResponse) {
        
            return $authenticationErrorResponse;
        
        }

        //else do somthing ...

        $data = array(

            "name" => "admin",            
            "extra" => "Is awesome!",
            "id" => $request->get('id')

        );
        
        //$users = $this->getDoctrine()->getRepository('User')->findAll()
        
        $view = $this->view($data);

        return $this->handleView($view);

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
        
        $client = $accessToken->getClient();
        
        var_dump($client);
        
        return null;
    }


    
    /**
     * @Get(
     *     path = "/signup/{mail}/{username}/{pass}",
     *     name = "app_signup",
     *     requirements={"mail", "username", "pass"}
     * )
     * 
     * @View(serializerGroups={"user"})
     */
    public function signupAction(Request $request) {
        
        $array['mail'] = $request->get('mail');
        
        $array['username'] = $request->get('username');
        
        $array['pass'] = $request->get('pass');
        

        $succesfullyRegistered = $this->register($array['mail'], $array['username'], $array['pass'] );
        
        return $succesfullyRegistered;
   }
 


    private function register($email,$username,$password) {    
        
        $em = $this->getDoctrine()->getManager();
            
        $usersRepository = $em->getRepository("CoreBundle:User");

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

        $client_id = $this->createClientId($user);

        $arrayinfo =  array( 'user'=>$username,'client_id'=>$client_id["client_id"]);

        $view = $this->view($arrayinfo);

        return $this->handleView($view);

    }


    private function createClientId (User $user) {

        $client = new Client();

        $bytes = random_bytes(32);
        
        $random_id =  hash( 'tiger192,4', $user->getUsername() .  $bytes ); 

        $random_id =  $user->getId() . 'prfcy' . $random_id;

        $bytesecret = random_bytes(32);
        
        $random_secret = hash('tiger192,4', $user->getUsername() . $bytesecret);

        $em = $this->getDoctrine()->getManager();
        
        $connection = $em->getConnection();
        
        $statement = $connection->prepare("INSERT INTO oauth2_clients SET random_id = '".$random_id."', redirect_uris = '".serialize(array("/home"))."', secret = '".$random_secret."',  allowed_grant_types= '".serialize(array("password"))."'");
        
        if($statement->execute())
        { 
            return array("signup_response"=>"registred",  "client_id"=>$random_id);
        }

        return "\O/";
    }
   
}
