
Check => http://localhost/core_api/public/documentation to get Api Doc

*Use http command line tool or insomnia or other tools to test queries


AUTHENTICATE WITH FOS OAUTH AND FOS USER
========================================

Suscribe :

[ http://localhost/core_api/public/users/new/{email}/{username}/{password} ]

requete http GET http://localhost/core_api/public/users/new/john@who.mail/{john}/myp@ss
	
	=>  return Array ( User informations and Client response if registred )


Signin :

[ http://localhost/core_api/public/users/auth/{email}/{password} ]

requete http GET http://localhost/core_api/public/users/auth/john@who.mail/myp@ss
	
	=> return Array (Token informations);


DO QUERIES PROTECTED BY TOKEN 	
-----------------------------


Get user by id :

[ http://localhost/core_api/public/users/{id} ]

with access_token :
requete http GET http://localhost/core_api/public/users/2 \
	"Authorization:Bearer ZTg0YmRiZGViNmFkOWYyZDk4NmM5YzMxNGFiNDZhYzFjNjA5OGNjNDNjYmRlN2IzYmFiYzdhMzUxZTdjNjZiOQ"

	=> return User informations;
----------------

Get user by mail :

[ http://localhost/core_api/public/users/{mail} ]

with access_token :
requete http GET http://localhost/core_api/public/users/john@who.mail \
	"Authorization:Bearer ZTg0YmRiZGViNmFkOWYyZDk4NmM5YzMxNGFiNDZhYzFjNjA5OGNjNDNjYmRlN2IzYmFiYzdhMzUxZTdjNjZiOQ"

	=> return User informations;

----------------

Check user role: 

[ http://localhost/core_api/public/users/check ]

with access_token :
requete http GET  GET http://localhost/core_api/public/users/check \
	"Authorization:Bearer ZTg0YmRiZGViNmFkOWYyZDk4NmM5YzMxNGFiNDZhYzFjNjA5OGNjNDNjYmRlN2IzYmFiYzdhMzUxZTdjNjZiOQ"

	=> return User Roles;

----------------

Edit user profile mail :

[ http://localhost/core_api/public/users/{oldmail}/{newmail}/{password} ]

http GET http://localhost/core_api/public/users/john@who.mail/johnwho@who.com/myp@ss \
	"Authorization:Bearer ZTg0YmRiZGViNmFkOWYyZDk4NmM5YzMxNGFiNDZhYzFjNjA5OGNjNDNjYmRlN2IzYmFiYzdhMzUxZTdjNjZiOQ"

	=> return edited new user mail;


----------------------------------------------------------------------------------------------


CHECK IF AUTHENTICATED
-----------------------------------------------

=> Use this code for secure and check if authenticated in Controller functions .

    $checkauth = new PRFCYAuthCheck ($this->container);

    $authenticationErrorResponse = $checkauth->checkAuthAndGetErrorResponse($request);

    if ($authenticationErrorResponse) {

        return $authenticationErrorResponse;
    }

    # \O/ else user is authenticated so do something \O/

# ----------------------------------------