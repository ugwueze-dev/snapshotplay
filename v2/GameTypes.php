<?php
/**
   _____                   _______                    
  / ____|                 |__   __|                   
 | |  __  __ _ _ __ ___   ___| |_   _ _ __   ___  ___ 
 | | |_ |/ _` | '_ ` _ \ / _ \ | | | | '_ \ / _ \/ __|
 | |__| | (_| | | | | | |  __/ | |_| | |_) |  __/\__ \
  \_____|\__,_|_| |_| |_|\___|_|\__, | .__/ \___||___/
								 __/ | |              
								|___/|_|     
 */
class GameTypes extends SnapshotDataClass
{
	
	function __construct($params, $authJWTstring = null ) {
		parent::__construct($params, $authJWTstring, false); //passing false because we are not enforcing permissions here

		//$this->accessClientListArray['POST']['admin']   = true; //ANY ITEMS ADDED HERE WILL NOT WORK TILL IMPLEMENTED IN THE LOGIC
		//$this->accessClientListArray['POST']['all']     = true;
		//$this->accessClientListArray['POST']['user']    = true;
		$this->accessClientListArray['GET']['admin']    = true;
		$this->accessClientListArray['GET']['all']      = true;
		$this->accessClientListArray['GET']['user']     = true;
		//$this->accessClientListArray['PUT']['admin']    = true; //DO NOT ALLOW - IT WILL BREAK PROGRAMMING CODE
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

		$this->db->requiredFields = array(
				'gameTypeName',
				'description', 
				'rules', 
				'limits', 
				'logic'
		);

		// Set up the initial values for database fields based on provided parameters.
		$this->db->fieldsArray = array(
				'gameTypeName' => @$this->params['gameTypeName'],
				'description' => @$this->params['description'],
				'rules' => @$this->params['rules'],
				'limits' => @$this->params['limits'],
				'logic' => @$this->params['logic'],
				'active' => 1,
				'ts' => @$this->params['ts']
		);

		$this->id = isset($this->params['id']) ? $this->params['id'] : null;
		//casting data to correct type
		$this->json[] ='rules';
		$this->json[] ='limits';
		$this->json[] ='logic';
		$this->json[] ='imageIDs';

		// Filter the fieldsArray by removing null, false, and empty values.
		$this->db->fieldsArray = array_filter($this->db->fieldsArray, function ($value) {
			return ($value !== null && $value !== false && $value !== '');
		});
;
		
		
	}
	
	/**
	  _____ ______ _______
	 / ____|  ____|__   __|
	 | |  __| |__     | |
	 | | |_ |  __|    | |
	 | |__| | |____   | |
	  \_____|______|  |_|
	 */
	function displayGameTypes(){
		
		
		if (@$this->id > 0) {
            $response = $this->db->getRecord($this->id);
            $this->availableItems = $this->db->availableItems;
			
		}
		else {
			$this->processWhere($this->params);

			// Perform a database SELECT query using various filters.
			$response = $this->db->select(
				$this->db->tableJoins,
				$this->db->where,
				$this->db->whereGreater,
				$this->db->whereLess
			);
		}
		if(!isset($response['data'])){
			return array('error'=>__LINE__.": $response[message]");
		}
		$results = $response['data'];

		// Store the main query and available items in the class properties.
		$this->mainQuery = $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;


		/*
		|--------------------------------------------------------------------------
		| Add other details as needed
		|--------------------------------------------------------------------------
		*/
		// foreach ($results as $keyIndex => $row) {
		//     $listID = $row['id'];
		//     $results[$keyIndex]['admins'] = $this->getAdminsForLists($listID);
		//     $results[$keyIndex]['contacts'] = $this->getContactsForLists($listID);
		// }

		// Prepare and return the results.
		//return $this->prepareReturn($results);
		return $this->prepareReturn($results);
	}

	/**
	 _____   ____   _____ _______
	 |  __ \ / __ \ / ____|__   __|
	 | |__) | |  | | (___    | |
	 |  ___/| |  | |\___ \   | |
	 | |    | |__| |____) |  | |
	 |_|     \____/|_____/   |_|
	 */
// 	function addGameTypes(){
// 		if (!empty($this->id)){
// 			return array("error"=> __LINE__ . ": Blank ID must not be set when adding Blank");
// 		}
// 		$this->params['active'] = 1;
// // 		$passPhase = $this->params['blankPassPhrase'];
// // 		$this->db->fieldsArray['blankPassPhrase'] = password_hash($passPhase, PASSWORD_DEFAULT);
// 				$addResult = $this->db->addRecord();
// 		if(!empty($addResult['id']) && $addResult['id'] > 0){
// 			$this->id = $addResult['id'];
// 			return $this->displayGameTypes();
// 		}
// 		else {
// 			return array('error'=>__LINE__.": Could not add record", 'details'=>$addResult['message']);
// 		}
// 		if ($this->id >0){
// // 			if (!empty($this->params['lists'])){
// // 				$lists = $this->params['lists'];
// // 				$this->addListsToBlankID($lists,$this->id);
// // 			}
// 			return $this->displayGameTypes();
// 		}
// 		else {
// 			return array('error'=>__LINE__.": Could not add record");
// 		}
// 	}
	
	/**
	 _____  _    _ _______
	 |  __ \| |  | |__   __|
	 | |__) | |  | |  | |
	 |  ___/| |  | |  | |
	 | |    | |__| |  | |
	 |_|     \____/   |_|
	 */
	// function updateGameTypes(){
	// 	if (!empty($this->id)) {
	// 		// Update the record with the given ID
	// 		$updated = $this->db->updateRecord($this->db->table, $this->id);

	// 		if ($updated['status']!=='error') {
	// 			// If the update is successful, return the displayed GamesTypes
	// 			return $this->displayGameTypes();
	// 		} else {
	// 			// If there is an error during update, return an error message
	// 			return array('error' => __LINE__.": Could not update record", 'details' => $updated['message']);
	// 		}
	// 	} else {
	// 		// If no valid ID is given, return an error message
	// 		return array('error' => "no valid ID given: $this->id");
	// 	}
	// }
	
	/**
	 _____  ______ _      ______ _______ ______
	 |  __ \|  ____| |    |  ____|__   __|  ____|
	 | |  | | |__  | |    | |__     | |  | |__
	 | |  | |  __| | |    |  __|    | |  |  __|
	 | |__| | |____| |____| |____   | |  | |____
	 |_____/|______|______|______|  |_|  |______|
	 */
	// function removeGameTypes(){
	// 	if (is_array($this->id)){
	// 		$returnArray = array();
	// 		foreach ($this->id AS $recordID){
	// 			$returnArray[] = $this->db->deactivate($recordID);
	// 		}
	// 	}
	// 	else {
	// 		$returnArray = array($this->db->deactivate($this->id));
	// 	}
	// 	return $this->prepareReturn($returnArray);
	// }
	
	
}