<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
use App\Services\PRFCYAuthCheck;
use App\Services\PRFCYcheckRoles;
use App\Services\PRFCYcheckPass64;
use App\Services\PRFCYRegisterUser;
use App\Services\PRFCYAuthenticate;
use App\Services\PRFCYCreateClientId;
use App\Services\PRFCYGetTokenService;

class UserController extends FOSRestController
{

    /**
     * @Post(
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

        $checkpass = new PRFCYcheckPass64();

        $array['pass'] = $checkpass->decodePass64( $request->get('pass') );

        $em = $this->getDoctrine()->getManager();

        $service = new PRFCYRegisterUser($em);

        $succesfullyRegistered = $service->registerUser($array['mail'], $array['username'], $array['pass'],  $array['role'] );
        
        $view = $this->view($succesfullyRegistered);

        return $this->handleView($view);
    }
    



    /**
     * @Post(
     *     path = "/users/auth/{email}/{password}",
     *     name = "users_auth",
     *     requirements = {"email", "password"}
     * )
     * @View(serializerGroups={"auth"})
     */
    public function usersAuthAction(Request $request)
    {   
        $email = $request->get('email');

        $checkpass = new PRFCYcheckPass64();

        $password = $checkpass->decodePass64( $request->get('password') );

        $em = $this->getDoctrine()->getManager();

        $authservice =  new PRFCYAuthenticate( $em, $this->container );

        $authtoken = $authservice->authenticate( $email, $password );

        $view = $this->view( $authtoken  );

        return $this->handleView( $view );
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
        $checkauth = new PRFCYAuthCheck ($this->container);

        $authenticationErrorResponse = $checkauth->checkAuthAndGetErrorResponse($request);

        if ($authenticationErrorResponse) {

            return $authenticationErrorResponse;
        }
        
        $em = $this->getDoctrine()->getManager();
        
        $checkroles = new PRFCYcheckRoles($em, $this->container);

        $roles = $checkroles->userRolesChecking($request);
        
        switch( $roles ) {

            case "superadmin" : $view = $this->view( array('roles'=>'superadmin') ); return $this->handleView($view); break;

            case "admin" : $view = $this->view( array('roles'=>'admin') ); return $this->handleView($view); break;
            
            case "user" : $view = $this->view( array('roles'=>'user') ); return $this->handleView($view); break;
            
            case "anonym" : $view = $this->view( array('roles'=>'anonym') ); return $this->handleView($view); break;
            
            default: break;
        }
    }



    /**
     * @Get(
     *     path = "/users/id/{id}",
     *     name = "get_user",
     *     requirements = {"id"="\d+"}
     * )
     */
    public function getUserByIdAction(Request $request)
    {
        $checkauth = new PRFCYAuthCheck ($this->container);

        $authenticationErrorResponse = $checkauth->checkAuthAndGetErrorResponse($request);

        if ($authenticationErrorResponse) {

            return $authenticationErrorResponse;
        }

        $id = $request->get('id');

        $users = $this->getDoctrine()->getRepository('App:User')->findById($id);

        if($users) {

            $data = array(

                "id" => $users[0]->getId(),

                "username" => $users[0]->getUsername(),

                "mail" => $users[0]->getEmail(),
            );

            $view = $this->view( $data );

            return $this->handleView($view);
        }

        $view = $this->view( $users );

        return $this->handleView($view);
    }



    /**
     * @Get(
     *     path = "/users/mail/{mail}",
     *     name = "get_user",
     *     requirements = {"mail"}
     * )
     */
    public function getUserByMailAction(Request $request)
    {
        $checkauth = new PRFCYAuthCheck ($this->container);

        $authenticationErrorResponse = $checkauth->checkAuthAndGetErrorResponse($request);

        if ($authenticationErrorResponse) {

            return $authenticationErrorResponse;
        }

        $mail = $request->get('mail');

        $users = $this->getDoctrine()->getRepository('App:User')->findByEmail($mail);

        if($users) {

            $data = array(

                "id" => $users[0]->getId(),

                "username" => $users[0]->getUsername(),

                "mail" => $users[0]->getEmail(),
            );

            $view = $this->view( $data );

            return $this->handleView($view);
        }

        $view = $this->view( array("unknow id") );

        return $this->handleView($view);

    }



     /**
     * @Put(
     *     path = "/users/edit/mail/{mail}/{newmail}/{password}",
     *     name = "edit_mail",
     *     requirements = {"mail", "newmail"}
     * )
     */
    public function editMailProfileAction(Request $request)
    {
         # Checking user by fos

        $checkauth = new PRFCYAuthCheck ($this->container);

        $authenticationErrorResponse = $checkauth->checkAuthAndGetErrorResponse($request);

        if ($authenticationErrorResponse) {

            return $authenticationErrorResponse;
        }

        $checkpass = new PRFCYcheckPass64();

        $password = $checkpass->decodePass64($request->get('password'));

        $oldmail = $request->get('mail');
        
        $newmail = $request->get('newmail');
        
        $user_manager = $this->get('fos_user.user_manager');

        $user = $user_manager->findUserByEmail($oldmail);

        if($user) {

            # Check 1: on email
            
            $factory = $this->get('security.encoder_factory');
            
            $encoder = $factory->getEncoder($user);
            
            $salt = $user->getSalt();

            # Check 2: on password

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

        $view = $this->view(array('edited'=>'error: unknow user'));

        return $this->handleView($view);
    }



     /**
     * @Put(
     *     path = "/users/edit/password/{mail}/{password}/{newpassword}/{confirmpassword}",
     *     name = "edit_password",
     *     requirements = {"mail","password","newpassword","confirmpassword"}
     * )
     */
    public function editPasswordRequestAction(Request $request)
    {
        
        $checkauth = new PRFCYAuthCheck ($this->container);

        $authenticationErrorResponse = $checkauth->checkAuthAndGetErrorResponse($request);

        if ($authenticationErrorResponse) {

            return $authenticationErrorResponse;
        }

        $mail = $request->get('mail');
        
        $checkpass = new PRFCYcheckPass64();

        $password = $checkpass->decodePass64($request->get('password'));
        
        $newpassword = $checkpass->decodePass64($request->get('newpassword'));
        
        $confirmpassword = $checkpass->decodePass64($request->get('confirmpassword'));
        
        # Checking user by fos

        $user_manager = $this->get('fos_user.user_manager');
        
        $factory = $this->get('security.encoder_factory');

        # Check 1: on email

        $user = $user_manager->findUserByEmail($mail);

        if($user) {

            $encoder = $factory->getEncoder($user);
            
            $salt = $user->getSalt();

            # Check 2: on password

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

        $checkauth = new PRFCYAuthCheck ($this->container);

        $authenticationErrorResponse = $checkauth->checkAuthAndGetErrorResponse($request);

        if ($authenticationErrorResponse) {

            return $authenticationErrorResponse;
        }

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






}
