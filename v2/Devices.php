<?php
/**
  _____  ________      _______ _____ ______  _____ 
 |  __ \|  ____\ \    / /_   _/ ____|  ____|/ ____|
 | |  | | |__   \ \  / /  | || |    | |__  | (___  
 | |  | |  __|   \ \/ /   | || |    |  __|  \___ \ 
 | |__| | |____   \  /   _| || |____| |____ ____) |
 |_____/|______|   \/   |_____\_____|______|_____/
 
 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayDevices() method.
 |--------------------------------------------------------------------------
 */
class Devices extends SnapshotDataClass{
	
	function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring);

		//admin has no access to devices
		//$this->accessClientListArray['POST']['admin']   = true;
		//$this->accessClientListArray['POST']['all']     = true;
		//$this->accessClientListArray['POST']['user']    = true;
		//$this->accessClientListArray['GET']['admin']    = true;
		// $this->accessClientListArray['GET']['all']      = true;
		//$this->accessClientListArray['GET']['user']     = true;
		//$this->accessClientListArray['PUT']['admin']    = true;
		// $this->accessClientListArray['PUT']['all']      = true;
		$this->accessClientListArray['PUT']['user']     = true;
		//$this->accessClientListArray['DELETE']['admin'] = true;
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
				'deviceToken'
		);
		$this->db->fieldsArray = array(
				'id'				=> @$this->params['id'],
				'userID'			=> @$this->claimUserID,
				'deviceToken'		=> @$this->params['deviceToken'],
				'active'			=> @$this->params['active'],
		);
		$this->id = isset($this->claimUserID) ? $this->claimUserID : null;
		$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
			return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
		});

	}

	/**
	  _____  _    _ _______
	 |  __ \| |  | |__   __|
	 | |__) | |  | |  | |
	 |  ___/| |  | |  | |
	 | |    | |__| |  | |
	 |_|     \____/   |_|
	 */
	function updateDevices(){
		
		if (!empty($this->id)) {
			if(!isset($this->params['deviceToken']) || empty($this->params['deviceToken'])){
				http_response_code(400);
				$errorMsg = "Oops! Device token is required to update a device.";
				$navigation = null; //not sure what screen this is
				$response = $this->createApiResponse(400, null, $errorMsg, null,  $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
			// Update the record with the given ID
			$updated = $this->db->update('devices', $this->db->fieldsArray, array('userID'=>$this->id),$this->db->requiredFields);

			if ($updated['status']!=='error') {
				http_response_code(200);
				$data = array("message" => "Success!! Your device token has been updated" );
				return $this->prepareReturn($data);
				
			} else {
				http_response_code(400);
				$errorMsg = "Oops! Something went wrong. Your device token was not updated.";
				$debugInfo = $updated['message'];
				$navigation = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
		} else {
			// If no valid ID is given, return an error message
			$errorMsg = "Oops! A valid ID is required to update a device.";
			$debugInfo = array("details" => "No valid ID was given: $this->id". __LINE__ . " " . __FILE__);
			$navigation = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
	}
	

	
}