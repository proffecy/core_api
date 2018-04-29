<?php

namespace App\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CoreController extends Controller
{

############################################################################
    public function homeAction(Request $request)
    {
       /* var_dump($request);*/

        $account_check = 0;

        $securityContext = $this->container->get('security.authorization_checker');
        
        if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
        
            $account_check=1;
        }
        
        $appname = 'Proffecy';

        return $this->render('core/home.html.twig', array(

            'appname' => $appname,

            'account_check'=>$account_check,
        ));
    }
}
