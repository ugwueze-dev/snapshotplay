<?php
class Snapshots extends SnapshotDataClass{

	
	
	function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring); //passing false because we are not enforcing permissions here

		$this->accessClientListArray['POST']['admin']   = true;
		//$this->accessClientListArray['POST']['all']     = true;
		$this->accessClientListArray['POST']['user']    = true;
		//$this->accessClientListArray['GET']['admin']    = true;
		// $this->accessClientListArray['GET']['all']      = true;
		$this->accessClientListArray['GET']['user']     = true;
		//$this->accessClientListArray['PUT']['admin']    = true;
		// $this->accessClientListArray['PUT']['all']      = true;
		$this->accessClientListArray['PUT']['user']     = true;
		//$this->accessClientListArray['DELETE']['admin'] = true; //manually do to avoid any accidental deactivations
		// $this->accessClientListArray['DELETE']['all']   = true;
		$this->accessClientListArray['DELETE']['user']  = true;

		$isAccessGranted = $this->httpMethodAccessCheck(); //add to all endpoint constructors - set any changes above it
		//var_dump($isAccessGranted);
		if($isAccessGranted == false){
			http_response_code(404);
			$debugInfo = "The httpMethodAccessCheck failed. ";
			$errorMsg = "Oops! You don't have access to this info. Please contact support at support@connectandconquer.com for assistance. Code: " . __LINE__;
			$navigation = "Login";//TODO check this
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			die(json_encode($response));
		}

		$this->db->requiredFields = array(
			 
			// 'snapshotLocation', //This is null when we upload the original file
			'snapshotDetails', 
			'snapshotTypeID'
		);
		$this->db->fieldsArray = array(
			'id'               => @$this->params['id'],
			'userID'           => @$this->params['userID'],
			'assignmentID'     => @$this->params['assignmentID'],
			'snapshotLocation'  => @$this->params['snapshotLocation'],
			'snapshotDateTime'  => @$this->params['snapshotDateTime'],
			'snapshotTypeID'    => @$this->params['snapshotTypeID'],
			// 'fileURI'         => @$this->params['fileURI'],
			'notes'            => @$this->params['notes'],
			'snapshotDetails'   => @$this->params['snapshotDetails']
		);

		if(!empty($_FILES)){
			$this->tempFile = $_FILES['snapshot'];
		}
		$this->id = isset($this->params['id']) ? $this->params['id'] : null;
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
	function displaySnapshots(){ // TODO returns gameSnapshots by date order descending
		$results = array();
		if(isset($this->gameID) && !empty($this->gameID)) {
			
			$results = $this->getSnapshotDetailsSortedByGameID($this->claimUserID, $this->gameID, 'gameID');
		}
		else if(isset($this->gameCode) && !empty($this->gameCode)){
			
			$gameID = $this->getGameIDFromGameCode($this->gameCode);
			$results = $this->getSnapshotDetailsSortedByGameID($this->claimUserID, $gameID, 'gameID');
		}
		else {
			
			$results = $this->getSnapshotDetailsSortedByGameID($this->claimUserID, null, 'all'); 
		}
	
		//$results = $this->getAllSnapshotDetailsSortedByGameID($this->claimUserID);
		if(isset($results['error']) && $results['status'] == 403){
			http_response_code(403);
			$errorMsg = "Oops! You do not have permission to view snapshots for this game.";
			$debugInfo = array("details" => "User $this->claimUserID does not have permission to view snapshots for this game.");	
			$navigation = null;
			$response = $this->createApiResponse(403, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
		elseif(isset($results['error']) && $results['status'] == 400){
			//HACK - the formatting of this is only to match what is currently returned when we have snapshots so standard for front end

			$firstFileData = $this->generateFileData("https://api.snapshotplay.com/v2/media/system/noImageYet1.png");
			$secondFileData = $this->generateFileData("https://api.snapshotplaycom/v2/media/system/noImageYet2.png");
			
			$firstFileObject = (object) $firstFileData;
			$secondFileObject = (object) $secondFileData;
			$gameSnapshotsDetailsArray = 
			$data = array(
				$firstFileObject,
				$secondFileObject
	   		);
	   //return $this->prepareReturn($data);
			$response = $this->createApiResponse(200, $data, null, null, null); //using this to match what is currently returned when we have snapshots so standard for front end
			
			return ($response);
		}
		else{
			
			return $this->prepareReturn($results);
		}
	}
	/**
	  _____   ____   _____ _______
	 |  __ \ / __ \ / ____|__   __|
	 | |__) | |  | | (___    | |
	 |  ___/| |  | |\___ \   | |
	 | |    | |__| |____) |  | |
	 |_|     \____/|_____/   |_|

	 Posting does NOT create a snapshot record.  It uploads the file and returns the fileURI.
	 
	 */
	function addSnapshots() {

		// $isAdmin = $this->isAdmin();
		// if($isAdmin == true){
		// 	$errorMsg = "This functionality has not been implemented for Admins.";
		// 	$debugInfo = [
		// 		"message" => "This functionality has not been implemented for Admins.",
		// 		"location" => __FILE__ . " line " . __LINE__,
		// 	]; 
		// 	$response = $this->createApiResponse(400, null, "This functionality has not been implemented for Admins.", $debugInfo, null); //can add $debugInfo as 4th param
		// 	return $response;
		// }
		//Doesn't seem to be a need to check if admin, only uploading a file.  So either admin or user, not all
		if (!empty($this->id)) {
			http_response_code(400);
			$errorMsg = "Oops! SnapshotID must not be set when adding Snapshot";
			$debugInfo = array("details" => "SnapshotID must not be set when adding Snapshot");	
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		//MAKE SURE WE GOT A FILE
		if($this->tempFile['error'] > 0 || empty($this->tempFile)){
			http_response_code(400);
			$errorMsg = "Oops! There was an error with the file upload. Please try again.";
			$debugInfo = array("details" => "Error code: " . $this->tempFile['error']);
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		
		//MOVE FILE
		$fileName = $this->generateFileName($this->claimUserID);
		$url = $this->generateURL($fileName); 
		$fileMoved = $this->moveFileToCorrectLocation($fileName, $this->claimUserID);
		
		if($fileMoved !== true){
			http_response_code(400);
			$errorMsg = "Oops! There was an error moving the file to the correct location. Please try again.";
			$navigation = null;
			$debugInfo = $fileMoved;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}

		//ADD A SNAPSHOT RECORD 
		$snapshotID = $this->addNewSnapshots($url); //return new recordID? 
		
		if(isset($snapshotID['error'])){
			http_response_code(400);
			$errorMsg = "Oops! There was an error in adding the snapshot record. Please try again.";
			$debugInfo = array("details" => $snapshotID['error']);
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}

		$returnArray = array(
			'status' => 'success',
			'message' => "File uploaded successfully, moved and snapshot record added",
			'snapshotID' => $snapshotID
			
		);
		
		return $this->prepareReturn($returnArray);
	}
	
	/**
	  _____  _    _ _______
	 |  __ \| |  | |__   __|
	 | |__) | |  | |  | |
	 |  ___/| |  | |  | |
	 | |    | |__| |  | |
	 |_|     \____/   |_|
	 */
	
	
	/**
	  _____  ______ _      ______ _______ ______
	 |  __ \|  ____| |    |  ____|__   __|  ____|
	 | |  | | |__  | |    | |__     | |  | |__
	 | |  | |  __| | |    |  __|    | |  |  __|
	 | |__| | |____| |____| |____   | |  | |____
	 |_____/|______|______|______|  |_|  |______|
	 I rarely delete records, so I deactive them instead.
	 You may want to delete them so adjust as needed.
	 */
	function removeSnapshots(){

		$snapshot = $this->getSnapshotRecord($this->params['id']);
		$hasPermission = $this->checkForPermissionToDeleteSnapshot($snapshot);
		if($hasPermission == false){
			http_response_code(403);
			$errorMsg = "Oops! You do not have permission to delete this snapshot. Only hosts of associated games and owners of snapshots are allowed to delete.";
			$debugInfo = array("message" => "User $this->claimUserID does not have permission to delete this snapshot.", "details" => $snapshot);
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		
		if (is_array($this->id)){
			//$returnArray = array();
			foreach ($this->id AS $recordID){
				$returnArray= $this->db->deactivate($recordID);
			}
		}
		else {
			$returnArray = $this->db->deactivate($this->id);
		}
		return $this->prepareReturn($returnArray);
	}
	

	protected function checkForPermissionToDeleteSnapshot($snapshotRecord){

		$snapshotOwnerID = $snapshotRecord['userID'];
		$userXTargetID = $snapshotRecord['usersXtargetsID'];
		$gameID = $this->getGameIDFromUserByTargetID($userXTargetID);
		$hostUserID = $this->getHostUserID($gameID);

		if($snapshotOwnerID == $this->claimUserID || $hostUserID == $this->claimUserID){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Description: 
	 *
	 * @param 
	 * @return 
	 */
	protected function getGameIDFromUserByTargetID($userXTargetID){
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'id' => $userXTargetID,
			'active' => 1
		);
		$columnsArray = array(
			'gameID'
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
			return $result['data'][0]['gameID'];
		} else {
			return false;
		}

	}
		
	public function getAllGameIDsForUser($userID){
		$tablesArray = array('gamesXusers');
		$columnsArray = array('gameID');
		$whereArray = array(
			'userID' => $userID
		);
		$whereMoreString = 'GROUP BY gameID';
		$results = $this->db->select($tablesArray, 
										$whereArray, 
										array(), 
										array(), 
										$columnsArray,
										$whereMoreString,//$whereMoreString
										false,//$groupByColumn
										false,//$justDataBoolean
										null,//$numberPerPage
										null,//$pageNumber
										'gameID', 'DESC');
									
		if($results['status'] == 'warning' || $results['status'] == 'error'){
			return false;
		}
		else{
			foreach ($results['data'] as $value) {
				$gameIDs[] = $value['gameID'];
				
			}
	
			return $gameIDs;
		}
	}
	
	/**
	 * Description: GetSnapshotDetailsSortedByGameID
	 *
	 * @param int $userID, $gameID, $returnGameIDs (either 'all' or 'gameID' ), $numberOfSnapshotsPerGame (either 'all' or a number of snapshots per game to return
	 * @return mixed $gameCode on success or false on failure
	 */
	public function getSnapshotDetailsSortedByGameID($userID, $gameID, $returnGameIDs='all',$numberOfSnapshotsPerGame = 'all'){
		//get total number of games for the user
		// multiply number of games by $numberOfSnapshotsPerGame
		// set $limit to that number 
		$limit = null;
		//$gameIDs = $this->getAllGameIDsForUser($userID);
		$allGameIDs = $this->getAllGameIDsForUser($userID);
		foreach($allGameIDs as $key => $gameIDValue){
			if($gameIDValue == 1){
				unset($allGameIDs[$key]); //we don't want to grab snapshots from the demo game
			}
		}
		
		if($allGameIDs == false){
			return array('error' => 'No games found for the user', 'status' => 400);
		}
		if($returnGameIDs == 'gameID' && in_array($gameID, $allGameIDs) == false){
			return array('error' => 'The gameID provided is not associated with the user', 'status' => 403);
		}
		
		$totalGames = count($allGameIDs);
		$snapshotDetailsByGame = array();
		$tablesArray = $this->getCommonSnapshotTablesArray();
		$columnsArray = $this->getCommonSnapshotColumnsArray();
		$whereArray = $this->getCommonSnapshotWhereArray();
	
		
		//print_r($whereArray);die('320');
		if($returnGameIDs == 'all'){ 
			$totalGames = count($this->getAllGameIDsForUser($userID)); 
			if($totalGames == false){ //shouldn't reach here if false, but just in case
				return array('error' => 'No games found for the user', 'status' => 400);
			}
			$whereArray['usersXtargets.gameID'] = array_map('strval', $allGameIDs); //This code will convert each element in the $gameIDs array to a string using strval() and then assign the resulting array directly to $whereArray['usersXtargets.gameid'].
		}
		elseif($returnGameIDs == 'gameID'){
		
			$gameKey = 'usersXtargets.gameID';
			$whereArray[$gameKey] = $gameID;
		}
		else {
			
			$gameKey = "$returnGameIDs";
			$whereArray[$gameKey] ='`snapshots`.`userID`';
		}
		
		if($numberOfSnapshotsPerGame != 'all'){
			$limit = $totalGames * $numberOfSnapshotsPerGame;
		}
		else {
			$limit = null;
		}
		$whereGreaterArray = [];
		$whereLessArray = [];
		$response = $this->db->select($tablesArray, 
										$whereArray, $whereGreaterArray, 
										$whereLessArray, $columnsArray,
										'',//$whereMoreString
										false,//$groupByColumn
										false,//$justDataBoolean
										$limit,//$numberPerPage
										0,//$pageNumber
										'id', 'DESC');

			 //print_r($numberOfSnapshotsPerGame);die('360'); //??? No data found - what results are expected? 
		foreach ($response['data'] as $key => $value) {
			
			unset($value['availableItems']);
			
			/*/
 |--------------------------------------------------------------------------
 |         Move the snapshotID to be the first element in the $value array by 
 |         creating a new key-value pair and merging it with the original array.
 |--------------------------------------------------------------------------
 */
			
			$snapshotID = $value['id'];
			$value['snapshotID'] = $snapshotID;
			$value = array('snapshotID' => $snapshotID) + $value;
			$gameCode = $value['gameCode'];
			//$snapshotDetailsByGame = $value;
			//$snapshotDetailsByGame[$gameCode][] = $value; //testing for multiple game returns
			$snapshotDetailsByGame[] = $value; //testing for multiple game returns
			
		}

		$this->mainQuery = $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;
		//print_r($this->mainQuery);die('375');
		if(empty($snapshotDetailsByGame)){
			return array('error' => 'No snapshots found for the user', 'status' => 400);
		}
		else{
			return $snapshotDetailsByGame;
		}
		//return $snapshotDetailsByGame;
	}
	/**
	 * Description: Get all the snapshots for a user
	 *
	 * @param int $userID
	 * @return mixed $results - array of snapshots or false if no snapshots found
	 */
	public function getSnapshotDetailsByUserID($userID){
				// return $gameID;
				$snapshotDetails = array();
				//pull user data from db by id
				$tables = array("snapshots, users, usersXtargets, snapshotTypes, targetTypes, games, targets, targetStatuses, gameTypes");
				$columnsArray = array(
					"snapshots.id",
					"snapshots.userID",
					"users.fName",
					"users.lName",
					"snapshots.snapshotLocation",
					"snapshots.snapshotDateTime",
					"snapshots.fileURI",
					"snapshots.notes",
					"snapshots.snapshotDetails",
					"snapshotTypes.snapshotTypeName",
					"snapshotTypes.description",
					"usersXtargets.startDateTime",
					"usersXtargets.closedDateTime",
					"targetTypes.targetTypeName",
					"targetTypes.targetTypeDescription",
					"targets.targetName",
					"targets.targetSnapshotURI",
					"targets.targetDescription",
					"games.gameName",
					"games.gameCode",
					"games.gameStatusID",
					"games.gameStartDateTime",
					"games.hostUserID",
					"games.description",
					"gameTypes.gameTypeName",
					"gameTypes.description",
					"gameTypes.rules",
					"gameTypes.limits",
					"gameTypes.logic",
					"targetStatuses.targetStatusName",
					"targetStatuses.targetStatusDescription",
					"snapshots.active",
					"snapshots.ts"
				);
				$where = array(
					"snapshots.userID" => $userID,
					"snapshots.usersXtargetsID" => "`usersXtargets`.`id`",
					"snapshots.snapshotTypeID" => "`snapshotTypes`.`id`",
					"users.id" => "`snapshots`.`userID`",
					//"assignments.userID" => "`snapshots`.`userID`", //this is causing 0 results not sure why yet
					// "usersXtargets.targetTypeID" => "`targetTypes`.`id`",
					"usersXtargets.targetStatusID" => "`targetStatuses`.`id`",
					"usersXtargets.gameID" => "`games`.`id`",
					"usersXtargets.targetID" => "`targets`.`id`",
					"games.gameTypeID" => "`gameTypes`.`id`",
					"users.active" => 1,
					//"usersXtargets.active" => 1,
					"snapshotTypes.active" => 1,
					"targetTypes.active" => 1,
					"games.active" => 1,
					"targets.active" => 1,
					"gameTypes.active" => 1
				);
				$whereGreater = [];
				$whereLess = [];
				$whereMore = '';
				$response = $this->db->select($tables, $where, $whereGreater, $whereLess, $columnsArray, $whereMore, false, false, null, null, 'id', 'DESC');
				
				$results = $response['data'];
				$this->mainQuery = $this->db->fullQuery;
				$this->availableItems = $this->db->availableItems;
				
				//print_r($response);die('363');
				 if (empty($results)) {
					return false;
				}
				else {
					return $results;
				}
				
			}
	

	
	
	// function to get the common columns for things related to snapshots
	function getCommonSnapshotColumnsArray(){
		return  array(
			"snapshots.id",	"snapshots.userID",	"users.fName",	"users.lName",	
			"snapshots.snapshotLocation",		"snapshots.snapshotDateTime",	
			"snapshots.fileURI",	
			//"snapshots.notes",	"snapshots.snapshotDetails",	
			//"snapshotTypes.snapshotTypeName",	"snapshotTypes.description",	
			"usersXtargets.startDateTime",		"usersXtargets.closedDateTime",	
			"targetTypes.targetTypeName",	"targetTypes.targetTypedescription",	
			//"targets.targetName",	"targets.targetSnapshotURI",	
			"targets.targetDescription",		
			"games.id as gameID","games.gameName",	"games.gameCode",	
			"games.gameStatusID",		"games.gameStartDateTime",	"games.hostUserID",	
			//"games.description",	"gameTypes.gameTypeName",	"gameTypes.description",	
			//"gameTypes.rules",	"gameTypes.limits",		"gameTypes.logic",	
			"targetStatuses.targetStatusName",	"targetStatuses.targetStatusDescription",	
			//"snapshots.active",	"snapshots.ts"
		);
	}
	// function to get the common tables for things related to snapshots
	function getCommonSnapshotTablesArray(){
		return array(
			'snapshots', 
			'users', 
			'usersXtargets', 
			'snapshotTypes', 
			'targetTypes', 
			'games', 
			'targets', 
			'targetStatuses', 
			'gameTypes'
		);
	}
	// function to get the common where clauses for things related to snapshots
	function getCommonSnapshotWhereArray(){
		return array(
			
			"snapshots.usersXtargetsID" 	=> "`usersXtargets`.`id`",
			"snapshots.snapshotTypeID" 		=> "`snapshotTypes`.`id`",
			"`users`.`id`" 					=> "`snapshots`.`userID`",
			"`usersXtargets`.`userID`" 		=> "`snapshots`.`userID`",
			"`games`.`id`" 					=> "`usersXtargets`.`gameID`",
			"usersXtargets.targetID" 		=> "`targets`.`id`",
			"targetStatuses.id" 		=> "`usersXtargets`.`targetStatusID`",
			"games.gameTypeID" 				=> "`gameTypes`.`id`",
			"usersXtargets.targetStatusID" 	=> 3,
			"targetTypes.id" 				=> "`targets`.`targetTypeID`",
			"users.active" => 1, 
			//"usersXtargets.active" => 1, 
			"snapshotTypes.active" => 1,
			"targetTypes.active" => 1, 
			"games.active" => 1, 
			"targets.active" => 1,
			"gameTypes.active" => 1
		);
	}
	/**
	 * Description: Get the total number of games for a user
	 *
	 * @param int $userID
	 * @return mixed $response['id] an array of all the gameIDs for the user, false on failure or no games found
	 */
	function getTotalGameIDsForUser($userID){//using the assignment table
		/*/
 |--------------------------------------------------------------------------
 |         SELECT * FROM `api.snapshotplay.com`.gamesXusers
WHERE userID = 1
GROUP BY gameID
 |--------------------------------------------------------------------------
 */
		$tablesArray = array('gamesXusers');
		$columnsArray = array('COUNT(DISTINCT gameID) AS totalGames','gamesXusers.gameID');
		$whereArray = array('gamesXusers.userID' => $userID);
		// $whereArray['snapshots.userID'] = $userID;
		$whereGreaterArray = [];
		$whereLessArray = [];
		$whereMoreString = 'GROUP BY gameID';
		$groupByColumn = true;
		$justDataBoolean = true;
		$numberPerPage = null;
		$pageNumber = null;
		$orderByColumn = 'id';
		$orderByDirection = 'DESC';
		$response = $this->db->select($tablesArray, 
										$whereArray, 
										$whereGreaterArray, 
										$whereLessArray, 
										$columnsArray,
										$whereMoreString,//$whereMoreString
										$groupByColumn,//$groupByColumn
										$justDataBoolean,//$justDataBoolean
										//$numberPerPage,//$numberPerPage
										//$pageNumber,//$pageNumber
										//$orderByColumn, $orderByDirection
									);
		$this->mainQuery = $this->db->fullQuery;
		if(!isset($response['gameID'] ) || empty($response['gameID'])){
			return false;
		}
		else {
	    	return $response['gameID']; //total Games is returning 1 for each row, not the total number of games 
		}
	}

	
		/**
		 * Description: Get a snapshot record by ID
		 *
		 * @param int $recordID
		 * @return mixed $resultArray with record on success or false on failure
		 */
		protected function getSnapshotRecord($recordID){
			$tables = array(
			'snapshots'
			);
			$where = array(
				'id' => $recordID,
				'active'=> 1
			);
			
			// Call your database select method with the parameters
			$result = $this->db->select(
				$tables,
				$where,
				array(),     // $whereGreater
				array(),     // $whereLess
				array(), // $columnsArray
				'',          // $whereMore
				false
			);
			
			if ($result['status'] != 'warning' && $result['status'] != 'error') {
				unset($result['data'][0]['availableItems']);
				return $result['data'][0];
			} 
			else {
				return false;
			}
			
		}
	
		protected function generateFileData($fileURI) {
			return array(
				"snapshotID" => 0,
				"id" => 0,
				"userID" => null,
				"fName" => null,
				"lName" => null,
				"snapshotLocation" => null,
				"snapshotDateTime" => null,
				"fileURI" => $fileURI,
				"startDateTime" => null,
				"closedDateTime" => null,
				"targetTypeName" => null,
				"targetTypedescription" => null,
				"targetDescription" => null,
				"gameName" => null,
				"gameCode" => null,
				"gameStatusID" => null,
				"gameStartDateTime" => null,
				"hostUserID" => null,
				"targetStatusName" => null,
				"targetStatusDescription" => null,
			);
		}
		protected function generateURI($userID){
			$filePath = $this->mediaPath; //NOTE at some point we had talked about putting userID after, but since it is included in the filename as an alphanumeric, not sure if we need it here. 
			
			$fileName = $this->generateFileName($this->claimUserID);
			$url = $this->generateURL($fileName); 
			return $url;
		}
	
		protected function moveFileToCorrectLocation($fileName, $userID){
			$alphaNumericUserID = $this->alphaID($userID);
			$storageFilePath = $this->createStorageDirectoryIfNeeded($alphaNumericUserID);
			$moved = move_uploaded_file($this->tempFile['tmp_name'], $storageFilePath . '/'.$fileName);

			if (!$moved) {
				return array('error' => __LINE__ . ": Could not move file to $storageFilePath");
			}
			else{
				
				return true;
			}
			
		}
		protected function getAllSnapshotDetailsSortedByGameID($userID){
			$tables = array(
				'snapshots', 
				'users', 
				'usersXtargets', 
				'targetTypes', 
				'games', 
				'targets', 
				'targetStatuses', 
				'gameTypes',
				'gameStatuses'
			);
			$whereArray = array(
				'snapshots.usersXtargetsID' => '`usersXtargets`.`id`',
				'usersXtargets.gameID' => '`games`.`id`',
				'snapshots.userID' => '`users`.`id`',
				'usersXtargets.targetID' => '`targets`.`id`',
				'targets.targetTypeID' => '`targetTypes`.`id`',
				'games.gameStatusID' => '`gameStatuses`.`id`',
				'usersXtargets.targetStatusID' => '`targetStatuses`.`id`',
				'usersXtargets.userID' => $userID
			);
			$whereMoreString = 'GROUP BY `games`.`id`, `snapshots`.`id`';
			$columnsArray = array(
				'snapshots.id as snapshotID', 
				'snapshots.userID', 
				'users.fName', 
				'users.lName', 
				'snapshots.snapshotLocation', 
				'snapshots.snapshotDateTime',
				'snapshots.fileURI',
				'targetTypes.targetTypeName', 
				'targetTypes.targetTypeDescription',
				'targets.targetName',
			    'targets.targetSnapshotURI',
				'targets.targetDescription',
				'games.gameName',
				'games.gameCode',
				'games.gameStatusID',
				'games.gameStartDateTime',
				'games.hostUserID',
			    'gameTypes.gameTypeName',
			    'gameTypes.description',
				'gameTypes.rules',
				'gameTypes.limits',
				'gameTypes.logic',
				'targetStatuses.targetStatusName',
				'targetStatuses.targetStatusDescription',
				'snapshots.active',
				'snapshots.ts'
			);
			 $results = $this->db->select($tables, 
											$whereArray, 
											array(), 
											array(), 
											$columnsArray,
											$whereMoreString,//$whereMoreString
											false,//$groupByColumn
											false,//$justDataBoolean
											null,//$numberPerPage
											null,//$pageNumber
											'snapshots.id', 'DESC');
								
			if($results['status'] == 'warning' || $results['status'] == 'error'){
				return false;
			}
			else{
				return $results['data'];
			}
		}
}