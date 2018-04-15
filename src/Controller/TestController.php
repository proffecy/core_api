<?php

namespace App\Controller;

use App\Entity\Test;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
//use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use FOS\RestBundle\Controller\FOSRestController as Controller;


class TestController extends Controller
{
    /**
     * @Get(
     *     path = "/test/detail/{id}",
     *     name = "app_test_detail",
     *     requirements = {"id"="\d+"}
     * )
     *
     * @View(serializerGroups={"detail"})
     */
    public function detail(Test $test)
    {
        return $test;
    }

    /**
     * @Get(
     *     path = "/test/list",
     *     name = "app_test_list",
     * )
     *
     * @View(serializerGroups={"list"})
     */
    public function list()
    {
        return $test = $this->getDoctrine()->getRepository(Test::class)->findAll();
    }

    /**
     * @Post(
     *     path = "/test/create",
     *     name = "app_test_create",
     * )
     *
     * @ParamConverter("test", converter="fos_rest.request_body")
     */
    public function create(Test $test)
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($test);
        $em->flush();

        return $this->view($test, Response::HTTP_CREATED,
            [
                'location' => $this->generateUrl('app_test_detail', ['id' => $test->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);
    }


}
