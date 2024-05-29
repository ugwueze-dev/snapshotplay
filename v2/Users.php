<?php
/**
  _    _                      _____ _               
 | |  | |                    / ____| |              
 | |  | |___  ___ _ __ ___  | |    | | __ _ ___ ___ 
 | |  | / __|/ _ \ '__/ __| | |    | |/ _` / __/ __|
 | |__| \__ \  __/ |  \__ \ | |____| | (_| \__ \__ \
  \____/|___/\___|_|  |___/  \_____|_|\__,_|___/___/
 */
class Users extends SnapshotDataClass
{
	protected $passPhraseHash;
	protected $phone;
	protected $birthdate;
	protected $results = array();
	protected $error = array();
	public $subscriptionDetailArray = array();
	public $subscriptionBasicsArray = array();
	public $activeGameTokensArray = array();

	protected $resetPassPhraseFlag = false;
	protected $resetPhoneNumberFlag = false;

	function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring, false);

		$this->accessClientListArray['POST']['admin']   = true;
		$this->accessClientListArray['POST']['all']     = true;
		$this->accessClientListArray['POST']['user']    = true; //This is needed for a user to create a game. 
		$this->accessClientListArray['GET']['admin']    = true;
		// $this->accessClientListArray['GET']['all']      = true;
		$this->accessClientListArray['GET']['user']     = true;
		$this->accessClientListArray['PUT']['admin']    = true;
		// $this->accessClientListArray['PUT']['all']      = true;
		$this->accessClientListArray['PUT']['user']     = true;
		//$this->accessClientListArray['DELETE']['admin'] = true; //manually do to avoid any accidental deactivations
		// $this->accessClientListArray['DELETE']['all']   = true;
		$this->accessClientListArray['DELETE']['user']  = true;

		
		$this->userHttpMethodGranted = $this->httpMethodAccessCheck(); //add to all endpoint constructors - set any changes above it
	
		if($this->userHttpMethodGranted == false){
			http_response_code(401);
			$errorMsg = "Oops! You do not have permission to access this info. Code: " .__LINE__ ;
			$debugInfo = 'The httpMethodAccessCheck on line ' . __LINE__ . ' returned ' . $this->userHttpMethodGranted . '. ';
			$navigation = null; //TODO This is check will be changed with new authentication
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			die(json_encode($response));
		}

	
		if(isset($this->claimUserID)){
			$this->userID = $this->claimUserID;
		}
		//THIS IS HANDLED IN RESETPASSWORD endpoint
		// if(!empty($this->params['resetPassPhrase']) ){
		// 	$this->resetPassPhraseFlag = true;
		// }
		// $this->resetPassPhraseFlag = isset($this->params['resetPassPhrase']) ? $this->params['resetPassPhrase'] : false;
		
		if(!empty($this->params['resetPhone']) ){
			$this->resetPhoneNumberFlag = true;
		}
		$this->resetPhoneNumberFlag = isset($this->params['resetPhone']) ? $this->params['resetPhone'] : false;

		$this->phone = @$this->formatPhone($this->params['phone']); //TODO! allowing fewer digits than 10 - messes up formatting
		$this->birthdate = @$this->formatDate($this->params['birthdate']); 
		
		
	
		$this->db->requiredFields = array(
			'fName',
			'lName',
			'userRoleID',
			'email',
			'phone',
			'birthdate',
			// 'selfieSnapshotID'//not required as they have not created it yet
		);
		$this->db->fieldsArray = array(
			'id'          => @$this->params['id'],
			'fName'       => @$this->params['fName'],
			'lName'       => @$this->params['lName'],
			'userRoleID'        => @$this->params['userRoleID'],
			'email'       => @$this->params['email'],
			//'passPhraseHash' => @$this->passPhraseHash, //setting this value is handled in PUT and POST methods
			'phone'       => @$this->phone,
			'birthdate'   => @$this->birthdate,
			'selfieSnapshotID' => @$this->params['selfieSnapshotID'],
			'zipcode'     => @$this->params['zipcode'],
			'country'     => @$this->params['country'],
			'state'       => @$this->params['state'],
			//'userRoles'   => $this->userRolesArray, 
			// 'active'      => @$this->params['active'],
			
		);
		
		//see if params has a valid 'active' value then add it to the fieldsArray
		if (isset($this->params['active']) && ($this->params['active'] == 0 || $this->params['active'] == 1)){
			$this->db->fieldsArray['active'] = $this->params['active'];
		}
		//defaults to true if not set
		else {
			$this->db->fieldsArray['active'] = 1;
		}

		//if the method called is not addUsers or updateUsers, then we don't want to persist the password in the memory... Yes I am paranoid... :) 
		if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT' ){  
			unset($this->params['passPhrase']);// remove the plain text password from memory
		}
		//$this->id = isset($this->params['id']) ? $this->params['id'] : null; //set in parent constructor
		if($_SERVER['REQUEST_METHOD'] == 'POST' )
		{
			$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
				return ($value !== null && $value !== false && $value !== '');
			});
		}
		
		if(!empty($this->claimUserID)){
			$this->subscriptionBasicsArray = $this->getActiveSubscriptionBasicsByUserID($this->claimUserID); //if null then no active subscription
			$this->subscriptionDetailArray = $this->getActiveSubscriptionWithTransactionDetailsByUserID($this->claimUserID); //if null then no active subscription
		
			$tokens = $this->getActiveGameTokensByUserID($this->claimUserID);

			if(empty($tokens)){
				$this->activeGameTokensArray['playerTokens'] = 0;
				$this->activeGameTokensArray['hostTokens'] = 0;
			}
			else {
				
				foreach($tokens as $tokenType => $tokenDetails){
					if($tokenDetails['productID'] == PLAYER_TOKEN){
						//If quantity is not set, then set it to 0
						$this->activeGameTokensArray['playerTokens'] = $tokenDetails['totalQuantity'] ?? 0;
					}
					else if($tokenDetails['productID'] == HOST_TOKEN){
						$this->activeGameTokensArray['hostTokens'] = $tokenDetails['totalQuantity'] ?? 0;
					}
		
				}
			
			}
		}
		else {
			$this->activeGameTokensArray['playerTokens'] = 0;
			$this->activeGameTokensArray['hostTokens'] = 0;
		}
	
		// print_r(array('error'=>__LINE__."", 'this->id'=> $this->id, 'this->userRole'=> $this->userRole, 'this->requiredRoles'=> $this->requiredRoles));
		
	}
	
	/**
	  _____ ______ _______
	 / ____|  ____|__   __|
	 | |  __| |__     | |
	 | | |_ |  __|    | |
	 | |__| | |____   | |
	  \_____|______|  |_|

	 */
	function displayUsers(){ 
		
		$isAdmin = $this->isAdmin();
		if($isAdmin == true){
			$errorMsg = "This functionality has not been implemented for Admins.";
			$debugInfo = [
				"message" => "This functionality has not been implemented for Admins.",
				"location" => __FILE__ . " line " . __LINE__,
			]; 
			$response = $this->createApiResponse(400, null, "This functionality has not been implemented for Admins.", $debugInfo, null); //can add $debugInfo as 4th param
			return $response;
		}

		//todo: find where this is being call ed and is missing the $this->claimUserID
		if(empty($this->claimUserID)){
			$errorMsg = "Oops! User ID must not be empty";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		//changed to getUserDetailsWithSnapshotURL because we have a default snapshot for all users now
		// $results = $this->getUserDetails($this->claimUserID);
		$results = $this->getUserDetailsWithSnapshotURI($this->claimUserID);

		if (empty($results[0])){
			http_response_code(404);
			$errorMsg = "Oops! We don't have a record for this user.";
			$debugInfo = array('message' => "Could not find user with ID: $this->claimUserID", 'id'=>$this->claimUserID, 'results'=>$results);
			$navigation = 'Signup';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
		
		$this->results = $results[0]; 
	
		if(empty($this->results)){ //TODO
			return array('error'=>__LINE__.": Could not find user with ID: $this->claimUserID");
		}
		$this->results['selfieSnapshotURI'] = $this->results['selfieSnapshotURI'] . "?ts=" . time(); //add a time to the end of the URL to force a refresh		
		$this->results['subscriptionDetails'] = $this->subscriptionBasicsArray;
		$this->results['activeGameTokens'] = $this->activeGameTokensArray;
				
		$this->mainQuery	  = $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;
		$this->returnedItems = count($this->results);
		

		return  $this->prepareReturn($this->results);
	}
	/**
	  _____   ____   _____ _______
	 |  __ \ / __ \ / ____|__   __|
	 | |__) | |  | | (___    | |
	 |  ___/| |  | |\___ \   | |
	 | |    | |__| |____) |  | |
	 |_|     \____/|_____/   |_|
	 */
	function addUsers(){
		/**********************************************************************************
		 * New account rules - MVP //README
		 * New accounts are given targetSnapshotURI of the company logo
		 * Upon account creation a user is added to game #1 - which is the tutorial game
		 * They are assigned the role of active player
		 * In the uploads folder, a new folder is created with the alphanumeric of the user's ID
		 */
		
		$isAdmin = $this->isAdmin();
		if($isAdmin == true){
			$errorMsg = "This functionality has not been implemented for Admins.";
			$debugInfo = [
				"message" => "This functionality has not been implemented for Admins.",
				"location" => __FILE__ . " line " . __LINE__,
			]; 
			$response = $this->createApiResponse(400, null, "This functionality has not been implemented for Admins.", $debugInfo, null); //can add $debugInfo as 4th param
			return $response;
		}
		if (!empty($this->id)){
			http_response_code(400);
			$errorMsg = "Oops! User ID must not be set when adding User";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		if (!empty($this->params['passPhrase'])){
			$this->db->fieldsArray['passPhraseHash'] = $this->createPasswordHash($this->params['passPhrase']);
			unset($this->params['passPhrase']);// remove the plain text password from memory
		}
		else{
			http_response_code(400);
			$errorMsg = "Oops! You need to include the user passPhrase when adding a new user.";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		if ($this->isPersonOver13($this->birthdate) == false) {
			http_response_code(400);
			$errorMsg = "Oops! You must be over 13 years old to use this service.";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		} 
		
		
		$this->params['active'] = 1;
		
		$addResult = $this->addUserWithTargetID($this->db->fieldsArray, $this->db->requiredFields);
	
		if(!empty($addResult['id'])){
			$this->id = $addResult['id'];
			$this->addNewRoleForUser($this->id, $this->params['userRoleID']);
			$this->claimUserID = $this->id; //HACK not sure if this is the best way to do this
		
			//EACH USER STARTS WITH 3 FREE GAMEPLAY TOKENS AND 3 FREE HOST TOKENS
			$productID = PLAYER_TOKEN;
			$quantity = 1;
			$playerTokensAdded = $this->addFreeGameTokens($this->id, $productID, $quantity);
		
			$productID = HOST_TOKEN;
			$quantity = 1;
			$hostTokensAdded = $this->addFreeGameTokens($this->id, $productID, $quantity);

			$tokens = $this->getActiveGameTokensByUserID($this->id);
	
			foreach($tokens as $tokenType => $tokenDetails){
				if($tokenDetails['productID'] == PLAYER_TOKEN){
					//If quantity is not set, then set it to 0
					$this->activeGameTokensArray['playerTokens'] = $tokenDetails['totalQuantity'] ?? 0;
				}
				else if($tokenDetails['productID'] == HOST_TOKEN){
					$this->activeGameTokensArray['hostTokens'] = $tokenDetails['totalQuantity'] ?? 0;
				}
	
			}
			return $this->displayUsers();
		}
		else {
			http_response_code(400);
			$errorMsg = "Oops! Something went wrong and your record was not added. Please try again. If it continues contact support@connectandconquer.com. Error Details: " . $addResult['details'];
			$debugInfo = array("message" => "Could not add user record", "details" => $addResult['details']);
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		
	}
	
	/**
	  _____  _    _ _______
	 |  __ \| |  | |__   __|
	 | |__) | |  | |  | |
	 |  ___/| |  | |  | |
	 | |    | |__| |  | |
	 |_|     \____/   |_|
	 */
	function updateUsers(){

		$isAdmin = $this->isAdmin();
		if($isAdmin == true){
			$errorMsg = "This functionality has not been implemented for Admins.";
			$debugInfo = [
				"message" => "This functionality has not been implemented for Admins.",
				"location" => __FILE__ . " line " . __LINE__,
			]; 
			$response = $this->createApiResponse(400, null, "This functionality has not been implemented for Admins.", $debugInfo, null); //can add $debugInfo as 4th param
			return $response;
		}
		if (!empty($this->id)) {
			
			//UPDATE ONLY LOGGED IN USER DATA
			if($this->params['id'] != $this->claimUserID){ //Can user update another person's data? NO
				http_response_code(403);
				$errorMsg = "Oops! You don't have permission to update this user's data.";
				$debugInfo = array("message" =>"Requested ID to change does not match current logged in user", "details" => "Requested ID: $this->id, Logged in user ID: $this->claimUserID");
				$navigation = null;
				$response = $this->createApiResponse(403, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			} 

			//HANDLING RESET PHONE 
			if($this->resetPhoneNumberFlag == true){
				// print_r('reset phone flag is true '. PHP_EOL); 
				$this->resetPhoneNumber();
				$this->resetPhoneNumberFlag = false;
			}
			else {
				$this->onUpdateUsePhoneInDB($this->claimUserID);
				
			}
			
			//HANDLING RESET PASSPHRASE
			if($this->resetPassPhraseFlag == true){
				http_response_code(400);
				$errorMsg = "Please use the resetPassword endpoint to reset your password.";
				$navigation = 'ResetPassword';
				$debugInfo = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				// $this->resetPassPhraseHash();
				// $this->processedParams = "Sent params were processed except for phone number.";
			}
			//HANDLING UPDATING THE SELFIE SNAPSHOT (Profile pic)
			$userDetails = $this->getUserDetailsWithSnapshotURI($this->claimUserID);
			
			//if the user's selfieSnapshotID is different than the one sent in the request, then update the user's selfieSnapshotID
			if($userDetails['selfieSnapshotID'] != $this->params['selfieSnapshotID']){
				//handle updating profile image
				$selfieSnapshotID = $this->params['selfieSnapshotID'];
				//get the userXtargetID - by creating a new one
			
				$isActive = 0;
				$userXTargetRecord = $this->addUserXTargetRecord($this->claimUserID, $userDetails[0]['targetID'], 1, TARGET_STATUS_CLOSED_COMPLETE, $isActive, date('Y-m-d H:i:s')); //this is purposefully hardcoded to the demo game (1)
				//$userXtargetID = $this->addUserTargetRecord($userXTargetRecord, TARGET_STATUS_CLOSED_COMPLETE);

				//UPDATE THE TARGET RECORD FILEURI SYMBOLIC LINK TO POINT TO NEW SNAPSHOT IMAGE
				$snapshotRecord = $this->getSnapshotRecord($selfieSnapshotID);
				//GET the subDir and filename from the snapshotID
				$subDirAndFileName = substr($snapshotRecord['fileURI'], strpos($snapshotRecord['fileURI'], "/media/users/") + strlen("/media/users/"));
				$fileToPointTo = MEDIA_UPLOADS_DIR . "users/" . $subDirAndFileName;
				//update the new symbolic link	
				$symbolicLink = $this->createOrUpdateUserMediaStorageDirectoryAndSymbolicLink($this->claimUserID, $fileToPointTo);		

			
			}
			//UPDATE THE USER RECORD
			$columnsUpdateArray = array(
				'fName'       => @$this->params['fName'],
				'lName'       => @$this->params['lName'],
				'email'       => @$this->params['email'],
				'selfieSnapshotID' => @$this->params['selfieSnapshotID'],
				'zipcode'     => @$this->params['zipcode'],
				'country'     => @$this->params['country'],
				'state'       => @$this->params['state']
			);
			
			
			
			$whereClause = array('id'=>$this->claimUserID);
			$updated = $this->db->update('users', $columnsUpdateArray, $whereClause, $requiredFieldsArray = array());

			
			//ADD USER ROLE IF IT DOESN'T EXIST
			$userRoleRecordInfo = $this->userRoleRecordExists($this->id, $this->params['userRoleID']);
			
			if( $userRoleRecordInfo == false){
				$this->addNewRoleForUser($this->id, $this->params['userRoleID']);
			}
			//UPDATE RECORD INSTEAD OF ADDING NEW - IF EXISTS BUT IS NOT ACTIVE (if a user went from userRoleID to unuserRoleID ti would leave it not active)
			else {
				if($userRoleRecordInfo['status'] == 'not_active'){
					//$this->db->activateUserRole($this->claimUserID, $this->params['userRoleID'], $userRoleRecordInfo['id']);
					//activate existing record
					$table = 'userXuserRoles';
					$fieldsArray = array('id'=>$userRoleRecordInfo['id'],'userID'=>$this->claimUserID,'userRoleID'=>$this->params['userRoleID'],'active'=>1);
					$where = array('id'=>$userRoleRecordInfo['id']);
					$requiredFields= array('id','userID','userRoleID','active');
					$updated = $this->db->update($table, $fieldsArray, $where, $requiredFields);
					// print_r(array('id'=>$userRoleRecordInfo['id'], 'thisID'=> $this->id)); die('226');
					// print_r($updated); die();
				}
			}
			
			//HANDLING IF USER GOES FROM PAID TO UNPAID - DEACTIVATE RECORD
			$userXuserRoleHostRecordInfo = $this->userRoleRecordExists($this->claimUserID, 2);
	
			if ($userXuserRoleHostRecordInfo !== false && $this->params['userRoleID'] < 2) {
				$returnArray[] = $this->db->deactivateUserRole($userXuserRoleHostRecordInfo);
				if($returnArray[0]['status'] == 'error'){
					http_response_code(400);
					$errorMsg = "Oops! Something went wrong and your record was not updated. Please try again. If it continues contact support@connectandconquer.com";
					$debugInfo = array("message" => "Could not remove host role from user", "details" => $returnArray[0]);
					$navigation = null;
					$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
					return $response;
					
				}
			}

			if ($updated['status']!=='error') {
				// If the update is successful, return the displayed Users
				return $this->displayUsers();
			} else {
				// If there is an error during update, return an error message
				http_response_code(400);
				$debugInfo =  array('error' => __LINE__.": Could not update record", 'details' => $updated['message']);
				$errorMsg = "Oops! Something went wrong and your record was not updated. Please try again. If it continues contact support@connectandconquer.com";
				$navigation = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
			}
		} else {
			// If no valid ID is given, return an error message
			http_response_code(400);
			$errorMsg = "Oops! A valid ID is required when updating a user.";
			$debugInfo = array('error' => "no valid ID given: $this->id");
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
	}
	
	/**
	  _____  ______ _      ______ _______ ______
	 |  __ \|  ____| |    |  ____|__   __|  ____|
	 | |  | | |__  | |    | |__     | |  | |__
	 | |  | |  __| | |    |  __|    | |  |  __|
	 | |__| | |____| |____| |____   | |  | |____
	 |_____/|______|______|______|  |_|  |______|
	 */
	function removeUsers(){
		$isAdmin = $this->isAdmin();
		if($isAdmin == true){
			$errorMsg = "This functionality has not been implemented for Admins.";
			$debugInfo = [
				"message" => "This functionality has not been implemented for Admins.",
				"location" => __FILE__ . " line " . __LINE__,
			]; 
			$response = $this->createApiResponse(400, null, "This functionality has not been implemented for Admins.", $debugInfo, null); //can add $debugInfo as 4th param
			return $response;
		}
		if($this->claimUserID == 1){
			$returnArray['status'] = "success";
			$returnArray['message'] = "This is a test account, user cannot be deleted";
			return $this->prepareReturn($returnArray);
			
		}
		if($this->params['id'] != $this->claimUserID){ //Can user update another person's data? NO
			http_response_code(401);
			$errorMsg = "Oops! You don't have permission to delete this user's data.";
			$debugInfo = array("message" =>"Requested ID to change does not match current logged in user", "details" => "Requested ID: $this->id, Logged in user ID: $this->claimUserID");
			$navigation = null;
			$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		} //if yes, then we can check against ID's associated with user and give permission then - NOT MVP
		
		if (is_array($this->id)){
			// $returnArray = array();
			// foreach ($this->id AS $recordID){
			// 	$returnArray[] = $this->db->deactivate($recordID);
			// }
			http_response_code(400);
			$errorMsg = "Oops! Only one user may be deleted at a time.";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		elseif (!empty($this->id && $this->id == $this->claimUserID)) {
			$userAccountStatus = $this->deleteUserAccount($this->claimUserID);
			$returnArray['status'] = "success";
			$returnArray['message'] = "User account deleted";
			$line = __LINE__;
			$file = __FILE__;
			if(isset($userAccountStatus['error'])){
				
				$this->storeTestingData('UserDeleteAccount', 'error', 'An error occurred in deleting something related to user ' .$this->claimUserID, $file, $line, null, $this->claimUserID ); //store the error array in the testing data table
			}
			else {
				$this->storeTestingData('UserDeleteAccount', 'success', 'User account deleted', $file, $line, null, $this->claimUserID ); //store the error array in the testing data table
			}
			
		}
		elseif($this->id != $this->claimUserID){
			http_response_code(401);
			$errorMsg = "Oops! You don't have permission to delete this user's data.";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			

		}
		else {
			http_response_code(400);
			$errorMsg = "Oops! ID is required when deleting a user.";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		return $this->prepareReturn($returnArray);
	}

	
	function createPasswordHash($password){
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
	function verifyPassword($password,$hash){
		return password_verify($password, $hash);
	}
	//getter and setter for the passPhraseHash
	function getPassPhraseHash(){
		return $this->passPhraseHash;
	}
	function setPassPhraseHash($passPhraseHash){
		$this->passPhraseHash = $passPhraseHash;
	}
	//getter and setter for the phone
	function getPhone(){
		return $this->phone;
	}
	function setPhone($phone){
		$this->phone = $phone;
	}
	//get user's id and passPhraseHash by phone
	function getUserByPhone($formattedPhone){
		/*/
 |--------------------------------------------------------------------------
 |         Possible issue if phone not unique. Table has an iunique index on phone but just in case... be aware
 |--------------------------------------------------------------------------
 */
		$this->db->where = array('phone'=>$formattedPhone);
		$response = $this->db->select('users',$this->db->where);
		$results = $response['data'];

		if (count($results) == 1){
			// $this->id = $results[0]['id'];
			// $this->passPhraseHash = $results[0]['passPhraseHash'];
			return $results[0];//returns first result... should only be one result
		}
		elseif(count($results)>1){
			return array('error'=>__LINE__.": More than one user with formatted phone: $formattedPhone. Contact support");
		}
		else {
			return array('error'=>__LINE__.": Could not find user with formatted phone: $formattedPhone");
		}
	}

	//generate method to check if phone exists in db
	function checkIfPhoneExists($formattedPhone){
		$this->db->where = array('phone'=>$formattedPhone);
		$response = $this->db->select('users',$this->db->where);
		$results = $response['data'];
		if (count($results) == 1){
			return true;
		}
		elseif(count($results)>1){
			return array('error'=>__LINE__.": More than one user with formatted phone: $formattedPhone. Contact support");
		}
		else {
			return false;
		}
	}

	function isPersonOver13($birthdate) {
		// Check if $birthdate is set and not empty
		if (isset($birthdate) && !empty($birthdate)) {
			// Get the current date and time as a DateTime object.
			$currentDate = new DateTime();
	
			// If $birthdate is already a DateTime object, you can use it directly.
			if ($birthdate instanceof DateTime) {
				$birthDateTime = $birthdate;
			} else {
				// Convert the birthdate to a DateTime object.
				$birthDateTime = new DateTime($birthdate);
			}
	
			// Calculate the difference in years between the birthdate and the current date.
			$ageInterval = $currentDate->diff($birthDateTime);
	
			// Check if the person is over 13 years old. returns true if over age of 13
			return $ageInterval->y >= 13;
		} else {
			// Handle the case when $birthdate is empty or not set
			return false;
		}
	}

		/**
		 * Description: Runs checks to verify if new phone number exists in the database
		 *
		 * @param 
		 * @return mixed array on error or returns no data but continues if no error
		 */
	private function resetPhoneNumber(){
		$userPhone = $this->getPhonebyUserID($this->claimUserID);

		if(!empty($this->params['phone']) && $this->params['phone'] != $userPhone){ //Can user update another person's data? NO
			// print_r('phone is not empty and does not match user phone'. PHP_EOL);
			if($this->checkIfPhoneExists($this->phone ))
			{
				return array('error'=>__LINE__.": Phone number already exists in database ". PHP_EOL);
			}
			else {
				// print_r('New phone does not exist in db safe to continue'. PHP_EOL);
				return; //continue and update phone number - MVP (beyond, require user to verify they want phone to change)
			}
		}
	}
//TODO! This returns all active subscriptions, this needs to be adjusted so that there can never be more than 1 in the subscriber list
/**
 * Description: Get active subscriptions by UserID
 * Must include in the return the platform ID where the subscription was purchased so verification is easier
 */
	public function getActiveSubscriptionBasicsByUserID($userID){
		$currentDate = date('Y-m-d H:i:s');
		$tableArray = array( 'subscriptions', 'transactionsXproducts', 'products', 'transactions');
		$whereArray = array(
			'subscriptions.userID'=> $userID, 
			'transactions.id'=>'`subscriptions`.`transactionID`',
			'transactionsXproducts.transactionID'=>'`transactions`.`id`',
			'transactionsXproducts.productID'=>'`products`.`id`',
			'products.id' => array(MONTHLY_SUBSCRIPTION, ANNUAL_SUBSCRIPTION),
			'subscriptions.active'=> 1,
			'transactions.active'=> 1,
			'transactionsXproducts.active'=> 1);
		$whereGreaterArray = array('expirationDate'=>$currentDate);
		$columnArray = array(
			'transactions.userID',
			'products.id as productID', 
			'products.productName', 
			'subscriptions.expirationDate', 
			'transactions.transactionPlatformID',
			

		);
		$result = $this->db->select($tableArray, $whereArray, $whereGreaterArray, array(), $columnArray);
		
		if($result['status'] == 'success'){
			foreach($result['data'] as $subscriptionType => $subscriptionDetails){
				unset($result['data'][$subscriptionType]['availableItems']);
				
				

			}
			return $result['data'][0];
		}
		else {
			return null; //no active subscription
		}


	}
	public function getActiveSubscriptionWithTransactionDetailsByUserID($user){
		$currentDate = date('Y-m-d H:i:s');
		$tableArray = array( 'subscriptions', 'transactionsXproducts', 'products', 'transactions');
		$whereArray = array(
			'subscriptions.userID'=> $user, 
			'transactions.id'=>'`subscriptions`.`transactionID`',
			'products.id' => '`transactionsXproducts`.`productID`',
			'subscriptions.active'=> 1,
			'transactions.active'=> 1,
			'transactionsXproducts.active'=> 1
			);
		
		$whereGreaterArray = array('expirationDate'=>$currentDate);
		$columnArray = array(
			'products.id as productID', 
			'products.productName', 
			'subscriptions.expirationDate', 
			'transactions.transactionPlatformID',
			'transactions.transactionPlatformTransactionID',
			'subscriptions.transactionID',
			'transactions.platformTokenOrReceipt'

		);
		$result = $this->db->select($tableArray, $whereArray, $whereGreaterArray, array(), $columnArray);
	
		if($result['status'] == 'success'){
			foreach($result['data'] as $subscriptionType => $subscriptionDetails){
				unset($result['data'][$subscriptionType]['availableItems']);
				
				

			}
			return $result['data'][0];
		}
		else {
			return null; //no active subscription
		}


	}



	protected function getActiveGameTokensByUserID($userID){
		
		$currentDate = date('Y-m-d H:i:s');
		$tableArray = array( 'gameTokens', 'transactionsXproducts', 'products',  'transactions');
		$whereArray = array(
			'gameTokens.userID'=>$userID, 
			'transactions.userID' => '`gameTokens`.`userID`',
			'transactions.id' => '`gameTokens`.`transactionID`',
			'transactionsXproducts.transactionID' => '`transactions`.`id`',
			'transactionsXproducts.productID'=>'`products`.`id`',
			'products.id'=> array(PLAYER_TOKEN, HOST_TOKEN),
			'gameTokens.active'=>1
		);
		
		//$whereGreaterArray = array('expirationDateTime'=>$currentDate);
		//$whereMore = array();
		$whereMore = "GROUP BY products.id";
		$columnArray = array(
			'products.id as productID',
			'products.productName', 
			"SUM(quantityPurchased) AS 'totalQuantity'");
		$result = $this->db->select($tableArray, $whereArray ,array(), array(), $columnArray, $whereMore);


		
		if($result['status'] == 'success'){
			foreach($result['data'] as $tokenType => $tokenDetails){
				
				unset($result['data'][$tokenType]['availableItems']);
			}
	
			return $result['data'];
		}
		else {
			return null; //no active tokens
		}
		
	}
	

	protected function addNewRoleForUser($userID, $userRoleID){
		//check if userID is greater than 0
		if(empty($userID)){
			return array('error'=>__LINE__.": User ID must not be empty");
		}

		//check if user is already a host or player
		$userRoleHost = array();;
		$userRolePlayer = array();
		
		$userRoleID = !empty($userRoleID) ? $userRoleID : USER_ROLE_PLAYER; // 2 = host, 1 = player
			
			if( $this->userRoleRecordExists($userID, USER_ROLE_PLAYER) == false){
				$userRolePlayer = $this->setUserRole($userID, USER_ROLE_PLAYER); //everyone is a player(1) by default
			}
			
			if($userRoleID == 2 && $this->userRoleRecordExists($userID, USER_ROLE_HOST) == false){
				$userRoleHost = $this->setUserRole($userID, USER_ROLE_HOST); //add if host
			}
			
			if($userRolePlayer == false){
				return array('error'=>__LINE__.": Could not add user role as player");
			}
			else if($userRoleHost == false){
				return array('error'=>__LINE__.": Could not add user role as host");
			}
			else{
				return $this->displayUsers();
			}
	}
 

		/**
		 * Description: Get User details including selfieImageURL by UserID
		 *
		 * @param int $userID
		 * @return mixed $userDetails array on success or empty array on failure
		 *
		 */

		 /*/
 |--------------------------------------------------------------------------
 |         not used?
 |--------------------------------------------------------------------------
 */
		protected function getUserDetailsWithSnapshotURI($userID) {
			$userDetails = array();
			// Define your database query parameters here
			$tables = array(
				'`users`', '`targets`'
			);
			$where = array(
				'`users`.`id`' => $userID,
				// '`snapshots`.`userID`' => '`users`.`id`',// not needed as we are using the selfieSnapshotID to get the snapshotURI
			   '`users`.`targetID`' => '`targets`.`id`',
			   '`users`.`active`' => 1,
			   '`targets`.`active`' => 1
			);
			$columnsArray = array(
				'`users`.`id`',
				'`users`.`fName`',
				'`users`.`lName`',
				'`users`.`userRoleID`',
				'`users`.`email`',
				'`users`.`targetID`',
				'`targets`.`targetSnapshotURI` as selfieSnapshotURI',
				'`users`.`phone`',
				'`users`.`birthdate`',
				'`users`.`selfieSnapshotID`',
				'`users`.`zipcode`',
				'`users`.`country`',
				'`users`.`state`',
				'`users`.`active`',
				'`users`.`ts`'
			);
		
			// Call your database select method with the parameters
			$result = $this->db->select(
				$tables,
				$where,
				array(),    // $whereGreater
				array(),    // $whereLess
				$columnsArray, // $columnsArray
				'',         // $whereMore
				false       // $groupByColumn
			);
			
			if ($result['status'] != 'warning' && $result['status'] != 'error') {
				
				$userDetails[] = $result['data'][0];
			
			} else {
				// If no IDs were found, set to an empty array
				$userDetails= array();
			}
			  
			  unset($userDetails[0]['availableItems']);
			return $userDetails;
		}
		protected function getUserDetails($userID) {
			if(empty($userID)){
				return array('error '. __LINE__ =>"User ID must not be empty");
			}
			$userDetails = array();
			// Define your database query parameters here
			$tables = array(
				'`users`'
			);
			$where = array(
				'`users`.`id`' => $userID,
			   
				
			);
			$columnsArray = array(
				'`users`.`id`',
				'`users`.`fName`',
				'`users`.`lName`',
				'`users`.`userRoleID`',
				'`users`.`email`',
				'`users`.`phone`',
				'`users`.`birthdate`',
				'`users`.`selfieSnapshotID`',
				'`users`.`zipcode`',
				'`users`.`country`',
				'`users`.`state`',
				'`users`.`active`',
				'`users`.`ts`'
			);
			// Call your database select method with the parameters
			$result = $this->db->select(
				$tables,
				$where,
				array(),    // $whereGreater
				array(),    // $whereLess
				$columnsArray, // $columnsArray
				'',         // $whereMore
				false       // $groupByColumn
			);
			if ($result['status'] != 'warning' && $result['status'] != 'error') {
				
				$userDetails[] = $result['data'][0];
			
			} else {
				// If no IDs were found, set to an empty array
				$userDetails= array();
			}
			 //print_r($result); die();
			return $userDetails;
		}
		
function addUserWithTargetID($userColumnsArray, $userRequiredFieldsArray = array(), $targetRequiredFieldsArray = array()) {

	$targetID = 0;
	$targetColumnsArray = array(
		    'targetName'=>$userColumnsArray['fName'].' '.$userColumnsArray['lName'],
    		'targetSnapshotURI'=> "https://api.snapshotplay.com/v2/media/system/yourSnapshot.png",
  		  'targetDescription'=>'Snapshot User'
	);	
        // Insert into targets table
        $targetResponse = $this->db->insert('targets', $targetColumnsArray);
        if ($targetResponse['status'] === 'error') {
			//retrun error

            return $targetResponse;
        }
		else {
        $targetID = $targetResponse['id'];
        
        // Insert into users table
		
        $this->db->fieldsArray['targetID'] = $targetID;
		$this->db->fieldsArray['active'] = 1;

		$addResult = $this->db->addRecord();
		
		if (!empty($addResult['id'])) {//removed the greater than 0 check as Empty checks for 0
			
			$newUserID = $addResult['id'];
			$gameID = 1; //tutorial game
			//add users taget cross table
			$this->id = $addResult['id'];
			//add target for the default global gameID 1 - tutorial game
			$target = new UserTargets(array());
			$newTarget = $target->addUserTargetsRecord($newUserID, $targetID, $gameID, TARGET_STATUS_GAME_STARTED);
			if(!empty($newTarget['error'])){
				return $newTarget;
			}
			else{
				$this->claimUserID = $newUserID;
				$participantTypeID = 2;//active player
				$joinGame = new JoinGames(array());
				$defaultGameAdd = $joinGame->addUserToGame($newUserID, $gameID, $participantTypeID);
				if(!empty($defaultGameAdd['error'])){
					return array('error' 	=> __LINE__ . ": Added user and target but could not add  gamexUsers. Could not add record",
								 'details' 	=> $defaultGameAdd);
					}
				}
		} else {
			return array('error' => __LINE__ . ": Could not add record", 'details' => $addResult['message']);
		}
		return $addResult;
        // $userResponse = $this->db->insert('users', $userColumnsArray, $userRequiredFieldsArray);
        // if ($userResponse['status'] === 'error') {
        //     //return error
        //     return $userResponse;
        }

	}



	/*
		Get array of tokens by userID
		returns array of tokens including gameTokenID, productID, transactionID, expirationDate, active 
	*/
	public function getUserTokenDetailsForTokenTypeByUserID($productID, $userID){
			$tablesArray = array('gameTokens', 'transactionsXproducts');
			$whereArray = array(
			'gameTokens.userID'=>$userID,
			'transactionsXproducts.id'=>'`gameTokens`.`transactionID`',
			'transactionsXproducts.productID'=>$productID,
			'gameTokens.active'=>1);
		$whereGreaterArray = array('expirationDateTime'=>date('Y-m-d H:i:s'));
		$columnArray = array(
			'gameTokens.id as gameTokenID', 
			'transactionsXproducts.productID', 
			'transactionsXproducts.id as transactionID', 
			'gameTokens.expirationDateTime', 'gameTokens.active'
		);
		$result = $this->db->select($tablesArray, $whereArray, $whereGreaterArray, array(), $columnArray);
		
		return $result['data'];
		
	}

	public function deactivateGameTokenRecord($gameTokenID){
		
		$table = 'gameTokens';
		$fieldsArray = array('id'=>$gameTokenID,'active'=>0);
		$where = array('id'=>$gameTokenID);
		$requiredFields= array('id','active');
		$updated = $this->db->update($table, $fieldsArray, $where, $requiredFields);
		return $updated;
	}

	public function canUserAccessGameBasedOnTransactionHistory($userSubscriberArray, $userTokenArray, $accessRequested){
		//HANDLE SUBSCRIPTIONS FIRST
		if($userSubscriberArray != null){
			//echo('userSubscriberArray is not null' . PHP_EOL);
			//We only verify if we show the subscription is inactive and no tokens - we check to verify if the subscription is still active
			$access =  array('access' => 'granted', 'product' => 'subscription', "details" => $userSubscriberArray);
			
		}
		//IF NO SUBSCRIPTIONS, CHECK FOR HOST TOKENS
		else if ($accessRequested == 'createGame'){
			
			//if user has host tokens
			if(isset($userTokenArray['hostTokens']) && $userTokenArray['hostTokens'] >= 1 ){
				$access =  array('access' => 'granted', 'product' => 'hostToken', "details" => $userTokenArray, 'subscriptionDetails' => $userSubscriberArray, 'tokens' => $userTokenArray['hostTokens']);
			}
			else { //No host tokens, creation of game denied
				$access =  array('access' => 'denied', 'product' => 'hostToken', "details" => $userTokenArray, 'subscriptionDetails' => $userSubscriberArray, 'tokens' => $userTokenArray['hostTokens']);			}
		}
		//OTHERWISE CHECK FOR PLAYER TOKENS
		else if ($accessRequested == 'joinGame'){
		
			//if user has player tokens
			if(isset($userTokenArray['playerTokens']) && $userTokenArray['playerTokens'] >= 1 ){
				//$access =  array('access' => 'granted', 'product' => 'playerToken', "details" => $userSubscriberArray);
				$access =  array('access' => 'granted', 'product' => 'playerToken', "details" => $userTokenArray, 'subscriptionDetails' => $userSubscriberArray, 'tokens' => $userTokenArray['hostTokens']);

			}
			else { //No player tokens, join game denied
				//$access =  array('access' => 'denied', 'subscriptionDetails' => $userSubscriberArray, 'tokens' => $userTokenArray['playerTokens']);
				$access =  array('access' => 'denied', 'product' => 'hostToken', "details" => $userTokenArray, 'subscriptionDetails' => $userSubscriberArray, 'tokens' => $userTokenArray['playerTokens']);			

			}
		}
		//IF NO SUBSCRIPTIONS, HOST TOKENS, OR PLAYER TOKENS, VERIFY PREVIOUS SUBSCRIPTION IS NO LONGER ACTIVE DENY ACCESS
		else {//Todo if we get here there is big problem
			
			//TODO! if previous subscription, but no longer active, verify that subscription is not active
			//$recentSubscription = $this->getLatestActiveSubscription($this->claimUserID);
			//$subscriptionValid = $this->isSubscriptionValid($userSubscriberArray);
			//if($subscriptionValid){
				$access =  array('access' => 'denied', 'subscriptionDetails' => $userSubscriberArray, 'tokens' => $userTokenArray['playerTokens']);
			// }
			// else {
			// 	$access =  array('access' => 'denied', 'subscriptionDetails' => $userSubscriberArray);
			// 	//TODO! just deactivate or do we need to check against expire date and update individual items? Unsure. Deactivating for now
			// 	$deactivateSubscription = $this->deactivateSubscriptionByUserID($this->claimUserID);
			// }
		}
		
		return $access;
	}
	public function isSubscriptionValid($userSubscriberArray){
	
		//check which platform the subscription is on
		$platformID = $userSubscriberArray['transactionPlatformID'];
		$isSubscriptionValid = false;
		
		switch ($platformID) {
			case PLATFORM_GOOGLE:
				
				//connect to that platform
				$serviceAccountKeyFilePath = 'oauthCredentials/playAdminJsonKey.json';
				$packageName = 'com.snapshotplay';
				$productID = $userSubscriberArray['productID'];
				$purchaseToken = $userSubscriberArray['purchaseToken'];
				if($productID == MONTHLY_SUBSCRIPTION){
					$platformProductID = PLATFORM_GOOGLE_MONTHLY_SUBSCRIPTION;
				}
				else if($productID == ANNUAL_SUBSCRIPTION){
					$platformProductID = PLATFORM_GOOGLE_ANNUAL_SUBSCRIPTION;
				}
				$client = GoogleConnectors::connectToGoogleAndroidPublisher($serviceAccountKeyFilePath, $productID, $packageName, $platformProductID, $purchaseToken);

				if ($client) {
					$requestUrl = $client->getConfig('url');
					
					// Get the response using the constructed URL
					$res = $client->get($requestUrl);
					$subscriptionDetails = json_decode($res->getBody(), true);
					
					$currentTimeMillis = round(microtime(true) * 1000);
					$expiryTimeMillis = $subscriptionDetails['expiryTimeMillis'];
					$autoRenewing = $subscriptionDetails['autoRenewing'];
					

					if ($autoRenewing && $currentTimeMillis < $expiryTimeMillis) {
						// If autoRenewing is true and the current time is before the expiry time, subscription is valid
						return true;
					} elseif (!$autoRenewing) {
						// If autoRenewing is false, regardless of the expiry time, subscription is no longer active
						return false;
					} else {
						// In all other cases, subscription is not valid
						return false;
					}
				}
				else {
					http_response_code(400);
				$errorMsg = "Oops! Something went wrong, there was an issue verifying your subscription through Google Play. Contact support@connectandconquer.com for help.";
				$debugInfo = array("details" => "Connection to Google Play failed" . __LINE__ . " " . __FILE__ , 'client' => $client);
				$navigation = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
				return $response;
				}
				
			case PLATFORM_APPLE:
				//get receipt
				$receipt = $userSubscriberArray['platformTokenOrReceipt'];
				$isPaymentDebug = true; //for testing only
				$verifyResult = $this->verifyApplePurchase($receipt, $isPaymentDebug);
				if($verifyResult['status'] == 0){
					// Expiration date from the receipt
					//$expiresDateString = $verifyResult['receipt']['in_app'][0]['expirates_date'];
					$expiresDateString = "2024-02-11 20:11:42 Etc/GMT";

					// Convert expiration date string to a Unix timestamp
					$expiresTimestamp = strtotime($expiresDateString);

					// Current time as a Unix timestamp
					$currentTimestamp = time();
					if($expiresTimestamp > $currentTimestamp){
						$isSubscriptionValid = true;
					}
					else {
						$isSubscriptionValid = false;
					}
				}
				else {
					$isSubscriptionValid = false;
				}
				return $isSubscriptionValid;
				
			
			
			default:
				# code...
				break;
		}
		
		return $isSubscriptionValid;
	}

	public function addFreeGameTokens($userID, $productID, $quantity, $notes = null){
		$params = array(
			'deviceType'=> 'system', //system is the device for free tokens
			'userID'=>$this->claimUserID, 
			'transactionPlatformID'=>PLATFORM_SYSTEM, 
			'transactionPlatformTransactionID'=>'freeTokens',
			'platformTokenOrReceipt'=>'freeTokens',
			'datePurchase'=>date('Y-m-d H:i:s'),
			'expirationDateTime'=>date('Y-m-d H:i:s', strtotime('+100 year')),
			'notes'=>$notes,
			'active'=>1);
			$expirationDate = date('Y-m-d H:i:s', strtotime('+100 year'));
			// echo ($this->authJWTstring);
		$transaction = new Transactions($params, $this->authJWTstring);
		$transaction->claimUserID = $userID;
		
		
		
		//create a transaction
		$transactionID = $transaction->addTransactionRecord($params);
		
		if($transactionID == false){
			return array('error'=>__LINE__.": Could not add transaction record");
		}
		else {
			$addTransactionXProductRecord = $transaction->processTransactionXProducts($transactionID, $productID, $quantity);
			if($addTransactionXProductRecord == true){
				$expirationDate = date('Y-m-d H:i:s', strtotime('+100 year'));
				$gameTokensAdded = $transaction->processGameTokens($this->claimUserID, $transactionID, $expirationDate, 3); //the 3 is added in order to create 3 separate logs for the free tokens
				if($gameTokensAdded == true){
					return array('success'=>__LINE__.": Added free game tokens");
				}
				else {
					return array('error'=>__LINE__.": Could not add game tokens");
				}	
			}
			else {
				return array('error'=>__LINE__.": Could not add transactionXproduct record");
			}
		}

		
	
	}

		
}