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
use App\Services\PRFCYAuthCheck;
use App\Services\PRFCYcheckRoles;
use App\Services\PRFCYcheckPass64;
use App\Services\PRFCYAuthenticate;


class HumanController extends FOSRestController
{

    /**
    *
    * @Post(
    *     path = "/human/new/{password}/{mail}",
    *     name = "human_app",
    *     requirements = { "mail", "password" }
    * )
    * @View( serializerGroups={"init"} )
    *
    */
    public function initialiseAction(Request $request)
    {
        # Need token and endoded password

        $checkauth = new PRFCYAuthCheck ($this->container);

        $authenticationErrorResponse = $checkauth->checkAuthAndGetErrorResponse($request);

        if ($authenticationErrorResponse) {

            return $authenticationErrorResponse;
        }

        $checkpass = new PRFCYcheckPass64();

        $password = $checkpass->decodePass64($request->get('password'));

        $email = $request->get('mail');

        $em = $this->getDoctrine()->getManager();

        $usercheck = new PRFCYUserCheck ($em, $this->container);
        
        $id = $usercheck->userCheck( $email, $password );

        $humanexist = $em->getRepository("App:Human")->createQueryBuilder('h')
        
               ->Where('h.user_id LIKE :id')

               ->andWhere('h.mail LIKE :mail')
        
               ->setParameter('id', $id)

               ->setParameter('mail', $email)
        
               ->getQuery()
        
               ->getResult();

        if( !$humanexist ) { 

            if( $id ) { 

                $human = new Human ();

                $human->setMail( $email );
                
                $human->setIsUser( true );

                $human->setUserId( $id );
            
                $checkroles = new PRFCYcheckRoles( $em, $this->container );

                $roles = $checkroles->userRolesChecking($request);

                if( $roles == "admin" ) $human->setRoles( array('ADMIN', 'USER') );
                
                if( $roles == "superadmin" ) $human->setRoles( array('SUPER_ADMIN', 'ADMIN', 'USER') );

                if( $roles == "user" ) $human->setRoles( array('USER') );

                $em->persist($human);

                $em->flush();

                $human_id =  $human->getId();
                
                $view = $this->view( array(
                    
                    'registred'=>1, 
                    
                    'human_id'=>$human_id,

                    'user_id'=>$id,

                    'roles' => $roles,

                    'email' => $email,

                    'message'=>'please update your organisation and app names'

                    )
                );
                
                return $this->handleView($view);
            }
            
            $view = $this->view( array(
                
                'registred'=>0, 
              
                'message'=>'email or password is not valid'
                )
            );
            
            return $this->handleView($view);  
        }

        $view = $this->view( array(
                
                'registred'=>0, 
              
                'message'=>'Human exist'
                )
            );
            
        return $this->handleView($view); 

    }
   


    /**
    *
    * @Put(
    *     path = "/human/{password}/{email}/{firstname}/{lastname}/{organisation_name}/{organisation_definition}/{phone}/{fullstreet}/{postcode}/{town}/{country}",
    *     name = "human_organisation",
    *     requirements = { "password", "email", "firstname", "lastname", "organisation_name", "organisation_definition", "phone", "fullstreet", "postcode", "town", "country" }
    * )
    * @View( serializerGroups={"init"} )
    *
    */
    public function initHumanAction(Request $request)
    {
        $checkauth = new PRFCYAuthCheck ($this->container);

        $authenticationErrorResponse = $checkauth->checkAuthAndGetErrorResponse($request);

        if ($authenticationErrorResponse) {

            return $authenticationErrorResponse;
        }

        $checkpass = new PRFCYcheckPass64();

        $password = $checkpass->decodePass64($request->get('password'));

        $usercheck = new PRFCYUserCheck ($em, $this->container);

        $id = $usercheck->userCheck( $email, $password );

        $em = $this->getDoctrine()->getManager();

        $human = $em->getRepository("App:Human")->createQueryBuilder('h')
        
               ->Where('h.user_id LIKE :id')

               ->andWhere('h.mail LIKE :mail')
        
               ->setParameter('id', $id)

               ->setParameter('mail', $email)
        
               ->getQuery()
        
               ->getResult();

        if($human) {

            $human->setFirstname($request->get('firstname'));
            
            $human->setLastname($request->get('lastname'));
            
            $human->setOrganisationName($request->get('organisation_name'));
            
            $human->setOrganisationDefinition($request->get('organisation_definition'));
            
            $human->setPhone($request->get('phone'));
            
            $human->setFullstreet($request->get('fullstreet'));
            
            $human->setPostcode($request->get('postcode'));
            
            $human->setTown($request->get('town'));
            
            $human->setCountry($request->get('country'));

            $em->persist($human);

            $em->flush();

            $view = $this->view(array('edited'=>1));

            return $this->handleView($view);
        }

        $view = $this->view(array('edited'=>0));

        return $this->handleView($view);
    }



    /**
    *
    * @Put(
    *     path = "/human/edit/{password}/{email}/{field}/{value}",
    *     name = "human_edit_field",
    *     requirements = { "field", "value" }
    * )
    * @View( serializerGroups={"init"} )
    *
    */
    public function editHumanValueAction(Request $request)
    {
        $checkauth = new PRFCYAuthCheck ($this->container);

        $authenticationErrorResponse = $checkauth->checkAuthAndGetErrorResponse($request);

        if ($authenticationErrorResponse) {

            return $authenticationErrorResponse;
        }

        $checkpass = new PRFCYcheckPass64();

        $password = $checkpass->decodePass64($request->get('password'));

        $email = $request->get('email');

        $em = $this->getDoctrine()->getManager();

        # Check if user exist & return user_id

        $usercheck = new PRFCYUserCheck ($em, $this->container);

        $id = $usercheck->userCheck( $email, $password );

        # Check if human exist with user_id

        $human = $this->getDoctrine()
        
        ->getRepository("App:Human")
        
        ->findOneBy([
            
            'user_id' => $id,
            
            'mail' => $email,
        ]);

        if( $human ) {
            
            $fieldsname = $em->getClassMetadata('App:Human')->getFieldNames();

            $field = $request->get( 'field' );

            $value = $request->get( 'value' );

            $connection = $em->getConnection();
            
            if( $field != 'id' && in_array( $field, $fieldsname ) ) {

                if( $this->getSetter( $field, $value, $email, $id, $em ) ) { 
                    
                    $lastid = $connection->lastInsertId();

                    $view = $this->view(array('edited'=>1, 'message'=>$field . ' updated', 'value'=>$value));

                    return $this->handleView($view);   
                }
            }

            $view = $this->view(array('edited'=>0, 'message'=>'Field ' . $field . ' do not exist'));

            return $this->handleView($view);
        }
    }



    private function getSetter( $field, $value, $email, $id, $em ) {

        $human = $this->getDoctrine()
        
        ->getRepository("App:Human")
        
        ->findOneBy([
            
            'user_id' => $id,
            
            'mail' => $email,
        ]);

        switch( $field )  {

            case "firstname": $func = $human->setFirstname( $value );
            
            case "lastname" : $func = $human->setLastname( $value );
            
            case "organisation_name": $func = $human->setOrganisationName( $value );
            
            case "organisation_definition": $func = $human->setOrganisationDefinition( $value );
            
            case "phone": $func = $human->setPhone( $value );
            
            case "fullstreet": $func = $human->setFullstreet( $value );
            
            case "postcode": $func = $human->setPostcode( $value );
            
            case "town": $func = $human->setTown( $value );
            
            case "country": $func = $human->setCountry( $value );
        }

        return $func;
    }

}