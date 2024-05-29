<?php
use Google\Client as Google_Client; //Google API PHP Client for firebase message and play store
use Google\Auth\ApplicationDefaultCredentials; // Google API PHP Client for firebase batch message

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client as GuzzleClient;
use Firebase\JWT\JWT;
//use GuzzleHttp\Client;



/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayPushNotifications() method.
 |--------------------------------------------------------------------------
 */
class Push extends SnapshotDataClass{
    
    function __construct($params, $authJWTstring = null) {
        parent::__construct($params, $authJWTstring);
        $this->db->requiredFields = array(
            // 'userID', 
            // 'senderID', 
            // 'recipientID', 
            // 'notificationText', 
            // 'notificationTypeID', 
            // // 'actionLink', 
            // 'usersPhoneToken', 
            // 'sentDatetime', 
            // 'isRead'
            );

        $this->db->fieldsArray = array(
            'userID'              => @$this->params['userID'],
            'senderID'            => @$this->params['senderID'],
            'recipientID'         => @$this->params['recipientID'],
            'notificationText'    => @$this->params['notificationText'],
            'notificationTypeID'  => @$this->params['notificationTypeID'],
            //   'actionLink'          => @$this->params['actionLink'],
            'usersPhoneToken'     => @$this->params['usersPhoneToken'],
            'sentDatetime'        => @$this->params['sentDatetime'],
            'isRead'              => @$this->params['isRead'],
            'active'              => @$this->params['active']
        );

        $this->id = isset($this->params['id']) ? $this->params['id'] : null;
        $this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
            return ($value !== null && $value !== false && $value !== '');
        });
    }
    

    /**
      _____   ____   _____ _______
     |  __ \ / __ \ / ____|__   __|
     | |__) | |  | | (___    | |
     |  ___/| |  | |\___ \   | |
     | |    | |__| |____) |  | |
     |_|     \____/|_____/   |_|
     */
    function addPush() {
        
        if( $this->params['systemKey'] == null && $this->params['systemKey'] != SYSTEM_KEY){
            http_response_code(401);
                $debugInfo = array(
                    'line'=>__LINE__,
                    'file'=>__FILE__,
                    'error'=>'Endpoint is not public and requires authorization'
                );
                $errorMsg = "Oops! You don't have permission to access this endpoint.";
                $navigation = null;
                $response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation);
                return $response;
        }
        else {
            //PROCESS AN ARRAY OF PHONE NUMBERS TO SEND NOTIFICATIONS TO
            if(isset($this->params['recipientPhoneNumber']) && is_array($this->params['recipientPhoneNumber'])){
                
                $sendNotifications = array();
                foreach($this->params['recipientPhoneNumber'] as $key => $phoneNumber){
                    $phoneNumber = $this->formatPhone($phoneNumber);
                    $userID = $this->getUserIDFromPhone($phoneNumber);
                    $sendNotification = $this->prepareAndSendNotification($userID, $this->params['title'], $this->params['body'], $this->params['status'], $phoneNumber);
                    $sendNotifications[$key]['phoneNumber'] = $phoneNumber;
                    $sendNotifications[$key]['userID'] = $userID;
                    $sendNotifications[$key]['sendNotification'] = $sendNotification;
                    
                }
                return $this->prepareReturn($sendNotifications);
            }else {
                //PROCESS A SINGLE PHONE NUMBER TO SEND NOTIFICATIONS TO
                $phoneNumber = $this->formatPhone($this->params['recipientPhoneNumber']);
                $userID = $this->getUserIDFromPhone($phoneNumber);
                $result = $this->prepareAndSendNotification($this->params['userID'], $this->params['title'], $this->params['body'], $this->params['status'], null);
                $sendNotification = array('sendNotification' => $result, 'phoneNumber' => $phoneNumber, 'userID' => $userID);
                return $this->prepareReturn($sendNotification);
            }
        }

    }
    

    

    

    protected function prepareAndSendNotification($userID, $title, $body, $status, $image){
        $accessToken = $this->getOAUTHToken();
        
        $token = $this->getUserDeviceToken($userID);
        
        //working token
        //$token = 'f29JnxghSY2tN7p_BXYAm9:APA91bF-gjBSk5J1EgdSb3_Cdrg-9lNeVPJRLDhlnDmyU9PzC1uvFIKSvlYQ8SjM5WzfyjcipVFPDRC7UlMsrwlVb9Ck7TLnN3uvd9JuHqunjev-xs549AO5s7xlPB-8WIoAbwTkL-oD';
        
        //$result = $this->sendNotification($token, $title, $body, $accessToken, $status, $image, $icon); //implement once GD added
        $result = $this->sendNotification($token, $title, $body, $accessToken, $status, $image);
        
        return $result;
    }
    private function generateToken($client)
	{
		$client->fetchAccessTokenWithAssertion();
		$accessToken = $client->getAccessToken();
       // var_dump($client->fetchAccessTokenWithAssertion());
		
		// save the oauth token json on your database or in a secure place on your server
		//$tokenJson = json_encode($access_token);
		$this->saveToken($accessToken['access_token']);
		
		return $accessToken['access_token'];
	}
    // protected function getOAUTHToken() {
    //     // Load the service account credentials
    //     $keyFilePath = __DIR__ . '/oauthCredentials/firebaseJsonKey.json'; // Adjust the path accordingly
    //     $scopes = ['https://www.googleapis.com/auth/firebase.messaging']; // Adjust scopes as needed
    
    //     // Create ServiceAccountCredentials instance
    //     $credentials = new ServiceAccountCredentials($scopes, $keyFilePath);
    
    //     try {
    //         // Fetch OAuth2 token
    //         $token = $credentials->fetchAuthToken();
            
    //         // Log the retrieved token
    //         if (isset($token['access_token'])) {
    //             // Token retrieved successfully
    //             // Log or display the token for debugging
    //             error_log('OAuth token retrieved successfully: ' . $token['access_token']);
    //             return $token['access_token'];
    //         } else {
    //             // Token retrieval failed
    //             error_log('Failed to retrieve OAuth token');
    //             return null;
    //         }
    //     } catch (Exception $e) {
    //         // Handle exception
    //         error_log('Error retrieving OAuth token: ' . $e->getMessage());
    //         return null;
    //     }
    // }
    
    
    // protected function getOAUTHToken() {
    //     // Load the service account credentials
    //     $keyFilePath = __DIR__ . '/oauthCredentials/firebaseJsonKey.json'; // Adjust the path accordingly
    //     $scopes = ['https://www.googleapis.com/auth/firebase.messaging']; // Adjust scopes as needed

    //     //TODO get rid of these lines once working
    //     // Load the service account key file
    //     $serviceAccountKey = json_decode(file_get_contents($keyFilePath), true);
    //     // Define the token payload
    //     $payload = [
    //         'iss' => $serviceAccountKey['client_email'], // Issuer claim: The email address of the service account
    //         'scope' => 'https://www.googleapis.com/auth/firebase.messaging', // The scope of the token
    //         'aud' => 'https://oauth2.googleapis.com/token', // Audience claim: The token endpoint URL
    //         'iat' => time(), // Issued at claim: The current timestamp
    //         'exp' => time() + 3600, // Expiration time: Set to 1 hour from now
    //     ];

    //     // Sign the token with the service account's private key
    //     $jwt = JWT::encode($payload, $serviceAccountKey['private_key'], 'RS256');
    
    //     // Create ServiceAccountCredentials instance
    //     $credentials = new ServiceAccountCredentials($scopes, $keyFilePath);
    
    //     // Fetch OAuth2 token
    //     $token = $credentials->fetchAuthToken();
    
    //     // Log the token for debugging
    //     error_log('OAuth2 Token: ' . json_encode($token));

    //     error_log('Token Expiration: ' . date('Y-m-d H:i:s', $token['expiresAt']));

        // header("Content-Type: application/json");
        // echo json_encode([
        //     "Line " . __LINE__ => __FILE__,
        //     'function' => debug_backtrace()[1]['function'] ?? 'N/A',
        //         "zzReturn" => $token['access_token'],
        //         "expire"=> 'Token Expiration: ' . date('Y-m-d H:i:s', $token['expiresAt']),
        //         "JWT"=> $jwt,
        // ]);
        // die();


        

        // $token = 'ya29.c.c0AY_VpZglWI4yJjfRNn2NwNgpL2841N8VRxFs3KnVuDIE5oSCUzGM88tmNVYKmnd01QraIVEVM_gPYhTC8WYGCxYTUz0UxBHPREh-KCCO_7bW3CFeywThKy5QjLS3nXM3yNRIJw5EE7beRTdGF3i-ncq5bzGYImXSYGXczaXNH8jvvHIN864RA56yNvyNdz5x_MxmbFBFPkLksaOIPaGOqvK9A4QDIIH5Lu7Vyl2Kff4pqvhpAkfYWcbjsoyjJaiZG8wIBDmSaPt5in-hAW_gYbq7q9eKg0z6UhY94msJ2wOzYK6z58uG7mnu5s3Mz_3mB9Hma88ZcRzDlrxP68e-nTy1nZtvJNzq6OA_gSiHbjrS0XoyXcbkuMlNaAL387DFv0OQyglpgbsy0IdoQoiY1JhBnfXUWSeiaq7kSjqhJoIz0JISWoZt9jRkrXU1orf2QJeWwVZuI7yuu3ac7kSZv3Ixsh9FQqdamUZs2-eJ3q8j0OSwepFx0cosIsUzOmUmU6Z8Ii96anR3k_ypanqvOpgnvv8uVwVjRZy36djoxU-l0BhIY1fJoQ_FrVvO_OUqFlgwc_nQlIl2lnZc-Xh5M2marX_i6YS49j11Y7dr-n-Mn4xxn80loRSBVXM5chq5uvf7ck5Ivi-aIRoaR_0FV_jVj-d5xU0WU9443y9e8xSf2OirqMkId3eXgcf_hlsfiU96d7-f-uZUvR2zMFyqWZlJd6k7s9kk9pk8gc0t3JMbQzfVQfuv3Jz3Udv56nuzBU16eQ3p31ax3sfsFyJkb68OrO7XfXkF8Yfehz71VxeyBs2wM-o0yl1S0efjzZXzm2omO0Jc3gfIR53ZB-hjb1FkM3Q_yjyuodkYZi5x9ySlb-xuxfJlSbrOtQ3ivQtz5h_Oj-ush0MVgkQRW3otmkqhwFwIfzfY0k6Zj98uSQauShjot4MUdU1triu3imliwxieOVwu-pbJM_o3BtMIaxQfcr3mRuY7qr5_BJsWqiRzs2sV2x07zBh'; // Replace with your actual access token
        // $secretKey = '-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDhfWOZs4IjVVS+\nZoLCG3lQ4S8eNnZunhOiU+RZWDafYV9fRZKqV0/0+rdkU4H24pOYGtz3aHpjEuNL\nTMoIBEWWR4bmQaYYvuzp5i+LPXwWPLJyoMWRn04nOpIvtOC+ZwtuDWyB5GdyjI+W\n95qXPlLIXKaK8KWEUxc2mCamBJDD4bAupvPSTrWfzWnBdwBihCxzyz6liQjPEIaQ\nZ8fPsKZg6mz2zWJojNkHcgUPM5SToRJM0Ry3bWF2e8ETRX9Boje+quMyAcl2Sva2\nr7Df3n0M+ODc2SSBrUskdpmv+mFqyFtIxbNa20WtLhjYN+hVXnEGAPsnGBzGhNxA\njK/dLGprAgMBAAECggEAQyuJeHc/TIw2RtOdnvn1LREvaoYZyIDhY2kvLCg5eUfV\n07kTZBfHUzg+Wi4AGI3NHrHd4ftm/c1aFQvGYRX4rrgW6M9kjZmOVGzD++vwY2Vo\nWPoocGUusEPj2T899aoXfwyTpqvl3vsdKQEQNX6UxWfIGrpt69meoB454RmaECdD\nBFCOUhVbDRDlZdADpBwDbo+q/LczuwRJSVOLLnd3x7qX7fcD7UB9CGtXcxstAIbI\n6toABpB9MMnzC255fHadHJ6tTo8FIXWmhFfEWrK9to0Jtd6jOPn3pLSzAtoRVUN8\nqw7ABflWwTQcOCWScJbJmJ9XI8VsfwuOopxiJNxGkQKBgQD6L+IXk3BL1KtsEvQa\nXMhxO5fXKSzBnwn05dtZs3YpG7g+0PMERIdo1j+5Nemx7nr9J6GaNTc9XRHZ8N/U\niXPt4J+HFa0LJWxmdCPSrQsw19dTQf4imvKrxK2HQrHXgtHSazFpVbnxfEMChDC3\nksYkm1Awxa3Hkwn4Ygcni8+KOwKBgQDmups0cufds3OFn0gZoJVmuXYs5eljeeeN\nd/KKoY2/YJWWoc/GF1KFDLsMNjMc8XTfjOKGSMuy51A3bw/+YbY+cVwDQot35iG/\nqEETMF2MzHjqYz60deUiMcluA7V+KLRi+YflbupIo04wtwV8EQBmmSNPZemNZ3Wm\nKdfGPAxtkQKBgA/fhdq1EgTdo1qiATbC+Dym6UVPaXWHX5xYEQip6C9HgPMLUr/M\nkULHhRd9IiyV0tvI7enYwn50LzPCopDbf61x+O0bJPV2FGCTscomDHW23AR+RV9w\nsUDQo6nXQ21G8YUGJAZo1/lIRwAb3vNmroeYCwhtIk9tNs65eXa62hoNAoGANdY8\n+NQG/lgtEHTGIhSyR7HmvTs4Zhm/yea/FVH0/T7QKMHA9XvjqV/7FTJgYtSEbNU2\n1haOUURgF0828ChkmdVhnvEcEfIE1e+wn4Wdjoj3fdmCfCE2QfBUC/OZmcJttNp8\ntzBWQnS4fe12vihJa6c38gLZqEEH1qnUy1CfpcECgYAcKg7FWIumidjyCh0kmHUg\nAVsdwKSeQlkap2HNXXI8KWzkY4kzrcw+PiT5rtf5JVx3Hk3L7w/joFk9J4jafGeA\nhU91kJpFrE1lbPTqZ2Cs8P4L9fQPvIoVbUKkJQI5bciKQxqqp661snSkkaQ9T7xQ\ncQO1WIebuKJXAkMMLFdsmg==\n-----END PRIVATE KEY-----\n';
        // // Decode the token
        // $decodedToken = JWT::decode($token, $secretKey);

        // // Access the claims
        // $iat = $decodedToken->iat; // Issued at claim (creation time)

        // echo 'Token created at: ' . date('Y-m-d H:i:s', $iat);
    
    //     return $token['access_token'];
    // }
    protected function getOAUTHToken(){
        $filePath = __DIR__ . '/oauthCredentials/firebaseJsonKey.json';
        $client = new Google_Client();
        
        $client->setAccessType("offline");
        $client->setPrompt("consent");
    
        try {
            $client->setAuthConfig($filePath);
            $client->addScope(Google_Service_FirebaseCloudMessaging::FIREBASE_MESSAGING);
    
            // Retrieve the saved OAuth token if it exists
            $savedToken = $this->getSavedToken("oauthTokenFirebase");
            if ($savedToken != null) {
                // Set the saved token to the client
                $client->setAccessToken($savedToken);
                // var_dump($client->isAccessTokenExpired());
                // die();

                if ($client->isAccessTokenExpired()) {
                    $client->fetchAccessTokenWithAssertion();
                    $accessToken = $client->getAccessToken();
                    $this->saveToken($accessToken['access_token']);
                    return $accessToken['access_token'];
                } else {
                    // Token is still valid, return it
                    return $savedToken;
                }
            } else {
                // No token exists, generate a new one
                $client->fetchAccessTokenWithAssertion();
                $accessToken = $client->getAccessToken();
    
                // Save the token
                $this->saveToken($accessToken['access_token']);
    
                return $accessToken['access_token'];
            }
        } catch (Google_Exception $e) {
            // Handle exception
            $errorMsg = "Oops! There was an error sending a notification," . $e->getMessage();
            $navigation = null;
            $debugInfo = null;
            $response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation);
            return $response;
        }
    }
    
    protected function getSavedToken($key){
		$table = 'configurations';
		$where = array(
			'keyName' => $key
		);
		$columnsArray = array(
			'value'
		);
		$result = $this->db->select(
			$table,
			$where,
			array(),    // whereGreater
			array(),    // whereLess
			$columnsArray, // columnsArray
			'',         // whereMore
			false       // groupByColumn
		);
		if(isset($result['data'][0]['value'])){
			
			return $result['data'][0]['value'];
		}
		else {
			
			return null;
		}
		
	}
    private function prepareData($token,$title, $body, $accessToken, $status = null, $image = null) {
        $message = [
            'message' => [
                //'token' => $token,

                'token'=> 'c37c-ouv1kILpDh2jH92H5:APA91bGDBLp8Fx5DNOGnb7_FLG2dUcMLGobIxxvHkHh8Z0Gy5w_aW0FPwKw7MPSHZR-FAwI2eEdiY2f23J8NvTfyW7D7RqdqpvENCqnlH81NyMJAB1Nda1oDTd331qL9ohJGXoRfAcny',
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'image' => $image,
                ],
                'data' => [
                    'isweb' => 'True',
                    'image' => $image,
                    'weburl' => 'https://www.google.com/',
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'mutable-content' => 1,
                        ],
                    ],
                    'fcmOptions' => [
                        'image' => $image,
                    ],
                ],
                'android' => [
                    'notification' => [
                        'image' => $image,
                    ],
                ],
            ],
        ];
        return json_encode($message);
    }
    function sendNotification($token,$title, $body, $accessToken, $status = null, $image = null) {
        $projectId = 'snapshot-play-bb404'; // Replace with your Firebase project ID
        $url = "https://fcm.googleapis.com/v1/projects/snapshot-play-bb404/messages:send";
        $encodeData = $this->prepareData($token,$title, $body, $accessToken, $status, $image);
        
        // echo '<pre>';
        // print_r($token);
        // echo '<br>';
        // print_r($title);
        // echo '<br>';

        // print_r($body);
        // echo '<br>';

        // print_r($accessToken);
        // echo '<br>';

        // print_r($status);
        // echo '<br>';



        // die("this is in the correct place");

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' =>  'application/json',
        ];
        $client = new GuzzleClient();

        try {
            $request = $client->post($url, [
                'headers' => $headers,
                "body" => $encodeData,
            ]);


            $response = $request->getBody();

            return $response;
        } catch (Exception $e) {
            echo '<pre>';
            print_r($e);
           die('there was an error') ;
           //add additional error notification
        }
        // $message = [
        //     'message' => [
        //         //'token' => $token,

        //         'token'=> 'f29JnxghSY2tN7p_BXYAm9:APA91bF-gjBSk5J1EgdSb3_Cdrg-9lNeVPJRLDhlnDmyU9PzC1uvFIKSvlYQ8SjM5WzfyjcipVFPDRC7UlMsrwlVb9Ck7TLnN3uvd9JuHqunjev-xs549AO5s7xlPB-8WIoAbwTkL-oD',
        //         'notification' => [
        //             'title' => $title,
        //             'body' => $body,
        //             'image' => $image,
        //         ],
        //         'data' => [
        //             'isweb' => 'True',
        //             'image' => $image,
        //             'weburl' => 'https://www.google.com/',
        //         ],
        //         'apns' => [
        //             'payload' => [
        //                 'aps' => [
        //                     'mutable-content' => 1,
        //                 ],
        //             ],
        //             'fcmOptions' => [
        //                 'image' => $image,
        //             ],
        //         ],
        //         'android' => [
        //             'notification' => [
        //                 'image' => $image,
        //             ],
        //         ],
        //     ],
        // ];
    
       
        
       // die("this is in the correct place");

        // $options = [
        //     'http' => [
        //         'header' => "Content-type: application/json\r\n" .
        //                     "Authorization: Bearer {$accessToken}\r\n",
        //         'method' => 'POST',
        //         'content' => json_encode($message),
        //     ],
        // ];
    
        // $context = stream_context_create($options);
        // // $options = stream_context_get_options($context);
        // // print_r($options);
        // // die($context);
        // $result = file_get_contents($url, false, $context);
    
        // if ($result === false) {
        //     $error = error_get_last();
        //     echo "HTTP request failed: " . $error['message'];
        // }
    
    
    }
    
    // function sendNotification($token, $title, $body, $accessToken, $status = null, $image = null) {

	// 	$url = 'https://fcm.googleapis.com/v1/projects/snapshot-play-bb404/messages:send';
	// 	//$accessToken = 'YOUR_FCM_ACCESS_TOKEN';

	// $message = [
	// 	'message' => [
	// 		//'token' => 'f29JnxghSY2tN7p_BXYAm9:APA91bF-gjBSk5J1EgdSb3_Cdrg-9lNeVPJRLDhlnDmyU9PzC1uvFIKSvlYQ8SjM5WzfyjcipVFPDRC7UlMsrwlVb9Ck7TLnN3uvd9JuHqunjev-xs549AO5s7xlPB-8WIoAbwTkL-oD',
	// 		'token' => $token,
	// 		'notification' => [
	// 			'title' => $title,
	// 			'body' => $body,
	// 			'image' => $image,
	// 		],
	// 		'data' => [
	// 			'isweb' => 'True',
	// 			'image' => $image,
	// 			'weburl' => 'https://www.google.com/',
	// 		],
	// 		'apns' => [
	// 			'payload' => [
	// 				'aps' => [
	// 					'mutable-content' => 1,
	// 				],
	// 			],
	// 			'fcmOptions' => [
	// 				'image' => $image,
	// 			],
	// 		],
	// 		'android' => [
	// 			'notification' => [
	// 				'image' => $image,
	// 			],
	// 		],
	// 	],
	// ];

	// $options = [
	// 	'http' => [
	// 		'header' => "Content-type: application/json\r\n" .
	// 					"Authorization: Bearer $accessToken\r\n",
	// 		'method' => 'POST',
	// 		'content' => json_encode($message),
	// 	],
	// ];
    
    // $context = stream_context_create($options);
    // $result = file_get_contents($url, false, $context);
    // var_dump($context);
    // var_dump($result);

    // header("Content-Type: application/json");
    // echo json_encode([
    //     "Line " . __LINE__ => __FILE__,
    //     'function' => debug_backtrace()[1]['function'] ?? 'N/A',
    //     "zzReturn" => file_get_contents($url, false, $context),
    //     "ANOTHER" => $result,
    // ]);
    // die();
 
    // if ($result === false) {
    //     $error = error_get_last();
    //     echo "HTTP request failed: " . $error['message'];
    // }
    //         return $result;
        
    //     }
}