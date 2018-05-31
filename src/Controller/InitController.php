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
use App\Entity\Human;
use App\Services\PRFCYUserCheck;
use App\Services\PRFCYAuthenticate;


class InitController extends FOSRestController
{

    /**
    *
    * @Get(
    *     path = "/initialise/new/{password}/{mail}/{username}/",
    *     name = "init_app",
    *     requirements = { "mail", "username", "password" }
    * )
    * @View( serializerGroups={"init"} )
    *
    */
    public function intialiseAction(Request $request)
    {

        $email = $request->get('mail');
        
        $username = $request->get('username');
        
        $password =  $request->get('pass');

        $em = $this->getDoctrine()->getManager();

        $usercheck = new PRFCYUserCheck ($em, $this->container);

        $id = $usercheck->userCheck( $email, $username, $password );

        if( $id ) { 

            $human = new Human ();

            # ...

            return $id; 
        }
        
        return false;
        
    }
   

}