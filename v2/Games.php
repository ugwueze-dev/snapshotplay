<?php
/**
 *   _____                           
 *  / ____|                          
 * | |  __  __ _ _ __ ___   ___  ___ 
 * | | |_ |/ _` | '_ ` _ \ / _ \/ __|
 * | |__| | (_| | | | | | |  __/\__ \
 *  \_____|\__,_|_| |_| |_|\___||___/
 *
 */
class Games extends SnapshotDataClass
{
	protected $verb;
	protected $gameObject; //is this used? 
	protected $results = array();
	protected $gameStartDateTime;

    function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring);

		$this->accessClientListArray['POST']['admin']   = true;
		//$this->accessClientListArray['POST']['all']     = true;
		$this->accessClientListArray['POST']['user']    = true;
		$this->accessClientListArray['GET']['admin']    = true;
		// $this->accessClientListArray['GET']['all']      = true;
		$this->accessClientListArray['GET']['user']     = true;
		$this->accessClientListArray['PUT']['admin']    = true;
		// $this->accessClientListArray['PUT']['all']      = true;
		$this->accessClientListArray['PUT']['user']     = true;
		//$this->accessClientListArray['DELETE']['admin'] = true;
		// $this->accessClientListArray['DELETE']['all']   = true;
		$this->accessClientListArray['DELETE']['user']  = true;
			// var_dump($this->accessClientListArray);
			// die();
			//echo($this->httpMethodAccessCheck());
		$this->userHttpMethodGranted = $this->httpMethodAccessCheck(); //add to all endpoint constructors - set any changes above it
		//var_dump($this->userHttpMethodGranted);
		if($this->userHttpMethodGranted == false){
			http_response_code(401);
			$errorMsg = "Oops! You do not have permission to access this info. Code: " .__LINE__;
			$debugInfo = 'The httpMethodAccessCheck on line ' . __LINE__ . ' returned ' . $this->userHttpMethodGranted . '. ';
			$navigation = null; //TODO This is check will be changed with new authentication
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			die(json_encode($response));
		}
        $this->db->requiredFields = array(
            // 'id',
            'gameName',
			'gameModeID',
            'gameTypeID',
            // 'gameCode',
           // 'description',
        );
        $this->db->fieldsArray = array(
            'id' => @$this->params['id'],
            'gameName' => @$this->params['gameName'],
			'gameModeID' => @$this->params['gameModeID'],
            'gameTypeID' => @$this->params['gameTypeID'],
            'gameCode' => @$this->params['gameCode'],
            'description' => @$this->params['description'],
			//'gameStartDateTime'=>@$this->params['gameStartDateTime'],
			'hostUserID'=>@$this->params['hostUserID'],
			//'gameStatus'=>@$this->params['gameStatus'],
			'gameStatusID'=>@$this->params['gameStatusID'],
            'active' => @$this->params['active'],
           );
		   if (!empty($this->params['id'])){

        	$this->id = @$this->params['id'];
		   }
		   elseif (!empty($this->gameID)) {
			   $this->id = $this->gameID;
			   
		   }
		   else {
			   $this->id = null;
		   }	

		// Set the 'verb' property to the 'verb' parameter value from $params, or null if not provided.
		$this->verb = isset($this->params['verb']) ? $this->params['verb'] : null;

        $this->db->fieldsArray = array_filter($this->db->fieldsArray, function ($value) {
            return ($value !== null && $value !== false && $value !== '');//anonymous function that cleans the array
        });
		//PERMISSIONS
		
	
			if($this->id != NULL){
				// Define a mapping of participantTypeID to allowed methods and reason codes
				// FOR GAMES CLASS, ADMINS CAN DO ALL CRUD - not limiting here
				$allowedMethods = [
					PARTICIPANT_TYPE_SPECTATOR => ['GET'],         // Spectator can only GET
					PARTICIPANT_TYPE_ACTIVE_PLAYER => ['GET', 'PUT'],  // Active Player can GET and PUT
					PARTICIPANT_TYPE_CAPTURED_PLAYER => ['GET'],  // Captured Player can only GET
					PARTICIPANT_TYPE_HOST => ['GET', 'PUT'],  // Host can GET, PUT (DELETE is handled in JoinGames endgame method	)
					
				];

				// Define a mapping of reason codes
				$reasonCodes = [
					1 => "Spectators and Players can only GET",
					2 => "Active Players can GET ",
					3 => "Captured Players can only GET",
					4 => "Hosts can GET, PUT, and DELETE",
					
				];

				

				
				// FOR GAMES CLASS, ADMINS CAN DO ALL CRUD
				if($this->isAdmin() == false )  {
					$participantTypeIDArray = $this->getParticipantTypeIDs($this->id, $this->claimUserID);
					if($participantTypeIDArray != false) {
						$lowestParticipantTypeID = min($participantTypeIDArray); //HACK this will get the lowest permissions, but then doesn't allow the host to do antyhing. Need to find a way to determine if host is acting as host or player. 
					}
					else {
						$lowestParticipantTypeID = $participantTypeIDArray;
					}
	
					//if ($lowestParticipantTypeID === false || !in_array($requestMethod, $allowedMethods[$lowestParticipantTypeID])) {
					if ($lowestParticipantTypeID === false ) {
					
						//$reason = $reasonCodes[$participantTypeIDArray] ?? "Unauthorized access";
						http_response_code(401);
						$errorMsg = "Oops! You do not have permission to access this info." . __LINE__;
						$debugInfo = [
							"message" => "Unauthorized access",
							"participantTypeIDArray" => $participantTypeIDArray,
							"requestMethod" => $this->httpMethod,
							"allowedMethods" => $allowedMethods,
							"lowestParticipantTypeID" => $participantTypeIDArray,
							//"reason" => $reason,
							"location" => __FILE__ . " line " . __LINE__,
						];
						$navigation = null; //TODO This is check will be changed with new authentication
						$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo,  $navigation); //can add $debugInfo as 4th param
						die(json_encode($response));
						
					}
				}

				
			}
			// else {
			// 	//TODO / verify POST request
			// 	//if from a player or spectator they can only have 10 or less games they create
			// 	//if from a host they can have unlimited games they create
			// }

    }

    /**
     *  _____ ______ _______
     * / ____|  ____|__   __|
     * | |  __| |__     | |
     * | | |_ |  __|    | |
     * | |__| | |____   | |
     *  \_____|______|  |_|
     */
	
    function displayGames()
    {
		
		/*/
		|--------------------------------------------------------------------------
		|         Access is limited to Admin and User
		|--------------------------------------------------------------------------
		*/
		//check verifyAdminRequest () - checks if user isAdmin
		//if True - run getRecord
		
		//if admin, they must pass in either a userID, or a gameID.  Return the either all games for the user, or the game details for the gameID
		$return =array();
		$userID = $this->claimUserID; //this is overwritten with param['userID'] if admin
		$orderBy = 'id';
		$order = 'ASC';
		$returnRecord = '';
		//add to the table here
		$this->db->tables = array('gameTypes', 'gamesXusers','gameModes', 'users','games');
		$columnsArray = array(
			'`games`.`id`',
			'`games`.`gameName`',
			'`games`.`gameTypeID`',
			'`gameTypes`.`gameTypeName`',
			'`games`.`gameCode`',
			'`games`.`gameStatusID`',
			'`games`.`gameStartDateTime`',
			'`games`.`hostUserID`',
			"(SELECT CONCAT(fName, ' ', lName) FROM users WHERE id = games.hostUserID) AS hostName",
			'`users`.`selfieSnapshotID`',
			'`games`.`description`',
		);
		$this->db->whereArray = array(
			'games.gameTypeID' => '`gameTypes`.`id`',
			'games.gameModeID' => '`gameModes`.`id`',
			'gamesXusers.gameID' => '`games`.`id`',
			'users.id' => '`gamesXusers`.`userID`',	
			// 'games.hostUserID' => '`users`.`id`',
			'gamesXusers.participantTypeID' => array(PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_CAPTURED_PLAYER, PARTICIPANT_TYPE_SPECTATOR),
		);
		$isAdmin = $this->isAdmin(); 

		//This only restricts to logged in user data if NOT admin
		if($isAdmin == false) {
			$this->db->whereArray['gamesXusers.userID'] = $userID;
			
		} //if admin, they must pass in either a userID, or a gameID.  Return the either all games for the user, or the game details for the gameID
		else {
			$userID = $this->params['userID'];
			if(!empty($userID) && $userID > 0){
				$this->db->whereArray['gamesXusers.userID'] = $userID;
				
			}
			else {
				//todo REQUIRING USERID ALWAYS AND GAMEID OPTIONAL - THIS IS DUE MY STRUGGLE GETTING THE GAMEID TO WORK ALONE WITH GAME OPTIONS - COME BACK TO THIS
				http_response_code(400);
				$debugInfo = array('params' => $this->params, 'message' => 'Admin must pass in at least the userID,  and can include the gameID if wanting specific game details');
				$errorMsg = "Oops! Admin must pass in at least the userID,  and can include the gameID if wanting specific game details. Code: " . __LINE__;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo); //can add $debugInfo as 4th param
				return $response;
				
			
			}

		}

		if (@$this->id > 0){
			
			$this->db->whereArray['games.id'] = $this->id;
			
		}
		// header("Content-Type: application/json");
		// echo json_encode([
		// 	"Line " . __LINE__ => __FILE__,
		// 	'function' => debug_backtrace()[1]['function'] ?? 'N/A',
		// 	"zzReturn" => $this->db->whereArray,
		// ]);
		// die();
		
		//GET CURRENT GAMES
		if($this->params['getGames'] == 'currentGames'){
			$returnRecord = 'currentGames';
			//write method to get current games (no ended ones)
			$this->db->whereArray['games.gameStatusID'] = $this->id;
			$this->db->whereArray = array(
				'games.gameTypeID' => '`gameTypes`.`id`',
				'games.gameModeID' => '`gameModes`.`id`',
				'gamesXusers.gameID' => '`games`.`id`',
				'users.id' => '`gamesXusers`.`userID`',
				// 'games.hostUserID' => '`users`.`id`',
				'gamesXusers.participantTypeID' => array(PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_SPECTATOR, PARTICIPANT_TYPE_CAPTURED_PLAYER),
				'gamesXusers.userID' => $userID,
			
			);
			$whereMoreString = " AND ((games.gameStatusID = 7 AND gamesXusers.participantTypeID IN ('1', '2')) OR (games.gameStatusID  IN ('1', '2', '5') AND gamesXusers.participantTypeID IN ('1', '2', '3')))";
			

		} //GET GAME HISTORY
		else if(isset($this->params['getGames']) == 'gameHistory'){
			//write method to only return games that have ended, or that gameStatusID = 7 & userParticipantTypeID = CAPTURED_PLAYER
			$returnRecord = 'currentGames';
			//write method to get current games (no ended ones)
			$this->db->whereArray['games.gameStatusID'] = $this->id;
			$this->db->whereArray = array(
				'games.gameTypeID' => '`gameTypes`.`id`',
				'games.gameModeID' => '`gameModes`.`id`',
				'gamesXusers.gameID' => '`games`.`id`',
				'users.id' => '`gamesXusers`.`userID`',
				// 'games.hostUserID' => '`users`.`id`',
				'gamesXusers.participantTypeID' => array(PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_SPECTATOR, PARTICIPANT_TYPE_CAPTURED_PLAYER),
				'gamesXusers.userID' => $userID,
			
			);
			$whereMoreString = " AND ((games.gameStatusID = 7 AND gamesXusers.participantTypeID IN ('3')) OR (games.gameStatusID  IN ('3', '4', '6') ))";

		}
		else {
			$this->processWhere($this->params);
			//FIXME this select statement may not be accurate what is in dbHelper
			
		}
		
		$response = $this->db->select($this->db->tables,$this->db->whereArray,$this->db->whereGreater,$this->db->whereLess, $columnsArray, $whereMoreString, false, false, 'all', null, $orderBy, $order);
	
		$results = $response['data'];
		$this->mainQuery		 	= $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;

		if(count($results) < 1){
			http_response_code(250);
			$debugInfo = array(
				"message" => "No games found for user $this->claimUserID",
				"location" => __FILE__ . " line " . __LINE__,
			);
			//$errorMsg = "Oops! No games were found for you! ";
			
			$navigation = 'Menu';
			$response = $this->createApiResponse(250, null, null, $debugInfo,  $navigation); //can add $debugInfo as 4th param
			$this->storeTestingData('HTTP RESPONSE for displayGames', 'success', 'HTTP response code 250 No games found for user', __FILE__, __LINE__);
			return $response;
			
		}
		/*/
		 |--------------------------------------------------------------------------
		 |Returning menu options associated with the game here
		 |--------------------------------------------------------------------------
		 */
		foreach ($results AS $keyIndex=>$gameDetails){
			
			$participantTypeName = $this->getParticipantInfoForGameByUserID($gameDetails['id'], $userID);
			$results[$keyIndex]['userParticipantType'] =$participantTypeName;
			$results[$keyIndex]['menuOptions']	= array($this->getMenuOptionsByGameID($gameDetails, $userID));

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
		
		//return the results with

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
	function addGames(){

		/*/
		|--------------------------------------------------------------------------
		|         Access is limited to Admin and User
		|--------------------------------------------------------------------------
		*/

		/**********************************************************************************
		 * NEW ACCOUNT RULES - PHASE 2 //README
		 * Any users assigned to game #1 (tutorial game) can not be assigned to any other game
		 * Once they have completed their target for game #1 they are removed from the game
		 * 
		 * 1. if a user is a paid subscriber, then can create and join any game.
		 * 2. If a user is not a paid subscriber, they can purchase a credit to create a game or a less expensive price credit to join a game (this allows them to have joined more than 1 game)
		 * 3. if they are not a paid subscriber and have no credits they can only join 1 game 
		 */
		//FIXME verify a user can not be added to gamesXusers for the game if the entry already exists
		//Error if game startTime is in past
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

		//1. IF GAME START DATE TIME IS SET, VERIFY IT IS IN THE FUTURE
		$this->gameStartDateTime = $this->validateAndSetGameStartDateTime();
		if(isset($this->gameStartDateTime['error'])){
			return $this->gameStartDateTime;
		}
		//echo('gameStartTimeValidation: ' . $gameStartTimeValidation);
		//2. HAS USER PAID/PURCHASED RIGHT TO CREATE GAME 
		$user = new Users($this->params, $this->authJWTstring);
		$userAccessInfo = $user->canUserAccessGameBasedOnTransactionHistory($user->subscriptionDetailArray, $user->activeGameTokensArray, 'createGame');
		
		$userHasAccessToCreate = $this->verifyUserAccess($userAccessInfo);
		//echo('userHasAccessToCreate: ' . $userHasAccessToCreate);
		if($userHasAccessToCreate != true){
			return $userHasAccessToCreate; //return error message
		}
		else {
			//proceed with game creation
	
			//3. HAS USER COMPLETED DEMO GAME
			$userHasCompletedDemoGame = $this->userHasCompletedDemoGame($this->claimUserID);
			//echo('userHasCompletedDemoGame: ' . $userHasCompletedDemoGame);
			if($userHasCompletedDemoGame != true){
				return $userHasCompletedDemoGame; //return error message
			}
			else {
			
				//4. VALIDATE GAME ID AND CODE ARE NOT SET
				$gameCode = $this->params['gameCode'];
				$validationResponse = $this->validateGameIDAndGameCodeNotSet($this->id,$gameCode);
				//echo('validationResponse: ' . $validationResponse);
				if($validationResponse != true){
					return $validationResponse;
				}
				else {
					//5. SET VARIABLES TO CREATE GAME
					$addResult = $this->setVariablesAndCreateGame();
					//echo('addResult: ' . $addResult);
					//6. If SUCCESSFULLY ADDED GAME - ADD HOST TO GAME (AS BOTH HOST AND PLAYER/SPECTATOR)
					if(!empty($addResult['id']) && $addResult['id'] > 0){
						
						$this->id = $addResult['id'];
						$addGamesXUsersRecords = $this->determineHostTypeAndAddGamesXUsersRecord();
						//echo('addGamesXUsersRecords: ' . $addGamesXUsersRecords);
						if($addGamesXUsersRecords != true){
							return $addGamesXUsersRecords;
						}
						else {
							//7. IF GAME WAS PAID FOR WITH TOKEN, UPDATE THE TOKEN RECORD TO SHOW IT WAS USED
							if ($userAccessInfo['product'] == 'hostToken') {
								$updateToken = $this->handleHostTokenDeactivation($userAccessInfo);
								//echo("we are handeling the host token");

							}	//Else payed for with subscription no additional action needed continue on
								/**
								 * WE NEED TO GET TOKENS WORKING HERE!
								 */
							// if($updateToken != true){
							// 	echo("token not updated");
								
							// 	//notify slack there is a game issue, don't break game play
							// 	$this->storeTestingData('addGamesTokenUpdateError', 'error', 'Token not updated. $updateToken = ' .  $updateToken, __FILE__, __LINE__);
							// 	$response = $this->createApiResponse(400, null, $updateToken, null, null); //can add $debugInfo as 4th param
							// 	return $response; //this would be an error message array
							// }
							// else {
								$this->authInfoArray['status'] = JWT_STATUS_REQUEST_NEW; //a new JWT is required to give access to the new game
								$results = $this->getGameDetailsByID($this->id);
								//echo('results: ' . $results);
								if($results == false){
									$errorMsg = "Oops! Something went wrong getting game details. Please contact support at support@connectandconquer.com";
									$navigation = 'MyGame';
									$debugInfo = null;
									$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
									return $response;
										
								}
								http_response_code(200); //HACK this is currently returning 400 on success. Haven't found reason why, hardcoding 200 here to overwrite it. 
								$returnArray = $results;
								//echo('returnArray: ' . $returnArray);
								return $this->prepareReturn($returnArray); //return the results with
							// }
							
						}
					}
					else {
						http_response_code(400);
						$errorMsg = "Oops! Something went wrong creating the game. Please contact support at support@connectandconquer.com";
						$debugInfo = [
							"message" => "Could not add record to games table. ",
							"details" => $addResult['message'],
							"location" => __FILE__ . " line " . __LINE__,
						];
						$navigation = 'Menu';
						$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
						return $response;
						
					}

				}

			
			}

				
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
	function updateGames(){
	/*/
		|--------------------------------------------------------------------------
		|         Access is limited to Admin and Users
		|--------------------------------------------------------------------------
		*/

		/*/
		|--------------------------------------------------------------------------
		|        THIS method is for either updating game details or calling the methods 
		|		 start/end game which will update the game status and call the appropriate
		|        methods. To update gameStatus, user must be host or admin & 
		| 	     must pass a verb (start or end). if verb is not passed, game details will be updated
		|
		|        Do a switch case to call the different methods of game play. 
		|		 Like: Start, Snapshot, End, etc.
		|		 VERBS: start, end, reset, autoStart
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

		$gameID = $this->id;
		if (empty($gameID) && $this->params['verb'] != 'autoStart') { 
			// If no valid ID is given, return an error message
			http_response_code(400);
			$errorMsg = "Oops! A valid gameID is required to update a game.";
			$debugInfo = array('error' => "no valid ID given: $gameID");
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
		$hostUserID = $this->getHostUserID($gameID); //this->id is the gameID
		$gameStatus = $gameStatusID = $this->getGameStatusIDByGameID($gameID);
		
		//Giving host and or an admin access to start/stop game
		if($this->isAdmin() || $hostUserID == $this->claimUserID){
			
				if (!empty($this->verb)){
					$error = 0;
					switch ($this->verb) {
						case 'start':
							//verify access to start game
							
							if($gameStatusID != GAME_STATUS_CREATED){
								http_response_code(400);
								$errorMsg = "Oops! This game is not ready to start.";
								$debugInfo = array('error' => __LINE__.": Game does not equal created. ",  'gameStatusID' => $gameStatusID);
								$navigation = "MyGames";
								$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); 
								
							}
							else {
								$response =  $this->startGame($gameID);	
							}
						
							break;
						case 'end':
							if($gameStatusID != GAME_STATUS_STARTED){
								http_response_code(400);
								$errorMsg = "Oops! This game is not currently running.";
								$debugInfo = array('error' => __LINE__.": Game is not started",  'gameStatusID' => $gameStatusID);
								$navigation = "MyGames";
								$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); 
								
							}
							else {
								$gameEndReason = $this->params['gameEndReason'];
								if(empty($gameEndReason)){
									if($this->isAdmin()){
										$gameEndReason = "Game ended by Admin";
									}
									else{
										$gameEndReason = "Game ended by Host";
									}
									
								}
								$response =  $this->endGame($gameID, $gameEndReason);
								
							}
							break;
						// To help with testing, I added a reset verb.
						case 'reset':
							$response =  $this->resetGame();
						 	break;
						case 'autoStart': //autostart will grab all the id's needed and loop thru
							$response =  $this->autoStartGame();
							break;
						default:
							# code...
							break;
					}
					
					return $response;
				
				} 
				else { //If no verb is passed, update the game details

					if($gameStatusID == GAME_STATUS_ENDED){
						http_response_code(400);
						$errorMsg = "Oops! This game has ended. You can not update the game details.";
						$debugInfo = array('error' => "Game has ended");
						$navigation = null;
						$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
						return $response;
					}
					
					// Update the record with the given ID - do not allow status to be updated here
					if(isset($this->db->fieldsArray['gameStatusID'])){
						unset($this->db->fieldsArray['gameStatusID']);
					}
					
					if(isset($this->params['gameStartDateTime'])){
						$this->gameStartDateTime = $this->validateAndSetGameStartDateTime();
						$this->db->fieldsArray['gameStartDateTime'] = $this->gameStartDateTime;
					}
					$updated = $this->db->updateRecord($this->db->table, $gameID);
		
					if ($updated['status']!=='error') {
						// If the update is successful, return the displayed Targets
						return $this->displayGames();
					} else {
						// If there is an error during update, return an error message
						http_response_code(400);
						$errorMsg = "Oops! Something went wrong when updating the game. Please try again, or contact support@connectandconquer.com";
						$debugInfo = array('error' => __LINE__.": Could not update record", 'details' => $updated['message']);
						$navigation = "MyGames";
						$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
						return $response;
					}
					
				}
		} 
		else { //If not admin or host isn't the user, return an error message
			http_response_code(401);
			$errorMsg = "Oops! Only the host or an admin can update the game details or start or end a game.";
			$debugInfo = array('error' => __LINE__.": Only admin or host can update game details, 'claimUserID' => $this->claimUserID, 'hostUserID' => $this->params['hostUserID'], 'isAdmin' => $this->isUserAdmin()");
			$navigation = "MyGames";
			$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
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

	 THIS IS HANDLED IN GAMEPLAYS ENDGAME METHOD
	 */

	

	
	/**
	 * Generates a unique game code.
	 *
	 * @return string The generated game code.
	 */
	function generateGameCode(){
		// Define the characters available for the code
		$characters = array_merge(range(2, 9), range('A', 'Z'));
		// Remove characters that might cause confusion
		$characters = array_diff($characters, ['L', 'O', 'I']);

		// Generate the first code part
		$code1 = '';
		for ($i = 0; $i < 3; $i++) {
			// Randomly select a character index from the available characters
			$randomIndex = array_rand($characters);
			// Append the selected character to the code part
			$code1 .= $characters[$randomIndex];
		}

		// Generate the second code part
		$code2 = '';
		for ($i = 0; $i < 3; $i++) {
			// Randomly select a character index from the available characters
			$randomIndex = array_rand($characters);
			// Append the selected character to the code part
			$code2 .= $characters[$randomIndex];
		}

		// Combine the code parts with a hyphen to form the complete game code
		$fullCode = $code1 . '-' . $code2;

		// Check if the generated game code is unique
		if (!$this->checkGameCodeUnique($fullCode)) {
			// The generated game code is not unique, generate a new one
			return $this->generateGameCode();
		}

		// Return the generated game code
		return $fullCode;
	}

	/**
	 * Checks if a game code is unique and not already used in the Game table.
	 *
	 * @param string $gameCode The game code to check.
	 * @return bool Returns true if the game code is unique, false otherwise.
	 */
	function checkGameCodeUnique($gameCode){
		// Prepare the condition to check for the game code in the Game table
		$where = array(
			'gameCode' => $gameCode,
			'active' => '1',
		);

		// Perform a select query to fetch the records with the given game code
		$response = $this->db->select('games', $where);
		$results = $response['data'];

		// Check if any records exist with the given game code
		if (count($results) > 0){
			// The game code is not unique, return false
			return false;
		}
		else {
			// The game code is unique, return true
			return true;
		}
	}
	/**
	 * Description: In a dataset of games, find the game with the given search key
	 * ie: findGameBy($data, 'id', 1) will return the game with id 1 
	 * ie: findGameBy($data, 'gameCode', 'ABC-123') will return the game with gameCode ABC-123
	 *
	 * @param array $data The dataset to search in.
	 * @param string $searchKey The key to search for.
	 * @param string $searchValue The value to search for.
	 * @return $game The game with the given search key and value, or null if not found.
	 */
	function findGameBy($data, $searchKey, $searchValue) {
		foreach ($data as $game) {
			if (isset($game[$searchKey]) && $game[$searchKey] === $searchValue) {
				return $game; // Game found
			}
		}
		
		return null; // Game not found
	}
	
	/**
	 * Description: Get details of game based on gameID
	 *
	 * @param int $gameID
	 * @return mixed $gameDetails on success or false on failure
	 */
	function getGameDetailsByID($gameID) {
		$tables = array(
			'games',
			'users',
			'gameTypes'
		);
		$where = array(
			'games.id' => $gameID,
			'games.hostUserID' => '`users`.`id`',
			'games.gameTypeID' => '`gameTypes`.`id`'
			
		);
		$columnsArray = array(
			'`games`.`id`',
			'`games`.`gameName`',
			'`games`.`gameTypeID`',
			'`gameTypes`.`gameTypeName`',
			'`games`.`gameCode`',
			'`games`.`gameStatusID`',
			'`games`.`gameStartDateTime`',
			'`games`.`hostUserID`',
			'`users`.`fName`',
			'`users`.`lName`',
			'`users`.`selfieSnapshotID`',
			'`games`.`description`',
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
		
		if ($result['status'] == 'success'){
			unset($result['data'][0]['availableItems']);
			
			$gameDetails = $result['data'][0];
			return $gameDetails;
		}
		else {
			return false;
		} 
		
	}

	protected function formatDataForDisplayGamesReturn($gameData){
		$returnArray = array();
		if($gameData != false){
			$playerArray = array();
			foreach($gameData as $game){
				$playerArray = $this->getAllUsersJoinedToGame($game['gameID']);
			//	$playerArray = $this->getAllUserDetailsJoinedToGame($gameID);
				$game['playerArray'] = $playerArray;
				$returnArray[] = $game;
			}
			// $playerArrayOptions = array('userID', 'fName', 'lName', 'participantTypeID', 'participantTypeName', 'selfieSnapshotID');
			// foreach	($gameData as $game) {
			// 	print_r($game);
			// 	foreach ($game as $key => $value) {
			// 		if(in_array($key, $playerArrayOptions)) {
			// 			$playerArray[$key] = $value;
			// 		}
			// 		else {
			// 			$gameDetails[$key] = $value;
			// 		}
			// 	}
			
			// 	$gameDetails['playerArray'] = $playerArray;
			// 	$returnArray = $gameDetails;
			// }
			header("Content-Type: application/json");
			echo json_encode([
				"Line " . __LINE__ => __FILE__,
				'function' => debug_backtrace()[1]['function'] ?? 'N/A',
				"#playerArray" => $returnArray,
			]);
			die();
			
		}
		else {
			$returnArray['error'] = "No games found for user $this->claimUserID";
		}
		return $returnArray;
	}

	protected function getMenuOptionsByGameID($game, $userID) {
	
		$gameID = $game['id'];
		$gameStatusID = $game['gameStatusID'];
		$hostID = $game['hostUserID'];
		//testData
		//$gameStatusID = GAME_STATUS_CREATED;
		//$hostID = 1; //is host
		//$hostID = 2; //not host
		
		$gameStartString = $game['gameStartDateTime'];
		//$gameStartDateTime = "2024-06-05 13:21:25";
		$countdownToStart = $this->calculateSecondsToStart($gameStartString);
	
		//CHECK IF USER IS HOST
		if($hostID == $this->claimUserID){
			$userIsHost = true;
		}
		else {
			$userIsHost = false;
		}
		//CHECK PARTICIPANT STATUS - To get here I assume the user is a participant due to endpoint permissions
		$participantTypeIDArray = $this->getParticipantTypeIDs($gameID, $userID); //if host this might return 2 values see count below to handle
		//$participantTypeIDArray = $this->getParticipantTypeIDs(3, 1); //if host this might return 2 values see count below to handle
	
		if (count($participantTypeIDArray) > 1){ //This will be the case if the user is both a host and a player/spectator. Only want to get player or spectator here
			$participantTypeID = min($participantTypeIDArray);
		}
		else {
			$participantTypeID = $participantTypeIDArray[0]; //there is only 1 ID returned in this case
		}
		//testData
		//$participantTypeID = PARTICIPANT_TYPE_SPECTATOR;
		//$participantTypeID = PARTICIPANT_TYPE_ACTIVE_PLAYER;
		
		$spectatorRuleArray = $this->getMenuOptionsSpectatorRuleArray();
		$playerRuleArray = $this->getMenuOptionsPlayerRuleArray();
		$hostRuleArray = $this->getMenuOptionsHostRuleArray();


		/*/
		 |--------------------------------------------------------------------------
		 |Returning menu options associated with the game here
		 | For each GameStatusID: 
		 |   CREATE HOST/GAMESTATUS RULE ARRAY
		 | - if host, get similarities between hostRuleArray and gameRuleArray
		 |   CREATE PARTICIPANTTYPE/GAMESTATUS RULE ARRAY
		 |  - get similarities between the participantTypeRuleArray for user (either player or spectator) and gameRuleArray
		 |  THE ONLY TIME RULES WILL BE ADDED TO HOST ARRAY IS IF HOST IS A PLAYER AND GAME IS STARTED OR ONGOING
		 |  RETURN THE ARRAY
		 |
		 |  Array_intersect_assoc returns only where both keys and values match. It removes the other keys and values
		 |  After building the rules for which options the user can see (true values), at the end we will add all the keys
		 |  back into the array so that all keys are there each time to standardize for front end.
		 |--------------------------------------------------------------------------
		 */
		switch ($gameStatusID) {
			case GAME_STATUS_CREATED:
				$createdGameRuleArray = $this->getMenuOptionsCreatedGameRulesArray();
	

				//SET HOST RULES AND CREATE MENU OPTION ARRAY
				if($userIsHost == true){
					//array_intersect_assoc must be used to compare BOTH keys and values to return correct data
					$menuOptionArray = array_intersect_assoc($createdGameRuleArray, $hostRuleArray);
					
				}
				else { //NOT A HOST
					
					//PARTICIPANT TYPE RULE ARRAY SET
					if ($participantTypeID == PARTICIPANT_TYPE_SPECTATOR || $participantTypeID == PARTICIPANT_TYPE_CAPTURED_PLAYER){
						
						$participantTypeRuleArray = $spectatorRuleArray;
					
					}
					elseif ($participantTypeID == PARTICIPANT_TYPE_ACTIVE_PLAYER){
						
						$participantTypeRuleArray = $playerRuleArray;
					}
					//GET COMMON RULES BETWEEN PARTICIPANT TYPE AND GAME STATUS
					$participantAndGameStatusRuleArray = array_intersect_assoc($createdGameRuleArray, $participantTypeRuleArray);
					$menuOptionArray = $participantAndGameStatusRuleArray; //if not host, only need participantTypeRuleArray and gameStatus
				}
				
				break;
			
			case GAME_STATUS_STARTED:
				$startedGameRuleArray = $this->getMenuOptionsStartedGameRulesArray();
				
				//SET HOST RULES AND CREATE MENU OPTION ARRAY
				if($userIsHost == true){
					
					//array_intersect_assoc must be used to compare BOTH keys and values to return correct data
					$menuOptionArray = array_intersect_assoc($startedGameRuleArray, $hostRuleArray);
			
					if($participantTypeID == PARTICIPANT_TYPE_ACTIVE_PLAYER){ //if host is a player, add the player rules to the host rules
						
						$menuOptionArray['takeSnapshot'] = true; //the only added rule for host/player - spectating host can not take snapshot
					}			
					
				}
				else { //NOT A HOST
					//PARTICIPANT TYPE RULE ARRAY SET
					if ($participantTypeID == PARTICIPANT_TYPE_SPECTATOR || $participantTypeID == PARTICIPANT_TYPE_CAPTURED_PLAYER){
						$participantTypeRuleArray = $spectatorRuleArray;
					}
					elseif ($participantTypeID == PARTICIPANT_TYPE_ACTIVE_PLAYER){
						$participantTypeRuleArray = $playerRuleArray;
					}
					//GET COMMON RULES BETWEEN PARTICIPANT TYPE AND GAME STATUS
					$participantAndGameStatusRuleArray = array_intersect_assoc($startedGameRuleArray, $participantTypeRuleArray);
					$menuOptionArray = $participantAndGameStatusRuleArray; //if not host, only need participantTypeRuleArray and gameStatus
				}
				break;
			case GAME_STATUS_EXPIRED:
				//handle same way as ended			
			case GAME_STATUS_ENDED:
				$endedGameRuleArray = $this->getMenuOptionsEndedGameRulesArray();
			
				//SET HOST RULES AND CREATE MENU OPTION ARRAY
				if($userIsHost == true){
					//array_intersect_assoc must be used to compare BOTH keys and values to return correct data
					$menuOptionArray = array_intersect_assoc($endedGameRuleArray, $hostRuleArray);
					
				}
				else { //NOT A HOST
					//PARTICIPANT TYPE RULE ARRAY SET
					if ($participantTypeID == PARTICIPANT_TYPE_SPECTATOR || $participantTypeID == PARTICIPANT_TYPE_CAPTURED_PLAYER){
						$participantTypeRuleArray = $spectatorRuleArray;
					}
					elseif ($participantTypeID == PARTICIPANT_TYPE_ACTIVE_PLAYER){
						$participantTypeRuleArray = $playerRuleArray;
					}
					//GET COMMON RULES BETWEEN PARTICIPANT TYPE AND GAME STATUS
					$participantAndGameStatusRuleArray = array_intersect_assoc($endedGameRuleArray, $participantTypeRuleArray);
					$menuOptionArray = $participantAndGameStatusRuleArray; //if not host, only need participantTypeRuleArray and gameStatus
				}

				break;
			
			case GAME_STATUS_SUSPENDED:
				$suspendedGameRuleArray = $this->getMenuOptionsSupsendedGameRulesArray();

				//SET HOST RULES AND CREATE MENU OPTION ARRAY
				if($userIsHost == true){
					//array_intersect_assoc must be used to compare BOTH keys and values to return correct data
					$menuOptionArray = array_intersect_assoc($suspendedGameRuleArray, $hostRuleArray);
					
				}
				else { //NOT A HOST

					//PARTICIPANT TYPE RULE ARRAY SET
					if ($participantTypeID == PARTICIPANT_TYPE_SPECTATOR || $participantTypeID == PARTICIPANT_TYPE_CAPTURED_PLAYER){
						$participantTypeRuleArray = $spectatorRuleArray;
					}
					elseif ($participantTypeID == PARTICIPANT_TYPE_ACTIVE_PLAYER){
						$participantTypeRuleArray = $playerRuleArray;
					}
					//GET COMMON RULES BETWEEN PARTICIPANT TYPE AND GAME STATUS
					$participantAndGameStatusRuleArray = array_intersect_assoc($suspendedGameRuleArray, $participantTypeRuleArray);
					$menuOptionArray = $participantAndGameStatusRuleArray; //if not host, only need participantTypeRuleArray and gameStatus
				}
				break;
			
			case GAME_STATUS_ONGOING:
				$ongoingGameRuleArray = $this->getMenuOptionsOngoingGameRulesArray();

				if($hostID == 0){ //demo game
					$ongoingGameRuleArray['invite'] = false;
					$ongoingGameRuleArray['withdraw'] = false;
				}
				//SET HOST RULES AND CREATE MENU OPTION ARRAY
				if($userIsHost == true){
					//array_intersect_assoc must be used to compare BOTH keys and values to return correct data
					$menuOptionArray = array_intersect_assoc($ongoingGameRuleArray, $hostRuleArray);
			
					if($participantTypeID == PARTICIPANT_TYPE_ACTIVE_PLAYER){ //if host is a player, add the player rules to the host rules
						$menuOptionArray['takeSnapshot'] = true; //the only added rule for host/player - spectating host can not take snapshot
					}
					
				}
				else { //NOT A HOST

					//PARTICIPANT TYPE RULE ARRAY SET
					if ($participantTypeID == PARTICIPANT_TYPE_SPECTATOR || $participantTypeID == PARTICIPANT_TYPE_CAPTURED_PLAYER){
						$participantTypeRuleArray = $spectatorRuleArray;
					}
					elseif ($participantTypeID == PARTICIPANT_TYPE_ACTIVE_PLAYER){
						$participantTypeRuleArray = $playerRuleArray;
					}
					
					//GET COMMON RULES BETWEEN PARTICIPANT TYPE AND GAME STATUS
					$participantAndGameStatusRuleArray = array_intersect_assoc($ongoingGameRuleArray, $participantTypeRuleArray);
					$menuOptionArray = $participantAndGameStatusRuleArray; //if not host, only need participantTypeRuleArray and gameStatus
				}
				break;
			
				
			default:
				# code...
				break;
		}


		// These are all the keys that should be in the array
		$allKeys = [
			'start', 'end', 'invite', 'withdraw', 'edit', 'winners',
			'snapshotsGallery', 'showCountDown', 'secondsTillStart',
			'startEarly', 'takeSnapshot',
		];
		
		//This is explained in comment section above as to why this is done
		//Make sure all arrayKeys are in the array, if they aren't set, set them to false - this standardizes the array for front end
		$menuOptionRuleArray = array_merge(array_fill_keys($allKeys, false), $menuOptionArray);
		
		if($menuOptionRuleArray['showCountDown'] == true){
			
			$menuOptionRuleArray['secondsTillStart'] = $countdownToStart;
		}
		else {
			
			$menuOptionRuleArray['secondsTillStart'] = null;
		}

		return $menuOptionRuleArray;
	}

	//TODO this is great for unit test!
	protected function calculateSecondsToStart($gameStartString){
		//use for testing
		
		$gameStartDateTime = new DateTime($gameStartString);
		
		// Get the current date and time
		$currentDate = new DateTime();

		// Check if the game start date is in the future
		if ($gameStartDateTime > $currentDate) {
			// Calculate the difference in seconds
			$secondsUntilGameStart = $gameStartDateTime->getTimestamp() - $currentDate->getTimestamp();

			return $secondsUntilGameStart;
		} else {
			return null;
		}
	}

	protected function getMenuOptionsSpectatorRuleArray(){
		return array(
			'start' => false,
			'end' => false,
			'invite' => true,
			'withdraw'=> true,
			'edit'=> false,
			'winners'=> true,
			'snapshotsGallery'=> true,
			'showCountDown'=> true,
			'secondsTillStart'=> null, //this will be null unless showCountdown is true 
			'startEarly'=> false,
			'takeSnapshot'=> false,
		);
	}
	protected function getMenuOptionsPlayerRuleArray(){
		return array(
			'start' => false,
			'end' => false,
			'invite' => true,
			'withdraw'=> true,
			'edit'=> false,
			'winners'=> true,
			'snapshotsGallery'=> true,
			'showCountDown'=> true,
			'secondsTillStart'=> null, //this will be null unless showCountdown is true 
			'startEarly'=> false,
			'takeSnapshot'=> true,
		);
	}
	protected function getMenuOptionsHostRuleArray(){
		return array(
			'start' => true,
			'end' => true,
			'invite' => true,
			'withdraw'=> false,
			'edit'=> true,
			'winners'=> true,
			'snapshotsGallery'=> true,
			'showCountDown'=> true,
			'secondsTillStart'=> null, //this will be null unless showCountdown is true and is set in the function that calls this
			'startEarly'=> true,
			'takeSnapshot'=> false,
		);
	}
	protected function getMenuOptionsCreatedGameRulesArray(){
		return array(
			'start' => true,
			'end' => true,
			'invite' => true,
			'withdraw'=> true,
			'edit'=> true,
			'winners'=> false,
			'snapshotsGallery'=> false,
			'showCountDown'=> true,
			'secondsTillStart'=> null, //this will be null unless showCountdown is true and is set in the function that calls this
			'startEarly'=> true,
			'takeSnapshot'=> false,
		);
	}
	
	protected function getMenuOptionsStartedGameRulesArray(){
		return array(
			'start' => false,
			'end' => true,
			'invite' => false,
			'withdraw'=> true,
			'edit'=> false,
			'winners'=> false,
			'snapshotsGallery'=> true,
			'showCountDown'=> false,
			'secondsTillStart'=> null, //this will be null unless showCountdown is true and is set in the function that calls this
			'startEarly'=> false,
			'takeSnapshot'=> true,
		);
	}

	protected function getMenuOptionsEndedGameRulesArray(){
		return array(
			'start' => false,
			'end' => false,
			'invite' => false,
			'withdraw'=> false,
			'edit'=> false,
			'winners'=> true,
			'snapshotsGallery'=> true,
			'showCountDown'=> false,
			'secondsTillStart'=> null, //this will be null unless showCountdown is true 
			'startEarly'=> false,
			'takeSnapshot'=> false,
		);
	}

	protected function getMenuOptionsSupsendedGameRulesArray(){
		return array(
			'start' => true,
			'end' => true,
			'invite' => false,
			'withdraw'=> true,
			'edit'=> false, //this may be true in future - edit the available hours of the game play
			'winners'=> false,
			'snapshotsGallery'=> true,
			'showCountDown'=> false,
			'secondsTillStart'=> null, //this will be null unless showCountdown is true 
			'startEarly'=> false,
			'takeSnapshot'=> false,
		);
	}

	protected function getMenuOptionsOngoingGameRulesArray(){
		return array(
			'start' => false,
			'end' => false,
			'invite' => true,
			'withdraw'=> true,
			'edit'=> false,
			'winners'=> false,
			'snapshotsGallery'=> false, //I think this is true based on gameType (if demo game no, but if another game yes? )
			'showCountDown'=> false,
			'secondsTillStart'=> null, //this will be null unless showCountdown is true 
			'startEarly'=> false,
			'takeSnapshot'=> true,
		);
	}

/**
	 * Start a game
	 * 1. identify the game type
	 * 2. identify the game players
	 * 3. make array of players, items etc
	 * 4. send assignments to players
	 */

	 function startGame($gameID){  

		// use this sql query to gather info to get the player array to make the assignments
		$startGameStartTime = microtime(true);
			$tables = array(
				'gamesXusers',
				'games',
				'users');

			$whereArray = array(
				'games.id' => '`gamesXusers`.`gameID`',
				'gamesXusers.gameID' => $gameID,
				'gamesXusers.userID' => '`users`.`id`',
				'gamesXusers.participantTypeID' => PARTICIPANT_TYPE_ACTIVE_PLAYER,
				'games.gameStatusID' => GAME_STATUS_CREATED,
				'games.active' => 1,
				'users.active' => 1,
				'gamesXusers.active' => 1,
			);
			$whereGreaterArray = array();
			$whereLessArray = array();
			$columnsArray = array(
				'`gamesXusers`.`id`',
				'`gamesXusers`.`userID`',
				'`gamesXusers`.`gameID`',
				'`games`.`gameTypeID`',
				'`users`.`targetID`'
			);

			$whereMoreString = '' ;
			$groupByColumnBoolean = false;
			$justDataBoolean = true;
			$numberPerPage= 'all';
			// $playerArray = $this->db->select($tables, $where, $whereGreater, $whereLess,$columnsArray,$whereMoreString,$groupByColumnBoolean,$justData, $numberPerPage);
			$playerArray = $this->db->select($tables, 
                    $whereArray,
                    $whereGreaterArray,
                    $whereLessArray,
                    $columnsArray, 
                    $whereMoreString,
                    $groupByColumnBoolean, 
                    $justDataBoolean, 
                    $numberPerPage);

				// header("Content-Type: application/json");
				// echo json_encode([
				// 	"Line " . __LINE__ => __FILE__,
				// 	'function' => debug_backtrace()[1]['function'] ?? 'N/A',
				// 	"playerArray" => $playerArray,
				// 	"query" => $this->db->fullQuery,
				// ]);
				
			//TODO add error handling
			//if status is set and = error, return error 
			if(isset($playerArray['status']) && $playerArray['status'] == 'error'){
				http_response_code(400);
				$errorMsg = "Oops! Something went wrong in starting the game. Please try again or contact customer support for assistance.";
				$debugInfo = array("details" => "Error in starting game. " . $playerArray['message'] . " " . __LINE__ . " " . __FILE__);
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}

			if (count($playerArray) < 1) {
				http_response_code(400);
				$errorMsg = "Oops! There are not enough players to start this game.";
				$debugInfo = array("details" => "Error in starting game. " . $playerArray['message'] . " " . __LINE__ . " " . __FILE__);
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
			if(count($playerArray) < 2 && $playerArray[0]['gameTypeID'] != 1) { //Todo this might not be true for all game types. Look at it. 
				http_response_code(400);
				$errorMsg = "Oops! To start a game there must be at least 2 players for this game type.";
				$debugInfo = array("details" => "Error in starting game. " . $playerArray['message'] . " " . __LINE__ . " " . __FILE__);
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
			
			/*/
		|--------------------------------------------------------------------------
		|      look at config.php for the game types when we have more than two
		|--------------------------------------------------------------------------
		*/
			foreach ($playerArray as $players) {
				$userTargetID = $players['id'];
				$xKey = $userTargetID;
				$newPlayersArray[$xKey] = $players;
				//GET DEVICE TOKENS HERE
				$tokenArray[$players['userID']] = $this->getPlayerDeviceTokens($players['userID']);
				
				//increment userTargetTotal in redis for each player - these are only active players - not spectators
				$redisKey = "user" . $players['userID'] .REDIS_TARGET_STRING;
				$incBy = 1;
				$this->redis->increaseByValue($redisKey, $incBy);

			}
			$assignments = $this->makeAssignments($newPlayersArray,$gameID);
			
			// Get the current time
			$gameStartDateTime = date('Y-m-d H:i:s');
			$columnsUpdateArray = array(
				'gameStartDateTime' => $gameStartDateTime,
				'gameStatusID' => GAME_STATUS_STARTED
			);
			$whereArray = array('id' => $gameID);
			// Prepare the SQL query
			$startResult = $this->db->update('games', $columnsUpdateArray, $whereArray, array());
			
			$playersAdded = count($assignments);
			$startGameEndTime = microtime(true);
			$totalTime = $startGameEndTime - $startGameStartTime;
			$totalTime = floor($totalTime * 1000) / 1000;
			$responseArray = array(
				'status' => 'success',
				'game status' => 'started',
				//'game result' => $startResult,
				'message' => "Game $this->gameCode started with $playersAdded players in $totalTime seconds",
				//'assignments' => $assignments //can add in for testing
			);

			//Send message to all players that the game has started
			$messageID = $this->addMessage("Game Started", "Game $this->gameCode has started");
			$gameParticipantArray = $this->getAllUsersJoinedToGame($gameID);
			//foreach ($playerArray as $player){ //was using player array, but this does not include spectators
			foreach ($gameParticipantArray as $player){
				$notificationID = $this->addNotification($messageID, $player['userID']);
				if($notificationID <= 0){
					//todo update this to log it, to an error log - currently only logging we have - 
					$this->storeTestingData("CreateNotification","fail", "error: NotificationID returned  ". $notificationID, __LINE__, __FILE__, $gameID, $this->claimUserID);
				}
				$dataToSend = "Game $this->gameCode has started";
				$gameID = $this->gameID;
				$navigationStatus = 'gameStarted';
				$socketSent = $this->publishToSocket($dataToSend, $this->gameID, $player['userID'], $navigationStatus);

				if($socketSent['status'] == 'error'){
					$this->socketEventInfo[$player['userID']] = array('error' => __LINE__.": There was an error sending a socket message to user $player[userID]", "socketError" => $socketSent);
				}
				
			}
	
			http_response_code(200);
			$data = array($responseArray);
			$navigation = null;
				$debugInfo = null;
			$response = $this->createApiResponse(200, $data, null, $this->socketEventInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		

	 }

	 protected function getPlayerDeviceTokens($userID){
		$table = 'devices';
		$whereArray = array(
			'userID' => $userID,
			'active' => 1
		);
		$columnsArray = array(
			'deviceToken'
		);
		$result = $this->db->select($table, $whereArray, array(), array(), $columnsArray, '', false, true, 'all');

		return array($result);
	 }

	 function resetGame(){
		// set the time to null
		$gameStartDateTime = null;
		$startGameTimeArray = array(
			'gameStartDateTime' => $gameStartDateTime,
			'gameStatusID' => GAME_STATUS_RESET
		);
		$whereArray = array('id' => $this->gameID);
		// Prepare the SQL query
		$startResult = $this->db->update('games', $startGameTimeArray, $whereArray, array());
		// return $this->assignElementsToElementsInArray($players);
		$assignmentsResetArray = array(
			'assignmentStatusID'=>1,
			'closedDateTime'=>date('Y-m-d H:i:s'),
			'closed'=>1);
		$assignmentWhereArray = array('gameID'=>$this->gameID);
		$assignementResult = $this->db->update('assignments', $assignmentsResetArray, $assignmentWhereArray,array());
		return $this->prepareReturn($startResult);
		
	 }

	 function makeAssignments($newPlayersArray, $gameID){
		// randomize the players, items etc
		shuffle($newPlayersArray);
		$assignments = array();
		// create the beginning of the assignments array 
		// by popping the last element and setting the variableS
		$lastPlayer = array_pop($newPlayersArray);
		$targetID = $lastPlayer['targetID'];//used in the foreach loop
		$lastPlayerUserID = $lastPlayer['userID'];//used AFTER the loop	
		$lastAssignmentID = $lastPlayer['id'];
		foreach ($newPlayersArray as $xKey => $userTargetArray) {
			$userID = $userTargetArray['userID'];
			// the previous targetID to the current userID
			$isActive = 1;
			$assignments[$xKey]= $this->addUserXTargetRecord($userID, $targetID, $gameID, TARGET_STATUS_GAME_STARTED, $isActive );
				
			//set the current targetID for the next loop
			$targetID = $userTargetArray['targetID'];
		}
		$isActive = 1;
		//assign the first player user ID to the last targetID
		$assignments[$lastAssignmentID] = $this->addUserXTargetRecord($lastPlayerUserID, $targetID, $gameID, TARGET_STATUS_GAME_STARTED, $isActive);

		return $assignments;
	}
	//method to insert a record into the usersXtargets table


	protected function getGamesWithStartTimesPassed(){
		$tables = array(
			'games'
		);
		$whereLessArray = array(
			'gameStartDateTime' => date('Y-m-d H:i:s')
		);
		$whereArray = array(
			
			'gameStatusID' => GAME_STATUS_CREATED,
			'active' => 1
		);
		$columnsArray = array(
			'id',
			'gameCode',
			'gameStartDateTime',
			'gameStatusID'
		);
		$whereMoreString = '';
		$groupByColumnBoolean = false;
		$justDataBoolean = false;
		$numberPerPage = 'all';
		$result = $this->db->select($tables, $whereArray, array(), $whereLessArray, $columnsArray, $whereMoreString, $groupByColumnBoolean, $justDataBoolean, $numberPerPage);
		
		
		return $result;

	}

	protected function autoStartGame(){
		
		if($this->isAdmin()){
			$count = 0;
			$games = $this->getGamesWithStartTimesPassed();
		
			if($games['status'] == 'success'){
				$gamesArray = $games['data'];
				foreach ($gamesArray as $game) {
					$gameID = $game['id'];
					$results = $this->startGame($gameID);
				
				 // Check if starting the game failed and append to the file if it did
				 if ($results['status'] != 200) {
					$error = $results['error'];
					$failedData = "Failed to start game with ID: $gameID Error: $error \n"; // Customize this message as needed
					$filePath = 'failed_results.txt';
					$this->appendFailedResultsToFile($failedData, $filePath);
					
				 }
				 else {
					
					$count++;
					$gameIDArray = array('gameID' => $gameID, 'startGameMessage'=> $results['data'][0]['message']);
				 }

				}
				$responseArray = array(
					'status' => 'success',
					'num games started' => $count,
					'message' => "Auto started $count games",
					'idArray of games started' => array($gameIDArray)
				);

			}
			else {
				$responseArray = array(
					'status' => 'error',
					'message' => "No games to start"
				);
			}
			return $responseArray;
		}
		else {
			$responseArray = array(
				'status' => 'error',
				'message' => "You do not have permission to auto start games"
			);
			http_response_code(400);
			$data = null;
			$error = "You do not have permission to auto start games";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, $data, $error, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
		
	}
/**
 * Description: This either sets the gameStartDateTime to null or verifes that the gameStartDateTime is in the future
 *   Used for game creation 
 *
 * 
 * 
 * @return string|array Returns a DateTime string for a valid startDateTime,
 *                      'Unscheduled' if unset or no error and startTimeString is empty,
 *                      or an array with ['status'] 400 error message if the game start time is in the past.
 *    
 * 
 */
	private function verifyGameStartDateTimeInFuture() {
		$gameStartString = $this->params['gameStartDateTime'] ?? null;
	
		if (!empty($gameStartString)) {
			$dateObject = DateTime::createFromFormat('Y-m-d h:i:s A', $gameStartString);

			$gameStartEpoch = $dateObject->format('U');
			$currentTimeEpoch = time();
			// Subtract 5 minutes (300 seconds) from current time to create the threshold time
			$thresholdTime = $currentTimeEpoch - 300;
	
			if ($gameStartEpoch < $thresholdTime) {
				http_response_code(400);
				$errorMsg = "Oops! Game start time must be in the future.";
				$debugInfo = [
					"message" => "Game start time must be in the future.",
					"location" => __FILE__ . " line " . __LINE__,
				];
				$navigation = null;
				return $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation);
			}
			$gameStartDateTimeString = $dateObject->format('Y-m-d H:i:s');
		} else {
			// Indicate that the game start time is unscheduled
			$gameStartDateTimeString = "unscheduled";
		}

		return $gameStartDateTimeString;
	}

	/**
	 * Description: Checks if user has permission to create game (either by subscription or host token)
	 *
	 *
	 * @return bool|array TRUE is returned if user can create, array with ['status'] 400 error message if user does not have permission to create game - returns true if access is given
	 */
	private function verifyUserAccess($userAccessInfo) {
		
		
		if($userAccessInfo['access'] == 'denied') {
			$errorMsg = "";
			if($userAccessInfo['subscriptionDetails'][0]['productID'] == MONTHLY_SUBSCRIPTION || $userAccessInfo['subscriptionDetails'][0]['productID'] == ANNUAL_SUBSCRIPTION) {
				$errorMsg = "Oops! Your subscription is no longer active. Please visit the store to renew your subscription or purchase a host token to create a game.";
			}
			else if ($userAccessInfo['product'] == 'hostTokens') {
				$errorMsg = "Oops! You do not have a host token to create a game. Please visit the store to purchase a host token.";
			}
			else {
				$errorMsg = "Oops! You do not have permission to join a game. Please visit the store to purchase a subscription or player token.";
			}
	
			http_response_code(400);
			$debugInfo = [
				"message" => "User subscription is no longer valid.",
				"location" => __FILE__ . " line " . __LINE__,
				"details" => $userAccessInfo,
			];
			$navigation = 'Menu';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation);
			
			return $response;
		}
	
		return true; // No error, user access is granted
	}
	/**
	 * Description: Verify user has completed the demo game before they can create or join any other games
	 *
	 * @param int $userID
	 * @return bool|array TRUE is returned if user has completed the demo game, array with ['status'] 400 error message if user has not completed the demo game
	 */
	public function userHasCompletedDemoGame($userID){
		//if user is a member of GameID 1 they can not join any other game or create games - this is where they set their profile
		$recordExists = $this->gamesXUsersRecordExists(SYSTEM_GAME_SET_SNAPSHOT, $this->claimUserID);
		if($recordExists != false){
			http_response_code(400);
			$errorMsg = "Oops! You must complete the tutorial game before you can create or join any other games.";
			$debugInfo = [
				"message" => "User is already a member of game #1",
				"location" => __FILE__ . " line " . __LINE__,
			];
			$navigation = 'MyGame';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		else {
			return true;
		}
	}

	private function validateGameIDAndGameCodeNotSet($gameID, $gameCode) {
		if (!empty($gameID)) {
			http_response_code(400);
			$errorMsg = "Oops! GameID must not be set when adding Game";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation);
			return $response;
		}
	
		if (!empty($gameCode)) {
			http_response_code(400);
			$errorMsg = "Oops! GameCode must not be set when adding Game";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation);
			return $response;
		}
	
		return true; // No error, game ID and game code are not set
	}
	
	/**
	 * Description: Set the variables needed to start a game, and then create the game in the db
	 *
	 * 
	 * @return 
	 */
	private function setVariablesAndCreateGame(){
		$this->db->fieldsArray['gameStatusID'] = GAME_STATUS_CREATED;
		$this->db->fieldsArray['gameCode']  = $this->generateGameCode();
		$this->db->fieldsArray['gameStartDateTime']  = $this->gameStartDateTime;
		$this->db->fieldsArray['active']  = 1;
		$this->db->fieldsArray['hostUserID']  = $this->claimUserID;
		
		$addResult = $this->db->addRecord();
		return $addResult;
	}

	/**
	 * Description: Determine if host is a player or spectator and add the gamesXusers record
	 *
	 * 
	 * @return bool|array TRUE is returned if the gamesXusers record is added, array with ['status'] 400 error message if the record is not added
	 */
	private function determineHostTypeAndAddGamesXUsersRecord() {
		$hostParticipantType = "";
		if(isset($this->params['hostParticipantType']) && !empty($this->params['hostParticipantType'])) {
			if($this->params['hostParticipantType'] == PARTICIPANT_TYPE_SPECTATOR) {
				$hostParticipantType = PARTICIPANT_TYPE_SPECTATOR;
			} else {
				$hostParticipantType = PARTICIPANT_TYPE_ACTIVE_PLAYER;
			}
		} else {
			$hostParticipantType = PARTICIPANT_TYPE_ACTIVE_PLAYER;
		}
		
		$addGamesXUsersRecords = $this->addGamesXUsersRecordForHostAndParticipantType($this->id, $this->claimUserID, $hostParticipantType);
		if($addGamesXUsersRecords == false){
			http_response_code(400);
			$errorMsg = "Oops! Something went wrong adding you to the game. Please contact support at support@connectandconquer.com";
			$debugInfo = [
				"message" => "Error adding record to gamesXusers",
				"location" => __FILE__ . " line " . __LINE__,
			];
			$navigation = 'Menu';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		else{
			return true;
		}
		
	}
	/** 
	 * Description: Deactivate the user's host token if they used one to create the game
	 *
	 * @param 
	 * @return array|bool Returns TRUE if the host token is deactivated, array with ['status'] 400 error message if the host token is not deactivated
	 */
	private function handleHostTokenDeactivation($userAccessInfo) {
		$user = new Users($this->params, $this->authJWTstring);
		
		if($userAccessInfo['product'] == 'hostToken') {
			// Get the tokenID to deactivate from the list of active tokens
			$tokenToDeactivate = $user->getUserTokenDetailsForTokenTypeByUserID(HOST_TOKEN, $this->claimUserID);
			
			// Deactivate the token - only deactivates/makes changes in the gametoken table. not decrementing transactionxProductRecord 
			// quantity as it reflects the total purchased in the transaction receipt
			$updateToken = $user->deactivateGameTokenRecord($tokenToDeactivate[0]['gameTokenID']);
			
			if($updateToken == false) {
				http_response_code(400);
				$errorMsg = "Oops! Something went wrong updating your game token. Please contact support at support@connectandconquer.com";
				$navigation = 'Menu';
				$debugInfo = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				
				return $response;
			}
			else {
				return true;
			}
		}
		else{
			http_response_code(400);
			$errorMsg = "Oops! Something went wrong updating your game token. Please contact support at support@connectandconquer.com";
			$navigation = 'Menu';
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			
			return $response;

		}
	}
	/*
	 * Description: Validate the game start date time and set the gameStartDateTime variable
	 *
	 * @return string|array Returns a DateTime string for a valid startDateTime,
	 *                      'Unscheduled' if unset or no error and startTimeString is empty,
	 *                      or an array with ['status'] 400 error message if the game start time is in the past.
	 */

	public function validateAndSetGameStartDateTime(){
		$verifiedGameStartDateTime = $this->verifyGameStartDateTimeInFuture();
	
		if(isset($verifiedGameStartDateTime['status']) && $verifiedGameStartDateTime['status'] == 400) {
			return $verifiedGameStartDateTime;
		}
		else if($verifiedGameStartDateTime == 'unscheduled'){
			return null;
		}
		else {
			return $verifiedGameStartDateTime; //string of valid date time
		}
	}
	
	
	

}
