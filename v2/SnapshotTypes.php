<?php
/**
   _____                   _____ _           _     _______                    
  / ____|                 / ____| |         | |   |__   __|                   
 | (___  _ __   __ _ _ __| (___ | |__   ___ | |_     | |_   _ _ __   ___  ___ 
  \___ \| '_ \ / _` | '_ \\___ \| '_ \ / _ \| __|    | | | | | '_ \ / _ \/ __|
  ____) | | | | (_| | |_) |___) | | | | (_) | |_     | | |_| | |_) |  __/\__ \
 |_____/|_| |_|\__,_| .__/_____/|_| |_|\___/ \__|    |_|\__, | .__/ \___||___/
                    | |                                  __/ | |              
                    |_|                                 |___/|_|              
 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displaySnapshotTypes() method.
 | 
 |	 	For endpoints where the table is a type. IE: gameTypes, targetTypes
 |      GET is allowed without authentication for frontend to use
 |		Make sure to add as 'all' in Access Level Read permissions in config.php
 |--------------------------------------------------------------------------
 */
class SnapshotTypes extends SnapshotDataClass{
	
	function __construct($params) {
		parent::__construct($params);
		//$this->accessClientListArray['POST']['admin']   = true;//DO NOT ALLOW - ITEMS CREATED HERE WILL NOT BE IMPLEMENTED IN PROGRAM AUTOMATICALLY
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
			$errorMsg = "Oops! You don't have access to this info. Please contact support at support@connectandconquer.com for assistance. Code: " . __LINE__;
			$navigation = "Login";//TODO check this
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			die(json_encode($response));
		}

		$this->db->requiredFields = array(
				'snapshotTypeName',
		);
		$this->db->fieldsArray = array(
				'id'				=> @$this->params['id'],
				'snapshotTypeName'				=> @$this->params['snapshotTypeName'],
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
	function displaySnapshotTypes(){
	
		$typeOptions = $this->getAllTypes('snapshotTypes');
		$results = $typeOptions['data'];
		$this->mainQuery		 	= $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;

		return  $this->prepareReturn($results);
	}
}