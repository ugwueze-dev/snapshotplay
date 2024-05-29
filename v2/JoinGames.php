<?php
/**
       _       _        _____                      
      | |     (_)      / ____|                     
      | | ___  _ _ __ | |  __  __ _ _ __ ___   ___ 
  _   | |/ _ \| | '_ \| | |_ |/ _` | '_ ` _ \ / _ \
 | |__| | (_) | | | | | |__| | (_| | | | | | |  __/
  \____/ \___/|_|_| |_|\_____|\__,_|_| |_| |_|\___|
 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayJoinGames() method.
 |--------------------------------------------------------------------------
 */
class JoinGames extends SnapshotDataClass{
	protected $results = array();
	protected $game;
	function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring);

		//Overwrite access permissions for this class methods
		$this->accessClientListArray['POST']['admin'] = true;
        $this->accessClientListArray['POST']['all'] = true; //This is for Users to join a game. Post on users is 'all' because it is a public endpoint
        $this->accessClientListArray['POST']['user'] = true;
        $this->accessClientListArray['GET']['admin'] = true;
        // $this->accessClientListArray['GET']['all'] = true;
        $this->accessClientListArray['GET']['user'] = true;
        $this->accessClientListArray['PUT']['admin'] = true;
        // $this->accessClientListArray['PUT']['all'] = true;
        $this->accessClientListArray['PUT']['user'] = true;
         $this->accessClientListArray['DELETE']['admin'] = true;
        // $this->accessClientListArray['DELETE']['all'] = true;
        // $this->accessClientListArray['DELETE']['user'] = true;

		$this->userHttpMethodGranted = $this->httpMethodAccessCheck(); //add to all endpoint constructors - set any changes above it
		
		if($this->userHttpMethodGranted == false){
			http_response_code(401);
			$errorMsg = "Oops! You do not have permission to access this info. Code: " .__LINE__ . __FILE__;
			$debugInfo = 'The httpMethodAccessCheck on line ' . __LINE__ . ' returned ' . $this->userHttpMethodGranted . '. ';
			$navigation = null; //TODO This is check will be changed with new authentication
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			die(json_encode($response));
		}

	
		// Set table to gamesXusers
		$this->db->table = 'gamesXusers';
		unset($this->db->tableJoins);
		$this->db->tableJoins[] = 'gamesXusers';

		$this->db->requiredFields = array(
			'userID', 
			'gameID', 
		);
		$this->db->fieldsArray = array(
			'id'		=> @$this->params['id'],
			'userID'	=> @$this->claimUserID,
			'gameID'	=> @$this->params['gameID'],
			'participantTypeID'	=> @$this->params['participantTypeID'],
		);
		
		//IF PARAMS ARE NOT SET - SET THEM TO NULL
		$this->id = isset($this->params['id']) ? $this->params['id'] : null;
		$this->userID = isset($this->claimUserID) ? $this->claimUserID : null;
		$this->gameID = isset($this->params['gameID']) ? $this->params['gameID'] : null;
		$this->gameCode = isset($this->params['gameCode']) ? $this->params['gameCode'] : null;
		
		$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
			return ($value !== null && $value !== false && $value !== '');
		});
	
		
	}
	
	/**
	  _____ ______ _______
	 / ____|  ____|__   __|
	 | |  __| |__     | |
	 | | |_ |  __|    | |
	 | |__| | |____   | |
	  \_____|______|  |_|
	 */
	function displayJoinGames(){
		/*/
		|--------------------------------------------------------------------------
		|         Access is limited to Admin Only
		|--------------------------------------------------------------------------
		*/
		//check verifyAdminRequest () - checks if user isAdmin
		//if True - run getRecord
		$isAdmin = $this->isAdmin(); //FIXME change this once admin is implemented
		$userID = $this->claimUserID;
		$return =array();
		//add to the table here
		$this->db->tables = array('gameTypes', 'gamesXusers','gameModes', 'users','games');
		$this->db->whereArray = array(
			'games.gameTypeID' => '`gameTypes`.`id`',
			'games.gameModeID' => '`gameModes`.`id`',
			'gamesXusers.gameID' => '`games`.`id`',
			'users.id' => '`gamesXusers`.`userID`',	
			'gamesXusers.participantTypeID' => array(PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_CAPTURED_PLAYER, PARTICIPANT_TYPE_SPECTATOR),
		);
				
		//This only restricts to logged in user data if NOT admin
		if($isAdmin == false) {
			$this->db->whereArray['gamesXusers.userID'] = $userID;
		}
		else {
			//$userID = $this->params['userID'] ?? 0; //?? is a null coalescing operator if userID is not set then set it to null
			$userID = $this->params['userID']; //?? is a null coalescing operator if userID is not set then set it to null
			if(!empty($userID) && $userID > 0){
				$this->db->whereArray['gamesXusers.userID'] = $userID;
			}//using the elseif caused the same game to return multiple times.  Not sure it is worth adjusting the code to allow for this. So for now just requiring userID if a gameID is passed in. 
		//else if((empty($userID) || $userID == 0) && empty($this->id)) {
			else {
				//todo REQUIRING USERID ALWAYS AND GAMEID OPTIONAL - THIS IS DUE MY STRUGGLE GETTING THE GAMEID TO WORK ALONE - COME BACK TO THIS
				http_response_code(400);
				$errorMsg = "Oops! Admin must pass in userID,  and can include the gameID if wanting specific game details. Code: " . __LINE__;
				$debugInfo = array('params' => $this->params, 'message' => $errorMsg);
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo); //can add $debugInfo as 4th param
				return $response;
				
			
			}
		}
		if (@$this->id > 0){

			$this->db->whereArray['games.id'] = $this->id;
			
		}
		else {
			$this->processWhere($this->params);
			//FIXME this select statement may not be accurate what is in dbHelper
			
		}
		
		$response = $this->db->select($this->db->tables,$this->db->whereArray,$this->db->whereGreater,$this->db->whereLess);
	
		if($response['status'] == "warning" || $response['data'] == 0) {
			http_response_code(404);
			$debugInfo = array("message" => $response['message']);
			$errorMsg = "Oops! Something went wrong. You either are not joined to any games or don't have access to view the game requested. Code: " . __LINE__;
			$navigation = 'Menu';
			$response = $this->createApiResponse(404, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
		$results = $response['data'];
		$this->mainQuery		 	= $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;
	
		/*/
		 |--------------------------------------------------------------------------
		 |Returning menu options associated with the game here
		 |--------------------------------------------------------------------------
		 */
		foreach ($results AS $keyIndex=>$gameDetails){
			$participantTypeName = $this->getParticipantInfoForGameByUserID($gameDetails['id'], $this->claimUserID);
			$results[$keyIndex]['userParticipantType'] =$participantTypeName;
			$results[$keyIndex]['participantArray']	= $this->getAllUserDetailsJoinedToGame($gameDetails['id']);

			//is this needed here? possibly can remove it
			if($gameDetails['gameStatusID'] == GAME_STATUS_ENDED){
				$winnerDetails = $this->getWinnerDetailsByGameID($gameDetails['id']);
				if(isset($winnerDetails['error'])){
					$results[$keyIndex]['winnerDetails'] = array(
						array(
						'winnersData' => null,
						'winnersError' => $winnerDetails['error'],
						)
					);
					
				}
				else {
					$results[$keyIndex]['winnerDetails'] = array($winnerDetails);
				}
				
			}
		}
		$this->mainQuery = $this->db->fullQuery;
        $this->availableItems = $this->db->availableItems;
		$this->processedParams =  "Sent params were not processed. Returned all records related to logged in user - ? MVP ?.";
	
		return  $this->prepareReturn($results);

		
	}
	/**
	  _____   ____   _____ _______
	 |  __ \ / __ \ / ____|__   __|
	 | |__) | |  | | (___    | |
	 |  ___/| |  | |\___ \   | |
	 | |    | |__| |____) |  | |
	 |_|     \____/|_____/   |_|
	 */
	function addJoinGames() { 
		$isAdmin = $this->isAdmin();
		
		if($isAdmin == true && empty($this->params['userID'])){
			http_response_code(401);
			$errorMsg = "Oops! Admins must include userID to join to game. Code: " . __LINE__;
			$debugInfo = array('params' => $this->params, 'message' => $errorMsg);
			$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo); //can add $debugInfo as 4th param
			return $response;
		}
		else if($isAdmin == true && !empty($this->params['userID'])){
			$this->claimUserID = $this->params['userID']; //TODO this might not be wise, but other methods are using the claimUserID and not a param
			$this->db->fieldsArray['userID'] = $this->claimUserID; //must be set here, as it isn't set in the constructor without jwt
		}

		if(empty($this->claimUserID)){ //this check is needed due to permissions for $this->accessClientListArray['POST']['all'] = true; (see constructor). This prevents public access to join a game. 
			http_response_code(401);
			$errorMsg = "Oops! You must be logged in to join a game. Code: " . __LINE__;
			$debugInfo = array('params' => $this->params, 'message' => $errorMsg);
			$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo); //can add $debugInfo as 4th param
			return $response;
		}
		

		// Check if user is eligible to join a game
		$isUserJoinedToGame = $this->getAllJoinedGames($this->claimUserID);
		$params = array(
			'id' => $this->claimUserID,
			'phone' => $this->getPhonebyUserID($this->claimUserID),
			'gameID' => $this->id,
			
		);
		
		$participantTypeID = $this->params['participantTypeID'];
		if($participantTypeID == PARTICIPANT_TYPE_ACTIVE_PLAYER){
			$user = new Users($params, $this->authJWTstring);

			$userAccessInfo = $user->canUserAccessGameBasedOnTransactionHistory($user->subscriptionDetailArray, $user->activeGameTokensArray, 'createGame');
			
			// if($userAccessInfo['access'] == 'denied' ){
			// 	$errorMsg = "Oops! Please purchase a token or subscription to join the game.  Code: " . __LINE__;
			// 	if($userAccessInfo['subscriptionDetails'][0]['productID'] == MONTHLY_SUBSCRIPTION || $userAccessInfo['subscriptionDetails'][0]['productID'] == ANNUAL_SUBSCRIPTION){
					
			// 		$errorMsg = "Oops! Your subscription is no longer active. Please visit the store to renew your subscription or purchase a host token to create a game. Code: " . __LINE__;
			// 	}
			// 	else if ($userAccessInfo['access'] == 'denied' && $userAccessInfo['product'] == 'playerTokens'){
			// 		$errorMsg = "Oops! You do not have a token to join a game. Please visit the store to purchase a token. Code: " . __LINE__;
			// 	}

			// 	http_response_code(400);
			// 	$debugInfo = [
			// 		"message" => "User subscription is no longer valid.",
			// 		"location" => __FILE__ . " line " . __LINE__,
			// 		"details" => $userAccessInfo,
			// 	];
			// 	$navigation = 'Menu';
			// 	$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			// 	return $response;
			// }
		
			
		}
		//VALIDATE JOIN GAME REGARDLESS OF PARTICIPANT TYPE
		$joinGameError = $this->validateJoinGame();
		
		if ($joinGameError !== null) {
			http_response_code(400);
			$navigation = isset($joinGameError['navigation']) ? $joinGameError['navigation'] : null;
			$debugInfo = $joinGameError;
			$response = $this->createApiResponse(400, null, $joinGameError['message'], $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}

		// ADD USER TO THE GAME
		$addResult = $this->joinUserToGame();
	
		if (!empty($addResult['id']) && $addResult['id'] > 0) {
			//If game was paid for with token, update the token record to show it was used
			if($userAccessInfo['product'] == 'playerTokens'){
			//Get the tokenID to deactivate from the list of active tokens
			$tokenToDeactivate = $user->getUserTokenDetailsForTokenTypeByUserID(PLAYER_TOKEN, $this->claimUserID);
			
			//Deactivate the token
			$updateToken = $user->deactivateGameTokenRecord($tokenToDeactivate[0]['gameTokenID']);
			
				if($updateToken == false){
					http_response_code(400);
					$errorMsg = "Oops! Something went wrong updating your game token. Please contact support at support@connectandconquer.com";
					$navigation = 'Menu';
					$debugInfo = null;
					$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
					return $response;
				}
			}

			// Fetch game and participant details
			
			$gameDetails = $this->fetchGameDetails();
	
			$participantDetails = $this->fetchParticipantDetails($this->gameID);
			
			 $gameCode = $this->params['gameCode'];
			$hostUserID = $gameDetails['hostUserID'];
			
			// Prepare and return the result
			$this->authInfoArray['status'] = JWT_STATUS_REQUEST_NEW;
			$this->results['gameDetails'] = array($gameDetails);
			$this->results['hostDetails'] = array($this->getHostDetails($hostUserID));
			$this->results['activePlayers'] = $participantDetails['activePlayers'];
			$this->results['spectators'] = $participantDetails['spectators'];
			$this->results['captured'] = $participantDetails['captured'];

			$playerArray = $this->getNameAndImageOfUsersJoinedToGame($this->gameID);
			$joiningPlayerName = $this->getFullNameByUserID($this->claimUserID);
			$shortMessage = "Player joined game $this->gameCode";
			$longMessage = "Player " . $joiningPlayerName . " has joined the game $this->gameCode";
			$messageID = $this->addMessage($shortMessage, $longMessage);
			foreach ($playerArray as $player){ //NOT USING SOCKET HERE TO REDIRECT
				$notificationID = $this->addNotification($messageID, $player['userID']);
				
				if($notificationID <= 0){
					//todo update this to log it, to an error log - currently only logging we have - 
					$this->storeTestingData("CreateNotification","fail", "error: NotificationID returned  ". $notificationID, __LINE__, __FILE__, $gameCode, $this->claimUserID);
				}
				
			}
	
			return $this->prepareReturn($this->results);
		} else {
			http_response_code(400);
			$errorMsg = "Oops! There was an issue adding you to the game. Please try again or contact support for assistance. Code: " . __LINE__;
			$debugInfo = array("message" => "Could not add record", "details" => $addResult['message']);
			$navigation = 'JoinGame';
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
	 
	 UPDATE THE MEMBERS OF THE GAME BY REMOVING USER FROM GAME - UNLESS HOST - THEN END GAME

	 USING UPDATE JOIN GAMES TO REMOVE USER FROM GAME INSTEAD OF DELETE BECAUSE A BODY IS REQUIRED IN THE REQUEST 
	 AND DELETE DOES NOT ALLOW A BODY IN THE REQUEST (AS PER MY UNDERSTANDING)

	 */
	function updateJoinGames(){
	/*/
	|--------------------------------------------------------------------------
	|         Access is limited to Admin, Players, Spectators (Hosts must end game)
	|--------------------------------------------------------------------------
	
	|--------------------------------------------------------------------------
	|    Withdrawing from the game - transfer the target - treat it like a snapshot
	|--------------------------------------------------------------------------
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
	
		//REQUIRED PARAMS TO BE SENT ON POST REQUEST
		$gameID = $this->params['gameID'];
		$removingUserID = $this->params['removingUserID'];
		$removalReasonID = $this->params['removalReasonID']; //match targetStatusID
		//TODO validate these inputs
			
		//CHECK PERMISSION TO REMOVE USER
		$permissionResult = $this->checkPermissionToRemoveUser($gameID, $removingUserID);
		
		if (isset($permissionResult['error'] )) {
			$errorMsg = "Oops! You do not have permission to remove this player.";
			$debugInfo = array("message" =>"Error in permission to remove user. " . $permissionResult['error'] ,"details" => "Error in permission to remove user. " . $permissionResult['error']. __LINE__ . " " . __FILE__);
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		
		//IF GLOBAL GAME NO REASSIGNMENT OF TARGETS NEEDED
		//TODO update and add record.  Update only with active = 0, then create new record with active = 1 and duplicate info. 
		$gameTypeName = $this->getGameTypeNameByGameID($gameID);
		if ($gameTypeName === 'Global') {
			
			$result = $this->withdrawFromGlobalGame($removingUserID, $gameID);
		
			if($result['status'] != 'success'){
				$debugInfo = array("message" =>"Error in withdrawing from global game. " . $result['message'] ,"details" => "Error in withdrawing from global game. " . $result['message']. __LINE__ . " " . __FILE__);
				$errorMsg = "Oops! There was an error removing you from the game. Try again, or please contact customer support.";
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
			}
		} 
		else {
			$removingUserID = $this->params['removingUserID'];
			$removalReasonID = $this->params['removalReasonID']; //match targetStatusID
			$gameID = $this->params['gameID'];
			//WITHDRAW FROM NON-GLOBAL GAME
			$result = $this->withdrawFromSingleTargetGame($removingUserID, $gameID, $removalReasonID);
	
			if(isset($result['error'])  && !empty($result['error'])){
				$debugInfo = array("message" =>"Error in withdrawing from non-global game. " . $result['error'] ,"details" => "Error in withdrawing from non-global game. " . $result['error']. __LINE__ . " " . __FILE__);
				$errorMsg = "Oops! There was an error removing you from the game. Try again, or please contact customer support.";
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
			}
		}	
		return $result;
	 }
		

	
	public function checkIfUserIsAlreadyInGame($userID, $gameID){
		//check if user is already in the game
		$response = $this->db->select('gamesXusers',array('userID'=>$userID,'gameID'=>$gameID,'active'=>1),$this->db->whereGreater,$this->db->whereLess,['id'],'',true);
		
		// print_r($response); die('checkIfUserIsAlreadyInGame');
		if(!empty($response['data'])){
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Description: Get array of playerIDs in game with their participantTypeID
	 *
	 * @param int $gameID
	 * @return 
	 */
	public function getParticipantIDsInGame($gameID){
		$targetIDs = array();
		//pull user data from db by id
		$tables = array(
			'gamesXusers'
		);
		$where = array(
			'gameID' => $gameID,
			'gamesXusers.participantTypeID' => array(PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_CAPTURED_PLAYER, PARTICIPANT_TYPE_SPECTATOR),
			
		);
		$columnsArray = array(
			'`gamesXusers`.`userID`',
			'`gamesXusers`.`participantTypeID`'

			
		);

		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			false // when true, it is returning each column as its own array
		);
		
		 if(!empty($result['data'])){
			return $result['data'];
		}
		else {
			return false;
		}

	}
	/**
	 * Add a user to a game
	 * @param int $userID
	 * @param int $gameID
	 * 
	 */

	public function addUserToGame($userID, $gameID, $participantTypeID = 1){
		//check if user is already in the game
		if($this->checkIfUserIsAlreadyInGame($userID, $gameID) == true){
			return array("error" => " The UserID ".$userID." is already in the game");
		}
		elseif($this->getCurrentJoinedGames($userID) != false){
			return array("error" =>  " You are already in a game. Each user can only be joined to one game at a time.");
		}
		else{
			//add user to game
			$table = 'gamesXusers';
			$insertArray = array(
				'userID' => $userID,
				'gameID' => $gameID,
				'participantTypeID' => $participantTypeID
			);
			$addResult  = $this->db->insert($table, $insertArray);
			if (!empty($addResult['id'])) {
				return $addResult;
			} else {
				return array('error' => __LINE__ . ": Could not add record", 'details' => $addResult);
			}
		}
	}		

		// Create a method to validate whether the user can join the game
	private function validateJoinGame() {
		$recordExists = $this->gamesXUsersRecordExists(SYSTEM_GAME_SET_SNAPSHOT, $this->claimUserID);
		if($recordExists != false){
			return array('navigation' => 'MyGame', 'message'=>"Oops! You must upload a snapshot selfie before creating or joining a game. We want to make sure you're all set for a great gaming experience!");
		}
		// $moreThanOneGame = $this->getCurrentJoinedGames($this->claimUserID);
		// if($moreThanOneGame != false){
		// 	return array('message'=> "You are already in a game. Each user can only be joined to one game at a time." );
		// }
		$gameID = $this->getGameIDFromGameCode($this->gameCode);
		if($gameID == false){
			return array('navigation' => 'JoinGame','message'=>"Oops! The game code is not valid. Double-check the code or contact support for assistance");
		}
		$gameStatusID = $this->checkGameStatusID($this->gameID);
		if ($gameStatusID != GAME_STATUS_CREATED){
			return array('message' =>  "Unfortunately, the game you're trying to join has already started/ended, and as a result, new players are unable to join. Refer to Game (ID:".$this->gameID.", CODE:".$this->gameCode.").  Code:".__LINE__ );
		}
		if (!empty($this->id)) {
			return array('navigation' => null,"message" =>  " Oops! It seems there's an issue with joining the game. The gameXuserID must not be set when adding a JoinGame.  Code:".__LINE__  );
		}
		elseif (empty($this->gameID) && empty($this->gameCode) ) {
			return array('navigation' => 'JoinGame',"message" => " Uh-oh! It looks like there's an issue adding you to the game. To proceed, please make sure to set either the Game ID or provide a Valid Game Code  Code:". __LINE__ );
		}
		// elseif (empty($this->params['userID'])) {
		// 	return array("error" => __LINE__ . ": UserID must be set when adding a user to a Game");
		// }
		elseif (empty($this->claimUserID)) {
			return array('navigation' => 'Login',"message" => " To join a game, please log in. Need help? Reach out to our support team at support@connectandconquer.com. Happy gaming!  Code:".__LINE__ );
		}
		// else if($this->claimUserID != $this->params['userID']){
		// 	return array('error' => __LINE__ . ": User ID must match logged in user ID");
		// }
		else if ($this->checkIfUserIsAlreadyInGame($this->claimUserID, $this->gameID) == true){
			return array('navigation' => 'MyGame',"message" =>  "Oops! The UserID ".$this->claimUserID." is already in the game " );
		}
		//check if user exists
		elseif ($this->checkIfUserExists($this->claimUserID) == false){
			return array('navigation' => 'Signup',"message" => "Oops! The UserID ".$this->claimUserID." doesn't exist. Please check the ID or contact support for assistance.  Code:".__LINE__ );
		}
	}

	// Create a method to add the user to the game
	private function joinUserToGame() {
		$this->params['active'] = 1;
		$addResult = $this->db->addRecord();
		//return array('id' => $addResult['id']);
		return array('id' => $addResult);
	}

	// Create a method to fetch game details
	private function fetchGameDetails() {
		
		if(!empty($this->params['adminKey'])){
			
			$gamesObject = new Games(['id'=>$this->gameID, 'adminKey'=>$this->params['adminKey'], 'userID'=>$this->params['userID']], $this->authJWTstring);	
		
		}
		else {
			
			$gamesObject = new Games(['id'=>$this->gameID], $this->authJWTstring);
		
		}
			
		$data = $gamesObject->displayGames();
		
		$gameDetails = $data['data'];
		
		foreach($gameDetails as $game){
			if( $game['id'] === $this->gameID){
				$joinedGameDetails = $game;
			}		
		}

		return $joinedGameDetails;
	}

	// Create a method to fetch participant details
	private function fetchParticipantDetails($gameID) {
		$participants= $this->getParticipantIDsInGame($gameID);
		foreach($participants AS $player){
			
			$targetID = $this->getTargetIDByUserID($player['userID']);
			$targetDetails = $this->getTargetDetails($targetID);
			$playerInfo['userID'] = $player['userID'];
			$playerInfo['targetID'] = $targetDetails['id'];
			$playerInfo['name'] = $targetDetails['targetName'];
			$playerInfo['snapshotURI'] = $targetDetails['targetSnapshotURI'] . "?ts=" . time();//add timestamp to force refresh and avoid issue with caching

			if($player['participantTypeID'] == PARTICIPANT_TYPE_ACTIVE_PLAYER){
				$participantDetails['activePlayers'][] = $playerInfo;
			}
			else if ($player['participantTypeID'] == PARTICIPANT_TYPE_SPECTATOR){
				$participantDetails['spectators'][] = $playerInfo;
			}
			else if ($player['participantTypeID'] == PARTICIPANT_TYPE_CAPTURED_PLAYER){
				$participantDetails['captured'][] =  $playerInfo;
			}					
		}

			if(empty($participantDetails['activePlayers'])){
				$participantDetails['activePlayers'] = [];
			}
			if(empty($participantDetails['spectators'])){
				$participantDetails['spectators'] = [];
			}
			if(empty($participantDetails['captured'])){
				$participantDetails['captured'] = [];
			}
		return $participantDetails;
	}

	// Create a method to get host details
	private function getHostDetails($userID) {
		$hostDetails = $this->getLimitedUserDetailByID($userID);
		$hostDetails['fileURI'] = $hostDetails['fileURI'] . "?ts=" . time();//add timestamp to force refresh and avoid issue with caching
		return $hostDetails;
	}

	/*/
		|--------------------------------------------------------------------------
		|        WITHDRAW FROM GAME METHODS
		|--------------------------------------------------------------------------
		*/
	function checkPermissionToRemoveUser($gameID, $removingUserID) {
		
		if ($removingUserID != $this->claimUserID) {
			$hostedGames = $this->getCurrentHostedGames($this->claimUserID);
			
			if (empty($hostedGames) || !in_array($gameID, $hostedGames)) {
				return array('error' => "User $this->claimUserID is not a host of game $gameID. Only hosts have permission to remove another user from a game");
			}
			} else {
				$this->authInfoArray['status'] = JWT_STATUS_REQUEST_NEW;
		}

		return array('status' => 'success');
	}

	function withdrawFromGlobalGame($removingUserID, $gameID) {
		if($gameID == 1){
			$errorMsg = "Oops! You cannot withdraw from the demo game. Please take a selfie and it will remove you from the game. This is required for game play";
			$navigation = 'MyGame';
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		//UPDATE THE USER TARGET RECORD FOR THE REMOVED USER
		$userXTargetRecord = $this->getOpenUserXTargetRecordByUserAndGameID($removingUserID, $gameID);
		unset($userXTargetRecord['availableItems']);
		$userXTargetRecord['closedDateTime'] = date('Y-m-d H:i:s');
		$userXTargetRecord['targetStatusID'] = TARGET_STATUS_CLOSED_PLAYER_WITHDRAWN;
		$userXTargetRecord['active'] = 0;
		$updateResult = $this->updateAndGenerateUserTargetRecord($removingUserID, $userXTargetRecord, TARGET_STATUS_CLOSED_PLAYER_WITHDRAWN);
		if($updateResult['status'] != 'success'){
			http_response_code(400);
			$errorMsg = "OOPS! There was an error while withdrawing the player from the game. Please contact customer support.";
			$debugInfo = array('message' =>" Failed to close userTarget record for user $removingUserID in game $gameID" . __LINE__ . __FILE__);
			$navigation = 'MyGame';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		//TODO decrement the active targets in redis
		
		//DEACTIVATE THE GAME X USER RECORD FOR THE REMOVED USER
		$result = $this->deactivateGamesXUsersRecord($removingUserID, $gameID);
		if ($result['status'] === 'success') {
			return array(
				'status' => '200',
				'message' => "Successfully removed $removingUserID from game $gameID",
				'gameInfo' => $this->$this->getGameInfoByGameID($gameID),
			);
		}
		
		return $result;
	}
	function withdrawFromSingleTargetGame($removingUserID, $gameID, $removalReasonID) {
		//CHECK IF HOST IS TRYING TO WITHDRAW - NOT ALLOWED, MUST END GAME
		$hostUserID = $this->getHostUserID($gameID);
		if($hostUserID == $removingUserID){
			$debugInfo = array('message' => "User $removingUserID is the host of game $gameID. The host cannot withdraw from the game. Use EndGame to close the game and remove the host and all players", 'gameID' => $gameID, 'removingUserID' => $removingUserID);
			$errorMsg = "Oops! You are the host of this game. The host cannot withdraw from the game. Use EndGame to close the game and remove the host and all players.";
			$navigation = 'MyGame';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		//CHECK IF GAME IS CREATED OR STARTED - IF CREATED NO REASSIGNMENT, JUST CLOSE GAMESXUSERS RECORD
		$gameStatusID = $this->getGameStatusIDByGameID($gameID);
		
		if($gameStatusID != GAME_STATUS_CREATED){

			$userXTargetRecord = $this->getOpenUserXTargetRecordByUserAndGameID($removingUserID, $gameID);	

			if ($userXTargetRecord === false) {
				$debugInfo = array('message' => "No usersXtargets record for user $removingUserID in game $gameID", 'gameID' => $gameID, 'removingUserID' => $removingUserID);
				$errorMsg = "Oops! There was an error removing you from the game due to an issue finding your target. Please contact customer support for assistance.";
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg,$debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
	
			$targetIDNeedsReassign = $userXTargetRecord['targetID'];

			//DECREMENT THE WTIHDRAWING USER'S ACTIVE TARGETS IN REDIS
			$this->redis->decreaseByOne('user'.$removingUserID. REDIS_TARGET_STRING);

			//CLOSE USER TARGET RECORD FOR REMOVED USER
			$updateResults = $this->updateAndGenerateUserTargetRecord($userXTargetRecord['id'], $userXTargetRecord, $removalReasonID);
		
			if($updateResults['status'] != 'success'){
				$debugInfo = array('message' => "Failed to close userTarget record for user $removingUserID in game $gameID", 'gameID' => $gameID, 'removingUserID' => $removingUserID, 'updateResults' => $updateResults);
				$errorMsg = "OOPS! There was an error while withdrawing the player from the game. Please contact customer support.";
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
					
			}
		
			//Close the record of the user whose target is the removed user
			$removedUserTargetID = $this->getTargetIDByUserID($removingUserID);
			$record = $this->getOpenUserXTargetRecordByTargetAndGameID($removedUserTargetID, $gameID);
			if($record == false){
				$debugInfo = array('message' => "No usersXtargets record for user $removingUserID in game $gameID", 'gameID' => $gameID, 'removingUserID' => $removingUserID);
				$errorMsg = "Oops! Something went wrong while removing you from the game. Please contact customer support for assistance.";
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}

			//CREATE A NEW USER TARGET RECORD FOR THE REMOVED USER'S TARGET
			$update = $this->updateAndGenerateUserTargetRecord($record['id'], $record, TARGET_STATUS_CLOSED_PLAYER_WITHDRAWN);
			if($update['status'] != 'success'){
				$debugInfo = array('message' => "Failed to close userTarget record for user $removingUserID in game $gameID", 'gameID' => $gameID, 'removingUserID' => $removingUserID, 'updateResults' => $updateResults);
				$errorMsg = "Oops! There was an error while withdrawing the player from the game. An issue occurred in closing the users' target record Please contact customer support.";
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
		
		
			//Assign the removed user's target to the user whose target was removed
			$assignmentMade = $this->assignTargetToUser($targetIDNeedsReassign, $record['userID'], $gameID);
			if(isset($assignmentMade['error'])){
				$debugInfo = array('message' => "Failed to assign target $targetIDNeedsReassign to user {$record['userID']} in game $gameID", 'gameID' => $gameID, 'removingUserID' => $removingUserID, 'assignmentMade' => $assignmentMade);
				$errorMsg = "Oops! There was an error while withdrawing the player from the game. An issue occurred in assigning the removed user's target to the user whose target was removed. Please contact customer support.";
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
			//NOTIFY USERS OF TARGET CHANGE
			$userID = $record['userID'];
			$notificationID = $this->createSingleNotification("You have a new target.", "Your target has been changed.  Please check your target details.", $userID);
			if($notificationID <= 0){
				//todo update this to log it, to an error log - currently only logging we have - 
				$this->storeTestingData("CreateNotification","fail", "error: NotificationID returned  ". $notificationID, __LINE__, __FILE__, $gameID, $this->claimUserID);
			}
			
			$dataToSend = "Your target has been changed.  Please check your target details.";
			
			$navigationStatus = 'getTarget';
			$socketSent = $this->publishToSocket($dataToSend, $gameID, $record['userID'], $navigationStatus);
			
			
			if($socketSent['status'] == 'error'){
				$this->socketEventInfo[$record['userID']] = array('error' => __LINE__.": There was an error sending a socket message to user" . $record['userID'], "socketError" => $socketSent);
			}
//end it here
		
			//Check how many remaining userXtarget records for the game are active
			$remainingUserXTargetRecords = $this->getOpenUserXTargetRecordsByGameID($gameID);
		
			if (count($remainingUserXTargetRecords) == 1) {
				
				// End the game and declare a winner
				$endGameResult = $this->endGame($gameID, 'winner');
				
				if (isset($endGameResult['error'])) {
					$errorMsg = "Oops! There was an error ending the game. Please contact customer support for assistance.";
					$debugInfo = array('message' => "Failed to end game $gameID", 'gameID' => $gameID, 'removingUserID' => $removingUserID, 'endGameResult' => $endGameResult);
					$navigation = 'MyGame';
					$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
					return $response;
					
				}
				else {
					return $endGameResult;
				}
			}
		}
	
		// DEACTIVATE USER FROM GAME
		$isRemoved = $this->deactivateGamesXUsersRecord($removingUserID, $gameID);
		if ($isRemoved['status'] !== 'success') {
			$debugInfo = array('message' => "Failed to deactivate user $removingUserID from game $gameID", 'gameID' => $gameID, 'removingUserID' => $removingUserID, 'isRemoved' => $isRemoved);
			$errorMsg = "Oops! There was an error removing you from the game. Please contact customer support for assistance.";
			$navigation = 'MyGame';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
	
		// PREPARE THE RESPONSE
		$returnArray = array(
			'status' => 'success',
			'message' => "You (UserID: ". $removingUserID . ") have been successfully removed from game $gameID",
			//'gameInfo' => array($this->getGameInfoForResponse($gameID)),
		);
	
		return $this->prepareReturn($returnArray);
	}

	public function getOpenUserXTargetRecordByTargetAndGameID($targetID, $gameID){
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'targetID' => $targetID,
			'gameID' => $gameID,
			'targetStatusID' => TARGET_STATUS_GAME_STARTED
		);
		
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			array(), // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
	
		if(isset($result['returnedItems']) && $result['returnedItems'] > 0){
			return $result['data'][0];
		}
		else {
			
			return false;
		}
	 }

	 function assignTargetToUser($targetID, $userID, $gameID) {		
		$newTargetDetails = array(
			'userID' => $userID,
			'targetID' => $targetID,
			'gameID' => $gameID,
			'startDateTime' => date('Y-m-d H:i:s'),
			'closedDateTime' => null,
			'targetStatusID' => TARGET_STATUS_TARGET_TRANSFERRED,
		);
	
		$recordExists = $this->openUserTargetRecordExists($newTargetDetails['userID'], $newTargetDetails['targetID'], $newTargetDetails['gameID']);
		if (!$recordExists) {
			$newTarget = new UserTargets([], $this->authJWTstring);
			$newTargetResult = $newTarget->addUserTargetsRecord($newTargetDetails['userID'], $newTargetDetails['targetID'], $newTargetDetails['gameID'], TARGET_STATUS_TARGET_TRANSFERRED);
			if ($newTargetResult['status'] !== 'success') {
				return array('error' => $newTargetResult['message']);
			}
		}
	}

	protected function getGameInfoByGameID($gameID){
		$tables = array(
			'games'
		);
		$where = array(
			'id' => $gameID
		);
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			array(), // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
	   //  print_r($result);die('getUserIDByTargetID');
		if($result['status'] == 'success'){
			return $result['data'][0];
		}
		else {
			return false;
		}
	}

	

}