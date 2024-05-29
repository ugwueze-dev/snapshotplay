<?php
/**
   _____                        __  __           _           
  / ____|                      |  \/  |         | |          
 | |  __  __ _ _ __ ___   ___  | \  / | ___   __| | ___  ___ 
 | | |_ |/ _` | '_ ` _ \ / _ \ | |\/| |/ _ \ / _` |/ _ \/ __|
 | |__| | (_| | | | | | |  __/ | |  | | (_) | (_| |  __/\__ \
  \_____|\__,_|_| |_| |_|\___| |_|  |_|\___/ \__,_|\___||___/
 
 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayGameModes() method.
 |--------------------------------------------------------------------------
 */
class GameModes extends SnapshotDataClass{
	
	function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring);

		$this->accessClientListArray['POST']['admin']   = true;
		//$this->accessClientListArray['POST']['all']     = true;
		//$this->accessClientListArray['POST']['user']    = true;
		$this->accessClientListArray['GET']['admin']    = true;
		$this->accessClientListArray['GET']['all']      = true;
		$this->accessClientListArray['GET']['user']     = true;
		$this->accessClientListArray['PUT']['admin']    = true;
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
				'gameModeName',
				
		);
		$this->db->fieldsArray = array(
				'id'					=> @$this->params['id'],
				'gameModeName'			=> @$this->params['gameModeName'],
				'gameModeDescription'	=> @$this->params['gameModeDescription'],	
				'gameModeInstructionURL'=> @$this->params['gameModeInstructionURL'],
				//'gameModeImageURL'		=> @$this->params['gameModeImageURL'],	
				'gameModeRules'			=> @$this->params['gameModeRules'],
				'gameModeLimits'		=> @$this->params['gameModeLimits'],
				'gameModeLogic'			=> @$this->params['gameModeLogic'],
				'active'				=> @$this->params['active'],
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
	function displayGameModes(){
		/*/
		|--------------------------------------------------------------------------
		|         Access is public
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
			$response = $this->db->select(array('gameModes'),array(),array(),array());
		}
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
	function addGameModes() {
		/*/
		|--------------------------------------------------------------------------
		|         Access is limited to ADMIN Only
		|--------------------------------------------------------------------------
		*/
		$isAdmin = $this->isAdmin();
	
		if(!$isAdmin){
			http_response_code(401);
			$errorMsg = "Oops! You don't have permission to access this endpoint. Only Admins can add Game Modes";
			$debugInfo = array("details" => "User Role does not give acess to ".ENDPOINT ." endpoint" . __LINE__ . " " . __FILE__);
			$navigation = null;
			$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
		else {
			if (!empty($this->id)) {
				http_response_code(400);
				$errorMsg = " Game Mode ID must not be set when adding a game mode";
				$navigation = null;
				$debugInfo = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
			$this->params['active'] = 1;
	
			$addResult = $this->db->addRecord();
			if (!empty($addResult['id']) && $addResult['id'] > 0) {
				$this->id = $addResult['id'];
				return $this->displayGameModes();
			} else {
				http_response_code(400);
				$errorMsg = "Oops! Something went wrong, your gameMode was not added.";
				$debugInfo = array("details" => $addResult['message'] . __LINE__ . " " . __FILE__);
				$navigation = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
				return $response;
				
			}
		}
		
	}
	
	
}