# core_api

SIGNUP
------
requete http://s.wbrm/core_api/public/signup/test@test.test/test/test
	=>  return client_id;

requete http://s.wbrm/core_api/public/client_id/32prfcy8deadd06230bc1f4d6d7814a0dcda823f6960d9dd3f808da
	=> return client_secret;

LOGIN
------
http POST http://s.wbrm/core_api/public/oauth/v2/token \
    grant_type=password \
    client_id=1_2bcbxd9e24g0gk4swg0kwgcwg4o8k8g4g888kwc44gcc0gwwk4 \
    client_secret=4ok2x70rlfokc8g0wws8c8kwcokw80k44sg48goc0ok4w0so0k \
    username=admin \
    password=admin
ou

http://s.wbrm/core_api/public/oauth/v2/token?grant_type=password@client_id=1_3bcbxd9e24g0gk4swg0kwgcwg4o8k8g4g888kwc44gcc0gwwk4&client_secret=4ok2x70rlfokc8g0wws8c8kwcokw80k44sg48goc0ok4w0so0k&username=admin&password=admin

	=> return bearer MzEwNGVjNjcwNTczODU5NzdiMWQ2NTdjMDVhYWM3MzU2YzViMmZmYWM5OTYyN2I5ODllZDQ1MTBjMTgxNGVhYQ

========================

QUERY protected by oauth
http GET http://s.wbrm/core_api/public/home/2 \
	"Authorization:Bearer MzEwNGVjNjcwNTczODU5NzdiMWQ2NTdjMDVhYWM3MzU2YzViMmZmYWM5OTYyN2I5ODllZDQ1MTBjMTgxNGVhYQ"

CHECK OAUTH IN CONTROLLER
------------------------

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

    $authenticationErrorResponse = $this->checkAuthAndGetErrorResponse($request);
    
    if ($authenticationErrorResponse) {
    
        return $authenticationErrorResponse;
    
    }

# ------------------------------------------------------------------------------------------------