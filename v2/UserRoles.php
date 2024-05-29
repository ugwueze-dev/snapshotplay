<?php
/**

  _    _  _____ ______ _____     _____   ____  _      ______  _____ 
 | |  | |/ ____|  ____|  __ \   |  __ \ / __ \| |    |  ____|/ ____|
 | |  | | (___ | |__  | |__) |  | |__) | |  | | |    | |__  | (___  
 | |  | |\___ \|  __| |  _  /   |  _  /| |  | | |    |  __|  \___ \ 
 | |__| |____) | |____| | \ \   | | \ \| |__| | |____| |____ ____) |
  \____/|_____/|______|_|  \_\  |_|  \_\\____/|______|______|_____/ 
                                                                    
                                                                    

 INTERNAL CLASS ONLY - NOT ENDPOINT

 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayRoles() method.
 |--------------------------------------------------------------------------
 */
class UserRoles extends SnapshotDataClass{
	
	function __construct($params ) {
		parent::__construct($params);

		//$this->accessClientListArray['POST']['admin']   = true; //DO NOT ALLOW WILL BREAK PROGRAMMING CODE
		//$this->accessClientListArray['POST']['all']     = true;
		//$this->accessClientListArray['POST']['user']    = true;
		$this->accessClientListArray['GET']['admin']    = true;
		// $this->accessClientListArray['GET']['all']      = true;
		//$this->accessClientListArray['GET']['user']     = true;
		//$this->accessClientListArray['PUT']['admin']    = true; //DO NOT ALLOW WILL BREAK PROGRAMMING CODE
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
			$errorMsg = "Oops! You don't have access to this info. Please contact support at support@connectandconquer.com for assistance. Code: " . __LINE__;
			$navigation = "Login";//TODO check this
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			die(json_encode($response));
		}
		$this->db->requiredFields = array(
				'roleName',
				'roleType' //Enum (system or game - default is system)
		);
		$this->db->fieldsArray = array(
				'id'				=> @$this->params['id'],
				'roleName'				=> @$this->params['roleName'],
				'roleType'			=> @$this->params['roleType'],
				'description'		=> @$this->params['description'],
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
	function displayUserRoles(){
		$return =array();
		if (@$this->id > 0){
			$response = $this->db->getRecord($this->id);
			$this->availableItems = $this->db->availableItems;
			//  return $this->db->getRecord($this->id);
		}
		else {
			$this->processWhere($this->params);
			$return =array();
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
	function addUserRoles() {
		if (!empty($this->id)) {
			return array("error" => __LINE__ . ": Blank ID must not be set when adding Blank");
		}
		$this->params['active'] = 1;
// 		$passPhase = $this->params['blankPassPhrase'];
// 		$this->db->fieldsArray['blankPassPhrase'] = password_hash($passPhase, PASSWORD_DEFAULT);
		$addResult = $this->db->addRecord();
		if (!empty($addResult['id']) && $addResult['id'] > 0) {
			$this->id = $addResult['id'];
			return $this->displayUserRoles();
		} else {
			return array('error' => __LINE__ . ": Could not add record", 'details' => $addResult['message']);
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
	function updateUserRoles(){
		if (!empty($this->id)) {
			// Update the record with the given ID
			$updated = $this->db->updateRecord($this->db->table, $this->id);

			if ($updated['status']!=='error') {
				// If the update is successful, return the displayed Roles
				return $this->displayUserRoles();
			} else {
				// If there is an error during update, return an error message
				return array('error' => __LINE__.": Could not update record", 'details' => $updated['message']);
			}
		} else {
			// If no valid ID is given, return an error message
			return array('error' => "no valid ID given: $this->id");
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
	function removeUserRoles(){
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