<?php
namespace App\Services;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PRFCYAuthCheck
{

	private $em;
	private $container;

	public function __construct(ContainerInterface $container) {
	    $this->container = $container;
	}




	public function checkAuthAndGetErrorResponse($request)
    {
        $tokenManager = $this->container->get('fos_oauth_server.access_token_manager.default');

        $bearerToken = $this->container->get('fos_oauth_server.server')->getBearerToken($request);
        
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