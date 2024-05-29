<?php
/**
 ╔╦╗┬ ┬┬┌─┐  ┬┌─┐  ┌─┐  ╔═╗┬  ┌─┐┌─┐┌─┐  ╔╦╗┌─┐┌┬┐┌─┐┬  ┌─┐┌┬┐┌─┐
  ║ ├─┤│└─┐  │└─┐  ├─┤  ║  │  ├─┤└─┐└─┐   ║ ├┤ │││├─┘│  ├─┤ │ ├┤
  ╩ ┴ ┴┴└─┘  ┴└─┘  ┴ ┴  ╚═╝┴─┘┴ ┴└─┘└─┘   ╩ └─┘┴ ┴┴  ┴─┘┴ ┴ ┴ └─┘
 ╦═╗┌─┐┌─┐┬  ┌─┐┌─┐┌─┐
 ╠╦╝├┤ ├─┘│  ├─┤│  ├┤
 ╩╚═└─┘┴  ┴─┘┴ ┴└─┘└─┘
 ╦ ╦┌─┐┌─┐┌─┐┬─┐┌─┐┌─┐┌─┐┌─┐  ╔╗ ┬  ┌─┐┌┐┌┬┌─
 ║ ║├─┘├─┘├┤ ├┬┘│  ├─┤└─┐├┤   ╠╩╗│  ├─┤│││├┴┐
 ╚═╝┴  ┴  └─┘┴└─└─┘┴ ┴└─┘└─┘  ╚═╝┴─┘┴ ┴┘└┘┴ ┴
 ┌─┐┌┐┌┌┬┐
 ├─┤│││ ││
 ┴ ┴┘└┘─┴┘
 ╦  ┌─┐┬ ┬┌─┐┬─┐┌─┐┌─┐┌─┐┌─┐  ┌┐ ┬  ┌─┐┌┐┌┬┌─
 ║  │ ││││├┤ ├┬┘│  ├─┤└─┐├┤   ├┴┐│  ├─┤│││├┴┐
 ╩═╝└─┘└┴┘└─┘┴└─└─┘┴ ┴└─┘└─┘  └─┘┴─┘┴ ┴┘└┘┴ ┴
 1st Blank
 2nd blank
 
 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayBlanks() method.
 |
 |		To create an endpoint:
 |		1. Add class name to config.php file
 |		2. Check with another endpoint to verify passing authJWTstring correctly 
 |			to both class and parent constructors
 |		3. Add permissions for the class into the config.php file (if needed)
 | 	    4. Add code to check for those permissions in the constructor
 |		
 |--------------------------------------------------------------------------
 */
class Blanks extends SnapshotDataClass{
	
	function __construct($params) {
		parent::__construct($params);
		
		//Overwrite access permissions for this class methods
		// $this->accessClientListArray['POST']['admin'] = true;
        // $this->accessClientListArray['POST']['all'] = true;
        // $this->accessClientListArray['POST']['user'] = true;
        // $this->accessClientListArray['GET']['admin'] = true;
        //     $this->accessClientListArray['GET']['all'] = true;
        //     $this->accessClientListArray['GET']['user'] = true;
        //     $this->accessClientListArray['PUT']['admin'] = true;
        //     $this->accessClientListArray['PUT']['all'] = true;
        //     $this->accessClientListArray['PUT']['user'] = true;
        //     $this->accessClientListArray['DELETE']['admin'] = true;
        //     $this->accessClientListArray['DELETE']['all'] = true;
        //     $this->accessClientListArray['DELETE']['user'] = true;

		$this->db->requiredFields = array(
				'blanksName',
				'',
				'',
				'other',
		);
		$this->db->fieldsArray = array(
				'id'				=> @$this->params['id'],
				'blanksName'				=> @$this->params['blanksName'],
				'phoneNumber'		=> @$this->params['phoneNumber'],
				'passPhrase'		=> @$this->params['passPhrase'],
				'other'				=> @$this->params['other'],
				
				'active'			=> @$this->params['active'],
		);
		$this->id = isset($this->params['id']) ? $this->params['id'] : null;
		$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
			return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
		});
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
	function displayBlanks(){
		/*/
		|--------------------------------------------------------------------------
		|         Access is limited to (Fill in the blank) Only
		|--------------------------------------------------------------------------
		*/
		$return =array();
		if (@$this->id > 0){
			$response = $this->db->getRecord($this->id);
			$this->availableItems = $this->db->availableItems;
			//  return $this->db->getRecord($this->id);
		}
		else {
			$this->processWhere($this->params);
			$response = $this->db->select($this->db->tableJoins,$this->db->where,$this->db->whereGreater,$this->db->whereLess);
		}
		$results = $response['data'];
		$this->mainQuery		 	= $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;
		/*/
		 |--------------------------------------------------------------------------
		 |Add other details as needed
		 |--------------------------------------------------------------------------
		 */
// 		foreach ($results AS $keyIndex=>$row){
// 			$listID = $row['id'];
// 			$results[$keyIndex]['admins']	= $this->getAdminsForLists($listID);
// 			$results[$keyIndex]['contacts']	= $this->getContactsForLists($listID);
// 		}
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
	function addBlanks() {
		/*/
		|--------------------------------------------------------------------------
		|         Access is limited to (Fill in the blank) Only
		|--------------------------------------------------------------------------
		*/
		if (!empty($this->id)) {
			http_response_code(400);
			$errorMsg = " Blank ID must not be set when adding Blan";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		$this->params['active'] = 1;
// 		$passPhase = $this->params['blankPassPhrase'];
// 		$this->db->fieldsArray['blankPassPhrase'] = password_hash($passPhase, PASSWORD_DEFAULT);
		$addResult = $this->db->addRecord();
		if (!empty($addResult['id']) && $addResult['id'] > 0) {
			$this->id = $addResult['id'];
			return $this->displayBlanks();
		} else {
			http_response_code(400);
			$errorMsg = "Oops! Something went wrong, your Blank was not added.";
			$debugInfo = array("details" => $addResult['message'] . __LINE__ . " " . __FILE__);
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
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
	function updateBlanks(){
		/*/
		|--------------------------------------------------------------------------
		|         Access is limited to (Fill in the blank) Only
		|--------------------------------------------------------------------------
		*/
		if (!empty($this->id)) {
			// Update the record with the given ID
			$updated = $this->db->updateRecord($this->db->table, $this->id);

			if ($updated['status']!=='error') {
				// If the update is successful, return the displayed Blanks
				return $this->displayBlanks();
			} else {
				// If there is an error during update, return an error message
				$errorMsg = "Oops! Something went wrong, your Blank was not updated.";
				$debugInfo = array("details" => $updated['message'] . __LINE__ . " " . __FILE__);
				$navigation = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo,  $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
		} else {
			// If no valid ID is given, return an error message
			$errorMsg = "Oops! No valid ID was given: $this->id";
			$navigation = null;
			$debugInfo = null;
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
	 I rarely delete records, so I deactive them instead.
	 You may want to delete them so adjust as needed.
	 */
	function removeBlanks(){
		/*/
		|--------------------------------------------------------------------------
		|         Access is limited to (Fill in the blank) Only
		|--------------------------------------------------------------------------
		*/
		if (is_array($this->id)){
			$returnArray = array();
			foreach ($this->id AS $recordID){
				$returnArray[] = $this->db->deactivate($recordID);
			}
		}
		else {
			$returnArray = array($this->db->deactivate($this->id));
		}
		return $this->prepareReturn($returnArray);
	}
	
}