<?php
/**

  _______                   _        __   __                          
 |__   __|                 | |       \ \ / /                          
    | | __ _ _ __ __ _  ___| |_ ___   \ V /   _   _ ___  ___ _ __ ___ 
    | |/ _` | '__/ _` |/ _ \ __/ __|   > <   | | | / __|/ _ \ '__/ __|
    | | (_| | | | (_| |  __/ |_\__ \  / . \  | |_| \__ \  __/ |  \__ \
    |_|\__,_|_|  \__, |\___|\__|___/ /_/ \_\  \__,_|___/\___|_|  |___/
                  __/ |                                               
                 |___/                                                                                          

 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayAssignments() method.
 |--------------------------------------------------------------------------
 */
class UserTargets extends SnapshotDataClass{
	protected $results = array();
	
	
	function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring); 

		//$this->accessClientListArray['POST']['admin']   = true; //DO NOT ALLOW WILL BREAK PROGRAMMING CODE
		//$this->accessClientListArray['POST']['all']     = true;
		$this->accessClientListArray['POST']['user']    = true; //Process catpured target
		$this->accessClientListArray['GET']['admin']    = true;
		// $this->accessClientListArray['GET']['all']      = true;
		$this->accessClientListArray['GET']['user']     = true;
		$this->accessClientListArray['PUT']['admin']    = true; //DO NOT ALLOW - IT WILL BREAK PROGRAMMING CODE
		// $this->accessClientListArray['PUT']['all']      = true;
		//$this->accessClientListArray['PUT']['user']     = true;
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

		// error_log(print_r('test', true));
		$this->db->table = 'usersXtargets'; //without setting here it was changing it to Targets. Not sure how to fix that.
		$this->db->requiredFields = array(
				'userID',
				'targetID',
				'gameID'
				
		);
		$this->db->fieldsArray = array(
				'id'				=> @$this->params['id'],
				'targetID'			=> @$this->params['targetID'],
				'userID'			=> @$this->params['userID'],
				'gameID'			=> @$this->params['gameID'],
				'startDateTime'		=> @$this->params['startDateTime'],
				'closedDateTime'	=> @$this->params['closedDateTime'],
				'targetStatusID'	=> @$this->params['targetStatusID'],
				'active'			=> @$this->params['active'],
		);
		
		$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
			return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
		});

		

		if($_SERVER['REQUEST_METHOD'] == "DELETE" ){
			@$this->id = $this->params['id'];
		}
		
	}
	//this is a comment
	/**
	  _____ ______ _______
	 / ____|  ____|__   __|
	 | |  __| |__     | |
	 | | |_ |  __|    | |
	 | |__| | |____   | |
	  \_____|______|  |_|
	 */
	function displayUserTargets(){
	
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
		//TODO! list all userTargets by gameCode 
		$userTargets = $this->GetUserTargets($this->claimUserID);

		if($userTargets == false){

			$isActivePlayer = $this->isActivePlayerInStartedGame($this->claimUserID);
			
			if($isActivePlayer != false){

				$currentTime = date("Y-m-d H:i:s");
				$noUserTargetFoundArray = array(
					"id"=> 0,
					"userID"=> $this->claimUserID,
					"targetID"=> 0,
					"targetName"=>"Contact support@connectandconquer.com",
					"targetTypeName"=>"system error",
					"targetSnapshotURI"=>"https://api.snapshotplay.com/v2/media/system/interrupted.png",
					"gameID"=> 0,
					"startDateTime"=> $currentTime,
					"closedDateTime"=> $currentTime,
				);

				$this->results = $noUserTargetFoundArray; //tODO
			}
			else {

				//return array('error' => "Error returning user targets for user $this->claimUserID");
				http_response_code(400);
				$errorMsg = "Oops! You don't have any assignments as you are not an active player in any started game. Code: " . __LINE__;
				$debugInfo = array('message' => "User $this->claimUserID is not an active player in any started game so they have no targets");
				$navigation = 'Menu';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;

			}

		}
		else {	
			$this->results = $userTargets;   
			$result = $this->updateRedisActiveTargetsTotal($userTargets, $this->claimUserID);

		}
		
		$this->mainQuery		 	= $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;
		$this->processedParams =  "Sent params were not processed. Returned all records related to logged in user - ? MVP ?.";
		/*/
		 |--------------------------------------------------------------------------
		 |Add other details as needed
		 |--------------------------------------------------------------------------
		 */
	
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
	public function addUserTargets(){

		if(isset($this->params) && !empty($this->params)){
			$this->testArray['params'] = $this->params;
			$this->testArray['params']['status'] = 'success';
		}
		else {
			$this->testArray['params']['status'] = 'error';
			$this->testArray['params']['message'] = '$this->params is empty';
		}
		
		//TODO* Add check that userXtarget entries aren't duplicates
		//TODO! If host removes himself from game it will trigger an endgame call
		$returnArray = array();
		//$userTargetObject = new UserTargets([], $this->authJWTstring);
		 
		if(!isset($this->params['userTargetID'] )|| !isset($this->params['snapshotID'])){
			http_response_code(400);
			
			$errorMsg = "Oops! Both a userXTargetID and snapshotID are required to process a snapshot. Code: " . __LINE__;
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			$filePath = 'capture_results.txt';
			$data = array($response);
			$dataString = json_encode($data);
			$this->appendFailedResultsToFile($dataString, $filePath);
			return $response;
		}
		$userXTargetID = $this->params['userTargetID'];
		$snapshotID = $this->params['snapshotID'];
		$snapshotLocation = $this->params['snapshotLocation'];
		
		$this->storeTestingData('STEP 0: addUserTargets POST', 'params', "UserID: $this->claimUserID, SnapshotID: $snapshotID, UserXTargetID: $userXTargetID ", __FILE__, __LINE__);
		
		//$this->storeTestingData('PARAMS PASSED', 'error', "Passed in snapshotLocation = json_encode($snapshotLocation)", __FILE__, __LINE__);
		//In order to Log all transactions, we pull the userXtarget record to create duplicates as we transactionally go thru the process
		//STEP 1. GET GAMEID AND USER X TARGET RECORD DETAILS 
		$userXTargetRecord = $this->getActiveUserXTargetRecord($userXTargetID);
		if($userXTargetRecord == false){
			http_response_code(400);
			$msg = "No usersTargets record for id $userXTargetID";
			$this->storeTestingData('STEP 1: getUserTargetRecordAndGameID', 'error', $msg, __FILE__, __LINE__);
			$debugInfo = array("message" =>"STEP 1 error: No usersTargets record for id $userXTargetID" ,"details" => "No usersTargets record for id $userXTargetID". __LINE__ . " " . __FILE__);
			$errorMsg =" Oops! Something went wrong with finding your target. Please try again or contact customer support for assistance. Code: ".__LINE__;
			$navigation = 'MyGame';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			$filePath = 'capture_results.txt';
			$data = array($response);
			$dataString = json_encode($data);
			$this->appendFailedResultsToFile($dataString, $filePath);
			return $response;
			
		}
		//Don't think this will happen, but shouldn't let a user capture someone they are not assigned
		
		if($userXTargetRecord['userID'] != $this->claimUserID){
			$msg = " User $this->claimUserID is not the owner of userTarget $userXTargetID";
			$this->storeTestingData('STEP 1a: getUserTargetRecordAndGameID', 'error', $msg, __FILE__, __LINE__);
			$debugInfo = array("message" =>"STEP 1a error: User $this->claimUserID is not the owner of userTarget $userXTargetID" ,"details" => "User $this->claimUserID is not the owner of userTarget $userXTargetID". __LINE__ . " " . __FILE__);
			$errorMsg = "Oops! It seems there is an issue completing your target due to a incorrect userTargetID.  Please contact us at support@connectandconquer.com. Code: ".__LINE__;
			$navigation = 'MyGame';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo,  $navigation); //can add $debugInfo as 4th param
			$filePath = 'capture_results.txt';
			$data = array($response);
			$dataString = json_encode($data);
			$this->appendFailedResultsToFile($dataString, $filePath);
			return $response;
		
		}
		$gameID = $userXTargetRecord['gameID'];
		$userID = $userXTargetRecord['userID'];
		$msg = " Successfully retrieved userTarget record for id $userXTargetID & verified This Claim User: $this->claimUserID != USERID: $userID ";
		$this->storeTestingData('STEP 1 & 1a: getUserTargetRecordAndGameID', 'success', $msg, __FILE__, __LINE__);

		

		//STEP 2. CLOSE THE ASSOCIATED USERTARGETS RECORD - by marking as inactive
		//TODO COMPLETE - update and add record.  Update only with active = 0, then create new record with active = 1 and duplicate info. 
		$isComplete = $this->updateAndGenerateUserTargetRecord($userXTargetID, $userXTargetRecord, TARGET_STATUS_CLOSED_COMPLETE);

		if ($isComplete['status'] == 'error'){
			$msg = "Unable to close user target record " . $isComplete['message'] . "IsComplete results: $isComplete UserXTargetID: $userXTargetID  UserTargetRecord: " . json_encode($userXTargetRecord);
			$this->storeTestingData('STEP 2: closeUserTargetRecord- mark initial inactive & create duplicated closed target record', 'error', $msg, __FILE__,__LINE__);
			$errorMsg = "Oops! There was an error closing the userTarget record. Please try again or contact customer support for assistance. Code: ".__LINE__;
			$debugInfo = array("message" =>"STEP 2 error: unable to close user target record " . $isComplete['message'] ,"details" => "unable to close user target record " . $isComplete['message']. " : " . __LINE__ . " " . __FILE__);
			$navigation = 'MyGame';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo,  $navigation); //can add $debugInfo as 4th param
			$filePath = 'capture_results.txt';
			$data = array($response);
			$dataString = json_encode($data);
			$this->appendFailedResultsToFile($dataString, $filePath);
			return $response;
			
		}
		$userTargetRecordJson = json_encode($userXTargetRecord);
		$msg = "Successfully closed captured UserTarget for userTargetsRecord - mark initial inactive & create duplicated closed target record. IsComplete results: $isComplete UserXTargetID: $userXTargetID  UserTargetRecord: $userTargetRecordJson " ;
		//$this->storeTestingData('STEP 2: closeUserTargetRecord - mark initial inactive & create duplicated closed target record', 'success', $msg, __FILE__, __LINE__);
		$this->storeTestingData('STEP 2: test why not saving', 'success', $msg, __FILE__, __LINE__);
		$updatedUserXTargetRecord = $isComplete['id'];
		$jsonEncodedSnapshotLocation = json_encode($snapshotLocation);
		// STEP 3. UPDATE THE SNAPSHOT RECORD WITH THE USERXTARGETID 
		//TODO - THIS DOES NOT STAY IN SCOPE OF ONLY UPDATING USERSXTARGETS update and add record.  Update only with active = 0, then create new record with active = 1 and duplicate info. 
		$isUpdated = $this->getAndUpdateSnapshotRecord($snapshotID, $updatedUserXTargetRecord, $jsonEncodedSnapshotLocation);
		if($isUpdated == false){
			$msg = "Error updating snapshot record for snapshotID $snapshotID and userXTargetID $userXTargetID";
			$this->storeTestingData('STEP 3: getAndUpdateSnapshotRecord', 'error', $msg, __FILE__, __LINE__);
			$errorMsg = "Oops! Something went wrong with updating the snapshot record. Please try again or contact customer support for assistance. Code: ".__LINE__;
			$debugInfo = array("message" =>"STEP 3 error: Error updating snapshot record for snapshotID $snapshotID and userXTargetID $userXTargetID" ,"details" => "Error updating snapshot record for snapshotID $snapshotID and userXTargetID $userXTargetID". __LINE__ . " " . __FILE__);
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			$filePath = 'capture_results.txt';
			$data = array($response);
			$dataString = json_encode($data);
			$this->appendFailedResultsToFile($dataString, $filePath);
			return $response;
			
		}
		$msg = " Successfully updating snapshot record for snapshotID $snapshotID and userXTargetID $userXTargetID" ;
		$this->storeTestingData('STEP 3: getAndUpdateSnapshotRecord', 'success', $msg, __FILE__, __LINE__);


		
		//STEP 4: UPDATE GAMESXUSERS to show user is now captured
		$capturedUserID = $this->getUserIDByTargetID($userXTargetRecord['targetID']);
		
		//DECREMENT ACTIVE TARGET FOR CAPTURED USER IN REDIS
		$this->redis->decreaseByOne('user'. $capturedUserID. REDIS_TARGET_STRING);

		$updateRecord = $this->updateGamesXUsersForCapturedUser($capturedUserID, $gameID);
		$jsonEncodedUpdateRecord = json_encode($updateRecord);
		$targetIDFromUserXTargetRecord = $userXTargetRecord['targetID'];
		
		if($updateRecord['status'] != 'success'){
			$msg = "Error updating GamesXUsers to show player has been captured." . $updateRecord['message'] ." for gamesXusers record ";
			$this->storeTestingData('STEP 4: updateGamesXUsersRecord', 'error', $msg, __FILE__,__LINE__);
			$debugInfo = array("message" =>"STEP 3 error: " . "Error updating GamesXUsers to show player has been captured." . $updateRecord['message'] ." for gamesXusers record " ,"details" => "Error updating GamesXUsers to show player has been captured." . $updateRecord['message'] ." for gamesXusers record ". __LINE__ . " " . __FILE__);
			$errorMsg = __LINE__ . " Oops! Something went wrong with updating the gamesXusers record. Please try again or contact customer support for assistance. Code: ".__LINE__;
			$navigation = 'MyGame';//TODO not sure if this is right location
			$response = $this->createApiResponse(400, null, $errorMsg. $debugInfo, $navigation); //can add $debugInfo as 4th param
			$filePath = 'capture_results.txt';
			$data = array($response);
			$dataString = json_encode($data);
			$this->appendFailedResultsToFile($dataString, $filePath);
			return $response;	
		}
		$this->storeTestingData('STEP 4: updateGamesXUsersRecord', 'success', "Update GamesXUsers to show player captured. CapturedUserID: $capturedUserID , UserXTargetRecord['targetID']: $targetIDFromUserXTargetRecord,  updateRecord: $jsonEncodedUpdateRecord ", __FILE__, __LINE__);
		$this->storeTestingData('STEP 5: Begin processing diff gameTypes', 'process', "Process by gameType/Launch", __FILE__, __LINE__);
		
		$gameTypeName = $this->getGameTypeNameByGameID($gameID);

		//HANDLE DIFFERENT GAME PROCESSING
		if($gameID == LAUNCH_GAME){
			$launchGameResults = $this->processLaunchGame();
			//2 scenarios will be returned in the results: 1) status 400 error with message 2) status 200 demo game completed
			return $this->prepareReturn($launchGameResults);
		}
		else {
			switch ($gameTypeName) {
				case 'Single Target':
					$capturedTargetID = $userXTargetRecord['targetID']; 
					$capturedUserID = $this->getUserIDByTargetID($capturedTargetID);
					
					$results = $this->processSingleTargetGame($gameID, $capturedUserID, $capturedTargetID);
				
					if ($results['status'] == 200) {
						return $results; // Process end game results
					} elseif ($results['status'] != 'success') {
						return $results; // Process error in one step
					}
					// If single target game is successful, do nothing
					break;
				// Other game type cases can be added here
				default:
				// Default case if none of the specified game types match
					break;
			}

			//STEP 5  DISPLAY THE TARGETS BY GAMECODE - THE NEW TARGET WILL BE INCLUDED
			$userTargetObject = new UserTargets([], $this->authJWTstring);
			$displayTargetResults = $userTargetObject->displayUserTargets();
			
			$data = $displayTargetResults['data'];
			if(isset($data)){
				$targets = $data;
				foreach($targets as $key=>$target){
					//print_r(array("target"=>$target, "key"=>$key));
					$gameCode = $this->getGameCodeFromGameID($target['gameID']);
					$target['gameCode'] = $gameCode;
					
					$returnArray['status'] = "newTargetAssigned";
					$returnArray['message'] = "Successfully processed snapshot and updated targets";
					$returnArray['targets'] = array($target);
					$returnArray['gameDetails'] = null;
					$returnArray['winnerDetails'] = null;
				}
				$msg = "Successfully displayed targets for user $this->claimUserID. Targets: " . json_encode($targets);
				$this->storeTestingData('STEP 5 (continued): displayUserTargets', 'success', $msg, __FILE__, __LINE__);
			}
		
			else if (strpos($results['error'], "started game") !== false){ //error contains "started game" 
				//Why would this happen? Because the user is not in a game, but is trying to get targets
				//This shouldn't happen because the user should be in a game to get targets
				$errorMsg = "Oops! You are not an active player in any started game. There are no targets to assign you. Code: " . __LINE__ ;
				$navigation = 'Menu';
				$debugInfo = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				$filePath = 'capture_results.txt';
				$data = array($response);
				$dataString = json_encode($data);
				$this->appendFailedResultsToFile($dataString, $filePath);
				$msg = "Target results: " . $results['error'];
				$this->storeTestingData('STEP 5: displayUserTargets', 'error', $msg, __FILE__, __LINE__);
				return $response;
			}

			if($this->errorReportingIsOn == true){
				$returnArray['testingData'] = $this->testArray;
			}
			//$returnArray['timeToComplete'] = $processSnapshotAndManageTargets;
			// header("Content-Type: application/json");
			// echo json_encode([
			// 	"Line " . __LINE__ => __FILE__,
			// 	'function' => debug_backtrace()[1]['function'] ?? 'N/A',
			// 	"returnArray" => $returnArray,
			// ]);
			// die();
			return $this->prepareReturn($returnArray);
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
	function updateUserTargets(){
		// SWAP Pseudocode
		/*
		 1. check if is admin, or return error
		 2. params must include:verb: 'swap & userTargetRecordID 
		 3. Process Swap
		 4. Return user SwapArray
		 	userDetails: for userReassignedTargetAfterTargetEliminated, userEliminatedInSwap, userReactivatedAfterSwap, reactivatedUsersNewTarget
			include player status, gameID, 		
		*/
		//1. check if is admin, or return error

		if (!$this->isAdmin()) {
			$errorMsg = "This functionality is only available to Admins.";
			$debugInfo = [
				"message" => $errorMsg,
				"location" => __FILE__ . " line " . __LINE__,
			];
			return $this->createApiResponse(400, null, $errorMsg, $debugInfo, null);
		}

		// Check if required parameters are set
		if (!isset($this->params['verb'])) {
			$errorMsg = "Oops! The verb is required to process a swap.";
		} else {
			$verb = $this->params['verb'];

			switch ($verb) {
				case 'swap':
					if (!isset($this->params['userTargetRecordID'])) {
						$errorMsg = "Oops! The capturingUserID, capturedUserID, and gameID are required to process a swap.";
					} else {
						$results = $this->processSwap($this->params['userTargetRecordID']);
					
						if($results['error']){
							$errorMsg = $results['error'];
						}
						else{
							// header("Content-Type: application/json");
							// echo json_encode([
							// 	"Line " . __LINE__ => __FILE__,
							// 	'function' => debug_backtrace()[1]['function'] ?? 'N/A',
							// 	"zzReturn" => $results,
							// 	'prepareReturn' => $this->prepareReturn($results),
							// ]);
							// die();
							return $this->prepareReturn($results);
						}
						
						break;
					}
				default:
					$errorMsg = "Oops! The verb must be 'swap' to process a swap.";
					break;
			}
		}

		// Return error response if applicable
		if (isset($errorMsg)) {
			$debugInfo = [
				"message" => $errorMsg,
				"location" => __FILE__ . " line " . __LINE__,
			];
			return $this->createApiResponse(400, null, $errorMsg, $debugInfo, null);
		}

		
		
	}
/**
	 * Description: This method processes the swap of two players in a game.
	 * If a player is incorrectly captured, the host can challenge the capture and swap the players.
	 * This makes the capturing player eliminated, and the captured player reactivated with a new target. (the target of the eliminated player)
	 * It also assigns the player whose target was eliminated a new target. (the swapped player)
	 *
	 * @param int $challengedUserTargetRecordID The ID of the userTargetRecord challenged as illegal

	 * @return 
	 */
	protected function processSwap($challengedUserTargetRecordID){
		/*
		
		 1. Duplicate the challenged record and close it with status 9, 'host eliminated'
		 	1a. 	Send notification and socket message to capturedUser - Your capture has been challenged, you have been eliminated by the host. "Captured"
		 	1b. 	Update the gameXusers record to show the user has been captured
		 2. Get current userTargetRecord for capturing user
		 	2a. get the targetID (this will be reasigned to the incorrectly captured user)  
			2b. Mark the record as closed with status 10, 'host Reversed'
		 3. Create a new userTargetRecord for the incorrectly captured user with the targetID from step 1
		 	3a. Send notification and socket message to the incorrectly captured user - Your capture has been challenged, you are now an active player in game, and have a new target
		 4. Get the targetID of the eliminated user
		 5. Get the userTargetRecord where the eliminated user is the target in the game - get the userID of person who is assigned to them
			5a. Mark the record as closed with status 10, 'hostSwapped'
		 6. Create a new target with the userID from step 8 as capturing, and the targetID of the swapped user as target in game - mark as 8 'reassigned'
			6a. Send notification and socket message to the user  - You have a new target
		 7. return userDetailsArray of swapped users
			*/
			$challengedUserTargetRecord = $this->getInactiveUserXTargetRecord($challengedUserTargetRecordID);
			$gameID = $challengedUserTargetRecord['gameID'];
			$eliminatedPlayerID = $challengedUserTargetRecord['userID'];
			$reversedStatusPlayerID = $this->getUserIDByTargetID($challengedUserTargetRecord['targetID']);
			$eliminatedPlayerTargetID = $this->getTargetIDByUserID($eliminatedPlayerID);
			$eliminatedPlayerCurrentUserTargetRecord = $this->getOpenUserXTargetRecordByUserAndGameID($eliminatedPlayerID, $gameID);
			$currentUserTargetRecordID = $eliminatedPlayerCurrentUserTargetRecord['id'];
			$eliminatedPlayerCurrentAssignedTargetID = $eliminatedPlayerCurrentUserTargetRecord['targetID'];
			
			//Step 1 - Process the player eliminated by the host
			$result = $this->processHostEliminatedPlayer($challengedUserTargetRecordID,  $challengedUserTargetRecord, $eliminatedPlayerID, $gameID);
			if($result['status'] == 'error'){
				$errorMsg = " Oops! There was an error processing the eliminated player. Code: " . __LINE__;
				$debugInfo = array("message" => $errorMsg, "details" => $result['message']);
				return $this->createApiResponse(400, null, $errorMsg, $debugInfo, null);
			}
	
			//Step 2 - Get Eliminated Players current userTargetRecord mark closed with status 'host eliminated' 
			$updateResults = $this->updateCurrentUserTargetRecordForEliminatedPlayer($currentUserTargetRecordID, $eliminatedPlayerCurrentUserTargetRecord);
			if($updateResults['status'] == 'error'){
				$errorMsg = " Oops! There was an error processing the eliminated player. Code: " . __LINE__;
				$debugInfo = array("message" => $errorMsg, "details" => $updateResults['message']);
				return $this->createApiResponse(400, null, $errorMsg, $debugInfo, null);
			}
		

			//Step 3 - Create a new userTargetRecord for the incorrectly captured user with the targetID from step 2
			$reverseProcessID = $this->processReversedCapturedUser($reversedStatusPlayerID, $eliminatedPlayerCurrentAssignedTargetID, $gameID);
			
			if(is_array($reverseProcessID) && $reverseProcessID['status'] == 'error'){
				$errorMsg = " Oops! There was an error processing the eliminated player. Code: " . __LINE__;
				$debugInfo = array("message" => $errorMsg, "details" => $reverseProcessID['message']);
				return $this->createApiResponse(400, null, $errorMsg, $debugInfo, null);
			}
			
			$swappedUserTargetRecord = $this->getActiveUserXTargetRecord($reverseProcessID);

			//Step 4 - Update the userTargetRecord where the eliminated player is the target and assign the target as the swapped player
			$updateRecord = $this->reassignPlayerWhoseTargetWasEliminatedInSwap($eliminatedPlayerID, $gameID, $challengedUserTargetRecord['targetID'] );
			
			if($updateRecord['status'] == 'error'){
				$errorMsg = " Oops! There was an error assigning the swapped player as target. Code: " . __LINE__;
				$debugInfo = array("message" => $errorMsg, "details" => $updateRecord['message']);
				return $this->createApiResponse(400, null, $errorMsg, $debugInfo, null);
			}
			$reassignedRecordID = $updateRecord['userTargetID'];
			$reassignedRecord = $this->getActiveUserXTargetRecord($reassignedRecordID);

			//Step 5 - Return the swapped user details
			$userDetailsArray = array(
				'status' => 'success',
				'message' => "Successfully processed swap based on UserTargetRecordID: $challengedUserTargetRecordID. " . 
							 "The following steps were taken: " . 
							 "Step 1. User: $eliminatedPlayerID was eliminated. " . 
							 "Setp 2. User: $reversedStatusPlayerID was reactivated with a new target: {$swappedUserTargetRecord['targetID']}. " . 
							 "Setp 3. User: {$reassignedRecord['userID']} was assigned to Target: {$reassignedRecord['targetID']}"
			);
			

			return $userDetailsArray;
	}

	protected function reassignPlayerWhoseTargetWasEliminatedInSwap($eliminatedPlayerID, $gameID, $newTargetID){
		$targetID = $this->getTargetIDByUserID($eliminatedPlayerID);
		$userTargetID = $this->getUserTargetRecordByTargetID($targetID, $gameID);
		$userTargetRecord = $this->getActiveUserXTargetRecord($userTargetID);

		//Close the record with eliminated target
		$result = $this->deactivateUsersXTargetsRecords($userTargetID);
		//Duplicate that record with target status Target status target eliminated by host
		$documentingSwapUserTargetID = $this->addUserTargetRecord($userTargetRecord,  TARGET_STATUS_TARGET_ELIMINATED_BY_HOST);
		
		//create a new record with the new targetID and 'target transferred' status
		$record = array(
			'userID' => $userTargetRecord['userID'],
			'targetID' => $newTargetID,
			'gameID' => $gameID,
			'startDateTime' => date('Y-m-d H:i:s'),
			'closedDateTime' => null,
			'targetStatusID' => TARGET_STATUS_TARGET_TRANSFERRED,
			'active' => 1,
		);
		$result = $this->db->insert('usersXtargets', $record, array_keys($record));
		$isActive = 1;
		$userTargetResult = $this->addUserTargetsRecord($userTargetRecord['userID'], $newTargetID, $gameID, TARGET_STATUS_TARGET_TRANSFERRED);

		if($userTargetResult['status'] == 'error'){
			$this->storeTestingData('SWAP STEP 4: closeUserTargetRecord as host eliminated', 'error', $userTargetResult['message'], __FILE__, __LINE__);
			return $userTargetResult;
		}
		else {
			return array(
				'status' =>'success',
				'userTargetID' => $userTargetResult['id']
			);
		}
	
			
	}

	/**
	 * Updates the current userTargetRecord for the eliminated player.
	 *
	 * This method updates the userTargetRecord for the eliminated player, marking it as closed with status 'host eliminated'.
	 *
	 * @param int $userTargetID The ID of the userTargetRecord to update.
	 * @param array $userTargetRecord The data of the userTargetRecord to update.
	 *   - 'closedDateTime' (string) The date and time when the record is closed.
	 *   - 'targetStatusID' (int) The ID of the target status to set.
	 *   - 'active' (int) Flag indicating if the record is active (1) or not (0).
	 * @return array The result of the operation.
	 *   - 'status' (string) The status of the operation, either 'success' or 'error'.
	 *   - 'message' (string|null) A message indicating the result of the operation, or null if successful.
	 */
	public function updateCurrentUserTargetRecordForEliminatedPlayer($userTargetID, $userTargetRecord){
		$userTargetRecord['closedDateTime'] = date('Y-m-d H:i:s');
		$userTargetRecord['targetStatusID'] = TARGET_STATUS_HOST_ELIMINATED;
		$userTargetRecord['active'] = 0;
			
		$eliminatedUserTargetRecordID = $this->updateAndGenerateUserTargetRecord($userTargetID, $userTargetRecord, TARGET_STATUS_HOST_ELIMINATED);
	
		if($eliminatedUserTargetRecordID['status'] == 'error'){
			$this->storeTestingData('SWAP STEP 2: closeUserTargetRecord as host eliminated', 'error', $eliminatedUserTargetRecordID['message'], __FILE__, __LINE__);
			return $eliminatedUserTargetRecordID;
		}

		return $eliminatedUserTargetRecordID;

	}

	public function processReversedCapturedUser($userID, $targetID, $gameID){
		//Create a new userTargetRecord for the incorrectly captured user with the targetID from step 2
		$userTargetRecord = array(
			'userID' => $userID,
			'targetID' => $targetID,
			'gameID' => $gameID,
			'startDateTime' => date('Y-m-d H:i:s'),
			'closedDateTime' => null,
			'targetStatusID' => TARGET_STATUS_TARGET_TRANSFERRED,
			'active' => 1,
		);

		$documentingSwapUsertTargetID = $this->addUserTargetRecord($userTargetRecord,  TARGET_STATUS_HOST_SWAPPED);
		$isActive = 1;
		$addResult = $this->addUserTargetsRecord($userID, $targetID, $gameID, TARGET_STATUS_TARGET_TRANSFERRED);
		if($addResult['status'] == 'error'){
			$this->storeTestingData('SWAP STEP 3: closeUserTargetRecord as host eliminated', 'error', $addResult['message'], __FILE__, __LINE__);
			return $addResult;
		}

		// 1a. Send notification and socket message to capturedUser
		$msgID = $this->addMessage("Host Reversed", "Your capture has been reversed by the host. You are now an active player in game {$gameID} and have a new target.");
		$this->notifyPlayer($userID, $msgID);

		// 1b. Publish socket message
		$dataToSend = "Player has been reactivated and has a new target";
		$navigationStatus = 'newTarget';
		$this->publishSocketMessage($userID, $gameID, $dataToSend, $navigationStatus);

		//Update gamesXusers record to show the user has been captured
		$updateRecord = $this->updateGamesXUsersForSwap($userID, $gameID, PARTICIPANT_TYPE_ACTIVE_PLAYER);
		//$updateRecord = $this->updateGamesXUsersForReactivatedUser($userID, $gameID);
		if($updateRecord == false){
			$msg = "Error updating GamesXUsers to show player has been reactivated." . $updateRecord['message'] ." for gamesXusers record ";
			$this->storeTestingData('SWAP STEP 3: updateGamesXUsersRecord', 'error', $msg, __FILE__,__LINE__);
			// CURRENTLY JUST STORING ERROR DATA, NOT STOPPING PROCESS
			// $debugInfo = array("message" =>"STEP 1b error: " . "Error updating GamesXUsers to show player has been captured." . $updateRecord['message'] ." for gamesXusers record " ,"details" => "Error updating GamesXUsers to show player has been captured." . $updateRecord['message'] ." for gamesXusers record ". __LINE__ . " " . __FILE__);
			// $errorMsg = __LINE__ . " Oops! Something went wrong with updating the gamesXusers record. Please try again or contact customer support for assistance. Code: ".__LINE__;
			// $navigation = 'MyGame';//TODO not sure if this is right location
			// $response = $this->createApiResponse(400, null, $errorMsg. $debugInfo, $navigation); //can add $debugInfo as 4th param
				
		}
		 
		//UPDATE REDIS TO SHOW 1 LESS ACTIVE TARGET
		$this->redis->increaseByOne('user'. $userID. REDIS_TARGET_STRING);
		
		return $addResult['id']; //return the id of the userTarget created

	}

	

/**
 * Description: Process the host eliminated player, by closing the userTarget record with status 9, 'host eliminated'
 *  			and sending a notification and socket message that player has been eliminated
 *
 * @param int $challengedUserTargetRecordID - the ID of the userXtarget record that is being challenged as an invalid capture
 * @return bool|array - true if successful, array with error message if not
 */
	protected function processHostEliminatedPlayer($challengedUserTargetRecordID, $challengedUserTargetRecord, $eliminatedPlayerID, $gameID){
		//1. Duplicate the challenged record and close it with status 9, 'host eliminated'
		$challengedUserTargetRecord['closedDateTime'] = date('Y-m-d H:i:s');
		$challengedUserTargetRecord['targetStatusID'] = TARGET_STATUS_HOST_ELIMINATED;
		$challengedUserTargetRecord['active'] = 0;
		$eliminatedUserTargetRecordID = $this->addUserTargetRecord($challengedUserTargetRecord,  TARGET_STATUS_HOST_ELIMINATED);
		if($eliminatedUserTargetRecordID['status'] == 'error'){
			$this->storeTestingData('SWAP STEP 1: closeUserTargetRecord as host eliminated', 'error', $eliminatedUserTargetRecordID['message'], __FILE__, __LINE__);
			return $eliminatedUserTargetRecordID;
		}

		// 1a. Send notification and socket message to capturedUser
		$msgID = $this->addMessage("Host Eliminated", "You have been eliminated from the game by the host");
		$this->notifyPlayer($eliminatedPlayerID, $msgID );

		// 1b. Publish socket message
		$dataToSend = "Player has been eliminated";
		$navigationStatus = 'capture';
		$this->publishSocketMessage($eliminatedPlayerID, $gameID, $dataToSend, $navigationStatus);

		//Update gamesXusers record to show the user has been captured
		$updateRecord = $this->updateGamesXUsersForSwap($eliminatedPlayerID, $gameID, PARTICIPANT_TYPE_CAPTURED_PLAYER);
		if($updateRecord == false){
			$msg = "Error updating GamesXUsers to show player has been captured." . $updateRecord['message'] ." for gamesXusers record ";
			$this->storeTestingData('SWAP STEP 1b: updateGamesXUsersRecord', 'error', $msg, __FILE__,__LINE__);
			// CURRENTLY JUST STORING ERROR DATA, NOT STOPPING PROCESS
			// $debugInfo = array("message" =>"STEP 1b error: " . "Error updating GamesXUsers to show player has been captured." . $updateRecord['message'] ." for gamesXusers record " ,"details" => "Error updating GamesXUsers to show player has been captured." . $updateRecord['message'] ." for gamesXusers record ". __LINE__ . " " . __FILE__);
			// $errorMsg = __LINE__ . " Oops! Something went wrong with updating the gamesXusers record. Please try again or contact customer support for assistance. Code: ".__LINE__;
			// $navigation = 'MyGame';//TODO not sure if this is right location
			// $response = $this->createApiResponse(400, null, $errorMsg. $debugInfo, $navigation); //can add $debugInfo as 4th param
				
		}
		 
		//UPDATE REDIS TO SHOW 1 LESS ACTIVE TARGET
		$this->redis->decreaseByOne('user'. $eliminatedPlayerID. REDIS_TARGET_STRING);
		
		return true;
	}
	

	/**
	  _____  ______ _      ______ _______ ______
	 |  __ \|  ____| |    |  ____|__   __|  ____|
	 | |  | | |__  | |    | |__     | |  | |__
	 | |  | |  __| | |    |  __|    | |  |  __|
	 | |__| | |____| |____| |____   | |  | |____
	 |_____/|______|______|______|  |_|  |______|
	 Targets are completed/closed/and removed thru the PUT method of userTargets
	 Withdraw (JoinGame PUT) closes any associated targets of person withdrawing from game, endGame (GAME STATUS POST) will deactivate all targets associated with the game
	 */

/******************************************************************************************************************
 * Other class methods
 *****************************************************************************************************************/
	
	public function updateUserTargetsRecord($recordID, $updatedData){
		
		if (!empty($updatedData['id'])) {
			$this->db->fieldsArray = $updatedData;
			unset($this->db->fieldsArray['availableItems']);
			
			// Update the record with the given ID
			$updated = $this->db->updateRecord($this->db->table, $this->db->fieldsArray, $this->id);
//print_r(array('updateRecord'=>$updated, 'recordID'=>$recordID, 'updatedData'=>$updatedData));
			if ($updated['status']!=='error') {
				// If the update is successful, return the displayed userTarget
				return $this->prepareReturn($updated); //TODO check this should go thru prepareReturn
			} else {
				// If there is an error during update, return an error message
				http_response_code(400);
				$errorMsg = "Oops! Something went wrong and your record was not updated. Please try again.";
				$debugInfo = array("message" => "Could not update record", "details" => $updated['message']);
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
		
		} else {
			// If no valid ID is given, return an error message
			http_response_code(400);
			$errorMsg = "Oops! Valid ID must be given to update Target.";
			$debugInfo = array("message" => "Could not update record. No valid ID given:", "details" => $updatedData['id']);
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
	}
	
	public function addUserTargetsRecord($userID, $targetID, $gameID, $targetStatusID){
		$table = 'usersXtargets';
		$fieldsArray = array(
				'userID'			=> $userID,
				'targetID'			=> $targetID,
				'gameID'			=> $gameID,
				'startDateTime'		=> date("Y-m-d H:i:s"),
				'closedDateTime'	=> null,
				'targetStatusID'	=> $targetStatusID,
				'active'			=> 1,
		);
		
		$addRecord = $this->db->insert($table, $fieldsArray, $this->db->requiredFields);
// header("Content-Type: application/json");
// echo json_encode([
// 	"Line " . __LINE__ => __FILE__,
// 	'function' => debug_backtrace()[1]['function'] ?? 'N/A',
// 	"addRecordTargetStatusID1" => $targetStatusID,
// 	'addRecord' => $addRecord,
// ]);

		if ($addRecord['status']!=='error') {
			// If the insert is successful, return the displayed userTarget
			return $addRecord;
		} else {
			// If there is an error during insert, return an error message
			return array('error' => __LINE__.": Could not add record", 'details' => $addRecord['message']);
		}
	}
	/*/
		|--------------------------------------------------------------------------
		|        Methods for processing snapshots (POST)
		|--------------------------------------------------------------------------
		*/
	protected function getActiveUserXTargetRecord($usersXtargetsID) {
		$recordArray = array();
		// Define your database query parameters here
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'id' => $usersXtargetsID,
			'active' => 1 //In order to be captured, the target must be active
		);
		
		// Call your database select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			array(), // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
		
	   $data = array();
		if ($result['status'] == 'success') {
		   
		   $data = $result['data'][0];
		} else {
			$data = false;
		}
	   
		return $data;
	}

	protected function getInactiveUserXTargetRecord($usersXtargetsID) {
		$recordArray = array();
		// Define your database query parameters here
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'id' => $usersXtargetsID,
			//'active' => 1 //In order to be captured, the target must be active
		);
		
		// Call your database select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			array(), // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
		
	   $data = array();
		if ($result['status'] == 'success') {
		   
		   $data = $result['data'][0];
		} else {
			$data = false;
		}
	   
		return $data;
	}

	/**
	 * Description: GET THE USERID OF A TARGET BY USING TARGETID
	 *
	 * @param int $targetID
	 * @return 
	 */
	protected function getUserIDByTargetID($targetID){
		$tables = array(
			'users'
		);
		$where = array(
			'targetID' => $targetID
		);
		$columnsArray = array(
			'id' 
		);
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			$columnsArray, // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
		// $resultJson = json_encode($result);
		// $queryJson = json_encode($this->db->fullQuery);
		// $this->storeTestingData('getUserIDByTargetIDQuery', 'query', "targetID: $targetID, result: " . json_encode($result), __FILE__, __LINE__);
	   //TODO find anywhere we reference data[0] and check how many records are returning
		if($result['status'] == 'success'){
			return $result['data'][0]['id'];
		}
		else {
			return false;
		}
	}

	protected function updateGamesXUsersForSwap($userID, $gameID, $participantTypeID){ 
		$gamesXusersRecord = $this->getPlayerRecordFromGamesXUsers($userID, $gameID);
		$gamesXusersRecord['participantTypeID'] = $participantTypeID;
		$updateRecord = $this->updateGamesXUsersRecord($gamesXusersRecord);
		return $updateRecord;
	}

	protected function updateParticipantType($userID, $gameID, $participantType){
		$record = $this->getPlayerRecordFromGamesXUsers($userID, $gameID);
		if($record == false){	
			return array('error' => "No gamesXusers record for user $userID in game $gameID");
		}
		else {
			
			$record['participantTypeID'] = $participantType;
			$record['active'] = 1;
			
			$isUpdated = $this->updateGamesXUsersRecord($record);
			
			if($isUpdated['status'] != 'success'){
				return array('error' => $isUpdated['message']);
			}
			else {
				return true;
			}
		}
	}

	public function getUserIDByUserXTargetID($recordID){
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'id' => $recordID
		);
		$columnsArray = array(
			'userID' 
		);
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			$columnsArray, // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
		//print_r($recordID);die('getUserIDByTargetID');
		if($result['status'] == 'success'){
			return $result['data'][0]['userID'];
		}
		else {
			return false;
		}
	 }

	 function processLaunchGame(){
		$this->storeTestingData('STEP 0Launch: LAUNCH GAME', 'success', "Process Launch Game Started" , __FILE__, __LINE__);
			
		// STEP 1Launch: DEACTIVATE THE GAMESXUSERSRECORD for the claim user and the launch game
		$result = $this->deactivateGamesXUsersRecord($this->claimUserID, LAUNCH_GAME); //this should be logged in user, a they are only one to submit snapshot
	
		//if error: log and return error response
		if($result['status'] != 'success'){
			$msg = "Result['message'] " . $result['message']." for user $this->claimUserID in game " . LAUNCH_GAME . " in the gamesXusers table";
			$this->storeTestingData('STEP 1Launch ifDemoGameDeactivateGamesXUsersRecord', 'error', $msg , __FILE__, __LINE__);
			$debugInfo = array("message" =>"STEP 1Launch: " . $result['message']." for user $this->claimUserID in game " . LAUNCH_GAME . " in the gamesXusers table" ,"details" => "Global Game STEP 4 error: " . $result['message']." for user $this->claimUserID in game 1 in the gamesXusers table". __LINE__ . " " . __FILE__);
			$errorMsg = __LINE__ . " Oops! Something went wrong with updating the gamesXusers record. Please try again or contact customer support for assistance.";
			$navigation = 'MyGame';//TODO not sure if this is right location
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			$filePath = 'capture_results.txt';
			$data = array($response);
			$dataString = json_encode($data);
			$this->appendFailedResultsToFile($dataString, $filePath);
			return $response;
		}
		else {
			
				//Step 1L success
			$msg = "Successfully deactivated gamesXusers record for user $this->claimUserID in game ". LAUNCH_GAME ." in the gamesXusers table";
			$this->storeTestingData('STEP 1Launch: ifDemoGameDeactivateGamesXUsersRecord', 'success', $msg, __FILE__, __LINE__);
			
			// STEP 2L: UPDATE THE TARGETID OF THE CLAIM USER WITH THE SNAPSHOT FILE URI - THIS WILL BE alphanumericUserID-profile.png
			$targetID = $this->getTargetIDByUserID($this->claimUserID);
			$targetDetails = $this->getTargetDetails($targetID);
			
			if(!isset($targetDetails['id'])){ //
				$msg = "No Target record for id $targetID";
				$this->storeTestingData('STEP 2Launch: getSnapshotDetails', 'error',  $msg, __FILE__,__LINE__);
				$debugInfo = array("message" =>"STEP 2Launch: No target record for id $targetID". __LINE__ . " " . __FILE__);
				$errorMsg = __LINE__ . " Oops! There is no active target record to update. Please try again or contact customer support for assistance. " . __LINE__;
				$navigation = 'MyGame';//TODO not sure if this is right location
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				$filePath = 'capture_results.txt';
				$data = array($response);
				$dataString = json_encode($data);
				$this->appendFailedResultsToFile($dataString, $filePath);
				return $response;
			}
			else {
				$msg = "Successfully deactivated gamesXusers record for user $this->claimUserID in game ". LAUNCH_GAME ." in the gamesXusers table";
				$this->storeTestingData('STEP 2Launch: ifDemoGameDeactivateGamesXUsersRecord', 'success', $msg, __FILE__, __LINE__);
				
				//STEP 3Launch CREATE A SYMBOLIC LINK TO THE SNAPSHOT FILE IN THE TARGETS TABLE
				$snapshotID = $this->params['snapshotID'];
				$updateRecord = $this->createSymbolicLinkAndUpdateTargetAndSnapshotRecord($snapshotID, $targetID);
				
				if($updateRecord['status'] != 'success'){
					$msg = "Error updating snapshot and target record with symbolic link. SnapshotID: $snapshotID, TargetID: $targetID, updateRecord: $updateRecord";
					$this->storeTestingData('STEP 3Launch: updateGamesXUsersRecord', 'error', $msg, __FILE__,__LINE__);
					$debugInfo = array("message" =>"Error updating snapshot and target record with symbolic link. SnapshotID: $snapshotID, TargetID: $targetID, updateRecord: $updateRecord ". __LINE__ . " " . __FILE__);
					$errorMsg = __LINE__ . " Oops! Something went wrong with updating your target record with your new snapshot. Please try again or contact customer support for assistance.";
					$navigation = 'MyGame';//TODO not sure if this is right location
					$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
					$filePath = 'capture_results.txt';
					$data = array($response);
					$dataString = json_encode($data);
					$this->appendFailedResultsToFile($dataString, $filePath);
					return $response;	
				}
				else {
					$msg = "Successfully updated snapshot and target record with symbolic link. SnapshotID: $snapshotID, TargetID: $targetID, updateRecord: $updateRecord";
					$this->storeTestingData('STEP 3Launch: updateGamesXUsersRecord', 'success', $msg, __FILE__,__LINE__);

					//STEP 4Launch: PUBLISH TO SOCKET THAT PLAYER HAS BEEN CAPTURED
					$dataToSend = "Player has been captured";
					$navigationStatus = 'firstGameComplete';
					$socketSent = $this->publishToSocket($dataToSend, LAUNCH_GAME, $this->claimUserID, $navigationStatus);	
					
					if($socketSent['status'] == 'error'){
						$filePath = 'socket_results.txt';
						$data = array('socketSent' => $socketSent, 'socketData' => $dataToSend, 'gameID' => LAUNCH_GAME, 'capturedUserID' => $this->claimUserID);
						$dataString = json_encode($data);
						$this->appendFailedResultsToFile($dataString, $filePath);
						$this->storeTestingData('STEP 4Launch: publishToSocket', 'error', "Error publishing to socket that player has been captured. Datastring: $dataString", __FILE__, __LINE__);
					}
					else{
						$this->storeTestingData('STEP 4Launch: publishToSocket', 'success', "Successfully published to socket that player has been captured. Sent to user: $this->claimUserID", __FILE__, __LINE__);

						//STEP 5Launch: USER RECORD WITH SELFIE SNAPSHOT ID
						$isUpdated = $this->getAndUpdateUserRecord($this->claimUserID, $snapshotID);
				
						if($isUpdated == false){
							$msg = "Unable to update user $this->claimUserID with snapshotID $snapshotID in the users table";
							$this->storeTestingData('STEP 5Launch: updateUserRecordSelfieSnapshot', 'error',  $msg, __FILE__,__LINE__);
							$debugInfo = array("message" =>"STEP 5Launch: Unable to update user $this->claimUserID with snapshotID $snapshotID in the users table" ,"details" => "Global Game: Unable to update user $this->claimUserID with snapshotID $snapshotID in the users table". __LINE__ . " " . __FILE__);
							$errorMsg = "Oops! There was an error updating the user details. Please try again or contact customer support for assistance." . __LINE__;
							$navigation = 'MyGame';//TODO not sure if this is right location
							$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
							$filePath = 'capture_results.txt';
							$data = array($response);
							$dataString = json_encode($data);
							$this->appendFailedResultsToFile($dataString, $filePath);
							return $response;
	
						}
						else {
							$msg = " Updated user $this->claimUserID with snapshotID $snapshotID in the users table";
							$this->storeTestingData('STEP 5Launch: updateUserRecordSelfieSnapshot', 'success',  $msg, __FILE__,__LINE__);
							$returnArray['message'] = "Successfully added selfie snapshot and removed user $this->claimUserID from game " . LAUNCH_GAME;
							$this->authInfoArray['status'] = JWT_STATUS_REQUEST_NEW;
							
							//STEP 6Launch: RETURN THE DEMO COMPLETE MESSAGE TO THE FRONT END
							$data = array(
								"status" => "DemoComplete",
								"message" =>"You have completed and been removed from the demo game. You have no new targets, please join or create a game to continue playing.",
								"targets" => null,
								//these last 2 only are in to standardize if game ends
								'gameDetails' => null,
								'winnerDetails' => null
								
							);

							$navigation = "firstGameComplete";
							$errorMsg = null;
							$response = $this->createApiResponse(200, array($data), $errorMsg, null, $navigation); //can add $debugInfo as 4th 
						
							return $data;
						
						}
						
					}
					
				}

			}
		}
	 }

	 /**
	  * Description: PROCESS THE UNIQUE STEPS TO COMPLETE A CAPTURE FOR A SINGLE TARGET GAME
	  *
	  * @param int $gameID
	  *	@param int $capturedUserID
	  * @param int $capturedTargetID
	  * @param object $userTarget
	  * @param object $userTargetObject
	  * 
	  * @return array $response on error [status, data, message, debugInfo, navigation]
	  * @return array $returnArray on success [status, data, message, debugInfo, navigation]
	  */
	 public function processSingleTargetGame($gameID, $capturedUserID, $capturedTargetID){
		// STEP 1 ST: GET THE USER X TARGET RECORD FOR THE CAPTURED USER
		$newTargetDetails = $this->getOpenUserXTargetRecordByUserAndGameID($capturedUserID, $gameID);
		// header("Content-Type: application/json");
		// echo json_encode([
		// 	"Line " . __LINE__ => __FILE__,
		// 	'function' => debug_backtrace()[1]['function'] ?? 'N/A',
		// 	"step 1 st" => $newTargetDetails,
		// ]);
		
		if($newTargetDetails == false){
			$msg = "No usersXtargets record for user $capturedUserID in game $gameID";
			$this->storeTestingData('STEP 1 ST: getUserTargetRecord&GameID', 'error', $msg, __FILE__, __LINE__);
			$debugInfo = array("message" =>"STEP 1 ST error: No usersXtargets record for user $capturedUserID in game $gameID" ,"details" => "No usersXtargets record for user $capturedUserID in game $gameID". __LINE__ . " " . __FILE__);
			$errorMsg = "Oops! Something went wrong with finding your target. Please try again or contact customer support for assistance.";
			$navigation = 'MyGame'; //TODO not sure if right place to go
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			$filePath = 'capture_results.txt';
			$data = array($response);
			$dataString = json_encode($data);
			$this->appendFailedResultsToFile($dataString, $filePath);
			return $response;
			
		}
		else {
			$msg = "Successfully retrieved userTarget record for CaapturedUserID:  $capturedUserID in game $gameID, CapturedTargetID: $capturedTargetID";
			$this->storeTestingData('STEP 1 ST: getUserTargetRecord&GameID', 'success', $msg, __FILE__, __LINE__);

			// STEP 2 ST:. CLOSE THE USER x TARGET RECORD FOR THE CAPTURED USER
			$isRecordClosed = $this->updateAndGenerateUserTargetRecord($newTargetDetails['id'], $newTargetDetails, TARGET_STATUS_CLOSED_USER_CAPTURED);
			$newTargetDetailsJson = json_encode($newTargetDetails);
	
			if ($isRecordClosed['status' == 'error']){
				$msg = "Record updated result" . $isRecordClosed['message']. "NewTargetDetails: " . $newTargetDetails['id'] . "NewTargetDetails: $newTargetDetailsJson TargetStatus: TARGET_STATUS_CLOSED_USER_CAPTURED  TargetStatusID: " . TARGET_STATUS_CLOSED_USER_CAPTURED ;
				$this->storeTestingData('STEP 2 ST: closeUserTargetRecord', 'error',  $msg, __FILE__,__LINE__);
				$debugInfo = array("message" =>"STEP 2 ST: error " . $isRecordClosed['message'] ,"details" => "STEP 5 error " . $isRecordClosed['message']. __LINE__ . " " . __FILE__);
				$errorMsg = "Oops! There was an error closing the userTarget record. Please try again or contact customer support for assistance.";
				$navigation = 'MyGame';//TODO not sure if this is right location
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				
				return $response;
				
			}
			else {
				$msg = "Successfully closed userTarget record for user $capturedUserID in game $gameID";
				$this->storeTestingData('STEP 2 ST: closeUserTargetRecord', 'success', $msg, __FILE__, __LINE__);

				// STEP 3 ST: ASSIGN THE CAPTURED USERS' TARGET TO THE CAPTURING USER (REASSIGN TARGET)
				$targetID = $newTargetDetails['targetID'];
				$userTargetObject = new UserTargets([], $this->authJWTstring);
				$newTargetResult = $userTargetObject->addUserTargetsRecord($this->claimUserID, $targetID, $gameID, TARGET_STATUS_TARGET_TRANSFERRED);


		
				if($newTargetResult['status'] != 'success'){
					$newTargetResultMessage = json_encode($newTargetResult['message']);
					$msg = "ReassignTarget Results: " . $newTargetResultMessage. "UserID: $this->claimUserID, GameID: $gameID, TargetID: $targetID TargetStatus: TARGET_STATUS_TARGET_TRANSFERRED TargetStatusID: " . TARGET_STATUS_TARGET_TRANSFERRED ;
					$this->storeTestingData('STEP 3 ST: assignCapturedUserTargetToCapturingUser', 'error', $msg, __FILE__, __LINE__);
					$debugInfo = array("message" =>" STEP 3 ST error: " . $newTargetResult['message'] ,"details" => "STEP 7 error: " . $newTargetResult['message']. __LINE__ . " " . __FILE__);
					$errorMsg = "Oops! There was an error assigning the target to you. Please try again or contact customer support for assistance.";
					$navigation = 'MyGame';//TODO not sure if this is right location
					$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
					$filePath = 'capture_results.txt';
					$data = array($response);
					$dataString = json_encode($data);
					$this->appendFailedResultsToFile($dataString, $filePath);
					return $response;
					
				}
				else {
					$msg = "Successfully assigned target $targetID to user " . $this->claimUserID . " in game $gameID";
					$this->storeTestingData('STEP 3 ST: reassignTarget', 'success', $msg, __FILE__, __LINE__);
					
					// STEP 4 ST: UPDATE CAPTURED USER PARTICIPANT TYPE TO CAPTURED IN GAMExUSER RECORD
					$isUpdated = $this->updateParticipantType($capturedUserID, $gameID, PARTICIPANT_TYPE_CAPTURED_PLAYER);
					
		
					if($isUpdated != true){
						$msg = "UpdatedParticipantType result:  ". $isUpdated['error'] . " CapturedUser: $capturedUserID GameID: $gameID ParticipantType: PARTICIPANT_TYPE_CAPTURED_PLAYER ParticipantTypeID: " . PARTICIPANT_TYPE_CAPTURED_PLAYER;
						$this->storeTestingData('STEP 4 ST: capturedUserSetToSpectator', 'error', $msg, __FILE__, __LINE__);	
					}
					else {
						$msg = "Successfully set user $capturedUserID to spectator in game $gameID";
						$this->storeTestingData('STEP 4 ST: capturedUserSetToSpectator', 'sucess', $msg, __FILE__, __LINE__);

						// STEP 5 ST: NOTIFY PLAYER THEY HAVE BEEN CAPTURED & NOTIFY PLAYER THEY HAVE A NEW TARGET
						//$userID = $capturedUserID; //TODO! do we need this?
						$dataToSend = "Player has been captured";
						$navigationStatus = 'spectator';
						$socketSentCaptured = $this->publishToSocket($dataToSend, $gameID, $capturedUserID, $navigationStatus);	
					
						if($socketSentCaptured['status'] == 'error'){
							$filePath = 'socket_results.txt';
							$data = array('socketSent' => $socketSentCaptured, 'socketData' => $dataToSend, 'gameID' => $gameID, 'capturedUserID' => $capturedUserID);
							$dataString = json_encode($data);
							$this->appendFailedResultsToFile($dataString, $filePath);
							$this->storeTestingData('STEP 5a ST: socketSentToCapturedUser', 'error', $dataString, __FILE__, __LINE__);
						}
					
						$this->storeTestingData('STEP 5a ST: socketSentToCapturedUser', 'success', "CapturedUserID: $capturedUserID, GameID: $gameID", __FILE__, __LINE__);
						//sending socket to player who is the capturer, this allows front end to update their target list to get their new target
						$dataToSend = "You have captured a player! You will now have a new target to capture.";
						$navigationStatus = 'newTarget';
						$socketSentNewTarget = $this->publishToSocket($dataToSend, $gameID, $this->claimUserID, $navigationStatus);	
						
					

						if($socketSentNewTarget['status'] == 'error'){
							$filePath = 'socket_results.txt';
							$data = array('socketSent' => $socketSentNewTarget, 'socketData' => $dataToSend, 'gameID' => $gameID, 'userID' => $this->claimUserID);
							$dataString = json_encode($data);
							$this->appendFailedResultsToFile($dataString, $filePath);
							$this->storeTestingData('STEP 5b ST: socketSentForNewTarget', 'error', $dataString, __FILE__, __LINE__);
							$debugInfo = array("message" =>"STEP 5b ST error: " . $socketSentNewTarget['message'] ,"details" => "STEP 5b ST error: " . $socketSentNewTarget['message']. __LINE__ . " " . __FILE__);
							// $errorMsg = "Oops! There was an error notifying you of your new target. Please try again or contact customer support for assistance.";
							// $navigation = 'MyGame';//TODO not sure if this is right location
							// $response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
							// return $response;
						}
						
							
						$this->storeTestingData('STEP 5b ST: socketSentForNewTarget', 'success', "UserID: " . $this->claimUserID . ", GameID: " . $gameID, __FILE__, __LINE__);
						
						// STEP 6 ST: CHECK HOW MANY REMAINING USERxTARGET RECORDS FOR GAMEID ARE ACTIVE - IF 1, END GAME DECLARE WINNER
						$remainingUserXTargetRecords = $this->getOpenUserXTargetRecordsByGameID($gameID);
						$endGame = false;
						$claimUsersTargetID = $this->getTargetIDByUserID($this->claimUserID);
						$msg = "Checking if game $gameID should be ended. RemainingUserXTargetRecords: $remainingUserXTargetRecords ClaimUsersTargetID: $claimUsersTargetID";
						//end if users's target is themself, OR if there is only one userXtarget record left
						if(($remainingUserXTargetRecords == false || count($remainingUserXTargetRecords) <= 1)){						
							$msg = "One or less userXtarget records left for game $gameID. RemainingUserXTargetRecords: " . $remainingUserXTargetRecords . " ClaimUsersTargetID: $claimUsersTargetID";
							$endGame = true;
							$endGameReason = "winner";
						}
						else if($claimUsersTargetID == $targetID ){
							$msg = " User $this->claimUserID has been assigned themselves (claimUsersTargetID: $claimUsersTargetID TargetID: $targetID)  in game $gameID. ";
							$endGame = true;
							$endGameReason = "winner";
						}
						else { //more than 1 target remaining and/or user has not been assigned themselves
							$endGame = false;
							
						}
						
						$this->storeTestingData('STEP 6 ST: processing check to end game', 'success', $msg , __FILE__, __LINE__);
						
						if($endGame == true){
							// STEP 7 ST: END GAME
							
							$endGameResult = $this->endGame($gameID, $endGameReason);
							$msg = "Successfully ended game $gameID with winner ";
							$this->storeTestingData('STEP 7 ST: oneTargetLeftEndGameCalled', 'sucess', $msg, __FILE__,  __LINE__);
											
							return $endGameResult;
							
						}
						else {
							$this->storeTestingData(' NO STEP 7 ST: only called if game ends', 'n/a', $msg , __FILE__, __LINE__);
							$result = array('status' => 'success', 'data' => null, 'message' => "Continue processing capture");
							return $result;

						}
						

							
						
						
					}
					
				}
			
			}
			

			
		}
		
		

		
	 }
	 
	 public function createSymbolicLinkAndUpdateTargetAndSnapshotRecord($snapshotID, $targetID) {

        $snapshotRecord = $this->getSnapshotRecord($snapshotID);
        $snapshotURI = $snapshotRecord['fileURI'];
        $afterUsers = strstr($snapshotURI, 'media/users/');
        $alphanumericUserIDSubDirAndFileName = substr($afterUsers, strlen("media/users/"));
        $fileToPointTo = MEDIA_UPLOADS_DIR . "users/" . $alphanumericUserIDSubDirAndFileName;
        
		//check if directory to store symbolic link exists, if not create it - same with symbolic link
        $symbolicLink = $this->createOrUpdateUserMediaStorageDirectoryAndSymbolicLink($this->claimUserID, $fileToPointTo);
        $substring = substr($symbolicLink, strpos($symbolicLink, "/uploads/") + strlen("/uploads/"));
        $profileSymbolicLinkURL = API_URL . "/media/" . $substring;

		//update targets table to store the symbolic link url
        $targetColumnsArray = array('targetSnapshotURI' => $profileSymbolicLinkURL);
        $targetWhereArray = array('id' => $targetID);
        $result = $this->db->update('targets', $targetColumnsArray, $targetWhereArray, array());
		if($result['status'] != 'success'){
			$msg = "Error updating targets table with symbolic link. SymbolicLink: $profileSymbolicLinkURL TargetID: $targetID updateResult: " . json_encode($result);
			$this->storeTestingData('STEP 1(Method): createOrUpdateUserMediaStorageDirectoryAndSymbolicLink', 'error', $msg, __FILE__, __LINE__);
			return $result;
		}
		else {
			$msg = "Created symbolic link to snapshot file in the targets table. SymbolicLink: $profileSymbolicLinkURL TargetID: $targetID updateResult: " . json_encode($result);
			$this->storeTestingData('STEP 1(Method): createOrUpdateUserMediaStorageDirectoryAndSymbolicLink', 'success', $msg, __FILE__, __LINE__);
			
			//update snapshots fileURI with symbolic link as well
			$snapshotColumnsArray = array('fileURI' => $profileSymbolicLinkURL);
			$snapshotWhereArray = array('id' => $snapshotID);
			$resultSnapshot = $this->db->update('snapshots', $snapshotColumnsArray, $snapshotWhereArray, array());
			return $resultSnapshot;
		}
       

		
    }
	




	
}