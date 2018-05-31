<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class DocController extends Controller
{



	/**
     * @Get(
     *     path = "/doc/register",
     *     name = "proffecy_doc"
     * )
     */
    public function docAction()
    {
        $appname="Proffecy Core Api";
        
        return $this->render('/core/doc/register_documentation.html.twig', array(
            'appname' => $appname,
        ));
    }


    /**
     * @Get(
     *     path = "/doc/auth",
     *     name = "proffecy_doc_auth"
     * )
     */
    public function docAuthAction()
    {
        $appname="Proffecy Core Api";
        
        return $this->render('/core/doc/auth_documentation.html.twig', array(
            'appname' => $appname,
        ));
    }


    /**
     * @Get(
     *     path = "/doc/services",
     *     name = "proffecy_doc_services"
     * )
     */
    public function docServicesAction()
    {
        $appname="Proffecy Core Api";
        
        return $this->render('/core/doc/services_documentation.html.twig', array(
            'appname' => $appname,
        ));
    }

}