<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
use App\Entity\AccessToken;
use App\Services\PRFCYCreateClientId;
use App\Services\PRFCYGetTokenService;

class UserController extends FOSRestController
{

    /**
     * @Get(
     *     path = "/users/new/{mail}/{username}/{pass}/{role}",
     *     name = "new_users",
     *     requirements = {"mail", "username", "pass", "role"}
     * )
     * @View(serializerGroups={"new"})
     */
    public function newUsersAction(Request $request)
    {
        $array['mail'] = $request->get('mail');
        
        $array['username'] = $request->get('username');
        
        $array['pass'] = $request->get('pass');
        
        $array['role'] = $request->get('role');

        $succesfullyRegistered = $this->registerUser($array['mail'], $array['username'], $array['pass'],  $array['role'] );
        
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
    public function usersAuthAction(PRFCYGetTokenService $service, Request $request)
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
               
               ->getResult()
            ;

            if( $client ) {
                
                # Get Secret

                $id_table_client = $client[0]->getId();
                
                $secret = $client[0]->getSecret();

                $randomid = $client[0]->getRandomId();
                
                $cid = $id_table_client.'_'.$randomid;

                $pass = $request->get('pass');

                $oauth_route = 'http://s.wbrm/core_api/public/oauth/v2/token';

                # Check 4: Request to check clientId & secret then get token from Oauth

                $token = $service->getToken(
                    
                    $oauth_route,
                    
                    $cid,
                    
                    $secret,
                    
                    $pass,
                    
                    $username

                    );

                # \O/ Return response token informations \O/

                $view = $this->view( $token );

                return $this->handleView( $view );
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


    /**
     * @Get(
     *     path = "/users/check",
     *     name = "get_roles",
     *     
     * )
     */
    public function checkRolesAction(Request $request)
    {
        switch( $this->userRolesChecking($request) ) {

            case "superadmin" : $view = $this->view( array('roles'=>'superadmin') ); return $this->handleView($view); break;

            case "admin" : $view = $this->view( array('roles'=>'admin') ); return $this->handleView($view); break;
            
            case "user" : $view = $this->view( array('roles'=>'user') ); return $this->handleView($view); break;
            
            case "anonym" : $view = $this->view( array('roles'=>'anonym') ); return $this->handleView($view); break;
            
            default: break;
        }
    }

   

    private function userRolesChecking($request)
    {
        
        $authenticationErrorResponse = $this->checkAuthAndGetErrorResponse($request);

        if ($authenticationErrorResponse) {

            return $authenticationErrorResponse;
        }
        
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



    /**
     * @Get(
     *     path = "/users/{id}",
     *     name = "get_user",
     *     requirements = {"id"="\d+"}
     * )
     */
    public function getUserByIdAction($id, Request $request)
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



    /**
     * @Get(
     *     path = "/users/{mail}",
     *     name = "get_user",
     *     requirements = {"mail"}
     * )
     */
    public function getUserByMailAction(Request $request)
    {
        $authenticationErrorResponse = $this->checkAuthAndGetErrorResponse($request);
        
        if ($authenticationErrorResponse) {
        
            return $authenticationErrorResponse;
        }
        $mail = $request->get('mail');

        $users = $this->getDoctrine()->getRepository('App:User')->findByMail($mail);

        $data = array(

            "id" => $users[0]->getId(),

            "username" => $users[0]->getUsername(),

            "mail" => $users[0]->getEmail(),
        );

        $view = $this->view( $data );

        return $this->handleView($view);
    }



     /**
     * @Get(
     *     path = "/users/edit/mail/{mail}/{newmail}/{password}",
     *     name = "edit_mail",
     *     requirements = {"mail", "newmail"}
     * )
     */
    public function editMailProfileAction(Request $request)
    {
        
        $password = $request->get('password');

        $oldmail = $request->get('mail');
        
        $newmail = $request->get('newmail');
        
        # Checking user by fos

        $user_manager = $this->get('fos_user.user_manager');
        
        $factory = $this->get('security.encoder_factory');

        # Check 1: on email

        $user = $user_manager->findUserByEmail($oldmail);
        
        $encoder = $factory->getEncoder($user);
        
        $salt = $user->getSalt();

        # Check 2: on password

        if($user) {

            if( $encoder->isPasswordValid($user->getPassword(), $password, $salt) ) {
                
                $user->setEmailCanonical($newmail);
        
                $user->setEmail($newmail);

                $entityManager = $this->getDoctrine()->getManager();

                $entityManager->persist($user);

                $entityManager->flush();

                $user_id = $user->getId();

                $view = $this->view(array('edited'=>$user->getEmail()));

                return $this->handleView($view);
            }
        }

        $view = $this->view(array('edited'=>'error'));

        return $this->handleView($view);
    }



     /**
     * @Get(
     *     path = "/users/edit/password/{mail}/{password}/{newpassword}/{confirmpassword}",
     *     name = "edit_password",
     *     requirements = {"mail","password","newpassword","confirmpassword"}
     * )
     */
    public function editPasswordRequestAction(Request $request)
    {
        
        $mail = $request->get('mail');
        
        $password = $request->get('password');
        
        $newpassword = $request->get('newpassword');
        
        $confirmpassword = $request->get('confirmpassword');
        
        # Checking user by fos

        $user_manager = $this->get('fos_user.user_manager');
        
        $factory = $this->get('security.encoder_factory');

        # Check 1: on email

        $user = $user_manager->findUserByEmail($mail);
        
        $encoder = $factory->getEncoder($user);
        
        $salt = $user->getSalt();

        # Check 2: on password

        if($user) {

            if( $encoder->isPasswordValid($user->getPassword(), $password, $salt) ) {
                    
                if($confirmpassword == $newpassword) {

                    $user->setPlainPassword($newpassword);

                    $entityManager = $this->getDoctrine()->getManager();

                    $entityManager->persist($user);

                    $entityManager->flush();

                    $user_id = $user->getId();

                    $view = $this->view(array('edited'=>'1'));

                    return $this->handleView($view);

                } else {

                    $view = $this->view(array('edited'=>'wrong confirm password'));

                    return $this->handleView($view);
                }
            }
        }

        $view = $this->view(array('edited'=>'error'));

        return $this->handleView($view);
    }



     /**
     * @Get(
     *     path ="/users/resetpassword/{userEmail}",
     *     name ="user_password_reset",
     *     requirements = { "userEmail" }
     * )
     *
     */
    public function resetPasswordRequestAction(Request $request)
    {
        $email = $request->get('userEmail');

        $user = $this->get('fos_user.user_manager')->findUserByEmail($email);

        if (null === $user) {
           
            throw $this->createNotFoundException();
        }

        if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
          
            //throw new BadRequestHttpException('Password request alerady requested');

            $view = $this->view(array('response' => 'Password request alerady requested', 'pass'=>$user->getPassword()));

            return $this->handleView($view);
        }

        if (null === $user->getConfirmationToken()) {
            
            /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
            
            $tokenGenerator = $this->get('fos_user.util.token_generator');
            
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        if($this->get('fos_user.mailer')->sendResettingEmailMessage($user)) {
        
            $user->setPasswordRequestedAt(new \DateTime());
            
            $this->get('fos_user.user_manager')->updateUser($user);

            $view = $this->view(array('response' => new Response(Response::HTTP_OK), 'pass'=>$user->getPassword()));

            return $this->handleView($view);
        }
    }



    private function getUserRoles($request) {

        $bearer_token = $this->get('fos_oauth_server.server')->getBearerToken($request);
       
        $em = $this->getDoctrine()->getManager();
        
        // select user_id from oauth2_access_tokens where token='$bearer_token'

        $userid = $em->getRepository("App:AccessToken")->createQueryBuilder('at')
        
               ->Where('at.token LIKE :token')
        
               ->setParameter('token', $bearer_token)
        
               ->getQuery()
        
               ->getResult();

        
        $roles = $userid[0]->getUser()->getRoles();

        return $roles;
        
    }




    private function registerUser($email,$username,$password, $roles) {    
        
        $em = $this->getDoctrine()->getManager();
            
        $usersRepository = $em->getRepository("App:User");

        $email_exist = $usersRepository->findOneBy(array('email' => $email));

        if($email_exist) {

            $view = $this->view(array('response'=>$username. ' exist'));

            return $this->handleView($view);
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

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->persist($user);

        $entityManager->flush();

        $user_id = $user->getId();

        $service = new PRFCYCreateClientId($entityManager);

        $createClientIdresponse = $service->createClientId($user);

        $arrayinfo =  array('id' => $user_id,  'user'=>$username,'email'=>$email, 'registred'=>true, 'client_reponse'=>$createClientIdresponse);

        $view = $this->view($arrayinfo);

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
        
        # $client = $accessToken->getClient();
        
        return null;
    }

}
