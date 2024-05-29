<?php
/**  _______                   _        
 |__   __|                 | |       
    | | __ _ _ __ __ _  ___| |_ ___  
    | |/ _` | '__/ _` |/ _ \ __/ __| 
    | | (_| | | | (_| |  __/ |_\__ \ 
    |_|\__,_|_|  \__, |\___|\__|___/ 
                  __/ |              
                 |___/               
 
 INTERNAL CLASS ONLY - NOT ENDPOINT
 */
                                                                                             
class Targets extends SnapshotDataClass{
	
	function __construct($params, $authJWTstring = null ) {
		parent::__construct($params, $authJWTstring, false); //passing false because we are not enforcing permissions here
		$this->db->requiredFields = array(
				//'targetName'
				
		);
		$this->db->fieldsArray = array(
				'id'				=> @$this->params['id'],
				'targetName'		=> @$this->params['targetName'],
				'targetSnapshotURI'	=> @$this->params['targetSnapshotURI'],
				'description'		=> @$this->params['description'],
				'active'			=> @$this->params['active'],
		);
		$this->id = isset($this->params['id']) ? $this->params['id'] : null;
		$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
			return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
		});
		$this->db->tableJoins = 'targets'; //without setting here it was changing it to Targets. Not sure how to fix that.
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
	function displayTargets(){
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
		//print_r($response);die('displayTargets');
		$results = $response['data'];
		$this->mainQuery		 	= $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;
		
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
	function addTargets() {
		// print_r($this->params);die('addTargets');
		if (!empty($this->id)) {
			return array("error" => __LINE__ . ": Targets ID must not be set when adding Targets");
		}
		$this->params['active'] = 1;
// 		$passPhase = $this->params['blankPassPhrase'];
// 		$this->db->fieldsArray['blankPassPhrase'] = password_hash($passPhase, PASSWORD_DEFAULT);
		$addResult = $this->db->addRecord();
		if (!empty($addResult['id']) && $addResult['id'] > 0) {
			$this->id = $addResult['id'];
			return $this->displayTargets();
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
	function updateTargets(){
		if (!empty($this->id)) {
			// Update the record with the given ID
			$updated = $this->db->updateRecord($this->db->table, $this->id);

			if ($updated['status']!=='error') {
				// If the update is successful, return the displayed Targets
				return $this->displayTargets();
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
	function removeTargets(){
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