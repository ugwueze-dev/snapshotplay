<?php

require 'vendor/autoload.php'; // Include the Guzzle library

//use GuzzleHttp\Client;

class SocketPublisher extends SnapshotDataClass {
    private $client;
    private $url;
    private $token;

    function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring);

        

        $this->url = ESINK_API_URL;
        $this->token = ESINK_API_URL;
        
    }

    public function createSocketBodyData($dataToSend, $gameID, $navigationStatus, $userIDSentTo = null){
		//TODO I think this will work for the data passing in, might be a new auth, might be a gameID, etc. 
        $userSocketUUID = $this->getSocketUUIDByUserID($userIDSentTo);
		$data = array(
            "userIDSentTo" => $userIDSentTo,
            "userSocket" => $userSocketUUID,
			"gameID" => $gameID, 
			"navigationStatus" => $navigationStatus,
            "numActiveTargets" => $this->redis->getKey('user' . $userIDSentTo . REDIS_TARGET_STRING), //will return null if key doesn't exist
            "data" => $dataToSend
		);
	
		return $data;
    }

    
    public function publishEventWithCurl($data, $type, $userID, $table, $timestamp = null, $source = null, $version = null) {
        // Data to be sent in the POST request
        $postData = array(
            'data' => $data,
            'type' => $type,
            'userId' => $userID,
            'table' => $table,
            // 'timestamp' => $timestamp ?? time(), // Use current timestamp if not provided
            // 'source' => $source ?? getenv('DEFAULT_SOURCE'), // Use default source if not provided
            // 'version' => $version ?? 1, // Use default version if not provided
            'securityToken' => ESINK_SECURITY_TOKEN,
           
        );
        // Convert the array to JSON
        $jsonPayload = json_encode($postData);  
        
        // Initialize cURL session
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, array(	
            //CURLOPT_URL => 'http://'.ESINK_API_URL.'/publish',	
            CURLOPT_URL => 'https://dev-esink.snapshotplay.com/publish',	
            CURLOPT_RETURNTRANSFER => true,	
            CURLOPT_ENCODING => '',	
            CURLOPT_MAXREDIRS => 10,	
            CURLOPT_TIMEOUT => 0,	
            CURLOPT_FOLLOWLOCATION => true,	
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,	
            CURLOPT_CUSTOMREQUEST => 'POST',	
            CURLOPT_POSTFIELDS =>$jsonPayload,
          CURLOPT_HTTPHEADER => array(	
            'Content-Type: application/json'	
          ),	
        ));


        // Execute cURL request
        $response = curl_exec($curl);

        // Check for errors
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            // Handle error
        }

        // Close cURL session
        curl_close($curl);

        // Handle response
        if ($response === false) {
            // Request failed
            return array('status' => 'error', 'message' => 'Request failed', 'error' => $error_msg);
        } else {
            // Request successful
            // Handle response
            $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($responseCode === 200) {
                // Request was successful
                return array('status' => 'success', 'message' => 'Request successful', 'response' => $response);
            } else {
                // Request failed with status code $responseCode
                return array('status' => 'error', 'message' => 'Request failed with status code ' . $responseCode, 'response' => $response);
            }
        }
    }


    // public function publishEvent($data, $type, $userId, $table, $timestamp = null, $source = null, $version = null) {
    //     $payload = [
    //         'data' => $data,
    //         'type' => $type,
    //         'userId' => $userId,
    //         'table' => $table,
    //         'timestamp' => $timestamp ?? time(), // Use current timestamp if not provided
    //         'source' => $source ?? getenv('DEFAULT_SOURCE'), // Use default source if not provided
    //         'version' => $version ?? 1, // Use default version if not provided
    //         'securityToken' => $this->token,
    //     ];

    //     $response = $this->client->post('/publish', [
    //         'json' => $payload,
    //         'headers' => [
    //             'Authorization' => 'Bearer ' . $this->token, // Pass the token as a header
    //             'Content-Type' => 'application/json',
    //         ]
    //     ]);

    //     return $response->getStatusCode() === 200;
    // }

    
   
}



