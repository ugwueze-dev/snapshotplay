<?php
/**
                _   _                _   _           _   _                 
     /\        | | | |              | | (_)         | | (_)                
    /  \  _   _| |_| |__   ___ _ __ | |_ _  ___ __ _| |_ _  ___  _ __  ___ 
   / /\ \| | | | __| '_ \ / _ \ '_ \| __| |/ __/ _` | __| |/ _ \| '_ \/ __|
  / ____ \ |_| | |_| | | |  __/ | | | |_| | (_| (_| | |_| | (_) | | | \__ \
 /_/    \_\__,_|\__|_| |_|\___|_| |_|\__|_|\___\__,_|\__|_|\___/|_| |_|___/
                                                                          
 */
class Authentications extends SnapshotDataClass
{
    protected $phone;
    protected $user;
    protected $userDetails;
    protected $origin;
    protected $isAuthenticated;

	function __construct($params) { 
		parent::__construct($params);
        
      //  $this->accessClientListArray['POST']['admin']   = true;
        $this->accessClientListArray['POST']['all']     = true;
        //$this->accessClientListArray['POST']['user']    = true;
        // $this->accessClientListArray['GET']['admin']    = true;
        // $this->accessClientListArray['GET']['all']      = true;
        // $this->accessClientListArray['GET']['user']     = true;
        // $this->accessClientListArray['PUT']['admin']    = true;
        // $this->accessClientListArray['PUT']['all']      = true;
        // $this->accessClientListArray['PUT']['user']     = true;
        // $this->accessClientListArray['DELETE']['admin'] = true;
        // $this->accessClientListArray['DELETE']['all']   = true;
        // $this->accessClientListArray['DELETE']['user']  = true;
            // var_dump($this->accessClientListArray);
            // die();
            $isAccessGranted = $this->httpMethodAccessCheck(); //add to all endpoint constructors - set any changes above it
            //var_dump($isAccessGranted);
            if($isAccessGranted == false){
                http_response_code(404);
                $debugInfo = "The httpMethodAccessCheck failed. ";
                $errorMsg = "Oops! You don't have access to this info. Please contact support at support@connectandconquer.com for assistance. error: " . __LINE__;
                $navigation = "Login";//TODO check this
                $response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
                die(json_encode($response));
            }
            $this->user = new Users($params);
            $this->phone = $this->user->formatPhone($params['phone']);
            $this->userDetails= $this->user->getUserByPhone($this->phone);

            if (isset($this->userDetails['error'])) {
                http_response_code(404);
                $errorMsg = "Oops! There is no user for $this->phone . Please double-check your phone number and try again, or use the Signup button to create an account. If you\'re having trouble, you can contact support at support@connectandconquer.comfor assistance.";
                $navigation = "Signup";//TODO check this
                $response = $this->createApiResponse(400, null, $errorMsg, null, $navigation); //can add $debugInfo as 4th param
                die(json_encode($response));

            }
            else {       
                $storedPassPhraseHash = $this->userDetails['passPhraseHash'];
                unset($this->userDetails['passPhraseHash']);
            }
           
            //testing the password check
            $this->isAuthenticated = password_verify($params['passPhrase'], $storedPassPhraseHash);
           

	}
	function POST(){
        
        if ($this->isAuthenticated) {
            $userID = $this->userDetails['id'];
            //add device ID to device table
            if(isset($this->params['deviceToken']) && !empty($this->params['deviceToken'])) {
                $deviceAdded = $this->addOrUpdateDevice($userID, $this->params['deviceToken']);
                if(isset($deviceAdded['error'])){
                    http_response_code(400);
                    $errorMsg = "Oops, there was an error while updating your device token. Please try again, or contact customer support.";
                    $navigation = 'Login';
                    $response = $this->createApiResponse(401, null, $errorMsg, null, $navigation); //can add $debugInfo as 4th param
                    return $response;
                    
                }
                //FIXME - this should go thru prepareReturn.  this was a hack to get it to return data:[{auth:...}] in the format needed
                $this->authJWTstring = $this->createJWT($this->userDetails);
               
                //Store the active targets total in redis for the user (done at login and userTargets/GET to keep updated)
                $userTargets = $this->GetUserTargets($userID);
                $result = $this->updateRedisActiveTargetsTotal($userTargets, $userID);
        
                
                //generate and store the socketUUID in the users table
                $socketUUID = $this->makeUUID();
                $storeSocketUUID = $this->updateSocketUUIDInUserTableByUserID($userID, $socketUUID);
                if(isset($storeSocketUUID['error'])){
                    http_response_code(400);
                    $errorMsg = "Oops, there was an error while updating your socket UUID. Please try again, or contact customer support.";
                    $navigation = 'Login';
                    $response = $this->createApiResponse(401, null, $errorMsg, null, $navigation); //can add $debugInfo as 4th param
                    return $response;
                }
                 $data = array('auth'=>$this->authJWTstring, 'userID'=>$userID, 'socketUUID' => $socketUUID);
                 return $this->prepareReturn($data);
            }
            else {
                http_response_code(404);
                $errorMsg = "Oops! The deviceID is missing. This is required for authorization.";
                $navigation = 'Login';
                $response = $this->createApiResponse(400, null, $errorMsg, null, $navigation ); //can add $debugInfo as 4th param
                return $response;
                
            }
            
        } else {
            //if the password is incorrect
            http_response_code(400);
            $errorMsg = "Oops! It looks like the password you entered is incorrect. Please double-check your password and try again. If you're having trouble, you can use the 'Forgot Password' option or contact support for assistance.";
            $navigation = 'Login';
            $response = $this->createApiResponse(400, null, $errorMsg, null,  $navigation ); //can add $debugInfo as 4th param
            return $response;
        }
    }

    function OPTIONS(){
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        return "OPTIONS, POST"; //FIXME / only showing word OPTIONS - need to show POST as well
    }

     function updateSocketUUIDInUserTableByUserID($userID, $socketUUID){
        $tables = "users";
        $columnArray = array('socketUUID' => $socketUUID);
        $whereArray = array('id' => $userID);
  
        $result = $this->db->update($tables, $columnArray, $whereArray);
   
       return $result;

    }

    


}