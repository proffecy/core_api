
Check => http://localhost/core_api/public/documentation to get Api Doc


AUTHENTICATE WITH FOS OAUTH AND FOS USER
========================================

SIGNUP
------
requete http://localhost/core_api/public/users/new/{email}/{username}/{password}
	=>  return Array ( User informations and Client response if registred )

requete http://localhost/core_api/public/users/auth/{email}/{password}
	=> return Array (Token informations);



DO QUERIES PROTECTED BY TOKEN
-----------------------------
http GET http://localhost/core_api/public/users/2 \
	"Authorization:Bearer ZTg0YmRiZGViNmFkOWYyZDk4NmM5YzMxNGFiNDZhYzFjNjA5OGNjNDNjYmRlN2IzYmFiYzdhMzUxZTdjNjZiOQ"


----------------------------------------------------------------------------------------------
=> in controller create function to check authentification ...


CHECK IF AUTHENTICATED WITH OAUTH IN CONTROLLER
-----------------------------------------------

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
	    
	    //$client = $accessToken->getClient();

	    return null;
	}

# ------------ Authenticate ------------

=> then use this code for secure and check if authenticated in functions xxxxAction() ...

    $authenticationErrorResponse = $this->checkAuthAndGetErrorResponse($request);
    
    if ($authenticationErrorResponse) {
    
        return $authenticationErrorResponse;
    
    }

    # \O/ else user is authenticated so do something \O/

# ----------------------------------------