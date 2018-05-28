<?php

namespace App\Services; 

class PRFCYGetTokenService 
{

  /**
  * Request to check clientId & secret then get token from Oauth
  *
  * @param string $oauth_route (http://s.wbrm/core_api/public/oauth/v2/token)
  * @param string $client_id
  * @param string $client_secret
  * @param string $password
  * @param string $username
  * @return jwt
  */
  public function getToken($oauth_route, $client_id, $client_secret, $password, $username) {

    $url = $oauth_route."?";

    $url .= "client_id=".$client_id."&client_secret=".$client_secret."&";
    
    $url .= "grant_type=password&password=".$password."&username=".$username; 
    
    $options = array(
          
          CURLOPT_URL            => $url, 
          
          CURLOPT_RETURNTRANSFER => TRUE,
          
          CURLOPT_HEADER         => false,

          CURLOPT_HTTPHEADER    => array('Content-Type: application/json')
    );
    
    # Send request and get response

    $CURL = curl_init();

    curl_setopt_array($CURL,$options);
  
    # Get response

    $content = curl_exec($CURL);
     
    curl_close($CURL);

    return json_decode($content, true);
  }
}
