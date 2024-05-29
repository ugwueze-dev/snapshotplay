<?php
/**

  _____                _     _____                                    _ 
 |  __ \              | |   |  __ \                                  | |
 | |__) |___  ___  ___| |_  | |__) |_ _ ___ _____      _____  _ __ __| |
 |  _  // _ \/ __|/ _ \ __| |  ___/ _` / __/ __\ \ /\ / / _ \| '__/ _` |
 | | \ \  __/\__ \  __/ |_  | |  | (_| \__ \__ \\ V  V / (_) | | | (_| |
 |_|  \_\___||___/\___|\__| |_|   \__,_|___/___/ \_/\_/ \___/|_|  \__,_|
                                                                        
                                                                        

 
 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayResetPassword() method.
 |--------------------------------------------------------------------------
 */
class ResetPassword extends SnapshotDataClass{
	
	protected $verb;
	protected $phone;
	function __construct($params) {
		parent::__construct($params);

		$this->accessClientListArray['POST']['admin']   = true;
		$this->accessClientListArray['POST']['all']     = true;
		//$this->accessClientListArray['POST']['user']    = true;
		//$this->accessClientListArray['GET']['admin']    = true;
		// $this->accessClientListArray['GET']['all']      = true;
		//$this->accessClientListArray['GET']['user']     = true;
		//$this->accessClientListArray['PUT']['admin']    = true;
		// $this->accessClientListArray['PUT']['all']      = true;
		//$this->accessClientListArray['PUT']['user']     = true;
		//$this->accessClientListArray['DELETE']['admin'] = true; //manually do to avoid any accidental deactivations
		// $this->accessClientListArray['DELETE']['all']   = true;
		//$this->accessClientListArray['DELETE']['user']  = true;

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
		
		if(isset($this->params['verb'] ) && !empty($this->params['verb'])){
			$this->verb = $this->params['verb'];
		}
		else {
			http_response_code(400);
			$errorMsg = 'Oops! The "verb" parameter is missing or invalid.';
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation ); //can add $debugInfo as 4th param
			die(json_encode($response));
			
		}
		if(isset($this->params['phone'] ) && !empty($this->params['phone'])){

			$this->phone = $this->formatPhone($this->params['phone']);
		}
		else {
			http_response_code(400);
			$errorMsg = 'Oops! The "phone" parameter is missing or invalid.';
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			die(json_encode($response));
			
		}

	}
	
	/**
	  _____   ____   _____ _______
	 |  __ \ / __ \ / ____|__   __|
	 | |__) | |  | | (___    | |
	 |  ___/| |  | |\___ \   | |
	 | |    | |__| |____) |  | |
	 |_|     \____/|_____/   |_|
	 */
	function addResetPassword() {
	/*/
		|--------------------------------------------------------------------------
		|         Do a switch case to call the different steps of reset password. 
		|		 Like: Start, Snapshot, End, etc.
		|--------------------------------------------------------------------------
		*/
		if(!isset($this->params['uuid'])  || empty($this->params['uuid']) || RESET_PASSPHRASE_UUID != $this->params['uuid']){
			http_response_code(400);
			$errorMsg = 'Oops! You need to provide a valid unique identifier to reset password.';
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		switch ($this->verb) {
			case 'sendEmail':
				//verify access to start game
				if(isset($this->phone) && !empty($this->phone)){
					return $this->sendEmailCode($this->phone); //setting the standard return in sendEmailCode
				}
				else {
					http_response_code(400);
					$errorMsg = "Oops! You need to provide a valid phone number to reset password.";
					$navigation = null;
					$debugInfo = null;
					$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
					return $response;

				}
				

			case 'resetPassword':
				if((isset($this->phone) && isset($this->params['resetCode']) && isset($this->params['newPassword']))){
					$passwordReset = $this->resetPassword($this->phone,$this->params['resetCode'],$this->params['newPassword']);
					unset($this->params['newPassword']);
					return $passwordReset;
					//return array('status'=> 400, 'message' => 'Error: Phone number is required. ('.__LINE__.')');
				}
				else {
					http_response_code(400);
					return array('status'=> 400, 'message' => 'Error: Phone number, reset code, and password are required to reset Password('.__LINE__.')');
				}
				
			default:
				break;
		}
		http_response_code(400);
		return array('status'=> 400, 'message' => "Bad Request: The 'verb' parameter is missing or invalid.");
	}
	//create a temp code for the password reset
	function createPassphraseResetCode ($length = 6){
		//make a 6 digit random uppercase letters string that does not include 0 or O, l or 1, or I, or S or 5, or Z or 2, or T or 7, or B or 8
		//this is to avoid confusion when reading the code
		//the characters can repeat in the string
		$code = '';
		$characters = 'ABCDEFGHJKLMNPQRUVWXY3469';
		while (strlen($code) < $length) {
			$code .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $code;
	}
	// function to create 
}