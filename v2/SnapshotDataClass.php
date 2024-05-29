<?php

use Lcobucci\JWT\Encoding\JoseEncoder;
//use Google\apiclient\Src\Client as Google_Client;
use Google\Client as Google_Client; //Google API PHP Client for firebase message and play store
use Google\Auth\ApplicationDefaultCredentials; // Google API PHP Client for firebase batch message
use GuzzleHttp\Promise; // Used for firebase async messaging
use GuzzleHttp\Client; // Used for firebase async messaging
use GuzzleHttp\Psr7\Request; // Used for firebase async messaging
require 'SocketPublisher.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);
require 'vendor/autoload.php';
require_once 'GoogleConnectors.php';
// use DateTimeImmutable;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\Clock\FrozenClock;
use Beste\Clock\UTCClock;
use Psr\Clock\ClockInterface;



class SnapshotDataClass /*/
 |--------------------------------------------------------------------------
 |        Maybe should be abstracted... ? Shrug ?
 |--------------------------------------------------------------------------
 */

{
	protected $accessClientListArray = array();
	protected $arrays 			= array();//used for casting
	protected $auth;
	protected $availableItems;
	protected $booleans 			= array();//used by casting
	protected $calledClass		= '';
	protected $cryptKey 			= '';
	protected $dates 			= array();//used by casting
	protected $db;//mySQL helper
	protected $endRecordNumber	= '';	//used by sorted sets (ZREVRANGE) to do pageinnation
	protected $floats 			= array();//used for casting
	protected $hashID;
	protected $httpVerbsArray 	= array(
			'add'=>'POST',
			'display'=>'GET',
			'remove'=>'DELETE',
			'update'=>'PUT'
	);
	protected $httpStatus;
	protected $id				= '';
	protected $integers 			= array();//used for casting
	protected $json				= array();//used for casting
	protected $key 				= '';
	protected $keyID;
	protected $userHttpMethodGranted;
	protected $mainQuery;
	protected $numberPerPage 	= 100; 	//used by sorted sets (ZREVRANGE) to do pageinnation
	protected $pageNumber 		= 1; 	//used by sorted sets (ZREVRANGE) to do pageinnation
	protected $pages 			= ''; 	//used by sorted sets (ZREVRANGE) to do pageinnation
	protected $params;
	protected $redis				= '';
	protected $startRecordNumber	= 0;		//used by sorted sets (ZREVRANGE) to do pageinnation
	protected $returnedItems 	= 0;		//used by sorted sets (ZREVRANGE) to do pageinnation
	protected $serverURL	;
	protected $testMode = 'live';
	protected $testTTL = '-1';
	protected $endpointRequest = '';
// 	protected $endpointRequest = '';
	
	//protected $stripePublicKey	= "pk_test_MmEbguR8QNclrlCtUWYHBmnX"; //default to test
	//protected $stripeSecretKey	= "sk_test_yFfZ060dVFBzytLTXwrfSKXC"; //default to test
	protected $stripePublicKey	= ""; //default to live
	protected $stripeSecretKey	= ""; //default to live
	public  $userID;
	protected $adminKey;
	protected $isAdmin = false;
	protected  $authJWTstring = false;
	protected $claimUserID; //this is the userID from the JWT claim
	
	public  $userEmail;
	public  $ipAddress;
	public  $endTime;
	public 	$jwt;
	public  $startTime;
	public  $class;
	public $gameID;
	public $gameCode;	
	protected $game;
	protected $dateTime;
	public $userRoles = array(); //player, host, etc
	public $roles = ""; //user, admin, system
	protected $authorizationHeader;
	protected $jwtExpirationTimestamp;
	protected $authInfoArray = array(); //stores status, expiration, and JWT string if returning one

	public $allowedPermissions = array(); //this is the array of permissions the user has for this endpoint (after comparing required)
	protected $allowedParams = array();
	protected $processedParams = '';
	protected $resultsKey; //key set based on RequestMethod
	protected $tempFile = null;
	private $uploadPath = "../uploads/";
	private $mediaPath = "";
	protected $debugMode = true; //if true, will display debug info in response
	//protected $errorArray = array(); //V1 check if this is needed
	protected $errorArray = array(); //V1 check if this is needed
	protected $testArray = array(); //V1 check if this is needed
	protected $errorReportingIsOn = true;
	//protected $errorReport = array();
	protected $middleware = array();
	protected $socketEventInfo = array(); //this can be used to help debug socket events being sent
	 protected $enforceAccessPermissions = true; //this will be false if the endpoint is public or if it is an internal class
	 protected $httpMethod;
	/*
	|--------------------------------------------------------------------------
	|        Endpoint is set in .htaccess (with Apache) and can be accessed
	|     	 with ENDPOINT constant
	|--------------------------------------------------------------------------
	*/
	function __construct($params,$authJWTstring = null, $enforceAccessPermissions = true) {
		
		
		if($this->params['debugMode'] == true){
			
			$this->debugMode = true;
		}
		else {
			$this->debugMode = false;
		}
		$this->httpMethod = $_SERVER['REQUEST_METHOD'];
		$defaultACLArray = array(
			'admin' => false,
			'all' 	=> false,
			'user' 	=> false
		);
		$this->accessClientListArray = array(
			'POST'=> $defaultACLArray,
			'GET' => $defaultACLArray,
			'PUT' => $defaultACLArray, 
			'DELETE' => $defaultACLArray,
			'OPTIONS' => array(
				'admin' => true,
				'all' 	=> true,
				'user' 	=> true
			)
		);	

		
		
		require_once 'dbHelper.php';
		$this->db = new dbHelper(); 
		$this->redis = new redisDBHelper();

		$this->adminKey = isset($params['adminKey']) ?
							 $params['adminKey'] : null;


		// putenv('GOOGLE_APPLICATION_CREDENTIALS=./oauthCredentials/firebaseJsonKey.json'); //for batch processing of messages
		$_SERVER['GOOGLE_APPLICATION_CREDENTIALS'] = file_get_contents('./oauthCredentials/firebaseJsonKey.json'); //for batch processing of messages
		$this->calledClass = get_called_class();
		//before doing anything else, see if authJWTstring is set
		if(!empty($_FILES)){ 
			$this->tempFile = $_FILES['snapshot'];
		}
	
	
		if(!empty($authJWTstring)){
			$this->authJWTstring = $authJWTstring;

		}
		// else { //if authJWTstring empty, it must have a requiredRole of 'all'otherwise restrict access
		// 	//check if adminKey isset & not null
		// 	//if so, set user access to system (generate a token? with system role?)
		// 	$requiredRoles = $this->defineRequiredRolesArray();
			
		// 	 if($enforceAccessPermissions !== false){ //these are classes not exposed to front end and don't need permissions
		// 		if(in_array('system',$requiredRoles) ) {
		// 			$this->isAdmin = true;
					
		// 		}
		// 		else if(!in_array('all',$requiredRoles)  ){
		// 			http_response_code(401);
		// 			$debugInfo = array(
		// 				'line'=>__LINE__,
		// 				'file'=>__FILE__,
		// 				'error'=>'Endpoint is not public and requires authorization'
		// 			);
		// 			$errorMsg = "Oops! This endpoint is not public and requires authorization.";
		// 			$navigation = "Login";
		// 			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
		// 			die(json_encode($response));
					
		// 		}
		// 		//else - should only get here if requiredRoles is all and authJwtString is empty
		// 		//TODO  is this needed? we don't return a prepareReturn for authorizations - find use case for request new
		// 		else if (in_array('all',$requiredRoles) && $this->endpointRequest == 'GET' && ENDPOINT == 'authentications'){
		// 			$this->authInfoArray['status'] = JWT_STATUS_REQUEST_NEW;
		// 		}
		// 		else {
		// 			$this->authInfoArray['status'] = JWT_STATUS_REQUEST_NEW;
		// 		}
		// 	 }
		// }
		
		
		$this->startTime = microtime(true);
		$this->ipAddress = $_SERVER['REMOTE_ADDR'];
		$this->params = $params;
		//$this->calledClass = get_called_class();
		$this->serverURL = gethostname();
		$this->db->tableJoins[]=lcfirst($this->calledClass);
		$this->db->table = $this->calledClass;

		
		//check if gameCode is set
		if (isset($this->params['gameCode']) && !empty($this->params['gameCode'])) {
			$this->gameCode = $this->params['gameCode'];
			$this->params['gameID'] = $this->getGameIDFromGameCode($this->gameCode);
			$this->gameID = $this->params['gameID'];
		}
		//check if gameID is set
		elseif(isset($this->params['gameID']) && !empty($this->params['gameID'])){ 
			$this->gameID = $this->params['gameID'];
			$this->gameCode = $this->getGameCodeFromGameID($this->gameID);
			$this->params['gameCode'] = $this->gameCode;
		}
		else {
			$this->gameID = null;
			$this->gameCode = null;
		}

		  
		if(ENDPOINT == 'users' && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE' )){
			$this->id = $this->claimUserID;
		}
		else if (ENDPOINT == 'users' && $_SERVER['REQUEST_METHOD'] != 'POST') //User POST is a public method
		{	
			$this->params['id'] = $this->claimUserID; 
		}
		elseif(ENDPOINT == 'gameTypes'){
			//HACK this is to avoid $this->db->where being set.  We just need to return all types
		}
		//else set the where clause to userID
		else {
			$this->db->where['userID'] = $this->claimUserID;
		}

		//TODO! add in check for type of player (spectator, host, etc) which will be used to determine permissions
		//$this->allowedPermissions = $this->getUserAllowedEndpointPermissions();
		
		//spectators of games only have permission for GET for Game and GamePlays
		if((ENDPOINT == 'games') &&  $_SERVER['REQUEST_METHOD'] != 'GET' ){
			if (!empty($this->gameID) && $this->isPlayerASpectatorByGameID($this->gameID, $this->claimUserID) && $_SERVER['REQUEST_METHOD'] != 'GET'){
				http_response_code(401);
				$debugInfo = array(
					'line'=>__LINE__,
					'file'=>__FILE__,
					'error'=>'Spectators only have permission to GET method in Game endpoint'
				);
				$errorMsg = "Oops! Spectators only have permission to GET method in Game and GamePlays endpoints.";
				$navigation = null; //This should be no longer used once change in authorization is method based
				$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				die(json_encode($response));
				
			}
			
		}

		/*/
 |--------------------------------------------------------------------------
 |         When we start having admins, we will allow other userIDs to be used here
 |--------------------------------------------------------------------------
 */
		// if (isset($this->params['userID']) && !empty($this->params['userID'])){
		// 	$this->userID = $this->params['userID'];
		// }
		// else {
		// 	$this->userID = $this->authUserID;
		// }


		$this->paging();//sets paging stuff
		// $this->dateTime = new DateTimeImmutable();
		// echo $date->format('Y-m-d H:i:s');
		/*/
                                                                                                       
,---.     |      ,---.          |    o           ,--.      |          --.--                    
`---.,---.|---   |    ,---.,---.|--- .,---.,---. |   |,---.|--- ,---.   |  ,   .,---.,---.,---.
    ||---'|      |    ,---|`---.|    ||   ||   | |   |,---||    ,---|   |  |   ||   ||---'`---.
`---'`---'`---'  `---'`---^`---'`---'``   '`---| `--' `---^`---'`---^   `  `---||---'`---'`---'
                                           `---'                           `---'|                        
		 */
			$this->floats[]	  ='querySeconds';
			$this->floats[]	  ='adminMonthlyMax';
			$this->integers[] ='id';
			$this->integers[] ='pageNumber';
			$this->integers[] ='pages';
			$this->integers[] ='availableItems';
			$this->integers[] ='returnedItems';
			$this->integers[] ='returnedRequestedItems';
			$this->integers[] ='returnedOtherItems';
			$this->integers[] ='numberPerPage';
			$this->integers[] ='version';
			$this->integers[] ='createdBy';
			$this->integers[] ='updatedBy';
			$this->integers[] ='adminSuperUser';
			$this->integers[] ='listMonthlyMax';
			$this->arrays[] ='toJSON';
			$this->integers[] ='active';

			if(!empty($params)){
				$jsonParams = json_encode($params);
				
				$this->storeTestingData("PARAMS SNAPSHOTDATA", "REQUEST", "UserID: " . @$this->claimUserID . " Params: $jsonParams",  __FILE__ , __LINE__);
			}
			
	}
	public function GET(){
		
		$method = 'display'.$this->calledClass;
		$this->endpointRequest = "Display $this->calledClass";
		if (method_exists($this->calledClass, $method)){
			
			//Default to Active but allow ALL too
			if (@$this->params['active']=='all'){
				unset($this->params['active']);
			}
			elseif (!isset($this->params['active'])){
				$this->params['active'] = 1;
			}
			
			//The $this->{$method}() allows calling a method as $var
			return $this->{$method}();
		}
		else {
			http_response_code(405);
			$debugInfo = array(
				'line'=>__LINE__,
				'file'=>__FILE__,
				'error'=>"The GET HTTP Request Method is not allowed for the $this->calledClass endpoint."
			);
			$errorMsg = "Oops! The GET HTTP Request Method is not allowed for the $this->calledClass endpoint.";
			$navigation = 'Menu';
			$response = $this->createApiResponse(405, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}				
	}	
		
	public function POST(){
		$method = 'add'.$this->calledClass;
		$this->endpointRequest = "Add $this->calledClass";
		if (method_exists($this->calledClass, $method)){
			//The $this->{$method}() allows calling a method as $var
			return $this->{$method}();
		}
		else {
			http_response_code(405);
			return array('error'=>"The POST HTTP Request Method is not allowed for the $this->calledClass endpoint.");
		}		
	}	
	public function PUT(){
		$method = 'update'.$this->calledClass;
		$this->endpointRequest = "Update $this->calledClass";
		if (method_exists($this->calledClass, $method)){
			//The $this->{$method}() allows calling a method as $var
			return $this->{$method}();
		}
		else {
			http_response_code(405);
			return array('error'=>"The PUT HTTP Request Method is not allowed for the $this->calledClass endpoint.");
		}
	}	
	public function DELETE(){ //FIXME Games endpoint calls delete after method has been removed from class. 
		$method = 'remove'.$this->calledClass;
		$this->endpointRequest = "Delete $this->calledClass";
		if (method_exists($this->calledClass, $method)){
			//The $this->{$method}() allows calling a method as $var
			return $this->{$method}();
		}
		else {
			http_response_code(405);
			return array('error'=>"The DELETE HTTP Request Method is not allowed for the $this->calledClass endpoint.");
		}
	}
	public function OPTIONS(){
		$this->sendHeaders();
	}
//Not sure this is used anymore..
	public function sendHeaders (){
		$methodString = 'OPTIONS';
		foreach ($this->httpVerbsArray AS $pre => $httpMethod){
			$method = $pre.$this->calledClass;
			if (method_exists($this->calledClass, $method)){
				$methodString .= ", $httpMethod";
			}
		}
		http_response_code(200);
		header("Access-Control-Allow-Methods: $methodString");
		header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers");
		header("Access-Control-Allow-Credentials: true");
	}
	public function makeUUID(){
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
	public function castData($data){
		$adjustedData = array();
		if (!is_array($data)){
			return $data;
		}
		foreach ($data as $key=>$value){
			
			if ($key === 'id' && !is_int($value)){
				$adjustedData[$key] = (int)$value;
				
// 				var_dump($value);
// 				$test = (int)$value;
// 				var_dump($test);
// 				die();
			}
			if (is_array($value)){
// 				var_dump($value);die();
				if(in_array("$key", $this->arrays)){

					$adjustedData[$key] = (array)$value;
				}
				else {
					$adjustedData[$key] = $this->castData($value);
				}
			}
			elseif (strpos($key,'PassPhrase') > 0 || $key =='availableItems' || $key =='passPhraseHash'){
				//do not display it... ever
			}
			elseif(in_array($key, $this->booleans)){
				$adjustedData[$key] = (boolean)$value;
			}
			elseif (in_array($key, $this->floats)){
				$adjustedData[$key] = (float)$value;
			}
			elseif (in_array($key, $this->integers) || substr($key, -2) == "ID"){
				$adjustedData[$key] = (int)$value;
			}
			elseif (in_array($key, $this->dates) || substr(strtolower($key), -4) == "date"){
				//elseif (in_array($key, $this->dates) || substr(($key, -4) == strtolower("date")){
				if (is_null($value)){
					$adjustedData[$key] = null;
				}
				else {
					$adjustedData[$key] = date('c',strtotime($value));
				}
			}
			elseif (in_array($key, $this->json)){
				if (is_null($value)){
					$adjustedData[$key] = null;
				}
				else {
				$adjustedData[$key] = json_decode($value,true);
				}
			}
			else {
				$adjustedData[$key]= $value;
			}
	
		}
		return $adjustedData;
	}
	//This is used for die statements or for returning errors outside of prepareReturn to standardize it
	function createApiResponse($status, $data = null, $error = null, $debugInfo = null, $navigation = null) {
		if(empty($data)){
			$castData = array($data);
		}
		else {
			$castData =$data;
		}
		
		if($this->debugMode == true){	
			return array(
				'status' => $status,
				'data' => $castData,
				'error' => $error,
				'navigation' => $navigation,
				'authInfo' => $this->authInfoArray,
				'debug' => $debugInfo
			);
		}
		else {
			return array(
				'status' => $status,
				'data' => $castData,
				'error' => $error,
				'navigation' => $navigation,
				'authInfo' => $this->authInfoArray
				
			);
		}

		
	}
	public function camelCaseString($string,$noStripArray = array()){
			// non-alpha and non-numeric characters become spaces
			$string = preg_replace('/[^a-z0-9' . implode("", $noStripArray) . ']+/i', ' ', $string);
			$string = trim($string);
			// uppercase the first character of each word
			$string = ucwords($string);
			$string = str_replace(" ", "", $string);
			$string = lcfirst($string);
			return $string;
	}
	public function deCamelCaseString($string){
		$newString = ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $string));
		$returnString = str_replace(' I D',' ID',$newString);
		return $returnString;
	}
	
	public function hasNestedArray($array) {
		foreach ($array as $value) {
			if (is_array($value)) {
				return true; // Found a nested array, return true
			}
		}
		return false; // No nested arrays found
	}
	public function countNestedArray($array) {
		$count = 0;
	
		foreach ($array as $value) {
			if (is_array($value)) {
				$count += $this->countNestedArray($value); // Recursively count nested arrays
			} else {
				$count++; // Increment count for non-array elements
			}
		}
	
		return $count;
	}
	
	public function countRecordsInDynamicObjects($jsonData) {
		// Decode the JSON data into a PHP associative array
		$data = json_decode($jsonData, true);
	
		// Get the "items" object
		$items = $data['items'];
	
		// Initialize an array to store the record counts
		$recordCounts = [];
	
		// Loop through each dynamically named sub-object in "items"
		foreach ($items as $objectName => $subObject) {
			// Count the records in the sub-object and store the count in the array
			$recordCounts[$objectName] = count($subObject);
		}
	
		return $recordCounts;
	}
	/*
	// Call the function with your JSON data
	$recordCounts = countRecordsInDynamicObjects($jsonData);
	
	// Print the record counts for each dynamically named sub-object
	foreach ($recordCounts as $objectName => $count) {
		echo "Count of records in $objectName: $count\n";
	}
	*/

	protected function generateURL($fileName){
		$urlPath = "/media/users/"; 
		$alphaNumericUserID = $this->alphaID($this->claimUserID);

		$url = API_URL . $urlPath . $alphaNumericUserID . "/" . $fileName;
		return $url;
	}

	protected function generateFileName($userID){
		$alphaNumericUserID = $this->alphaID($userID);
		//$alphaNumericTargetID = $this->alphaID($targetID);
		// $namingConvention = $alphaNumericUserID.'-'.$gameCode.'-'.$alphaNumericTargetID.'.png';
		//NOTE this naming convention changed with having to upload the file before receiving any info about it
		//$currentEpochTime = time(); //TODO use this for alphaNumericDateTime
		// $alphaNumericDateTime = $this->alphaID($currentEpochTime);
		//$namingConvention = $alphaNumericUserID.'-'.$alphaNumericDateTime.'.png'; //MVP remove alphanumeric date for testing
		$currentDateTime = date('Y-m-d H:i:s');
		// Remove dashes and colons
		$cleanedDateTime = str_replace(['-', ' ', ':'], '', $currentDateTime);

		$namingConvention = $alphaNumericUserID.'-'.$cleanedDateTime.'.png';
		return $namingConvention;
	}
	
	protected function createStorageDirectoryIfNeeded($alphaNumericUserID){
		
			
			$storageFilePath = MEDIA_UPLOADS_DIR . 'users/' . $alphaNumericUserID;
			if (!file_exists($storageFilePath)) {
				mkdir($storageFilePath, 0775, true);
				// Optionally, explicitly set permissions in case umask affects the default permissions
				chmod($storageFilePath, 0775);
			}
			return $storageFilePath;
	}

	


		/**
	 * Translates a number to a short alphanumeric version
	 *
	 * Translated any number up to 9,007,199,254,740,992
	 * to a shorter version in letters e.g.:
	 * 9007199254740989 --> PpQXn7COf
	 *
	 * If you want the alphaID to be at least 3 letter long, use the
	 * $pad_up = 3 argument
	 *
	 * more info on limitation:
	 * - http://blade.nagaokaut.ac.jp/cgi-bin/scat.rb/ruby/ruby-talk/165372
	 *
	 * @author  Kevin van Zonneveld &lt;kevin@vanzonneveld.net>
	 * @author  Simon Franz
	 * @author  Deadfish
	 * @author  SK83RJOSH
	 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
	 * @version   SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
	 * @link    http://kevin.vanzonneveld.net/
	 *
	 * @param mixed   $in   String or long input to translate
	 * @param boolean $to_num  Reverses translation when true
	 * @param mixed   $pad_up  Number or boolean padds the result up to a specified length
	 * @param string  $pass_key Supplying a password makes it harder to calculate the original ID
	 *
	 * @return mixed string or long
	 */
	function alphaID($in, $to_num = false, $pad_up = 5, $pass_key = 'Creating a Dent')
	{
		$out   =   '';
		//I removed aeiou to prevent bad words
		$index = 'bcdfghjklmnpqrstvwxz0123456789BCDFGHJKLMNPQRSTVWXZ';
		$base  = strlen($index);
		if ($pass_key !== null) {
			for ($n = 0; $n < strlen($index); $n++) {
				$i[] = substr($index, $n, 1);
			}
			$pass_hash = hash('sha256',$pass_key);
			$pass_hash = (strlen($pass_hash) < strlen($index) ? hash('sha512', $pass_key) : $pass_hash);
			for ($n = 0; $n < strlen($index); $n++) {
				$p[] =  substr($pass_hash, $n, 1);
			}
			array_multisort($p, SORT_DESC, $i);
			$index = implode($i);
		}
		if ($to_num) {
			// Digital number  <<--  alphabet letter code
			$len = strlen($in) - 1;
			for ($t = $len; $t >= 0; $t--) {
				$bcp = bcpow($base, $len - $t);
				$out = $out + strpos($index, substr($in, $t, 1)) * $bcp;
			}
			if (is_numeric($pad_up)) {
				$pad_up--;
				if ($pad_up > 0) {
					$out -= pow($base, $pad_up);
				}
			}
		} else {
			// Digital number  -->>  alphabet letter code
			if (is_numeric($pad_up)) {
				$pad_up--;
				if ($pad_up > 0) {
					$in += pow($base, $pad_up);
				}
			}
			for ($t = ($in != 0 ? floor(log($in, $base)) : 0); $t >= 0; $t--) {
				$bcp = bcpow($base, $t);
				$a   = floor($in / $bcp) % $base;
				$out = $out . substr($index, $a, 1);
				$in  = $in - ($a * $bcp);
			}
		}
		return $out;
	}
	
	//create function to get 50 most recent snapshots grouped by game 
	public function getMostRecentSnapshotsByGame($gameID){
		//print_r($gameID);die();
		// $mostRecentSnapshots = array();
		// $mostRecentSnapshots = $this->db->select('snapshots',array('gameID'=>$gameID,'active'=>1),$this->db->whereGreater,$this->db->whereLess,['id'],'',true);
		// return $mostRecentSnapshots;
		try {
			// get role names for the User
			$sql = "
			
				";
				$resultData = $this->db->prepareAndExecuteFetchAll($sql, $gameID, ':gameID');
				foreach($resultData as $key => $value){
					$resultData[$key] = $value['roleName'];
				}

				//check if any roles were found
				if (empty($resultData)) {
					$response['status'] = "warning";
					$response['message'] = "No snapshots found for game {$gameID}.";
					$response['data'] = null;
				} else {
					$response['status'] = "success";
					$response['message'] = "Most recent snapshots for game {$gameID}.";
					$response['data'] = $resultData;
				}
			} catch (PDOException $e) {
				$response['status'] = "error";
				$response['message'] = 'Select Failed: ' . $e->getMessage();
				$response['data'] = null;
			}
		//print_r($response);
			return $response['data'];
		
	}

	public function isPlayerASpectatorByGameID($gameID, $userID){
		$table = 'gamesXspectators';
		$where = array(
			'userId'=>$userID
		);
		$game = $this->db->select(
			$table, 
			$where);
		// $game = $this->db->select($table,$where,array(),array(),$column,array(),true);
		if(isset($game['data'][0]) && $game['data'][0]>0){
		
			return $game['data'][0];
		}
		else {
			return 0;
		}

		
	}

	public function getAllGamesForUser(){
		$tablesArray = array(
			'gamesXusers',
			'games',
			'users'
		);
		$whereArray = array(
			'`gamesXusers`.`userID`' => '`users`.`id`',
			'`games`.`id`' => '`gamesXusers`.`gameID`',
			'`users`.`id`' => '1',

		);
		$whereLessThanArray = array(
			'participantTypeID' => 4
		);

		$columnsArray = array(
			
			'`gamesXusers`.`gameID`',
			
		);

		
		 $game = $this->db->select($tablesArray,$whereArray,array(),$whereLessThanArray,$columnsArray,'',true);
		return $game;

		
	}
/**
 * Description: Get GameCode with the Snapshot ID - //FIXME not sure if working or needed after db change to target
 *
 * @param int $snapshotID 
 * @return mixed $gameInfo array on success, false on failure
 */
	// public function getGameCodeBySnapshotID($snapshotID){
	// 	$gameInfo = array();
	// // Define your database query parameters here
	// $tables = array(
	// 	'snapshots',
	// 	'usersXtargets',
	// 	// 'games',
	// );
	// $where = array(
	// 	'snapshots.usersXtargetsID' => 'usersXtargets.id',
	// 	// 'usersXtargets.gameID' => 'games.gameID',
	// 	'snapshots.id' => $snapshotID,
	// );
	
	// // Call your database select method with the parameters
	// $result = $this->db->select(
	// 	$tables,
	// 	$where,
	// 	array(),    // $whereGreater
	// 	array(),    // $whereLess
	// 	array(), // $columnsArray
	// 	'',         // $whereMore
	// 	false       // $groupByColumn
	// );
	// if ($result['status'] != 'warning' && $result['status'] != 'error') {
	// 	$gameInfo = $result['data'];
		
	// } else {
	// 	// If no IDs were found, set to an empty array
	// 	 $gameInfo = false;
	// }
	// return $gameInfo;
	// }
	protected function getNumberActivePlayersRemaining($userID){
		$tables = array(
			'gamesXusers'
		);
		$where = array(
			'participantTypeID' => PARTICIPANT_TYPE_ACTIVE_PLAYER,
			"(SELECT gameID FROM gamesXusers, games WHERE 1 AND userID = $userID AND gameStatusID = " . GAME_STATUS_STARTED . " AND gamesXusers.gameID = games.id)" => 'gameID',
			'active' => 1
		);
		$column = array(
			'COUNT(DISTINCT userID) AS activePlayersRemaining'
		);
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			$column, // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
		return $result;
	}
	/**
	 * Phase 2 return will follow these rules: 
	 * 1. "status" key will return http status code
	 * 2. "data" All requested info returned in "data" key
	 * 3. "error" will return error message if applicable
	 * 4. "debug" or "testing" will only print if testMode is true & will contain all info needed to troubleshoot
	 * 
	 *  Data will be an array of objects.  But there will be no indexes.  
	 *  ie: "data": {
	 * "userDetails": {
	 *	 {
		* "id": 1,
		* "firstName": "John",
		* "lastName": "Doe",
		* },
		*{
		* "id": 2,
		* "firstName": "Joe",
		* "lastName": "Player",
		* },
	*}
	*}
	 */
	public function prepareReturn($items = array()){
	
		$querySeconds = microtime(true) - $this->startTime; //V1 put in "debug" or other key for testing only
		$numActivePlayers = $this->getNumberActivePlayersRemaining($this->claimUserID);
		$string = debug_backtrace()[1]['function']; //V1 put in "debug" or other key for testing only
		$query = $this->deCamelCaseString($string);//V1 put in "debug" or other key for testing only
		$castItems = array();
		$authInfo = $this->getAuthReturnInfo();//V1 put in "debug" or other key for testing only - send thru microservice
		
		
		if (is_array($items) || is_object($items)) {
			foreach ($items as $count => $each) {
				if(is_array($each) || is_object($each)){
					$nestedCastItems[$count] = $this->castData($each);
					
				}
				$castItems[$count] = $this->castData($each);
			}
		}
		if(isset($nestedCastItems)){
			$this->returnedItems = $this->countNestedArray($nestedCastItems);
		}
		else {
			$this->returnedItems = count($castItems,0);
		}
		
		if(!isset($this->httpStatus)){
			$this->httpStatus = 200;
		}
		if($this->debugMode == false){
			$debugArray = array();
		}
		else{
			$debugArray = array(
				'socketEventInfo' => $this->socketEventInfo,
				'errorResult'		=> $this->errorArray,
				'apiServer'			=> $this->serverURL,
				'apiMode'			=> $this->testMode,
				'modeTTL'			=> $this->testTTL,
				'endpointRequest'	=> $this->endpointRequest,
				'mainQueryDetail'	=> $this->mainQuery, 
				'currentUserEmail'	=> $this->userEmail,
				'currentUserID'		=> $this->claimUserID,
				'querySeconds'		=> (float)$querySeconds,
				'returnedItems'		=> (int)$this->returnedItems,
				// 'returnedTotalItems'		=> (int)$this->returnedItems,
				// 'returnedRequestedItems' => (int)$this->returnedRequestedItems,
				// 'returnedOtherItems'=>(int)$this->returnedOtherItems,
				'pageNumber'			=> (int)$this->pageNumber,
				'pages'				=> (int)$this->pages,
				'availableItems'		=> (int)$this->availableItems,
				'numberPerPage'		=> (int)$this->numberPerPage,
				'numRemainingPlayers' => (int)$numActivePlayers,
				'query' => $query,
				'querySeconds' => $querySeconds,
				'numRemainingPlayers' => $numActivePlayers,
				'pages' => $this->pages,
				'pageNumber' => $this->pageNumber,
				'availableItems' => $this->availableItems,
				'numberPerPage' => $this->numberPerPage,
				'processedParams' => $this->processedParams,
				'databaseRequires' => $this->db->requiredFields,
				'databaseFilters' => $this->db->where,
				// 				'publicKey'			=> $this->stripePublicKey,
				'startRecordNumber'	=> (int)$this->startRecordNumber,
				'endRecordNumber'	=> (int)$this->endRecordNumber,
				'authInfo'			=> $authInfo,
				'sentParams'		=> $this->params,
				'processedParams'	=> (!empty($this->processedParams)? $this->processedParams : $this->params),
				'databaseRequires'	=> $this->db->requiredFields,
				'databaseFilters'	=> $this->db->where
			);
		}

		$this->pages = ceil($this->availableItems/$this->numberPerPage); //V1 put in "debug" or other key for testing only

		//We are checking to see if it is a single item return and casting as an array, otherwise, send it as is
		if  (is_null($items[0]) && is_null($items[1]) ) {
		//single item force the cast of an array for the front end to be standardized	
			return array(
				'status'			=> $this->httpStatus,		
				//'data'				=> array($this->castData($items)), //using $castItems (without array() returns data as an object, not array of objects. Need to test with multiple objects in data)
				'data'				=> array($items), //using $castItems (without array() returns data as an object, not array of objects. Need to test with multiple objects in data)
				'authInfo'			=> $authInfo,
				'error'				=> $this->errorArray,
				'navigation'		=> null, //NOTE: this should only be set if there is an error returned, is used in CreateApiResponse, but included for standardization
				'debug'				=> $debugArray,
			);
		}
		else{
			return array(
				'status'			=> $this->httpStatus,		
				//'data'				=> array($this->castData($items)), //using $castItems (without array() returns data as an object, not array of objects. Need to test with multiple objects in data)
				'data'				=> $items, //using $castItems (without array() returns data as an object, not array of objects. Need to test with multiple objects in data)
				'authInfo'			=> $authInfo,
				'error'				=> $this->errorArray,
				'navigation'		=> null, //NOTE: this should only be set if there is an error returned, is used in CreateApiResponse, but included for standardization
				'debug'				=> $debugArray,
			);
		}
		
	//	return array($standardizedItems);
			
		
	}

	public function convertJWTEpochToDateTime(){

		$expDateTime = date('Y-m-d H:i:s',$this->jwtExpirationTimestamp);
		//print_r($expDateTime);die();
		return $expDateTime;
	}
	
	public function getAuthReturnInfo(){
		$authInfo = array();

		if(!empty($this->authJWTstring)){
			$authInfo['status'] = $this->authInfoArray['status'];
			$authInfo['expires'] = $this->authInfoArray['expires']; 
			//new jwt after adding games/joining games (perhaps others)
			if($this->authInfoArray['status'] == JWT_STATUS_VALID_EXPIRES_SOON || $this->authInfoArray['status'] == JWT_STATUS_REQUEST_NEW){
				//??? is there a time a user would request a new jwt
				//return a new jwt
				$authInfo['jwt'] =  $this->createJWT();
				
				
			}
		}
		
		
		//print_r($authInfo);die();
		return $authInfo;
	}
	

	public function getAuthStatusBasedOnExpire(){
		$sevenDaysFromNowTimestamp = strtotime("+7 days");

		if(isset($this->authJWTstring)){
			if($this->authInfoArray['expires'] > time() && $sevenDaysFromNowTimestamp < $this->jwtExpirationTimestamp){
				return JWT_STATUS_VALID;
				
			}
			else if ($this->authInfoArray['expires'] > time() && $sevenDaysFromNowTimestamp >= $this->jwtExpirationTimestamp) {
				return JWT_STATUS_VALID_EXPIRES_SOON;
				
			}
			else if ($this->authInfoArray['expires'] < time()){
				return JWT_STATUS_EXPIRED;
				
			}
			
		}
		//if expires is less than 7 days, then 'validExpiresSoon' & resend Login. 
		//if expired 'expired'  

		
	}
	
	public function keyValuePairList($listArray,$noStripArray = array()){
		$result = array();
		$i = 0;
		foreach ($listArray as $id => $value){
			$key1 = $this->camelCaseString($id);
			if (is_array($value)){
				$result[$i]['urlParam'] = $key1;
				$result[$i]['displayName'] = $id;
				$result[$i]['items']= $this->keyValuePairList($value);
			}
			else {
				$key = $this->camelCaseString($value);
				$result[] = array('urlParam'=>$key,'displayName'=>$value);
			}
			$i++;
		}
		return $result;
	}
	
	// public function dbSelect(){
		/*/
		 |--------------------------------------------------------------------------
		 | To force/unforce your IP to Test Mode visit 
		 | @link https://api.Snapshot.com/v1/testingIP/?id=830b3a44-ef89-47a3-b735-06bfde0f4677
		 | expires after around 2.5 hours
		 |--------------------------------------------------------------------------
		 */
// 		if (strpos(__FILE__, 'cw.Snapshot.com')!==false || $this->redis->SISMEMBER('testModeIPs',$this->ipAddress)){
// 			$this->serverURL = 'https://cw.Snapshot.com';
// 			$this->stripeSecretKey = "sk_test_yFfZ060dVFBzytLTXwrfSKXC";
// 			$this->stripePublicKey = "pk_test_MmEbguR8QNclrlCtUWYHBmnX";
// 			$this->redis->SELECT(1);
// 			$this->testMode = 'test';
// 			$this->testTTL = $this->redis->TTL(testModeIPs);
// 		}
// 		else {
// 			$this->serverURL = 'https://api.Snapshot.com';
// 			$this->redis->SELECT(0);
// 			$this->testMode = 'live';
// 		}
	// }
	
	public function paging(){
		if (isset($this->params['numberPerPage'])){
			$this->numberPerPage = $this->params['numberPerPage'];
			unset($this->params['numberPerPage']);
		}
		if (isset($this->params['pageNumber'])){
			$this->pageNumber = $this->params['pageNumber'];
			unset($this->params['pageNumber']);
		}
		$this->endRecordNumber	= $this->pageNumber * $this->numberPerPage - 1;
		$this->startRecordNumber	= $this->endRecordNumber - $this->numberPerPage + 1;
	}
	function processWhere($keyValueArray){
		foreach ($keyValueArray AS $key =>$value ){
			if (in_array($key, $this->db->requiredFields) 
				|| in_array($key,$this->db->fieldsArray)
					){
				$this->db->where[$key] = $value;
			}
			elseif ($key == 'tsBefore'){

				$this->db->whereLess[$key] = strtotime($value);
			}
			elseif ($key == 'tsAfter'){
				$this->db->whereGreater[$key] = strtotime($value);
			}	
		}
	}
	function formatPhone($phoneNumber,$justNumber = false){//false means make it human
		// note: making sure we have something
		if(!isset($phoneNumber[3])) { return ''; }
		// note: strip out everything but numbers
		$phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
		if ($justNumber == true){
			return $phoneNumber;
		}
		$length = strlen($phoneNumber);
		switch($length) {
			case 7:
				return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phoneNumber);
			case 10:
				return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phoneNumber);
			case 11:
				return preg_replace("/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1($2) $3-$4", $phoneNumber);
			default:
				return $phoneNumber;
		}
		
	}
//Function formatDate will take a any date and return it in the a standard format that can store in mySQL
	function formatDate($date){ //NOTE some of the formats that work are "birthdate":"1970-Jul-19" or 7/19/1976
		$timestamp = strtotime($date);
		//handle errors here. If $timestamp is 1969-12-31 something went wrong
		if ($timestamp == false){
			return '0000-00-00 00:00:00';
		}
		else {
			return date('Y-m-d H:i:s',$timestamp);
		}
		
	}

	function sendEmail($to,$subject,$text,$html='',$toname='',$tag='',$replyto='no-reply@mg.Snapshot.com',$mailfromname='no-reply',$mailfrom='no-reply@mg.Snapshot.com'){
		define('MAILGUN_URL', 'https://api.mailgun.net/v3/mg.Snapshot.com');
		define('MAILGUN_KEY', '');
		$array_data = array(
				'from'=> $mailfromname .'<'.$mailfrom.'>',
				'to'=>$toname.'<'.$to.'>',
				'subject'=>$subject,
				'html'=>$html,
				'text'=>$text,
				'o:tracking'=>'no',
				'o:tracking-clicks'=>'no',
				'o:tracking-opens'=>'no',
				'o:tag'=>$tag,
				'h:Reply-To'=>$replyto
		);
		$session = curl_init(MAILGUN_URL.'/messages');
		curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($session, CURLOPT_USERPWD, 'api:'.MAILGUN_KEY);
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_POSTFIELDS, $array_data);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_ENCODING, 'UTF-8');
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($session);
		curl_close($session);
		$results = json_decode($response, true);
		return $results;
	}
	/*/
	 |--------------------------------------------------------------------------
	 |String handleing 
	 |--------------------------------------------------------------------------
	 */
	function strBreakLines($string){
		return preg_split("/((\r(?!\n))|((?<!\r)\n)|(\r\n))/", $string);
	}
	function strCleanSpaces($string){
		return preg_replace('/\s+/', ' ',$string);
	}
	function strWordsArray($string){
		return explode(' ', $string);
	}
	function strPrep($string){
		$array = array();
		$space = $this->strCleanSpaces($string);

		$break = $this->strBreakLines($space);
		foreach ($break AS $key=>$line){
			$array[]= $this->strWordsArray($line);
		}
		return $array;
	}
	
	function requiredFieldsCheck(){
		// Compare requiredFields and fieldsArray
		foreach ($this->db->requiredFields as $field) {
    		if ($this->db->fieldsArray[$field] === null) {
        	// A required field is null, return false
        	return false;
    		}
		}

		// All required fields have values, return true or perform further actions
		return true;

	}
	//Get hostUserID from gameID
	function getHostUserID($gameID){
		$table = 'games';
		$where = array('id'=>$gameID);
		$game = $this->db->select(
			$table, 
			$where);
		// $game = $this->db->select($table,$where,array(),array(),$column,array(),true);
		
		if(isset($game['data'][0]['hostUserID']) && $game['data'][0]['hostUserID']>0){
			return $game['data'][0]['hostUserID'];
		}
		else {
			return 0;
		}
	}
//get gameID from gameCode
	function getGameIDFromGameCode($gameCode){
		// Prepare the condition to check for the game code in the Game table
		$where = array(
			'gameCode' => $gameCode,
		);
		// Perform a select query to fetch the records with the given game code
		$response = $this->db->select('games', $where);
		
		// Check if any records exist with the given game code
		if ($response['status'] == 'success'){
			return $response['data'][0]['id'];
		}
		else {
			return false;
		}
		
	}

	function checkGameStartTime($gameID){
		// Prepare the condition to check for the game code in the Game table
		$where = array(
			'id' => $gameID,
		);
		// Perform a select query to fetch the records with the given game code
		$response = $this->db->select('games', $where);
		$results = $response['data'];
		// Check if any records exist with the given game code
		if (count($results) > 0){

			return $results[0]['gameStartDateTime'];
		}
		else {
			return false;
		}
	}
	// function to check game status based on GameCode
	function checkGameStatusID($gameID){
		// Prepare the condition to check for the game code in the Game table
		$where = array(
			'id' => $gameID,
		);
		// Perform a select query to fetch the records with the given game code
		$response = $this->db->select('games', $where);
	
		if(!isset($response['data'])){
			return array('error' => 'No game for gameID: ' . $gameID. ' found.');	
		}
		else {
			$results = $response['data'];
		}
		// Check if any records exist with the given game code
		if (count($results) > 0){

			return $results[0]['gameStatusID'];
		}
		else {
			return false;
		}

	}
	// function to check if user exist
	function checkIfUserExists($userID){
		// Prepare the condition to check for the game code in the Game table
		$where = array(
			'id' => $userID,
		);
		// Perform a select query to fetch the records with the given game code
		$response = $this->db->select('users', $where);
		$results = $response['data'];
		// Check if any records exist with the given game code
		if (count($results) > 0){

			return true;
		}
		else {
			return false;
		}

	}
	// function to get game code from gameID
	function getGameCodeFromGameID($gameID){
		// Prepare the condition to check for the game code in the Game table
		$where = array(
			'id' => $gameID,
		);
		// Perform a select query to fetch the records with the given game code
		$response = $this->db->select('games', $where);
		$results = $response['data'];
		// Check if any records exist with the given game code
		if (count($results) > 0){

			return $results[0]['gameCode'];
		}
		else {
			return false;
		}
	}

	function getGameCodeFromUserXTargetID($targetID){
		$tables = array(
			'usersXtargets',
			'games',
		);
		// Prepare the condition to check for the game code in the Game table
		$where = array(
			'usersXtargets.id' => $targetID,
			'usersXtargets.gameID' => '`games`.`id`'
		);
		$columns = array(
			'games.gameCode'
		);
		// Perform a select query to fetch the records with the given game code
		$response = $this->db->select($tables, $where, null, null, $columns, null, false);
		// check for an error status
		if ($response['status'] == 'error') {
			return $response;
		}
		$results = $response['data'];
		// Check if any records exist with the given game code
		if (count($results) > 0){

			return $results[0]['gameCode'];
		}
		else {
			return false;
		}
	}

	//get UserID from phone
	function getUserIDFromPhone($phone){
		$table = 'users';
		$where = array('phone'=>$phone);
		$user = $this->db->select(
			$table, 
			$where);
		// $game = $this->db->select($table,$where,array(),array(),$column,array(),true);
		
		if(isset($user['data'][0]['id']) && $user['data'][0]['id']>0){
			return $user['data'][0]['id'];
		}
		else {
			return 0;
		}
	}

	public function createJWT($userArray = null){
		$key =  InMemory::base64Encoded(JWT_SECRET_KEY);

		//lookup the userID from the phone number
		if($userArray !== null){
			$userID = $this->getUserIDFromPhone($userArray['phone']);
		}
		else {
			$userID = $this->claimUserID; //this is used when generating a new token for a logged in user
		}
		
		$userRolesArray = $this->createUserRolesArray($userID);
		$currentGames = $this->getCurrentGames($userID);
		if($currentGames == false){
			$currentGames = array();
		}
		// print_r($currentGames);die();
		// var_dump($this->getCurrentGames($userID));die();
		$token = (new JwtFacade())->issue(
			new Sha256(),
			$key,
			static fn (
				Builder $builder,
				DateTimeImmutable $issuedAt
			): Builder => $builder
				->issuedBy('https://api.snapshotsnapshot.com')
				->permittedFor('https://api.snapshotsnapshot.com')
				->expiresAt($issuedAt->modify('+30 days'))
				//->expiresAt($issuedAt->modify('+2 days')) //testing only for creating expired token
				->withClaim('userID', $userArray['userID'] = $userID)
				->withClaim('userRoles', $userRolesArray)
				->withClaim('currentGames', $currentGames)


		);
		// foreach ($userArray AS $key=>$value){
		// 	$token = $token->withClaim($key, $value);
		// }
		//var_dump($token->claims()->all());
		//var_dump($token->claims()->get('exp'));
		$jwt = $token->toString();
		// print_r($jwt);die();
		return $jwt;
		
	}

	/**
	 * Description: Testing tool to create fake jwts. 
	 *	If a phone number is required (if initializing an instance of an authentications object, for example) the demo user account phone 
	 *  can be used (208)555-5555
	 * @param array $userArray
	 * The format to create a fake JWT is as follows:
	 * // Example of $userArray without expiration time
	 *		$userArray = [
	 *			'nbf' => 1708552972,
	 *			 'exp' => 2 //number of days till expire to allow for testing of expired tokens
	 *			'iss' => 'https://api.snapshotsnapshot.com',
	 *			'aud' => 'https://api.snapshotsnapshot.com',
	 *			'userID' => 1,
	 * 			'userRoles' => ['player'], //this will always be player, host is determined down below
	 *			'currentGames' => [
	 *				'joinedGames' => [
	 *					'Spectator' => [],
	 *					'Active Player' => [3, 4],
	 *					'Captured Player' => []
	 *				],
	 *				'hostedGames' => [3, 4]
	 *			]
	 *		];
	 * @param bool $isTestJWT
	 * @return string $token->toString()
	 */
	public function createFakeJWT($userArray = null, $isTestJWT = false) {
		// Use a dummy secret key for testing purposes
		$key = InMemory::base64Encoded(JWT_SECRET_KEY);
	
		// If user data is not provided, use default mock data
		if ($userArray === null) {
			$userArray = [
				'nbf' => 1708552972, // Example not before time
			    'exp' => 2, // Days till expire
				'iss' => 'https://api.snapshotsnapshot.com', // Example issuer
				'aud' => 'https://api.snapshotsnapshot.com', // Example audience
				'userID' => 1, // Example userID
				'userRoles' => ['player'], // Example user roles
				'currentGames' => [
					'joinedGames' => [
						'Spectator' => [],
						'Active Player' => [3, 4],
						'Captured Player' => []
					],
					'hostedGames' => [3, 4]
				] // Example current games
			];
		}
	
		// Add a custom claim to signify this JWT as a test JWT
		if ($isTestJWT) {
			$userArray['isTestJWT'] = true;
		}
	
		$token = (new JwtFacade())->issue(
			new Sha256(),
			$key,
			static function (
				Builder $builder,
				DateTimeImmutable $issuedAt
			) use ($userArray) {
				return $builder
					->issuedAt($issuedAt->setTimestamp($userArray['nbf']))
					->expiresAt($issuedAt->modify('+'.$userArray['exp']))
					->issuedBy($userArray['iss'])
					->permittedFor($userArray['aud'])
					->withClaim('userID', $userArray['userID'])
					->withClaim('userRoles', $userArray['userRoles'])
					->withClaim('currentGames', $userArray['currentGames'])
					->withClaim('isTestJWT', $userArray['isTestJWT'] ?? false);
			}
		);
	
		return $token->toString();
	}
	
	
	
/**
 * Description: Get the participant Types of a user in a game
 *
 * @param int $gameID
 * @param int $userID
 * @return mixed $participantTypeIDs array with participant type returned, false if not
 */
protected function getParticipantTypeIDs($gameID, $userID) {
	
	// Define your database query parameters here
	$tables = array(
		'gamesXusers'
	);
	$where = array(
		'userID' => $userID,
		'gameID' => $gameID
	);
	$columnsArray = array(
		'participantTypeID',
		
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
	 

	if ($result['status'] == 'success') {
		foreach($result['data'] as $key=>$value){
			$participantTypeIDs[] = $value['participantTypeID'];
		}
		
		return $participantTypeIDs;
	} else {
		return false;
	}

}

protected function getParticipantTypeNameByID($typeID){
	// Define your database query parameters here
	$tables = array(
		'participantTypes'
	);
	$where = array(
		'id' => $typeID,
		'active' => 1
	);
	$columnsArray = array(
		// Define your columns here
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
	
	if ($result['status'] == 'success') {
		return $result['data'][0]['participantTypeName'];
	} else {
		return false;
	}
}
	public function getCurrentGames($userID){
		// Initialize arrays for different participant types
		$spectatorGames = [];
		$activePlayerGames = [];
		$capturedPlayerGames = [];
		
		//since jwt is renewed on each game end, we don't need to check if user can host here. 
		$currentGamesByUserID = $this->getCurrentJoinedGames($userID);
		if ($currentGamesByUserID == false){
			return false;
		}
		foreach($currentGamesByUserID as $key=>$value){
			if($value['participantTypeID'] == PARTICIPANT_TYPE_SPECTATOR){
				$spectatorGames[] = $value['gameID'];
			}
			else if($value['participantTypeID'] == PARTICIPANT_TYPE_ACTIVE_PLAYER ){ 
				$activePlayerGames[] = $value['gameID']; 
			}
			else if ($value['participantTypeID'] == PARTICIPANT_TYPE_CAPTURED_PLAYER){
				$capturedPlayerGames[] = $value['gameID'];
			}
		}

		$currentHostedGames = $this->getCurrentHostedGames($userID);
		$currentGames = array(
			'joinedGames' => array(
				'Spectator' => $spectatorGames,
				'Active Player' => $activePlayerGames,
				'Captured Player' => $capturedPlayerGames
			),
			
			'hostedGames' => $currentHostedGames
			
		);
		return $currentGames;
	}

	/**
	 * Description: Get all games user has joined and is active in
	 *  Due to the possiblity of having 2 active participantTypes (active player & host)
	 *  participantTypeID will only pull values 1 - 3 (spectator, activePlayer, and capturedPlayer)
	 *  the hostUserID is already pulled from the query, so we don't need to pull it again
	 * 
	 * The participantTypeID only returning 1-3 will allow the frontend to check the player status of the user by game
	 *
	 * @param int $userID
	 * @return mixed array on success, false on failure
	 */
	public function getAllJoinedGames($userID){
		$joinedGames = array();

		
		$tables = array(
			'games',
			'participantTypes',
			'gamesXusers',
			'users'
		);
		$where = array(
			'`gamesXusers`.`userID`' => $userID,
			'`users`.`id`' => '`gamesXusers`.`userID`',
			'`gamesXusers`.`gameID`' => '`games`.`id`',
			'`participantTypes`.`id`' => '`gamesXusers`.`participantTypeID`',
			//'`gamesXusers`.`active`' => 1,
			'`games`.`active`' => 1,
			'`gamesXusers`.`participantTypeID`' => array(1,2,3), //In clause to only get these participant types,
			'`games`.`gameStatusID`' => array(GAME_STATUS_CREATED, GAME_STATUS_STARTED, GAME_STATUS_ENDED, GAME_STATUS_ONGOING) //In clause to only get these game statuses),

		);
		$columnsArray = array(
			//'`games`.`id`',
			'`gamesXusers`.`gameID`',
			'`gamesXusers`.`userID`',
			//'`users`.`id`',
			'`users`.`fName`',
			'`users`.`lName`',
			'`users`.`selfieSnapshotID`',
			'`gamesXusers`.`participantTypeID`',
			'`participantTypes`.`participantTypeName`',
			'`games`.`gameTypeID`',
			'`games`.`gameName`',
			'`games`.`gameCode`',
			'`games`.`gameStatusID`',
			'`games`.`gameStartDateTime`',
			'`games`.`gameEndDateTime`',
			'`games`.`hostUserID`'
			
			
		);

		$results = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'GROUP BY  `games`.`gameCode`', // $whereMore
			false, // $groupByColumn
			false,
			'all',
			null,
			'gameEndDateTime, gameStartDateTime',
			'DESC'
		);
	  //print_r($results);die('1345');
		if ($results['status'] == 'success') {
			return $results['data'];
		}
		else {
			return array('error' => $results);
		}
	
	 
	// if($results['status'] == 'success'){
	// 	$count = count($results['data']);
		
	// 	if($count == 1 ){
	// 		//launch game - if player status is captured player return error else return game info
	// 		if($results['data'][0]['participantTypeID'] == PARTICIPANT_TYPE_CAPTURED_PLAYER){
	// 			$currentGame = array('error' => "No games found for user $userID");
	// 		}
	// 		else {
	// 			$currentGame = $results['data'];
	// 		}
			
	// 	}
	// 	else {
	// 		foreach ($results['data'] as $result){	
	// 			if($result['gameStatusID'] != GAME_STATUS_ENDED && $result['gameStatusID'] != GAME_STATUS_ONGOING){
	// 				unset($result['availableItems']);
	// 				$currentGame =  array($result);
	// 				break;
	// 			}
	// 			else {
	// 				$currentGame = array($results['data'][0]);
	// 			}
	// 		}
	// 	}

	// }
	// else {
	// 	$currentGame = array('error' => "No games found for user $userID");
	// }
	//return $currentGame;
}

	
protected function addGamesXUsersRecordForHostAndParticipantType($gameID, $userID, $hostParticipantType){
	//add entry to the gamesXusers table
	$gamesXUsersID = $this->addGameXUsers($gameID, $userID, PARTICIPANT_TYPE_HOST); //ParticipantTypeID 3 = host
	if($gamesXUsersID == false){
		return array('error'=>__LINE__.": Could not add entry to gamesXusers table for Host"); // is it better to log this than return an error? What should we log and how?
	}
	//add entry to the gamesXusers table
	$gamesXUsersID = $this->addGameXUsers($gameID, $userID, $hostParticipantType); //ParticipantTypeID 3 = host
	if($gamesXUsersID == false){
		return array('error'=>__LINE__.": Could not add entry to gamesXusers table for Active Player"); // is it better to log this than return an error? What should we log and how?
	}

	return true;
}

/**
 * Description: GET user Role ID by Name
 *
 * @param string $roleName
 * @return mixed array of roleInfo on success, on failure false
 */
protected function getUserRoleIDByName($roleName) {
	$roleInfo = array();
	// Define your database query parameters here
	$tables = array(
		'userRoles'
	);
	$where = array(
		'roleName' => $roleName
	);
	
	// Call your database select method with the parameters
	$result = $this->db->select(
		$tables,
		$where,
		array(),    // $whereGreater
		array(),    // $whereLess
		array(), // $columnsArray
		'',         // $whereMore
		false       // $groupByColumn
	);
	if ($result['status'] != 'warning' && $result['status'] != 'error') {
		$roleInfo = $result['data'];
		
	} else {
		// If no IDs were found, set to an empty array
		 $roleInfo = false;
	}
	return $roleInfo;
}
/**
 * Description: Add an entry to the gamesXusers table
 *
 * @param int $gameID
 * @param int $userID
 *  @param int $participantTypeID
 * @return int $result['id'] of the inserted record on success or false if failed
 */
public function addGameXUsers($gameID, $userID, $particpantTypeID) {
	$userIDs = array();
	// Define your database table here
	$table = 'gamesXusers';
	$this->db->fieldsArray = array(
		'userID' => $userID,
		'gameID' => $gameID,
		'participantTypeID' => $particpantTypeID,
		'active' => 1
	);
	// Call your database insert method with the parameters
	$result = $this->db->insert(
		$table,
		$this->db->fieldsArray
	);
	if(isset($result['id'])) {
		return $result['id'];
	}
	else {
		return false;
	}
}

/**
 * Description: Get the gameID and participantTypeID for all the games the user is in
 *
 * @param int $userID
 * @return mixed $results['data'] array of gameID's and participantTypeID's that the user is in, if none $results will be false
 */
 
	public function getCurrentJoinedGames($userID){


		//get the games the user is in with the gameStatusID = created, started or ongoing
		$tables = array(
			'gamesXusers',
			'games'
		);
		$where = array(
			'`gamesXusers`.`userID`' => $userID,
			'`gamesXusers`.`gameID`' => '`games`.`id`',
			'`games`.`gameStatusID`' => array(GAME_STATUS_CREATED, GAME_STATUS_STARTED, GAME_STATUS_ONGOING), //this is an IN clause
			'`gamesXusers`.`active`' => 1 //put back in live

		);
		$columnsArray = array(
			'`gamesXusers`.`id` as gamesXUserID',
			'`gamesXusers`.`gameID`',
			'`gamesXusers`.`participantTypeID`',
			 '`games`.`gameCode`',
			// '`games`.`gameName`',
			// '`games`.`gameStartDateTime`',
			// '`games`.`gameStatusID`',
			// '`games`.`hostUserID`',
		);
		$whereMore = 'AND participantTypeID < 4'; //don't return host level or above (just less than 4)

		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			$whereMore, // $whereMore
			false // $groupByColumn
		);

	
		//create an array [joinedGames] => all gameID's the user is in
		if($result['status'] == 'success'){
			return $result['data'];
		}
		else {
			return false;
		}
		
	}
	/**
	 * Description: Get all games that the user is hosting
	 *
	 * @param int $userID
	 * @return array $hostedGames array of gameID's that the user is hosting, if none array will be empty
	 */
	public function getCurrentHostedGames($userID){
		$hostedGames = array();

		$tables = array(
			'games',
			'users',
			'gamesXusers'
		);

		$where = array(
			'`gamesXusers`.`userID`' => '`users`.`id`',
			'`gamesXusers`.`gameID`' => '`games`.`id`',
			'`games`.`hostUserID`' => $userID,
			'`games`.`gameStatusID`' => array(GAME_STATUS_CREATED, GAME_STATUS_STARTED) //this is an IN clause

		);

		$columnsArray = array(
			'`games`.`id`',
			// '`games`.`gameCode`',
			// '`games`.`gameName`',
			// '`games`.`gameStartDateTime`',
			// '`games`.`gameStatusID`',
			// '`games`.`hostUserID`',
		);

		//set variable $wheremore to sql clause to group by games.id only returning distinct id's
		//$whereMore = 'GROUP BY `games`.`id`';
		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'GROUP BY  `games`.`id`', // $whereMore
			false // $groupByColumn
		);
		
		foreach ($result['data'] as $item) {
			foreach ($item as $key => $value) {
				if ($key === 'id') {
					$hostedGames[] = $value;
				}
			}
		}
		return $hostedGames;
	}

	/**
	 * Description: Get all gameIDs where the user is a spectator
	 *
	 * @param int $userID
	 * @return mixed $result['data'] array of gameID's that the user is spectating, if none array will be null
	 */
	protected function getGameIDsWhereUserIsSpectator($userID) {
		$joinedGames = array();
		// Define your database query parameters here
		$tables = array(
			'gamesXspectators'
		);
		$where = array(
			'userID' => $userID
		);
		$columnsArray = array(
			'gameID',
			''
		);
		// Call your database select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // whereGreater
			array(),    // whereLess
			$columnsArray, // columnsArray
			'',         // whereMore
			false       // groupByColumn
		);
		if(isset($result['data']) && !empty($result['data'])){
			foreach ($result['data'] as $item) {
				foreach ($item as $key => $value) {
					if ($key === 'id') {
						$joinedGames[] = $value;
					}
				}
			}
		}
		
		return $joinedGames;
		
	}
	

	/*/
 |--------------------------------------------------------------------------
 |        CREATE USER ROLES ARRAY structure
 |			array(
					'roles' => {$role1, $role2, $role3},
			)
 |--------------------------------------------------------------------------
 */
	public function createUserRolesArray($userID){
		$tables = array(
			'userXuserRoles',
			'userRoles',
			'users'
		);

		$where = array(
			
		'`userXuserRoles`.`userID`' =>'`users`.`id`',
		'`userRoles`.`id`' => '`userXuserRoles`.`userRoleID`',
		'`users`.`id`' => $userID
		);

		$columnsArray = array(
			'`userXuserRoles`.`id`',
			'`userRoles`.`roleName`',
			'`userRoles`.`description`',
			'`userXuserRoles`.`userID`',
			'`userXuserRoles`.`userRoleID`'
		);

		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			false // $groupByColumn
			// false,
			// null.
			// null,
			// 'userXuserRoles.userID'
		);

		$userRoleArray = array();
		foreach($result['data'] as $key => $value){
			$userRoleArray[$key] = $value['roleName'];
		}
		
		return $userRoleArray;
	}


	/**
	 * Description: Get all target ID's for the User
	 * 	target ID's could be a user, or an item
	 * 	target ID's are used to determine what data the user has access to
	 *
	 * @param int $userID the userID to get target ID's for
	 * @return array $targetIDs on success, false on failure
	 */
	public function getUserTargetIDs($userID)
	{
		$targetIDs = array();
		//pull user data from db by id
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'userID' => $userID
		);
		$columnsArray = array(
			'`usersXtargets`.`targetID`',
			
		);

		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			false // $groupByColumn
			
		);
		if(($result['status'] != "warning" && $result['status'] != "error")){
			foreach($result['data'] as $key => $value){
				$targetIDs[] = $value['targetID'];
			}
		}
		else { //if no roles were found, return false
			$targetIDs = false;
		}
	// print_r($targetIDs);die();
		return  $targetIDs;
	}	
	/**
	 * Description: Get the snapshot url by snapshotID
	 *
	 * @param int $snapshotID
	 * @return mixed $snapshotURL string url on success, false on failure
	 */
	protected function getSnapshotURLBySnapshotID($snapshotID)
	{
		$snapshotURL = array();
		//pull user data from db by id
		$tables = array(
			'snapshots'
		);
		$where = array(
			'id' => $snapshotID
		);
		$columnsArray = array(
			'`snapshots`.`fileURI`',
			
		);

		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			false // $groupByColumn
			
		);
		if(($result['status'] != "warning" && $result['status'] != "error")){
			$snapshotURL = $result['data'][0]['fileURI'];
		}
		else { 
			$snapshotURL = false;
		}
		
		return  $snapshotURL;
	}	
	/**
	 * Description: Get the snapshot url by snapshotID
	 *
	 * @param int $snapshotID
	 * @return mixed $snapshotURL string url on success, false on failure
	 */
	protected function getSnapshotURLByAsignedItemID($snapshotID)
	{
		$snapshotURL = array();
		//pull user data from db by id
		$tables = array(
			'snapshots'
		);
		$where = array(
			'id' => $snapshotID
		);
		$columnsArray = array(
			'`snapshots`.`fileURI`',
			
		);

		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			false // $groupByColumn
			
		);
		if(($result['status'] != "warning" && $result['status'] != "error")){
			$snapshotURL = $result['data'][0]['fileURI'];
		}
		else { 
			$snapshotURL = false;
		}
		
		return  $snapshotURL;
	}	

	//generate method that gets all unique gameID's associate with userID
	public function getDistinctIDsForEachGameIDByUserID($userID){ //how to get distinct? 
		try {
			// get all the distinct ID's in each game related to the userID
			$sql = "
			SELECT g.gameID, GROUP_CONCAT(DISTINCT gu.userID) AS userIDs
			FROM (
				SELECT DISTINCT gameID
				FROM gamesXusers
				WHERE userID = :userID
			) AS g
			JOIN gamesXusers gu ON g.gameID = gu.gameID
			GROUP BY g.gameID;
				";
				$resultData = $this->db->prepareAndExecuteFetchAll($sql, $userID, ':userID');

				//format so array is gameID => userID's
				$relatedUserIDs = array();
				foreach ($resultData as $key => $value) {
					foreach($value as $key => $value)
					if($key == 'gameID'){
						$gameID = $value;
						$relatedUserIDs[$gameID] = array();
					}
					elseif($key == 'userIDs'){
						$userIDs = $value;
						$relatedUserIDs[$gameID] = explode(',', $userIDs);
					}
				}
				 
				//check if any roles were found
				if (empty($resultData)) {
					$response['status'] = "warning";
					$response['message'] = "No related Users were found for the user.";
					$response['data'] = null;
				} else {
					$response['status'] = "success";
					$response['message'] = "Users for all gameID's associated with userID selected from the database.";
					$response['data'] = $relatedUserIDs;
				}
			} catch (PDOException $e) {
				$response['status'] = "error";
				$response['message'] = 'Select Failed: ' . $e->getMessage();
				$response['data'] = null;
			}
		
			return $response['data'];
		}

		/**
		 * Description: Set a user's role in db in userXuserRoles table
		 *
		 * @param $userID (int), $userRoleID (int) (free/player = 1, userRoleID/host = 2)
		 * @return mixed int $result['id'] if exists, false if not
		 */
	public function setUserRole( $userID, $userRoleID){
		$userIDs = array();
		//pull user data from db by id
		//insert($table, $columnsArray, $requiredFieldsArray = array())
		$tables = 'userXuserRoles';
	
		$this->db->fieldsArray = array(
			'userID' => $userID,
			'userRoleID' => $userRoleID,
			'active'=> 1
		);
		
		// Call your existing select method with the parameters
		$result = $this->db->insert(
			$tables,
			$this->db->fieldsArray,
			// $this->db->requiredFieldsArray
		);
		
		if(isset($result['id'])) {
			return $result['id'];
		}
		else {
			return false;
		}
	}	
	
	/**
	 * Description: Check if a user and game are related in db
	 *
	 * @param int $gameID
	 * @param int $userID
	 * @return mixed array ('status'=>active,
	 * 						id=> $result['data'][0]['id']) if exists & is active,			 
	 * 				 false if record does not exist
	 */
	protected function gamesXUsersRecordExists($gameID, $userID){
		$tables = array(
			'gamesXusers'
		);
		$where = array(
			'userID' => $userID,
			'gameID' => $gameID,
			'active' => 1
			
		);
		$columnsArray = array(
			'`gamesXusers`.`id`',
			
		);

		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			false, // $groupByColumn
			
		);
		//   print_r($result);die();
		if($result['status'] == 'success') {
			
			return ['status' => 'active', 'id' => $result['data'][0]['id']];
			
		} else {
			// Record does not exist
			return false;
		}
		
	}
	
	/**
	 * Description: Check if a user has a specific role in db
	 *
	 * @param $id, $userRoleID (free = 1, userRoleID = 2)
	 * @return mixed array ('status'=>active,
	 * 						id=> $result['data'][0]['id']) if exists & is active,
	 * 				 array ('status'=>not_active,
	 * 						id=> $result['data'][0]['id']) if exists & is not active,						 
	 * 				 false if record does not exist
	 */
	protected function userRoleRecordExists($id, $userRoleID){
		$tables = array(
			'userXuserRoles'
		);
		$where = array(
			'userID' => $id,
			'userRoleID' => $userRoleID,
			
		);
		$columnsArray = array(
			'`userXuserRoles`.`id`',
			
		);

		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			false, // $groupByColumn
			true //just return the data
		);
		// print_r($result);die();
		if(isset($result[0]['id'])) {
			// Check if the record is active (assuming 'active' is a boolean field)
			if(isset($result[0]['active']) && $result[0]['active']) {
				// Record exists and is active
				return ['status' => 'active', 'id' => $result[0]['id']];
			} else {
				// Record exists but is not active
				return ['status' => 'not_active', 'id' => $result[0]['id']];
			}
		} else {
			// Record does not exist
			return false;
		}
		
	}
	/********************************************************************************
	 * 
	 * 						Google Play Store Connection And Verification Functions
	 * 
	 ******************************************************************************/
private function initializePlayStoreVerification() {
	putenv('GOOGLE_APPLICATION_CREDENTIALS=v2\oauthCredentials\playstoreJsonKey.json');
	
	// Scopes for Play Store
	$scopes = [
		'https://www.googleapis.com/auth/androidpublisher'
	];

	$this->middleware = ApplicationDefaultCredentials::getMiddleware($scopes);
	
}


/********************************************************************************
 * 
 * 						Firebase Messaging Functions
 * 
 ******************************************************************************/
	 private function initializeFirebaseBatchMessaging() {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=v2\oauthCredentials\firebaseJsonKey.json');

        // Scopes for FCM
        $scopes = [
            'https://www.googleapis.com/auth/cloud-platform',
            'https://www.googleapis.com/auth/firebase.messaging',
        ];

        $this->middleware = ApplicationDefaultCredentials::getMiddleware($scopes);
        
    }

	// protected function sendNotificationsAsync(Client $client, array $deviceTokens, array $notificationDetails) {
	// 	$messages = [];
	
	// 	foreach ($deviceTokens as $token) {
	// 		$message = [
	// 			'token' => $token,
	// 			'notification' => [
	// 				'title' => $notificationDetails['title'],
	// 				'body' => $notificationDetails['body'],
	// 				'image' => $notificationDetails['image'],
	// 			],
	// 			'webpush' => [
	// 				'fcm_options' => [
	// 					'link' => $notificationDetails['link']
	// 				],
	// 			],
	// 		];
	// 		$messages[] = $message;
	// 	}
	
	// 	$requests = [];
	
	// 	foreach ($messages as $message) {
	// 		$request = new Request(
	// 			'POST',
	// 			'https://fcm.googleapis.com/v1/projects/snapshot-play-bb404/messages:send',
	// 			['Content-Type' => 'application/json'],
	// 			json_encode(['message' => $message])
	// 		);
	// 		$requests[] = $client->sendAsync($request);
	// 	}
	
	// 	Promise\settle($requests)->wait();
	// 	// Promise\all($requests)->then(function ($responses) {
	// 	// 	foreach ($responses as $response) {
	// 	// 		echo $response->getBody();
	// 	// 	}
	// 	// })->wait();
	// }
	protected function getPlayStoreOAUTHToken(){
		$filePath = __DIR__ . '/oauthCredentials/playstoreJsonKey.json';

		$client = new Google_Client();
		try {
			$client->setAuthConfig($filePath);
			$client->setScopes(['https://www.googleapis.com/auth/androidpublisher']);

			// retrieve the saved oauth token if it exists, you can save it on your database or in a secure place on your server
			$savedToken = $this->getSavedToken("oauthTokenFirebase");

			if ($savedToken != null) {
				// the token exists, set it to the client and check if it's still valid
				$client->setAccessToken($savedToken);
				if ($client->isAccessTokenExpired()) {
					// the token is expired, generate a new token and set it to the client
					$accessToken = $this->generateToken($client);
					$client->setAccessToken($accessToken);
				}
			} else {
				// the token doesn't exist, generate a new token and set it to the client
				$accessToken = $this->generateToken($client);
				$client->setAccessToken($accessToken);
			}

			$oauthToken = $accessToken;
			$this->saveToken($oauthToken);
	
			return $oauthToken;

			// the client is configured, now you can send the push notification using the $oauthToken.

		} catch (Google_Exception $e) {
			// handle exception
			$errorMsg = "Oops! There was an error sending a notification," . $e->getMessage();
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
	}	
		/**
		 * This uses Google API PHP CLIENT to get the OAUTH token for firebase messaging
		 * Description: Get the OAUTH token for firebase messaging
		 * Found using the following link: https://stackoverflow.com/questions/49782134/how-to-use-the-fcm-http-v1-api-with-php
		 *
		 * @param 
		 * @return 
		 */
	protected function getOAUTHToken(){
		$filePath = __DIR__ . '/oauthCredentials/firebaseJsonKey.json';

		$client = new Google_Client();
		try {
			$client->setAuthConfig($filePath);
			//$client->setAuthConfig("oauthCredentials/firebaseJsonKey.json");
			$client->addScope(Google_Service_FirebaseCloudMessaging::FIREBASE_MESSAGING);

			// retrieve the saved oauth token if it exists, you can save it on your database or in a secure place on your server
			$savedToken = $this->getSavedToken("oauthTokenFirebase");

			if ($savedToken != null) {
				// the token exists, set it to the client and check if it's still valid
				$client->setAccessToken($savedToken);
				if ($client->isAccessTokenExpired()) {
					// the token is expired, generate a new token and set it to the client
					$accessToken = $this->generateToken($client);
					$client->setAccessToken($accessToken);
				}
			} else {
				// the token doesn't exist, generate a new token and set it to the client
				$accessToken = $this->generateToken($client);
				$client->setAccessToken($accessToken);
			}

			$oauthToken = $accessToken;
			$this->saveToken($oauthToken);
	
			return $oauthToken;

			// the client is configured, now you can send the push notification using the $oauthToken.

		} catch (Google_Exception $e) {
			// handle exception
			$errorMsg = "Oops! There was an error sending a notification," . $e->getMessage();
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
	}	

	private function generateToken($client)
	{
		$client->fetchAccessTokenWithAssertion();
		$accessToken = $client->getAccessToken();
		
		// save the oauth token json on your database or in a secure place on your server
		//$tokenJson = json_encode($access_token);
		$this->saveToken($accessToken['access_token']);
		
		return $accessToken['access_token'];
	}
	protected function saveToken($token){
		
		$tokenExists = $this->tokenExists('oauthTokenFirebase');
	
		if($tokenExists == true){
			$result = $this->updateToken($token);
		}
		else {
			$result = $this->insertToken($token);
		}
		return $result;
	}

	protected function insertToken($token){
		$table = 'configurations';
		$columnsArray = array(
			'keyName' => 'oauthTokenFirebase',
			'value' => $token
		);
		$fieldsArray = array(
			'keyName',
			'value'
		);

		$result = $this->db->insert(
			$table,
			$columnsArray,
			$fieldsArray

		);
			
		return $result;
		
	}

	protected function tokenExists($key){
		$table = 'configurations';
		$where = array(
			'keyName' => $key
		);
		$columnsArray = array(
			'value'
		);
		$results = $this->db->select(
			$table,
			$where,
			array(),    // whereGreater
			array(),    // whereLess
			$columnsArray, // columnsArray
			'',         // whereMore
			false       // groupByColumn
		);
	
		return $results['status'] == 'success' ? true : false;
	}

	protected function updateToken($tokenJson){
		$table = 'configurations';
		$where = array(
			'keyName' => 'oauthTokenFirebase'
		);
		$columnsArray = array(
			'value' => $tokenJson
		);
		$result = $this->db->update(
			$table,
			$where,
			$columnsArray
		);
		
		return $result;
		
	}
	function sendNotificationWithImageNoStatus($token, $title, $body, $accessToken, $image) {

		$payload = ["message" => ["token" => $token, "data"=>["image" => $image], "notification"=>["title" => $title, "body"=> $body]]];
	
		$postdata = json_encode($payload);
		
		$opts = array('http' =>
			array(
				'method'  => 'POST',
				'header'  => 'Content-Type: application/json' . "\r\nAuthorization: Bearer $accessToken",
				'content' => $postdata
			)
		);
	
		$context  = stream_context_create($opts);
	
		$result = file_get_contents('https://fcm.googleapis.com/v1/projects/snapshot-play-bb404/messages:send', false, $context);
	
		return $result;
	
	}
	// function sendNotification($token, $title, $body, $accessToken, $status = null, $image = null, $icon = null) {
	function sendNotification($token, $title, $body, $accessToken, $status = null, $image = null) {

		$url = 'https://fcm.googleapis.com/v1/projects/snapshot-play-bb404/messages:send';
		//$accessToken = 'YOUR_FCM_ACCESS_TOKEN';

	$message = [
		'message' => [
			//'token' => 'f29JnxghSY2tN7p_BXYAm9:APA91bF-gjBSk5J1EgdSb3_Cdrg-9lNeVPJRLDhlnDmyU9PzC1uvFIKSvlYQ8SjM5WzfyjcipVFPDRC7UlMsrwlVb9Ck7TLnN3uvd9JuHqunjev-xs549AO5s7xlPB-8WIoAbwTkL-oD',
			'token' => $token,
			'notification' => [
				'title' => $title,
				'body' => $body,
				'image' => $image,
			],
			'data' => [
				'isweb' => 'True',
				'image' => $image,
				'weburl' => 'https://www.google.com/',
			],
			'apns' => [
				'payload' => [
					'aps' => [
						'mutable-content' => 1,
					],
				],
				'fcmOptions' => [
					'image' => $image,
				],
			],
			'android' => [
				'notification' => [
					'image' => $image,
				],
			],
		],
	];

	$options = [
		'http' => [
			'header' => "Content-type: application/json\r\n" .
						"Authorization: Bearer $accessToken\r\n",
			'method' => 'POST',
			'content' => json_encode($message),
		],
	];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
header("Content-Type: application/json");
echo json_encode([
    "Line " . __LINE__ => __FILE__,
    'function' => debug_backtrace()[1]['function'] ?? 'N/A',
    "zzReturn" => $context,
]);
die();
if ($result === false) {
    $error = error_get_last();
    echo "HTTP request failed: " . $error['message'];
}
		return $result;
	
	}

	protected function getSavedToken($key){
		$table = 'configurations';
		$where = array(
			'keyName' => $key
		);
		$columnsArray = array(
			'value'
		);
		$result = $this->db->select(
			$table,
			$where,
			array(),    // whereGreater
			array(),    // whereLess
			$columnsArray, // columnsArray
			'',         // whereMore
			false       // groupByColumn
		);
		if(isset($result['data'][0]['value'])){
			
			return $result['data'][0]['value'];
		}
		else {
			
			return null;
		}
		
	}
	protected function prepareAndSendNotificationToMultipleNumbers($recipientNumberArray, $title, $body, $status, $image){
		$accessToken = $this->getOAUTHToken();
		//$phoneNumberArray = array('(208)761-8466', '(208)921-0129', '(208)914-1277');
		
		//get each token for each user
		$result = "";
		foreach($recipientNumberArray as $phone){
			$userID = $this->getUserIDFromPhone($phone);
			$deviceTokens[$userID] = $this->getUserDeviceToken($userID);
			
			$result = $this->sendNotification($deviceTokens[$userID], $title, $body, $accessToken, $status, $image);
		}
		return $result;
	}
		
	
	protected function prepareAndSendNotification($userID, $title, $body, $status, $image){
		$accessToken = $this->getOAUTHToken();
		
		$token = $this->getUserDeviceToken($userID);
		
		//working token
		//$token = 'f29JnxghSY2tN7p_BXYAm9:APA91bF-gjBSk5J1EgdSb3_Cdrg-9lNeVPJRLDhlnDmyU9PzC1uvFIKSvlYQ8SjM5WzfyjcipVFPDRC7UlMsrwlVb9Ck7TLnN3uvd9JuHqunjev-xs549AO5s7xlPB-8WIoAbwTkL-oD';
		
		//$result = $this->sendNotification($token, $title, $body, $accessToken, $status, $image, $icon); //implement once GD added
		$result = $this->sendNotification($token, $title, $body, $accessToken, $status, $image);
		
		return $result;
	}

			
	function sendBatchNotification($tokens, $title, $body, $accessToken) {

		$boundary = "--subrequest_boundary";
		$multiPayload = $boundary;

		foreach ($tokens as $token) {
			$head = "Content-Type: application/http\r\n".
					"Content-Transfer-Encoding: binary\r\n\r\n".
					"POST /v1/projects/snapshot-play-bb404/messages:send\r\n".
					"Content-Type: application/json\r\n".
					"accept: application/json\r\n\r\n";
			
			$payload = ["message" => ["token" => $token, "notification"=>["title" => $title, "body"=> $body]]];

			$postdata = json_encode($payload);
			$multiPayload .= "\r\n".$head.$postdata."\r\n".$boundary;
			
		}
		
		$multiPayload .= "--";
		

		//Uncomment for debugging
		//echo "\n\n==== POST DATA====\n\n";
		//echo $multiPayload;


		$opts = array('http' =>
			array(
				'method'  => 'POST',
				'header'  => 'Content-Type: multipart/mixed; boundary="subrequest_boundary"' . "\r\nAuthorization: Bearer $accessToken",
				'content' => $multiPayload
			)
		);

		//Uncomment for debugging
		//echo "\n\n==== HTTP OPTIONS====\n\n";
		//var_dump($opts);

		$context  = stream_context_create($opts);


		// This is the path for sending push multiple tokens (upto 500 as per the docs)
		$result = file_get_contents('https://fcm.googleapis.com/batch', false, $context);

		//echo "\n\n======RESPONSE======\n\n";
		return $result;

	}
	protected function prepareAndSendBatchNotification($title, $body, $tokenArray){
		$accessToken = $this->getOAUTHToken();
		$result = $this->sendBatchNotification($tokenArray, $title, $body, $accessToken);
		return $result;
	}
	protected function getUserDeviceToken($userID){
		$table = 'devices';
		$where = array(
			'userID' => $userID
		);
		$columnsArray = array(
			'deviceToken'
		);
		$result = $this->db->select(
			$table,
			$where,
			array(),    // whereGreater
			array(),    // whereLess
			$columnsArray, // columnsArray
			'',         // whereMore
			false       // groupByColumn
		);
		if(isset($result['data'][0]['deviceToken'])){
			
			return $result['data'][0]['deviceToken'];
		}
		else {
			
			return null;
		}
		
	
	}


	//create method that sets $requiredRolesArray based on HTTP method and corresponding endpoint (as defined in config.php)
	// public function defineRequiredRolesArray(){
		
	// 	$method = $_SERVER['REQUEST_METHOD'];
		
	// 	//create a switch case where requiredRoles is set based on the endpoint and the method
	// 	switch ($method) {
	// 		case 'GET':
	// 			$requiredRoles = ENDPOINT_ACCESS_READ_LEVELS[lcfirst(ENDPOINT)];
	// 			break;
	// 		case 'POST':
	// 			$requiredRoles = ENDPOINT_ACCESS_CREATE_LEVELS[lcfirst(ENDPOINT)];
	// 			break;
	// 		case 'PUT':
	// 			$requiredRoles = ENDPOINT_ACCESS_UPDATE_LEVELS[lcfirst(ENDPOINT)];
	// 			break;
	// 		case 'DELETE':
	// 			$requiredRoles = ENDPOINT_ACCESS_DELETE_LEVELS[lcfirst(ENDPOINT)];
	// 			break;
	// 		case 'OPTIONS':
	// 			$requiredRoles = ENDPOINT_ACCESS_OPTIONS_LEVELS[lcfirst(ENDPOINT)];
	// 			break;
	// 		default:
	// 		http_response_code(401);
	// 		$debugInfo = array(
	// 			'error' => __LINE__.": Issue defining endpoint requiredRoles based on method and endpoint",
	// 			'params' => $this->params,
	// 			'method' => $method,
	// 			'endpoint' => ENDPOINT,
	// 			'authJwtString' => $this->authJWTstring
	// 		);
	// 		$errorMsg = "Oops! There was an issue grabbing the requiredRoles for this endpoint. Please try again.";
	// 		$navigation = null;
	// 		$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
	// 		return $response;
			
	// 	}
	// 	return $requiredRoles;
	// }

	// public function getUserAllowedEndpointPermissions(){
	
	// 	//define requiredRolesArray
	// 	$requiredRolesArray = $this->defineRequiredRolesArray();
	// 	$permissions = array();
	// 	if (in_array('all', $requiredRolesArray)) {
	// 		 $permissions = array('all');
	// 	}
	// 	else if(in_array('none', $requiredRolesArray)){
	// 		 $permissions = array('none');
	// 	}
	// 	else if(in_array('system', $requiredRolesArray)){
	// 		$permissions = array('system');
	// 	}
	// 	else {

	// 		foreach ($this->userRoles as $key => $value) {
	// 			//loop over each userRole 
			
	// 			if (in_array($value, $requiredRolesArray)) {
	// 				$permissions[] = $value;
	// 			}
	// 		}
	// 	}
	// 	//print_r(array("permissions"=>$permissions, "requiredRoles"=>$requiredRolesArray)); die('2796');
	// 	return $permissions;
		
	// }
/**
 * Description: Get only user data needed for other users to see
 *
 * @param int $userID
 * @return mixed array on success, false on failure
 */
public function getLimitedUserDetailByID($userID){
	$userIDs = array();
	//pull user data from db by id
	$tables = array(
		'users',
		'snapshots'
	);
	$where = array(
		'users.id' => $userID,
		'users.selfieSnapshotID' => '`snapshots`.`id`'
	);
	$columnsArray = array(
		'`users`.`id`',
		'`users`.`fName`',
		'`users`.`lName`',
		'`snapshots`.`fileURI`',
		
		
	);
	// Call your existing select method with the parameters
	$result = $this->db->select(
		$tables,
		$where,
		array(), // $whereGreater
		array(), // $whereLess
		$columnsArray, // $columnsArray
		'', // $whereMore
		false, // $groupByColumn
		false, // when true, it is returning each column as its own array
	);
	unset($result['data'][0]['availableItems']); //don't want to return this
	 
	//print_r($result);die();
	 if($result['status'] != "success"){
		$result = false;
	 }
	 else{
		return $result['data'][0];
	 }
}
	/**
	 * Description: For each game a user is in, it pulls all the userIDs associated with that game
	 *
	 * @param int $userID
	 * @return mixed $relatedInfo array of all users in games associated with user, or false on failure
	 * 				To access the data, use $relatedInfo[0]['nameofcolumn'] in calling method
	 */
	protected function getUniqueUserInfoRelatedToUserID($userID){
		$relatedInfo = array();

		$tables = array(
			"gamesXusers",
			"games",
			"users",
			"participantTypes",
			"snapshots"
		);
		
		$where = array(
			"gamesXusers.userID" => "`users`.`id`",
			"gamesXusers.gameID" => "`games`.`id`",
			"gamesXusers.participantTypeID" => "`participantTypes`.`id`",
			"users.selfieSnapshotID" => "`snapshots`.`id`",
			"gamesXusers.active" => 1,
			"games.active" => 1,
			"users.active" => 1,
			"snapshots.active" => 1
			
		);
		
		 $whereMore = "AND gamesXusers.gameID IN (SELECT gameID FROM gamesXusers WHERE userID = $userID)";
			
			// "group_by" => "userID",
			// "limit" => [0, 500]
		
		
		$columnsArray = array(
			"gamesXusers.userID",
			"gamesXusers.gameID",
			"games.gameName",
			"games.gameTypeID",
			"games.gameCode",
			"games.gameStatusID",
			"games.gameStartDateTime",
			"participantTypes.participantTypeName",
			"users.fName",
			"users.lName",
			"snapshots.fileURI AS userSnapshot"
		);
		
		$justData = false;
		
		$results = $this->db->select($tables, $where, array(), array(), $columnsArray,$whereMore, false, $justData);
		
		$this->mainQuery = $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;
		// Call your database select method with the parameters
		// $result = $this->db->select(
		// 	$tables,
		// 	array(),
		// 	array(),    // $whereGreater
		// 	array(),    // $whereLess
		// 	$columnsArray, // $columnsArray
		// 	$whereMore,         // $whereMore
		// 	false,       // $groupByColumn
			
		// );
		//  print_r($results); die();
		if ($results['status'] != 'warning' && $results['status'] != 'error') {
			// foreach ($result['data'] as $row) {
			// 	if (isset($row[''])) {
					$relatedInfo = $results['data'];
			// 	}
			// }
		} else {
			// If no IDs were found, set to an empty array
			$relatedInfo = false;
		}
		  //print_r($relatedInfo); die();
		return $relatedInfo;
	}
	
	/**
	 * Description: get Game Type Name by Game Type ID
	 *
	 * @param int gameTypeID
	 * @return mixed $gameTypeName on success, false on failure
	 */
	protected function getGameTypeNameByGameTypeID($gameTypeID) {
		$gameTypeName = array();
		// Define your database query parameters here
		$tables = array(
			'gameTypes'
		);
		$where = array(
			'id' => $gameTypeID
		);
		$columnsArray = array(
			'`gameTypeName`'
		);
		// Call your database select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			$columnsArray, // $columnsArray
			'',         // $whereMore
			false,      // $groupByColumn
			true 		//just data
		);
		//   print_r($result); die();
		if (isset($result[0] ) && !empty($result[0])) {
			return  $result[0]['gameTypeName'];
		} else {
			return false;
		}
		
	}
	public function getUniqueUserIdsRelatedToHostID($hostID){
		$userIDs = array();
		try {
			// get all the distinct ID's in each game related to the userID
			$sql = "
			SELECT DISTINCT gu.userID
			FROM gamesXusers gu
			JOIN games g ON gu.gameID = g.id
			WHERE g.hostUserID = :hostID
				";
				$resultData = $this->db->prepareAndExecuteFetchAll($sql, $hostID, ":hostID");
				// var_dump($resultData); die();		 
				//check if any roles were found
				if (empty($resultData)) {
					$response['status'] = "warning";
					$response['message'] = "No related Users were found for the user.";
					$userIDs = [];
				} else {
					$response['status'] = "success";
					$response['message'] = "Users for all gameID's associated with userID selected from the database.";
					$response['data'] = $resultData;
				}
			} catch (PDOException $e) {
				$response['status'] = "error";
				$response['message'] = 'Select Failed: ' . $e->getMessage();
				$response['data'] = null;
			}
		
			if(empty($reponse['message'])){
				foreach ($response['data'] as $key => $value) {
					foreach($value as $key => $value){
						if($key == 'userID'){
							$userIDs[] = $value;
						}
					}
				}
			}
			// print_r($userIDs); die();
			return $userIDs;
	}
/**
 * Description: Get all targets associated with a user
 *
 * @param int $userID
 * @return $targetDetail array on success, false on failure
 */
	public function GetUserTargets($userID){
		$targetDetail = array();
		$tables = array(
			'usersXtargets',
			'targets',
			'targetStatuses',
			'targetTypes',
			'games'
		);
		$where = array(
			'userID' => $userID,
			'usersXtargets.targetID' => '`targets`.`id`',
			'usersXtargets.targetStatusID' => '`targetStatuses`.`id`',
			'targets.targetTypeID' => '`targetTypes`.`id`',
			'targets.active' => 1,
			'usersXtargets.active' => 1,
			'targetStatuses.id' => array(1,8),
			'usersXtargets.gameID' => '`games`.`id`',
			
		);
		$columnsArray = array(
			'`usersXtargets`.`id`',
			'`usersXtargets`.`userID`',
			'`usersXtargets`.`targetID`',
			'`targets`.`targetName`',
			'`targetTypes`.`targetTypeName`',
			'`targets`.`targetSnapshotURI`',
			'`usersXtargets`.`gameID`',
			'`games`.`gameCode`',
			'`usersXtargets`.`startDateTime`',
			' `usersXtargets`.`closedDateTime`',
			'`targetStatuses`.`targetStatusName`'
			
		);
		// Call your database select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			$columnsArray, // $columnsArray
			'',         // $whereMore
			false,      // $groupByColumn
			true 		//just data
		);
		
	
		if (isset($result )) {
			foreach ($result as $key => &$value) { //referencing original array element with & to modify it instead of a copy. 
				$value['targetSnapshotURI'] = $value['targetSnapshotURI'] . '?ts=' . time();
				
			}
			unset($value); //break the reference with the last element
			return  $result;
		} else {
			return false;
		}
	
		
	}
/**
 * Description: Get game and user details in all games a user is in - organized by gameCode
 *
 * @param int $userID
 * @return mixed $organizedData array of all users in games associated with user, or false on failure
 */
	public function getUserDetailsForJoinedIDsByUserID($userID){
		// Initialize an empty result array
		$organizedData = [];
		
		$userInfoInGames = $this->getUniqueUserInfoRelatedToUserID($userID);
		
		if($userInfoInGames != false){
			//organize data by gameCode
			foreach ($userInfoInGames as $data) {
				$gameCode = $data['gameCode'];
				$userID = $data['userID'];

				// Create gameCode key if it doesn't exist
				if (!isset($organizedData[$gameCode])) {
					$organizedData[$gameCode] = [];
				}

				// Create userID key under gameCode if it doesn't exist
				if (!isset($organizedData[$gameCode][$userID])) {
					$organizedData[$gameCode][$userID] = [];
				}

				// Assign the user and game details under the appropriate keys
				$organizedData[$gameCode][$userID] = [
					'availableItems' => $data['availableItems'],
					'userID' => $data['userID'],
					'gameID' => $data['gameID'],
					'gameName' => $data['gameName'],
					'gameTypeID' => $data['gameTypeID'],
					'gameCode' => $data['gameCode'],
					'gameStatusID' => $data['gameStatusID'],
					'gameStartDateTime' => $data['gameStartDateTime'],
					'participantTypeName' => $data['participantTypeName'],
					'fName' => $data['fName'],
					'lName' => $data['lName'],
					'userSnapshot' => $data['userSnapshot'],
				];
			}
			return $organizedData;
		}
		else {
			return false;
		}
		
		
		
	}

	public function getUserDetailsRelatedToHostID($userID){
		$relatedUserDetailsArray = array();
			
		$userIDsForAllHostedGames = $this->getUniqueUserIdsRelatedToHostID($this->id);

		foreach ($userIDsForAllHostedGames as $item) {
			$relatedID = $item['userID']; // Extract the userID from the sub-array
			$limitedUserDetail = $this->getLimitedUserDetailByID($relatedID);
			$relatedUserDetailsArray[$relatedID] = $limitedUserDetail;
		}
		return $relatedUserDetailsArray;
	}

	/**
 * Description: Check if user has access to game details use to check for userID, gameID, gameCode
 *				Get all games and user details associated with a userID and check if the gameID is in the list
 * @param int $loggedInUser 
 * @param int $gameID to check if user has access to
 * @return array $response['success'] will be true if user has access and ['data'] will include the data they have access to, false if not
 * 
 * */
protected function userCanAccessValueInDataSet($loggedInUser, $columnToCheck, $valueToCheck){
	//get all userIDs associated with userID
	$userAndGameDetails = $this->getUniqueUserInfoRelatedToUserID($loggedInUser);
	
	//verify that the valueToCheck is a column in the dataset
	$columns = array_keys($userAndGameDetails[0]);
	
	if(!in_array($columnToCheck, $columns)){
		return ['success' => false, 'message' => 'Value not found in dataset. Available values are: ' . implode(', ', $columns) . '.'];
	}
	// Extract an array of values from the dataset
	$columnValues = array_column($userAndGameDetails, $columnToCheck);
	// print_r($columnValues); die();
	if(in_array($valueToCheck, $columnValues)){
		return ['success' => true, 'message' => 'User can access this data'];
    } else {
        return ['success' => false, 'message' => 'Value not found.'];
    }
}

	protected function getPhonebyUserID($userID){
		
		
		$tables = array(
			'users'
		);
		$where = array(
			'id' => $userID
		);
		$columnsArray = array(
			'`users`.`phone`'
			
		);

		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			true // when true, it is returning each column as its own array
		);
		//  print_r($result['data']['phone'][0]); die();
		return $result['data']['phone'][0];
	}

	/**
	 * Description: Get all game details associated with a userID
	 *
	 * @param int $userID 
	 * @return array $response['data'] will contain data on success, null on failure
	 * 					$response['status'] will contain 'success' or 'error'/'warning'
	 */
	protected function getGameDetailsAssociatedWithUser($userID){

		$gameDetails = array();
	// Define your database query parameters here
	$tables = array(
		'gamesXusers',
		'participantTypes',
		'games'
	);
	$where = array(
		'`gamesXusers`.`userID`' => $userID,
			'`gamesXusers`.`gameID`' => '`games`.`id`',
			'`participantTypes`.`id`' => '`gamesXusers`.`participantTypeID`',
			'`gamesXusers`.`active`' => 1,
			'`games`.`active`' => 1,
			'`gamesXusers`.`participantTypeID`' => array(1,2,3) //In clause to only get these participant types
	);
	$columnsArray = array(
		'games.id AS gameID',
		'games.gameName',
		'games.gameTypeID',
		'games.gameCode',
		'games.gameStatusID',
		'games.gameStartDateTime',
		'games.hostUserID',
		'games.description'
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
	// print_r($result); die('2525');
	if ($result['status'] != 'warning' && $result['status'] != 'error') {
		 $result['data'];
	} else {
		$result = false;
	}
	
	return $result;
}

		
	
	/**
	 * Description: Get all games hosted by the user
	 *
	 * @param int $userID
	 * @return array $response['data'] will contain data on success, null on failure
	 * 				$response['status'] will contain 'success' or 'error'/'warning'
	 */
	protected function getNumberGamesHostedByUserID($userID){
		
		try {
			// get all the distinct ID's in each game related to the userID
			$sql = "
			SELECT COUNT(g.id) AS numberOfGames
			FROM games g
			WHERE g.hostUserID = :userID
			GROUP BY g.hostUserID;
				";
				$resultData = $this->db->prepareAndExecuteFetchAll($sql, $userID, ":userID");
				// var_dump($resultData); die();		 
				//check if any roles were found
				if (empty($resultData)) {
					$response['status'] = "warning";
					$response['message'] = "No games were found for the user.";
					$response['data'] = null;
				} else {
					$response['status'] = "success";
					$response['message'] = "Game details for all gameID's associated with userID selected from the database.";
					$response['data'] = $resultData;
				}
			} catch (PDOException $e) {
				$response['status'] = "error";
				$response['message'] = 'Select Failed: ' . $e->getMessage();
				$response['data'] = null;
			}
		
			return $response;
			
			
	}

	protected function getAllHostedGameIDsByUserID($userID){
		$hostedGames = array();

		$tables = array(
			'games'
			
		);

		$where = array(
			'`games`.`hostUserID`' => $userID
			

		);

		$columnsArray = array(
			'`games`.`id`',
		
		);

		//set variable $wheremore to sql clause to group by games.id only returning distinct id's
		//$whereMore = 'GROUP BY `games`.`id`';
		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			false // $groupByColumn
		);
		
		foreach ($result['data'] as $item) {
			foreach ($item as $key => $value) {
				if ($key === 'id') {
					$hostedGames[] = $value;
				}
			}
		}
		return $hostedGames;

	}

	protected function removeUserRole($userID, $userRoleID){
		$tables = 'userXuserRoles';
		$where = array(
			'userID' => $userID,
			'userRoleID' => $userRoleID
		);
		$result = $this->db->delete(
			$tables,
			$where
		);
		return $result;
	}

	
	
	function addNewSnapshots($fileURI) {
		//$locationJason = json_encode($this->params['snapshotLocation']);
		unset($this->params['snapshotLocation']);
		
		$currentDateTime = date('Y-m-d H:i:s'); // Format: YYYY-MM-DD HH:MM:SS
		
		//create snapshot details  //??? this isn't available due to the way we are doing the upload
		$moreInfoArray['name'] 		= $this->tempFile['name'];
		$moreInfoArray['full_path']	= $this->tempFile['full_path'];
		$moreInfoArray['type'] 		= $this->tempFile['type'];
		$moreInfoArray['tmp_name'] 	= $this->tempFile['tmp_name'];
		$moreInfoArray['error'] 	= $this->tempFile['error'];
		$moreInfoArray['size'] 		= $this->tempFile['size'];
		$otherInfoJSON = json_encode($moreInfoArray);

		$this->db->fieldsArray['userID'] 			= $this->claimUserID;
		$this->db->fieldsArray['usersXtargetsID']	= null;
		$this->db->fieldsArray['snapshotLocation'] 	= ""; //??? this isn't available due to the way we are doing the upload
		$this->db->fieldsArray['snapshotDateTime']	= $currentDateTime;
		$this->db->fieldsArray['snapshotTypeID'] 	= SNAPSHOT_TYPE_UNDEFINED;
		$this->db->fieldsArray['fileURI'] 			= $fileURI;
		@$this->db->fieldsArray['notes'] 			= $this->params['notes'];
		$this->db->fieldsArray['snapshotDetails'] 	= $otherInfoJSON;
		$this->db->fieldsArray['active'] 			= 1;
		
		$this->db->table = 'snapshots';

		 $addResult = $this->db->addRecord();
		
	 	if (!empty($addResult['id']) && $addResult['id'] > 0) {
			$this->id = $addResult['id'];
			return $this->id;
		}else {
			return array('error' => __LINE__ . ": Could not add snapshot", 'details' => $addResult['message']);
		}
	}
	protected function getAndUpdateUserRecord($userID, $snapshotID){
		$userDetails = $this->getUserRecordByUserID($userID);
		
		if($userDetails == false){
			return false;
		}
		else {
			//update user record
			$userDetails = array(
				'fName' => $userDetails['fName'],
				'lName' => $userDetails['lName'],
				'email' => $userDetails['email'],
				'phone' => $userDetails['phone'],
				'selfieSnapshotID' => $snapshotID,
				'targetID' => $userDetails['targetID'],
				'active' => 1
			);
			$updateResult = $this->updateUserRecord($userID, $userDetails);
			
			if($updateResult['status'] == 'error'){
				return false;
			}
			else {
				return true;
			}
		}
	}
	protected function addOrUpdateDevice($userID, $deviceToken){
       $deviceExists = $this->checkDeviceForUserIDExists($userID);
	   if($deviceExists == true){
		   //update device
		   $updateResult = $this->updateDevice($userID, $deviceToken);
		   return $updateResult;
	   }
	   else {
		   //add device
		   $addResult = $this->addDevice($userID, $deviceToken);
		   
		  return $addResult;
	   }
    }
	protected function checkDeviceForUserIDExists($userID) {
		$tables = array(
			'devices'
		);
		$where = array(
			'userID' => $userID,
			'active' => 1
		);
		$columnsArray = array(
			'id'
		);
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			$columnsArray, // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
	
		if($result['status'] == 'success'){
			return true;
		}
		else {
			return false;
		}
	}
	protected function addDevice($userID, $deviceToken){
	  $tables = 'devices';
	   $fieldsArray = array(
		   'userID' => $userID,
		   'deviceToken' => $deviceToken,
		   'active' => 1,

	   );
	   $this->db->requiredFields = array(
		   'userID' ,
		   'deviceToken'
	   );
	   $addResult = $this->db->insert($tables, $fieldsArray, $this->db->requiredFields);

	   return $addResult;
	   
	}
	protected function updateDevice($userID, $deviceToken){
		$tables = 'devices';
	   $where = array(
		   'userID' => $userID
	   );
	   $updateResult = $this->db->update($tables, array('deviceToken' => $deviceToken), $where );
	  
	   return $updateResult;
	}
	protected function updateUserRecord($userID, $userDetails){
		$tables = 'users';
		$where = array(
			'id' => $userID
		);
		$result = $this->db->update($tables, $userDetails, $where );

		return $result;
		
	}

	//write a method to get a user record by userID
	protected function getUserRecordByUserID($userID){
		$tables = array(
			'users'
		);
		$where = array(
			'id' => $userID,
			'active' => 1
		);
		$columnsArray = array(
			'id',
			'fName',
			'lName',
			'email',
			'phone',
			'selfieSnapshotID',
			'notes',
			'targetID',
			'active'
		);
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			$columnsArray, // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
		//  print_r($result); die();
		if($result['status'] == 'success'){
			unset($result['data'][0]['availableItems']); //don't want to return this
			return $result['data'][0];
		}
		else {
			return false;
		}
	}

	/**
	 * Description: Deactivate GamesXUsers record 
	 *
	 * @param int $userID
	 * @param int $gameID
	 * @return array $result with ['status'] and ['message'] 
	 */
	protected function deactivateGamesXUsersRecord($userID, $gameID){
		$tables = 'gamesXusers';
		$where = array(
			'userID' => $userID,
			'gameID' => $gameID
		);

		$result = $this->db->update($tables, array('active' => 0), $where );
		 //print_r(array('userID'=> $userID, 'gameID' =>$gameID, 'result'=>$result)); die('2690');
		return $result;
	}
	
	protected function openUserTargetRecordExists($userID, $targetID, $gameID){
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'userID' => $userID,
			'targetID' => $targetID,
			'gameID' => $gameID,
			'active' => 1,
			'targetStatusID' => 1
			
		);
		$columnsArray = array(
			'`usersXtargets`.`id`',
			
		);

		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			false, // $groupByColumn
			true //just return the data
		);
		// print_r($result);die();
		if(isset($result[0]['id'])) {
			return  $result[0]['id']; 
		} 
		else {
			// Record does not exist
			return false;
		}
	}

	protected function getTargetIDByUserID($removingUserID){
		$tables = array(
			'users'
		);
		$where = array(
			'id' => $removingUserID
		);
		$columnsArray = array(
			'targetID' 
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
			return $result['data'][0]['targetID'];
		}
		else {
			return false;
		}
	}

	protected function getTargetDetails($targetID){
		$tables = array(
			'targets'
		);
		$where = array(
			'id' => $targetID
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
	protected function updateGameStatusID($gameID, $status){
		$tables = 'games';
		$where = array(
			'id' => $gameID
		);
		$result = $this->db->update($tables, array('gameStatusID' => $status), $where );
		return $result;
	}
	protected function getActiveGamesXUsersRecordsByGameID($gameID){
		$recordIDs = array();
		$tables = array(
			'gamesXusers'
		);
		$where = array(
			'gameID' => $gameID,
			'active' => 1
		);
		

		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$where,
			array(), // $whereGreater
			array(), // $whereLess
			array(), // $columnsArray
			'', // $whereMore
			false, // $groupByColumn
			true //just return the data
		);
		//  print_r($result);die('2744');
		if(isset($result)) {
			return $result;
		} 
		else {
			// Record does not exist
			return false;
		}
	}
	protected function updateGamesXUsersRecord($record){
		$recordID = $record['id'];
		$tables = 'gamesXusers';
		$where = array(
			'id' => $recordID
		);
		$result = $this->db->update($tables, $record, $where );
		return $result;
	}

	protected function getAllTypes($tableName){
		$result = $this->db->select($tableName, array(), array(), array(), array(), '', false);
		
		if($result['status'] == 'success'){
			return $result;
		}
		else {
			return false;
		}
	}
	protected function getSnapshotDetails($snapshotID){
		$tables = array(
			'snapshots'
		);
		$where = array(
			'id' => $snapshotID
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
	protected function getAndUpdateSnapshotRecord($snapshotID, $userTargetID, $location ){
		
		$snapshotRecord = $this->getSnapshotRecord($snapshotID);
		if($snapshotRecord == false){
			return array('error' => "No snapshot record for snapshotID $snapshotID");
		}

		$columnsUpdateArray = array("usersXtargetsID" => $userTargetID, "snapshotLocation" => $location, 'snapshotTypeID' => SNAPSHOT_TYPE_USER);
		$updateResult = $this->db->update('snapshots', $columnsUpdateArray, array('id' => $snapshotID));
		//$updateResult = $this->updateSnapshotRecord($snapshotID, $snapshotDetails);
		
		if($updateResult['status' != 'success']){
			return array('error' => "Could not update snapshot record for snapshotID $snapshotID");
		}
		
		return true;
		
	}
	 protected function getSnapshotRecord($snapshotID){
		$tables = array(
			'snapshots'
		);
		$where = array(
			'id' => $snapshotID
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
		// print_r($result); die();
		if($result['status'] == 'success'){
			return $result['data'][0];
		}
		else {
			return false;
		}
	 }
	protected function updateSnapshotRecord($snapshotID, $snapshotDetails){
		$tables = 'snapshots';
		$where = array(
			'id' => $snapshotID,
		);
		$result = $this->db->update($tables, $snapshotDetails, $where );
		
		return $result;
	}
	protected function updateTargetsRecord($targetID, $targetDetails){
		$tables = 'targets';
		$where = array(
			'id' => $targetID
		);
		$result = $this->db->update($tables, $targetDetails, $where );
		return $result;
	}
	/**
	 * Description: This method adds records to the data store so we can see transactionally what happens - atleast that is what Cameron says
	 *  In essence it is a log of what happens that we can store in the database
	 *
	 */
	protected function updateAndGenerateUserTargetRecord($userXTargetID, $userXTargetRecord, $targetStatus){
		unset($userXTargetRecord['availableItems']);
		//Deactivate the original record 
		$isDeactivated = $this->deactivateUsersXTargetsRecords($userXTargetID);
			
		if($isDeactivated['status'] == 'error'){
			return array('error' => "Could not deactivate userXtarget record for userTargetID $userXTargetID");
		}
		//create a new record that shows the closedDateTime and TargetStatusID = closed (might be different based on the record passed in params)
		$result = $this->addUserTargetRecord($userXTargetRecord, $targetStatus);
	
		return $result; 
	}
	protected function addUserTargetRecord($userXTargetRecord, $targetStatus, $isActive = 0){
		
	
		unset($userXTargetRecord['id']);
		unset($userXTargetRecord['availableItems']);
		$userXTargetRecord['targetStatusID'] = $targetStatus;
		$userXTargetRecord['active'] = $isActive;
		if($isActive == 1){
			$userXTargetRecord['startDateTime'] = date('Y-m-d H:i:s');
			$userXTargetRecord['closedDateTime'] = null;
		}
		else {
			$userXTargetRecord['closedDateTime'] = date('Y-m-d H:i:s');
		}

		$tables = 'usersXtargets';
	
		$result = $this->db->insert($tables, $userXTargetRecord, array_keys($userXTargetRecord));
	
		return $result;
	}
	/**
	 * Description: Close the userXtarget record by updating the record with closedDateTime and targetStatusID to closed
	 *
	 * @param int $recordID - this is the userTargetRecordID that we are closing
	 * @param array $userXTargetRecord - the record that we are closing, with the id of recordID
	 * @param int $targetStatus - the targetStatusID that we are closing the record with
	 * @return bool|array $result - true on success, array with error message on failure
	 */
	protected function closeUserTargetRecord($recordID, $userXTargetRecord, $targetStatus){
		//update the userXtarget record with closedDateTime and targetStatusID to closed
		$userXTargetRecord['closedDateTime'] = date('Y-m-d H:i:s');
		$userXTargetRecord['targetStatusID'] = $targetStatus;
		$userXTargetRecord['active'] = 0;
		
		$userTarget = new UserTargets($this->params, $this->authJWTstring);
		$result = $userTarget->updateUserTargetsRecord($recordID, $userXTargetRecord);
		
		if($result['status'] != 'success'){
			 return array('error' => $result['message']);
		}
		return true; //successfully closed the record
	}

	/**
	 * Description: Send a notification to the eliminated player
	 *
	 * @param int $eliminatedPlayerID - the userID of the player that was eliminated
	 * @return void
	 */
	protected function notifyPlayer($eliminatedPlayerID, $msgID) {
		
		$notificationID = $this->addNotification($msgID, $eliminatedPlayerID);
		if($notificationID <= 0){
			//todo update this to log it, to an error log - currently only logging we have -
			$this->storeTestingData("CreateNotification","fail", "error: NotificationID returned  ". $notificationID, __LINE__, __FILE__,  $this->claimUserID);
		}
	}

	/**
	 * Description: Send a socket to the eliminated player
	 *
	 * @param int $eliminatedPlayerID - the userID of the player that was eliminated
	 * @param int $gameID - the gameID of the game the player was eliminated from
	 * @return void
	 */
	protected function publishSocketMessage($eliminatedPlayerID, $gameID, $dataToSend, $navigationStatus) {
		
		$socketSent = $this->publishToSocket($dataToSend, $gameID, $eliminatedPlayerID, $navigationStatus);
	
		if($socketSent['status'] == 'error'){
			$this->socketEventInfo[$eliminatedPlayerID] = array('error' => __LINE__.": There was an error sending a socket message to user $eliminatedPlayerID", "socketError" => $socketSent);
			$this->storeTestingData("PublishSocketMessage","fail", "error: Socket message not sent to user $eliminatedPlayerID", __LINE__, __FILE__, $gameID, $this->claimUserID);
		}
		
	}

	/**
	 * Description: Get the userTargetRecordID for the userTargetRecord that is active by targetID and gameID
	 *
	 * @param int $targetID - the targetID of the userTargetRecord
	 * @param int $gameID - the gameID of the userTargetRecord
	 * @return array|false the userTargetRecord on success, false on failure
	 */
	public function getUserTargetRecordByTargetID($targetID, $gameID){
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'targetID' => $targetID,
			'gameID' => $gameID,
			'active' => 1
		);
		$columnsArray = array(
			'id',
			'userID',
			'targetID',
			'gameID',
			'active',
			'closedDateTime',
			'targetStatusID'
		);
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			$columnsArray, // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
		// print_r($result); die();
		if($result['status'] == 'success'){
			return $result['data'][0];
		}
		else {
			return false;
		}
	}


	protected function storeTestingData($testName, $testStatus, $testMsg, $file, $line, $gameID = null, $claimUserID = null){
		if(LOGGING_TEST_DATA == true){
			//checking if gameID is set, and claimUserID isset
			if(isset($this->gameID) && $gameID == null){
				$gameID = $this->gameID;
			}
			if(isset($this->claimUserID) && $claimUserID == null){
				$claimUserID = $this->claimUserID;
			}

			$table = 'testingData';
			$columnsArray = array(
				'testingName' => $testName,
				'testingStatus' => $testStatus,
				'testingMSG' => $testMsg,
				'testingGameID' => $this->gameID,
				'testingClaimUserID' => $this->claimUserID,
				'testingLocation' => $file .": Line " .$line,
				'httpMethod'=> $_SERVER['REQUEST_METHOD'],
			);
			$requiredFields = array();
		
			$addResult = $this->db->insert($table, $columnsArray, $requiredFields);
			
			if($addResult['status'] == 'error'){
				$columnsArray['queryError'] = $addResult;
				//if an error occurs inserting, then add all data to the message and insert as 'failed test'
				$jsonColumns = json_encode($columnsArray);
				$errorColumnsArray = array(
					'testingName' => 'Insert Failure',
					'testingStatus' => $addResult['message'],
					'testingMSG' => $jsonColumns,
					'testingLocation' => $file .": Line " .$line,
					'httpMethod'=> $_SERVER['REQUEST_METHOD'],
				);
				
				$addResult = $this->db->insert($table, $errorColumnsArray, $requiredFields);
			}
			
			
		}
			

	}
	protected function addPassphraseResetCode($userID, $code){
		$unixTimestamp1hrExpire = time() + 3600; // Unix timestamp for 1 hour from now
		$mysqlTimestamp = date('Y-m-d H:i:s', $unixTimestamp1hrExpire);

		$this->db->table = 'passphraseResetCodes';
		$this->db->fieldsArray = array(
			'userID' => $userID,
			'code' => $code,
			'expiresAt' => $mysqlTimestamp,
			'active' => 1
		);
		@$this->db->requiredFields = array(
			'userID',
			'code',
			'expiresAt'
		);
		$result = $this->db->addRecord();
		
		if($result['status'] == 'error'){
			return false;
		}
		else {
			return $result;
		}
	}
	protected function createAndSendEmailCode($userID, $email) {
		//create a random code
		$code = $this->createPassphraseResetCode();
		//store the code in the database
		$storedCode = $this->addPassphraseResetCode($userID, $code);
		if($storedCode == false){
			return false;
		}
		//send the email
		$emailObject = new SendEmails();
		$to = $email;
		$subject = "Password Reset Code";
		$message = "Your password reset code is: $code";
		$htmlMessage = $emailObject->generateHTMLMessage($code);

		$emailSent = $emailObject->send($to, $subject, $message, $htmlMessage);
		
		if($emailSent == false){
			return false;
		}
		return true;
	}
	protected function sendEmailCode($phone){
		
		//get the email address for the user
		$email = $this->getEmailByUserPhone($phone);
		$userID = $this->getUserIDFromPhone($phone);
		if($email == false){
			http_response_code(404);
			$errorMsg = "No email found for this phone number. Please contact customer service for help";
			$navigation = 'Profile';
			$debugInfo = null;
			$response = $this->createApiResponse(404, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
		
		//send the email
		$emailSent = $this->createAndSendEmailCode($userID, $email);
		//return the address the code was sent to 
		if($emailSent == false){
			http_response_code(500);
			$errorMsg = "Oops! Error occurred while sending email.";
			$navigation = null;
			$debugInfo = null;
			$response = $this->createApiResponse(500, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		else {
			$obfuscatedEmail = $this->hideEmailCharacters($email);
			$message = "A code has been sent to the following email: $obfuscatedEmail";
			$returnArray = array(
				'status' => 200,
				'message' => $message
			);
			return $this->prepareReturn($returnArray);
		}
		
	}

	//write a method to get the user email by phone
	protected function getEmailByUserPhone($phone){
		$table = 'users';
		$columns = array('email');
		$where = array(
			'phone' => $phone
		);
		$result = $this->db->select($table, $where, array(), array(), $columns, '', false);
	
		if($result['status'] == 'success'){
			return $result['data'][0]['email'];
		}
		else {
			return false;
		}
	}
	
	function hideEmailCharacters($email) {
		// Split the email into username and domain parts
		list($username, $domain) = explode('@', $email);
	
		// Determine the length of the username
		$usernameLength = strlen($username);

		if ($usernameLength >= 5) {
			// Hide some characters in the username, leaving the first character visible
			$hiddenUsername = substr($username, 0, 1) . str_repeat('*', $usernameLength - 5) . substr($username, -1);
		} else {
			// Show only the first character, hide the rest
			$hiddenUsername = substr($username, 0, 1) . str_repeat('*', $usernameLength - 1);
		}

	
		// Hide some parts of the domain
		$hiddenDomain =  str_repeat('*', 3) . substr($domain, -7);
		// Recombine the username and domain to form the obfuscated email address
		$obfuscatedEmail = $hiddenUsername . '@' . $hiddenDomain;
	
		return $obfuscatedEmail;
	}
	
	protected function deactivateAllPassphraseResetCodeRecordsForUser($userID){
		$tables = 'passphraseResetCodes';
		$where = array(
			'userID' => $userID
		);
		$result = $this->db->update($tables, array('active' => 0), $where );
		return $result;
	}
	//write method to reset passPhrase
	protected function resetPassword($phone, $resetCode, $newPassword){
		
		//get the userID by phone
		$userID = $this->getUserIDFromPhone($phone);
		if($userID == false){
			http_response_code(404);
			$errorMsg = "Oops! No user found for this phone number. Please contact customer service for help.";
			$navigation = 'Signup';
			$debugInfo = null;
			$response = $this->createApiResponse(404, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
		//get all expired resetCodes and deactivate them
		$deactivatedRecords = $this->deactivateAllExpiredPassPhraseResetCodesForUser($userID);

		//get the resetCode for the userID 
		
		$passphraseResetCodeRecord = $this->getPassPhraseResetRecordForUser($userID);
		if($passphraseResetCodeRecord == false){
			http_response_code(404);
			$errorMsg = "Oops! No active reset code was found for this user. Please request another code or contact customer service for help.";
			$navigation = "ForgotPassword";
			$debugInfo = null;
			$response = $this->createApiResponse(404, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
	
		$storedResetCode = $passphraseResetCodeRecord['code'];
		//By deactivating all remaining records, if they requested 2 codes that are still valid, they will only be able to use the most recent one
		$deactivateRecords = $this->deactivateAllPassphraseResetCodeRecordsForUser($userID);
		
		//compare the resetCode to the resetCodeForUser
		if($resetCode != $storedResetCode){
			http_response_code(400);
			$debugInfo = array(
				'userID' => $userID,
				'phone' => $phone,
				'resetCode' => $resetCode,
				'storedResetCode' => $storedResetCode
			);
			$errorMsg = "Oops! The reset code provided does not match the stored reset code. Please request another code or contact customer service for help.";
			$navigation = "ForgotPassword";
			$response = $this->createApiResponse(404, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}

		
		$updatePassPhrase = $this->resetPassPhraseHash($userID, $newPassword);
		if($updatePassPhrase == true){
			//return success message
			$message = "Your password has been successfully reset.";
			$returnArray = array(
				'status' => 200,
				'message' => $message
			);
			
			return $this->prepareReturn($returnArray);
		}
		else {
			http_response_code(500);
			$errorMsg = "Oops! Error occurred while resetting password. Please contact support@connectandconquer.com";
			$navigation = "ForgotPassword";
			$debugInfo = null;
			$response = $this->createApiResponse(500, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
			
		}
	}

	protected function getSoleWinnerDetailsByGameID($gameID){
		//check if sole winner of game
		$tables = array(
			'usersXtargets',
			'targets',
			'users'
		);
			

		$whereArray = array(
			'usersXtargets.userID' => '`users`.`id`',
			'users.targetID' => '`targets`.`id`',
			'usersXtargets.gameID' => $gameID,
			
			'usersXtargets.targetStatusID' => TARGET_STATUS_CLOSED_WINNER,
			//'usersXtargets.active' => 1
			
		);
		$columnsArray = array(
			'`usersXtargets`.`userID`',
			'`targets`.`targetName`',
			'`targets`.`targetSnapshotURI`'
		);
		// Call your existing select method with the parameters
		$result = $this->db->select(
			$tables,
			$whereArray,
			array(), // $whereGreater
			array(), // $whereLess
			$columnsArray, // $columnsArray
			'', // $whereMore
			false, // $groupByColumn
			true //just return the data
		);


		if(isset($result[0])) {
			unset($result[0]['availableItems']);
			return  $result[0]; 
		} 
		else {
			// Record does not exist
			return false;
		}
	}
	protected function getRemainingPlayersByGameID($gameID){
		$results = $this->getActivePlayerDetailsByGameID($gameID);

        $winnerDetails = [];

        if ($results == false) {
            $winnerDetails[] = "No remaining active players in game $gameID";
        } else {
            foreach ($results as $result) {
                $numCaptures = $this->getNumCapturesOfPlayerForGame($result['userID'], $gameID);
                $result['numberCaptures'] = $numCaptures; // either 0 or the number of captures in the game
                unset($result['availableItems']);
                unset($result['gamesXusersID']);
                unset($result['gameID']);
				$result['targetSnapshotURI'] = $result['targetSnapshotURI'] . '?ts=' . time();//add timestamp to force refresh
                $winnerDetails[] = $result;
            }
        }

        return $winnerDetails;
    }

	protected function getNameAndImageOfUsersJoinedToGame($gameID){
		$tables = array(
			'gamesXusers',
			'users',
			'snapshots'
		);
		$whereArray = array(
			'`gamesXusers`.`gameID`' => $gameID,
			'`gamesXusers`.`participantTypeID`' => array(PARTICIPANT_TYPE_SPECTATOR, PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_CAPTURED_PLAYER),
			'`gamesXusers`.`userID`' => '`users`.`id`',
			'`users`.`selfieSnapshotID`' =>'`snapshots`.`id`'
			
		);
		$columnsArray = array(
			'`gamesXusers`.`id`',
			'`gamesXusers`.`userID`',
			'`users`.`fName`',
			'`users`.`lName`',
			'`snapshots`.`fileURI`',
		);
		$results = $this->db->select($tables, $whereArray, array(), array(), $columnsArray, '', false, true, 'all');
		foreach ($results as &$item) {
			if (isset($item['availableItems'])) {
				unset($item['availableItems']);
			}
		}
		
		return $results;

	}

	/**
	 * Retrieves all users joined to a specific game. Both spectators and active players
	 *
	 * @param int $gameID The ID of the game.
	 * @return array An array containing user IDs of all users joined to the game.
	 *
	 */
	protected function getAllUsersJoinedToGame($gameID){
		
		$tables = array('gamesXusers');
		$whereArray = array(
			'gameID' => $gameID,
			'participantTypeID' => array(PARTICIPANT_TYPE_SPECTATOR, PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_CAPTURED_PLAYER),
		);
		$columnsArray = array('userID');
		$results = $this->db->select($tables, $whereArray, array(), array(), $columnsArray, '', false, true, 'all');
		foreach ($results as &$item) {
			if (isset($item['availableItems'])) {
				unset($item['availableItems']);
			}
		}
	
		return $results;

	}

	protected function getAllUserDetailsJoinedToGame($gameID) {
		$tables = array('gamesXusers', 'users','participantTypes');
		$whereArray = array(
			'gameID' => $gameID,
			'participantTypeID' => array(PARTICIPANT_TYPE_SPECTATOR, PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_CAPTURED_PLAYER),
			'gamesXusers.userID' => '`users`.`id`',
			'gamesXusers.participantTypeID' => '`participantTypes`.`id`'
		);
		$columnsArray = array(
			'userID',
			'fName',
			'lName',
			'participantTypeID',
			'participantTypeName',	
			'selfieSnapshotID'
		);
		$results = $this->db->select($tables, $whereArray, array(), array(), $columnsArray, '', false, true, 'all');
		foreach ($results as &$item) {
			if (isset($item['availableItems'])) {
				unset($item['availableItems']);
			}
		}

		return $results;
	}
	
	//function get active players by gameID
	public function getActivePlayerDetailsByGameID($gameID){
		$tables = array(
			'users',
			'targets',
			'gamesXusers'
		);
	
		$where = array(
			'gamesXusers.userID' => '`users`.`id`',
			'users.targetID' => '`targets`.`id`',
			'gamesXusers.gameID' => $gameID,
			'participantTypeID' => PARTICIPANT_TYPE_ACTIVE_PLAYER,
			'gamesXusers.active' => 1
		);
		
		$whereGreater = array();
		$whereLess = array();
		$columnsArray = array(
		'`gamesXusers`.`id` as gamesXusersID',
		'`gamesXusers`.`userID`',
		'`targets`.`targetName`',
		'`targets`.`targetSnapshotURI`',
		'`gamesXusers`.`gameID`',
		'`gamesXusers`.`participantTypeID`'
		);
		$whereMore = '';

		$response = $this->db->select($tables, $where, $whereGreater, $whereLess, $columnsArray);
		$this->mainQuery = $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;

		if($response['status'] == 'success'){
			return $response['data'];
		}
		else {
			return false;
		}
	}


	protected function getWinnerDetailsByGameID($gameID){
		$resutltArray = array();
		//check if sole winner of game
		$soleWinner = $this->getSoleWinnerDetailsByGameID($gameID);
		
		if($soleWinner != false){
			$soleWinner['numberCaptures'] = $this->getNumCapturesOfPlayerForGame($soleWinner['userID'], $gameID);
			$soleWinner['targetSnapshotURI'] = $soleWinner['targetSnapshotURI'] . '?ts=' . time(); //add timestamp to force refresh of image
			return $resultArray["winnerDetails"] = $soleWinner;
		}
		else {
			//get all active players in game
			$players = $this->getMultipleWinnersOfEndedGame($gameID);
			//$players = false; //for testing only
			if($players == false){
				$errorMsg = "No remaining active players in game $gameID, so no winners. Game may have been cancelled.";
				$navigation = null;
				$debugInfo = null;
				$response = $this->createApiResponse(404, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				//$resultArray["winnerDetails"] =  "No remaining active players in game $gameID, so no winners. Game may have been cancelled.";
			}
			else {
				foreach($players as $key=>$result){
					$numCaptures = $this->getNumCapturesOfPlayerForGame($result['userID'], $gameID);
					$result['numberCaptures'] = $numCaptures; //either 0 or number of captures in game. Error in query will still return 0. 
					unset($result['availableItems']);

					$result['targetSnapshotURI']= $result['targetSnapshotURI'] . '?ts=' . time(); //add timestamp to force refresh of image	- this avoids caching issues we found
					$resultArray = $result;
				}	

			}
			
			return $resultArray;
		}
	}

	/**
	 * Description: Get multiple winners of a game that is closed
	 *
	 * @param 
	 * @return 
	 */
	protected function getMultipleWinnersOfEndedGame($gameID) {
		
		$tables = array(
			'usersXtargets',
			'targets',
			'users'
		);
		$where = array(
			'usersXtargets.userID' => '`users`.`id`',
			'users.targetID' => '`targets`.`id`',
			'usersXtargets.gameID' => $gameID,
			'usersXtargets.targetStatusID' => TARGET_STATUS_CLOSED_GAME_ENDED
			
		);
		$columnsArray = array(
			'`usersXtargets`.`userID`',
			'`targets`.`targetName`',
			'`targets`.`targetSnapshotURI`',
			
			);
			$whereGreater = array();
			$whereLess = array();
	
			$response = $this->db->select($tables, $where, $whereGreater, $whereLess, $columnsArray);
			$this->mainQuery = $this->db->fullQuery;
			$this->availableItems = $this->db->availableItems;

			if($response['status'] == 'success'){
				return $response['data'];
			}
			else {
				return false;
			}
	}

	/**
	 * Description: Get number of captures for a player in a specific game
	 *
	 * @param int $userID
	 * @return int $gameID
	 */
	protected function getNumCapturesOfPlayerForGame($userID, $gameID) {
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'userID' => $userID,
			'gameID' => $gameID,
			'targetStatusID' => TARGET_STATUS_CLOSED_COMPLETE,
			'active' => 1
		);
		$columnsArray = array(
			'id'
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
			return count($result['data']);
		} else {
			// If no IDs were found, set to an empty array
			 return 0;
		}
		
	}

	//This only allows reset of passPhraseHash if the resetPassPhraseFlag is set to true
	// and also handles if front end sends only passphrase or entire user object to update
	function resetPassPhraseHash ($userID, $newPassword){
		
		//reset the passPhraseHash
		$user = new Users(['id' => $userID, 'passPhrase' => $newPassword], null, false);
		$passPhraseHash =  @$user->createPasswordHash($newPassword);
		unset($this->params['passPhrase']);
		
		$this->db->fieldsArray = $this->setFieldsToUpdatePassPhrase($userID);
		$this->db->fieldsArray['passPhraseHash'] = $passPhraseHash;
		$table = 'users';
		$update = $this->db->updateRecord($table, $userID);
	
		if($update['status'] == 'success'){
			return true;
		}
		else {
			return false;
		}
	}
	public function setFieldsToUpdatePassPhrase($userID){
		$this->db->tableJoins = 'users';
		$response = $this->db->getRecord($userID);
		unset($response['data'][0]['availableItems']);
		if($response == 'error'){
			return false;
		}
		$results = $response['data'][0];
		return $results;
	}

	public function onUpdateUsePhoneInDB($userID){
		//Grab userDetails from db
		$this->db->tableJoins = 'users';
		$response = $this->db->getRecord($userID);
		
		$results = $response['data'][0];

		//overwrite db->fields array using params 1st, then db results - implement setFieldsArrayByDatabaseThenParams
		$this->db->fieldsArray = $this->setFieldsArrayByParamsThenDB($this->params,$results);
		$this->db->fieldsArray['phone'] = $results['phone'];
		// print_r($this->db->fieldsArray); die();

		//reset flag
		$this->resetPhoneNumberFlag = false;
	}
	function setFieldsArrayByParamsThenDB($params,$databaseObject){

		$fieldsArray = array();
		foreach ($this->db->fieldsArray AS $key => $field){
			// echo($key."\n");
			if (isset($params[$key]) && (!empty($params[$key]) || $params[$key] === 0)){
				if($key == 'phone'){
					
					$fieldsArray[$key] = $this->formatPhone($params[$key]);
					
				}
				else{
					$fieldsArray[$key] = $params[$key];
				}
				
			}
			elseif (!empty($databaseObject[$key]) || $databaseObject[$key] === 0)
			{
				$fieldsArray[$key] = $databaseObject[$key];
			}
			
		}
		
		
		return $fieldsArray;
	}

	protected function deactivatePassPhraseResetCodesRecord($id){
		$tables = 'passphraseResetCodes';
		$where = array(
			'id' => $id
		);

		$result = $this->db->update($tables, array('active' => 0), $where );
		return $result;
	}
	protected function deactivateAllExpiredPassPhraseResetCodesForUser($userID){
		$returnArray = array();
		$passphraseResetCodesArray = $this->getAllExpiredPassPhraseResetCodesForUser($userID);
		if($passphraseResetCodesArray == false){
			return false; //no records found
		}
		else {
			foreach ($passphraseResetCodesArray as $passphraseResetCode) {
				$result = $this->deactivatePassPhraseResetCodesRecord($passphraseResetCode['id']);
				$returnArray[$passphraseResetCode['id']] = $result;
			}
			
			return $returnArray;
		}
		
		
	}
	protected function getAllExpiredPassPhraseResetCodesForUser($userID){
		$currentTimestamp = date("Y-m-d H:i:s"); 
		$tables = array(
			'passphraseResetCodes'
		);
		$where = array(
			'userID' => $userID,
			'active' => 1
		);
		$whereLess = array(
			'expiresAt' => $currentTimestamp
		);
		
		$result = $this->db->select(
			$tables,
			$where,
			array(),    // $whereGreater
			$whereLess,    // $whereLess
			array(), // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
		// print_r($result['data'][0]); die();
		if($result['status'] == 'success'){
			unset($result['data'][0]['availableItems']);
			return $result['data'];
		}
		else {
			return false;
		}

	}
	protected function getPassPhraseResetRecordForUser($userID){
		$currentTimestamp = date("Y-m-d H:i:s"); 
		$tables = array(
			'passphraseResetCodes'
		);
		$where = array(
			'userID' => $userID,
			'active' => 1
		);
		$whereGreater = array(
			'expiresAt' => $currentTimestamp
		);
		
		$result = $this->db->select(
			$tables,
			$where,
			$whereGreater,    // $whereGreater
			array(),    // $whereLess
			array(), // $columnsArray
			'',         // $whereMore
			false,       // $groupByColumn
			false,
			null,
			null,
			'expiresAt',
			'DESC'

		);

		if($result['status'] == 'success'){
			unset($result['data'][0]['availableItems']);
			return $result['data'][0];
		}
		else {
			return false;
		}
	}
	public function getGamesNotEndedByUserID($userID){
		$tables = array(
			'gameTypes',
			'gameModes',
			'gamesXusers',
			'users',
			'games'
		);
		// $where = array(
		// 	'games.gameTypeID' => '`gameTypes`.`id`',
		// 	'games.gameModeID' => '`gameModes`.`id`',
		// 	'gamesXusers.gameID' => '`games`.`id`',
		// 	'users.id' => '`gamesXusers`.`userID`',
		// 	'gamesXusers.participantTypeID' => array(PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_SPECTATOR, PARTICIPANT_TYPE_CAPTURED_PLAYER),
			
		// );
		$where = array(
			'games.gameTypeID' => '`gameTypes`.`id`',
			'games.gameModeID' => '`gameModes`.`id`',
			'gamesXusers.gameID' => '`games`.`id`',
			'users.id' => '`gamesXusers`.`userID`',
			'gamesXusers.participantTypeID' => array(PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_SPECTATOR, PARTICIPANT_TYPE_CAPTURED_PLAYER),
			'OR' => array(
				array(
					'games.gameStatusID' => 7,
					'gamesXusers.participantTypeID' => array(1, 2)
				),
				array(
					'games.gameStatusID' => array(1, 2, 5),
					'gamesXusers.participantTypeID' => array(1, 2, 3)
				)
			)
		);
		$columnsArray = array(
			'`games`.`id` as gameID',
			'`games`.`gameName`',
			'`games`.`gameStatusID`',
			'`games`.`gameStartDateTime`',
			'`games`.`gameEndDateTime`',
			'`games`.`gameEndReason`',
		);

	}

	

	protected function deleteUserAccount($userID){
		$notes = array();
		//1. Withdraw user from any active games
		$withdrawGameResults = $this->withdrawUserFromActiveGames($userID);
		$notes['withdrawGameResults'] = $withdrawGameResults;
		$endHostedGames = $this->endHostedGames($userID);
		$notes['endHostedGames'] = $endHostedGames;
		//2. Deactivate gamesXusers records, usersXtargets records, usersXuserRoles records
		$deactivateRecordsResults = $this->deactivateRecords($userID);
		$notes['deactivateRecordsResults'] = $deactivateRecordsResults;
		//3. Delete all snapshots from fileSystem and point to userRequestedImageRemoved file
		$deleteSnapshotsResults = $this->deleteSnapshots($userID);
		$notes['deleteSnapshotsResults'] = $deleteSnapshotsResults;
		//4. Delete device Token related to user
		$deleteDeviceTokenResults = $this->deleteDeviceToken($userID);
		$notes['deleteDeviceToken'] = $deleteDeviceTokenResults;
		// 5. Update the user table, replacing any personal info with userRequestedRemoval and deactivate the record
		$updateUser = $this->removePersonalInfo($userID, $notes);
		$notes['updateUser'] = $updateUser;
		if($updateUser['status'] == 'error'){
			//send email to support - with notes to support@connectandconquer.com
			$emailSupportSent = $this->sendEmailToSupport($userID,$notes);
			if($emailSupportSent != false){
				return array("error" => "Error occurred while updating user record and sending email to support", "notes"=>$notes);
			}else {
				return array("error" => "Error occurred while updating user record email has not been sent to support", "notes"=>$notes);
			}
			
		}
		else {
			$notes['updateUser'] = $updateUser;
			$notes['status'] = 'success';
			return $notes;
		}
    
	}
		protected function deleteDeviceToken($userID){
			$tables = 'devices';
			$where = array(
				'userID' => $userID
			);
			$columnsUpdateArray = array(
				'deviceToken' => "userRequestedRemoval",
				'active' => 0
			);
			$results = $this->db->update($tables, $columnsUpdateArray, $where );
			return $results;
		}
		protected function endHostedGames($userID){
			
			$params = array(
				"gameID"=>$userID,
				"gameEndReason" =>"host",
				"verb"=> "end"
			);
			$game = new Games($params, $this->authJWTstring);
			//get all games hosted by user
			$gameIDs = $this->getAllHostedGameIDsByUserID($userID);
			
			if($gameIDs != false){
				foreach($gameIDs as $gameID){
					//end the game
					
					$endGame = $game->endGame($gameID, "User requested deletion of account");
					$notes['endGame'][$gameID] = $endGame;
				}
				return $notes;
			}
			else {
				return false;
			}
		}
		protected function sendEmailToSupport($userID, $notes){
			//send the email
		$emailObject = new SendEmails();
		
		$to = 'support@connectandconquer.com';
		$subject = "Account Deletion Error";
		$message = "Error removing information for userID $userID.";
		$htmlMessage = $emailObject->generateSupportEmailHTML($notes);

		$emailSent = $emailObject->send($to, $subject, $message, $htmlMessage);
		
			if($emailSent == false){
				return $emailSent;
			}
			return true;
		}

		protected function removePersonalInfo($userID, $notesArray){
			$existingNotes = array();
			//get notes from user record
			$userRecord = $this->getUserRecordByUserID($userID);			
			if($userRecord != false && $userRecord['notes'] != null){
				$existingNotes = json_decode($userRecord['notes']);
			}
			else {
				$existingNotes['message'] = "No previous notes found";
			}
			
			$notesArray['message'] = "User requested removal of account on " . date('Y-m-d H:i:s');
			$userDetails = array(
				'fName' => 'userRequestedRemoval',
				'lName' => 'userRequestedRemoval',
				'phone' => 'userRequestedRemoval'.$userID,
				'email' => 'userRequestedRemoval'.$userID,
				'birthdate' => '1970-01-01T00:00:00',
				'zipcode' => '00000',
				'country' => 'userRequestedRemoval',
				'state' => 'userRequestedRemoval',
				'notes' =>json_encode($existingNotes + $notesArray),
				'active' => 0
			);
			$updateRecord = $this->updateUserRecord($userID, $userDetails);
			return $updateRecord;
		}
	
	protected function deleteSnapshots($userID){
		//get all snapshots for user
		$snapshots = $this->getAllSnapshotsForUser($userID);
		if($snapshots != false){
			foreach($snapshots as $snapshot){
				//delete snapshot from fileSystem
				$fileName = baseName($snapshot['fileURI']);
				$deleteResult[$snapshot['id']] = $this->deleteSnapshot($fileName);
				//update snapshot record to point to userRequestedImageRemoved file
				$newFileURI = "https://api.snapshotplay.com/v2/media/system/userRequestedImageRemoved.png";
				
				$currentDateTime = date('Y-m-d H:i:s');
				$snapshotDetails = array(
					'snapshotLocation' => json_encode(array("lat" => 43.6177, "lng" => 116.1996 )),
					"snapshotTypeID" => SNAPSHOT_TYPE_USER_DELETED,
					'fileURI' => $newFileURI,
					'notes' => 'User requested removal of image on '. $currentDateTime,
					'snapshotDetails' => json_encode(array('name' => 'userRequestedDeletion.png', "full_path" => $newFileURI)),
					'active' => 0
				);
				$updateResult[$snapshot['id']] = $this->updateSnapshotRecord($snapshot['id'], $snapshotDetails);
			}
			return array('deleteResult' => $deleteResult, 'updateResult' => $updateResult);
		}
	}

	protected function deleteSnapshot($fileName){
		$fileToDelete = '../uploads/' . $fileName;
		
		if (file_exists($fileToDelete)) {
			if (unlink($fileToDelete)) {
				return array("status"=>"success", "message" => "File deleted successfully.");
			} else {
				return array("status"=>"error", "message" => "Failed to delete the file.");
			}
		} else {
			return array("status"=>"error", "message" => "File not found.");
		}

	}

	protected function getAllSnapshotsForUser($userID){
		$tables = array(
			'snapshots'
		);
		$where = array(
			'userID' => $userID
			
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
		// print_r($result); die();
		if($result['status'] == 'success'){
			return $result['data'];
		}
		else {
			return false;
		}
	}
	function deactivateGamesXUsersRecords($userID) {
		$tables = 'gamesXusers';
		$where = array(
			'userID' => $userID
		);
		$result = $this->db->update($tables, array('active' => 0), $where );
		return $result;
		
	}
	 protected function isActivePlayerInStartedGame($userID){
		
		$tables = array(
			'gamesXusers',
			'games',
			
			);
		$whereArray = array(
			'gamesXusers.gameID' => '`games`.`id`',
			'gamesXusers.userID' => $userID,
			'gamesXusers.participantTypeID' => PARTICIPANT_TYPE_ACTIVE_PLAYER,
			'games.gameStatusID' => GAME_STATUS_STARTED,
			'games.active' => 1,
			'gamesXusers.active' => 1
		);
		$columnsArray = array(
			'`gamesXusers`.`id`',
			'`gamesXusers`.`userID`',
			'`gamesXusers`.`gameID`',
			'`gamesXusers`.`participantTypeID`',
			'`games`.`gameTypeID`',
			'`games`.`gameStatusID`',
			'`games`.`gameCode`',
			'`games`.`gameName`'
		
		);
		$result = $this->db->select($tables, 
			$whereArray,
			array(),
			array(),
			$columnsArray, 
			'',
			false, 
			true, 
			'all');
			
			error_log(print_r($result, true));	
		if (isset($result ) ) {
			return  $result;
		} else {
			return false;
		}
			
	 }
	protected function deactivateRecords($userID){
		$notes = array();
		//deactivate gamesXusers records
		$deactivateGamesXUsersResults = $this->deactivateGamesXUsersRecords($userID);
		$notes['deactivateGamesXUsersResults'] = $deactivateGamesXUsersResults;
		//deactivate usersXtargets records
		$deactivateUsersXTargetsResults = $this->deactivateUsersXTargetsRecords($userID);
		$notes['deactivateUsersXTargetsResults'] = $deactivateUsersXTargetsResults;
		//deactivate usersXuserRoles records
		$deactivateUsersXUserRolesResults = $this->deactivateUserXUserRolesRecords($userID);
		$notes['deactivateUsersXUserRolesResults'] = $deactivateUsersXUserRolesResults;
		return $notes;
	}
	protected function deactivateUsersXTargetsRecords($userTargetID){
		$tables = 'usersXtargets';
		$where = array(
			'id' => $userTargetID
		);
		$currentDateTime = date('Y-m-d H:i:s');
		$result = $this->db->update($tables, array('closedDateTime' => $currentDateTime, 'active' => 0), $where );
		return $result;
	}

	protected function deactivateUserXUserRolesRecords($userID){
		$tables = 'userXuserRoles';
		$where = array(
			'userID' => $userID
		);
		$result = $this->db->update($tables, array('active' => 0), $where );
		return $result;
	}

	protected function withdrawUserFromActiveGames($userID){
		//get all active games for user
		$activeGames = $this->getAllJoinedGames($userID);
		if($activeGames != false){
			foreach($activeGames as $activeGame){
				//withdraw user from game
				$params = array(
					'gameID' => $activeGame['gameID'],
					'removingUserID' => $userID,
					'removalReasonID' => TARGET_STATUS_CLOSED_PLAYER_WITHDRAWN,
					'verb' => 'withdraw'
				);
				$joinGame = new JoinGames($params, $this->authJWTstring);
				if($activeGame['gameTypeID'] == GAME_TYPE_GLOBAL){
					$results = $joinGame->withdrawFromGlobalGame($userID, $activeGame['gameID']); //HACK not sure if I should use this->params['gameID'] or $activeGame['gameID']
				}
				else { //TODO add in more elseifs for other game types
					$results = $joinGame->withdrawFromSingleTargetGame($userID, $activeGame['gameID'], TARGET_STATUS_CLOSED_PLAYER_WITHDRAWN);
				}
				
				//If items isset, return only that, otherwise return entire result set
				$withdrawResult[$activeGame['gameID']] = isset($results['items']) ? $results['items'] : $results;

			}
			return $withdrawResult;
		}
		return "User $userID was not an active player in any games";
	}
	protected function getParticipantInfoForGameByUserID($gameID, $userID) {
		$result = $this->getParticipantTypeIDs($gameID, $userID);
		if($result == false){
			return array('error' => "No participant info found for user $userID in game $gameID");
		}
        if (count($result) > 1) {
            $participantTypeID = min($result);
        } else {
            $participantTypeID = $result[0];
        }
		
        $participantTypeName = $this->getParticipantTypeNameByID($participantTypeID);
		//TODO just return participant 
        return $participantTypeName;
	}

	protected function getGameTypeNameByGameID($gameID){
		$tables = array(
			'games',
			'gameTypes'
		);
		$where = array(
			'games.id' => $gameID,
			'games.gameTypeID' => '`gameTypes`.`id`'
 
		);
		$columnsArray = array(
			'gameTypes.gameTypeName' 
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
	 
		if ($result['status'] == 'success') {
		   
		   return $result['data'][0]['gameTypeName'];
		} else {
			return  false;
		}
		
	 }

	 public function getOpenUserXTargetRecordByUserAndGameID($userID, $gameID){
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'userID' => $userID,
			'gameID' => $gameID,
			'targetStatusID' => array(TARGET_STATUS_INITIAL_ASSIGNMENT, TARGET_STATUS_TARGET_TRANSFERRED),
			'active' => 1
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
		// header("Content-Type: application/json");
		// echo json_encode([
		// 	"Line " . __LINE__ => __FILE__,
		// 	'function' => debug_backtrace()[1]['function'] ?? 'N/A',
		// 	"zzReturn" => $result,
		// 	'capturedUserID'=> $userID,
		// 	'gameID'=> $gameID
		// ]);
		// die();
		if($result['status'] == 'success' ){
			return $result['data'][0];
		}
		else {
			
			return false;
		}
	 }

	 protected function getGameStatusIDByGameID($gameID){
		//get game status
		$tables = array('games');
		$whereArray = array('id' => $gameID);
		$whereGreaterArray = array();
		$whereLessArray = array();
		$columnsArray = array('gameStatusID');
		$whereMoreString = '' ;
		$groupByColumnBoolean = false;
		$justDataBoolean = true;
		$numberPerPage= 'all';
		$gameStatusID = $this->db->select($tables, 
					$whereArray,
					$whereGreaterArray,
					$whereLessArray,
					$columnsArray, 
					$whereMoreString,
					$groupByColumnBoolean, 
					$justDataBoolean, 
					$numberPerPage
		);
		
		if(isset($gameStatusID['status']) && $gameStatusID['status'] == 'error'){
			return false;
		}else {
			unset($gameStatusID[0]['availableItems']);
			return $gameStatusID[0]['gameStatusID'];
		}
	}
	/**
	 * Retrieves open user-target records for a specific game ID.
	 *
	 * This method fetches records from the database for users' targets associated with the provided game ID,
	 * which are currently active (not closed).
	 *
	 * @param int $gameID The ID of the game for which to retrieve the user-target records.
	 *
	 * @return array|false Returns an array of open user-target records if retrieval is successful and records are found.
	 *                     Otherwise, returns false if no records are found or if an error occurs.
	 *       The returned array contains elements with the following structure:
	*                     [
	*                         {
	*                             "availableItems": int,         // Number of available items
	*                             "id": int,                     // Record ID
	*                             "userID": int,                 // User ID
	*                             "targetID": int,               // Target ID
	*                             "gameID": int,                 // Game ID
	*                             "startDateTime": string,       // Start date and time of the record
	*                             "closedDateTime": string|null, // Closed date and time of the record (null if not closed)
	*                             "targetStatusID": int,         // Target status ID
	*                             "active": int,                 // Active status (1 if active, 0 if inactive)
	*                             "ts": string                   // Timestamp of the record
	*                         },
	*                         ...
	*                     ]
	 */
	public function getOpenUserXTargetRecordsByGameID($gameID){
		$tables = array(
			'usersXtargets'
		);
		$where = array(
			'gameID' => $gameID,
			'active' => 1,
			
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
	    // header("Content-Type: application/json");
	    // echo json_encode([
	 	//    "Line " . __LINE__ => __FILE__,
	 	//    'function' => debug_backtrace()[1]['function'] ?? 'N/A',
	 	//    "zzReturn" => $result,
	    // ]);
		if($result['status'] == 'success' && $result['returnedItems'] > 0){
			return $result['data'];
		}
		else {
			return false;
		}
	}
	//FIXME this was put in snapshotData as I was setting up endpoints. It probably 
	//belongs somewhere else
	 /*/
	|--------------------------------------------------------------------------
	|        Ending the Game
	|1. There should never be an target that doesn't have a correlating snapshot eventually.
	| Reasons for ending Game
	| 1. Game ended by host
	| 2. Game ended due to player winning
	| 3. Game ended due to time limit - Not MVP
	| 4. Game ended due to inactivity - no one playing - Not MVP
	|--------------------------------------------------------------------------
	*/
	function endGame($gameID, $gameEndReason ) {
	
		$resultArray = array();
		if ($gameEndReason == 'winner') {//TODO handle single and multiple winners the same - remove this if statement. 
			
			//TODO update and add record.  Update only with active = 0, then create new record with active = 1 and duplicate info. 
			$result = $this->endGameByWinner($gameID);
			//print_r(array("result"=>$result));die();
			if (isset($result['error'] )) {
				$debugInfo = array('gameID' => $gameID, 'gameEndReason' => $gameEndReason, 'result' => $result);
				$errorMsg = __LINE__ . " Oops! Something went wrong when ending the game.  Try again, or contact customer support. Error: $result[error]";
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;
				
			}
			else {
				
				unset($result['winnerDetails']['availableItems']);

				$resultArray['status'] = "gameEndedWinner";
				$resultArray['message'] = "Game ended due to: " . $gameEndReason;
				$resultArray['targets'] = null;
				$resultArray['winnerDetails'] = $result['winnerDetails'];
				
			}
		} 
		else { //If not ending due to Game Winner
			$hostUserID = $this->getHostUserID($gameID);
			if($hostUserID != $this->claimUserID && $this->isAdmin == false){
				$debugInfo = array('gameID' => $gameID, 'gameEndReason' => $gameEndReason, 'hostUserID' => $hostUserID, 'claimUserID' => $this->claimUserID, 'message' => "Logged in user: $this->claimUserID is not the host of game $gameID. Only the host userID: $hostUserID can end the game. IsAdmin is set to: " . $this->isAdmin);
				$errorMsg = "Oops! Only the host or admin can end the game. If you would like to remove yourself from the game you can withdraw.";
				$navigation = 'MyGame';
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				return $response;

			}
			
			$resultArray['status'] = "gameEndedWinner";
			$resultArray['message'] = "Game ended due to: " . $gameEndReason;
			$resultArray['targets'] = null;
			$resultArray['winnerDetails'] =$this->getRemainingPlayersByGameID($gameID);
			//all remaining records for gameID are marked inactive & then new records are created for each player with targetStatusID = TARGET_STATUS_CLOSED_GAME_ENDED
			$activeRecordsArray = $this->getOpenUserXTargetRecordsByGameID($gameID);
			
			foreach($activeRecordsArray as $record){
				//DECREMENT USERS' ACTIVE TARGETS IN REDIS
				$this->redis->decreaseByOne('user' . $record['userID'] . REDIS_TARGET_STRING);

				//update record to close game
				unset($record['availableItems']);
				$recordID = $record['id'];
				$record['closedDateTime'] = date('Y-m-d H:i:s');
				$record['targetStatusID'] = TARGET_STATUS_CLOSED_GAME_ENDED;
				$record['active'] = 0;
				$updateRecord = $this->updateAndGenerateUserTargetRecord($recordID, $record, TARGET_STATUS_CLOSED_GAME_ENDED);
				if ($updateRecord['status'] != 'success') {
					$debugInfo = array('message' => $updateRecord['message'] ." for userTarget record with id {$record['id']}", 'gameID' => $gameID, 'gameEndReason' => $gameEndReason, 'recordID' => $recordID, 'record' => $record, 'updateRecord' => $updateRecord);
					$this->storeTestingData('updateUserTargetRecordError', "fail", $debugInfo , __FILE__ , __LINE__ , $gameID, $this->claimUserID );
					//notify admin
					//todo create slack integration we can publish to, for gamePlay issues
				}
				
			}
		
				//Notify players of gameEnd
				$playerIDs = $this->getAllUsersJoinedToGame($gameID);
				$messageID = $this->addMessage( "Game Ended", "Game $this->gameCode has been ended " . $gameEndReason );
				foreach($playerIDs as $player){
					$userID = $player['userID'];
					$notificationID = $this->addNotification($messageID, $userID);
					if($notificationID <= 0){
						//todo update this to log it, to an error log - currently only logging we have - 
						$this->storeTestingData("CreateNotification","fail", "error: NotificationID returned  ". $notificationID, __LINE__, __FILE__, $gameID, $this->claimUserID);
					}
					$dataToSend = "Game $this->gameCode has been ended  " . $gameEndReason;
					
					$navigationStatus = 'gameEnded';
					$socketSent = $this->publishToSocket($dataToSend, $gameID, $player['userID'], $navigationStatus);
					
					
					if($socketSent['status'] == 'error'){
						$this->socketEventInfo[$player['userID']] = array('error' => __LINE__.": There was an error sending a socket message to user $player[userID]", "socketError" => $socketSent);
					}
				}
		}
		
	
		$gameEndedResult = $this->updateGameStatusID($gameID, GAME_STATUS_ENDED);
		if ($gameEndedResult['status'] !== 'success') {
			$debugInfo = array('message'=> "Game $gameID status not updated to 'ENDED'",  'gameID' => $gameID, 'gameEndReason' => $gameEndReason, 'gameEndedResult' => $gameEndedResult);
			$errorMsg = "Oops! There was an issue updating the game status to 'ended'. Please contact customer support for assistance.";
			$navigation = 'MyGame';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			return $response;
		}
		// Deactivate all gamesXusers records for gameID
		$this->deactivateAllGameXUserRecordsByGameID($gameID);
		$currentDateTime = date('Y-m-d H:i:s');
		$gameDetails = array('gameEndDateTime' => $currentDateTime, 'gameStatusID' => GAME_STATUS_ENDED);
		
		//update game record with gameEndDateTime and 'ended status
		$this->updateGame($gameID, $gameDetails);
		
		$resultArray['gameDetails'] = array($this->getGameInfoByGameID($gameID));
		$this->authInfoArray['status'] = JWT_STATUS_REQUEST_NEW;
		$this->storeTestingData('gameEnded', 'success',  "JWT requested", __FILE__,__LINE__);
	
		return $this->prepareReturn($resultArray);
	}

	function endGameByWinner($gameID) {
		//notify
		$remainingUserXTargetRecords = $this->getOpenUserXTargetRecordsByGameID($gameID);
	// print_r(array('remainingUserXTargetRecords'=>$remainingUserXTargetRecords, 'gameID'=>$gameID, 'count'=>count($remainingUserXTargetRecords)));
		if ($remainingUserXTargetRecords == false || count($remainingUserXTargetRecords) == 1) {
			$record = $remainingUserXTargetRecords[0];
			$winnerUserID = $record['userID'];
			$winnerDetails = $this->getLimitedUserDetailByID($winnerUserID);
			$winnerTargetID = $this->getTargetIDByUserID($winnerUserID);
			$winnerTargetDetails = $this->getTargetDetails($winnerTargetID);
			$winnerTargetDetails['targetSnapshotURI'] = $winnerTargetDetails['targetSnapshotURI'] . "?ts=" . time(); //add timestamp to force refresh
			
			//DECREMENT USERS' ACTIVE TARGETS IN REDIS
			$this->redis->decreaseByOne('user' . $winnerUserID . REDIS_TARGET_STRING);
	
			$resultArray = array(
				'status' => 'success',
				'gameEndReason' => 'winner',
				'gameID' =>  $gameID,
				'winnerUserID' => $winnerUserID,
				'winnerDetails' => $winnerDetails,
				'winnerTargetDetails' => $winnerTargetDetails
			);
		
	
			$record['closedDateTime'] = date('Y-m-d H:i:s');
			$record['targetStatusID'] = TARGET_STATUS_CLOSED_WINNER;
			$record['active'] = 0;
			$updateRecord = $this->updateAndGenerateUserTargetRecord($record['id'], $record, TARGET_STATUS_CLOSED_WINNER);
		

			if ($updateRecord['status'] != 'success') {
				return array('error' => $updateRecord['message'] ." for userTarget record with id {$record['id']}");
			}
		} elseif ($remainingUserXTargetRecords < 1) {
			return array('error' => $remainingUserXTargetRecords['message']);
		} else {
			return array('error' => "There is more than one active userTarget record for game $gameID. To close the game, select gameEndReason = 'Host'.");
		}
	// print_r(array('resultArray'=>$resultArray));

	//Notify players of gameEnd
	//Grab all device tokens for players in game
		$playerIDs = $this->getAllUsersJoinedToGame($gameID);
		$messageID = $this->addMessage("Game Ended", "Game $this->gameCode has winner");
		foreach($playerIDs as $player){
			$userID = $player['userID'];
			$notificationID = $this->addNotification($messageID, $userID);
			if($notificationID <= 0){
				//todo update this to log it, to an error log - currently only logging we have - 
				$this->storeTestingData("CreateNotification","fail", "error: NotificationID returned  ". $notificationID, __LINE__, __FILE__, $gameID, $this->claimUserID);
			}
			
			$dataToSend = "Game $this->gameCode has winner";
		
			$navigationStatus = 'gameEnded';
			$socketSent = $this->publishToSocket($dataToSend, $gameID, $player['userID'], $navigationStatus);
			
			
			if($socketSent['status'] == 'error'){
				$this->socketEventInfo[$player['userID']] = array('error' => __LINE__.": There was an error sending a socket message to user $player[userID]", "socketError" => $socketSent);
			}
		}
		return $resultArray;
	}

	protected function isAdmin() {
		if ($this->adminKey == SYSTEM_KEY){
			$this->isAdmin = true;
		} else {
			$this->isAdmin = false;
		}
	
		return $this->isAdmin;
		
	}

	function getGameInfoForResponse($gameID) {
		$gameCode = $this->getGameCodeFromGameID($gameID);
		return  $this->getGameInfoByGameID($gameID);
		
	}

	protected function updateGame($gameID, $gameDetails){
		$tables = 'games';
		$where = array(
			'id' => $gameID
		);
		$result = $this->db->update($tables, $gameDetails, $where );
		return $result;
	}

	
	
	protected function deactivateAllGameXUserRecordsByGameID($gameID){
		$tables = 'gamesXusers';
		$where = array(
			'gameID' => $gameID
		);
		$result = $this->db->update($tables, array('active' => 0), $where );
		return $result;
	}
	// In moving functions out of gamePlays, didn't see a use for this method 
	//function deactivateUserXTargetRecords($gameID) {
	// 	$userTargetRecords = $this->getOpenUserXTargetRecordsByGameID($gameID);
	// 	if($userTargetRecords == false){
	// 		return;//no records to deactivate
	// 	}
	// 	foreach ($userTargetRecords as $record) {
	// 		$recordID = $record['id'];
	// 		$record['closedDateTime'] = date('Y-m-d H:i:s');
	// 		$record['targetStatusID'] = TARGET_STATUS_CLOSED_GAME_ENDED;
	// 		$record['active'] = 0;
	
	// 		$deactivateUserTarget = new UserTargets([], $this->authJWTstring);
	// 		$isClosed = $deactivateUserTarget->updateUserTargetsRecord($recordID, $record);
	
	// 		if ($isClosed['status'] !== 'success') {
	// 			return array('error' => $isClosed['message']);
	// 		}
	// 	}
	// }

	/*
	This will return only records of spectator,  player, or captured player
	Host records should not be returned here
	this is active users
	*/
	protected function getPlayerRecordFromGamesXUsers($userID, $gameID){ 
		$tables = array(
			'gamesXusers'
		);
		$where = array(
			'userID' => $userID,
			'gameID' => $gameID,
			'participantTypeID' => array(PARTICIPANT_TYPE_SPECTATOR, PARTICIPANT_TYPE_ACTIVE_PLAYER, PARTICIPANT_TYPE_CAPTURED_PLAYER),
			'active' => 1
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
		 //print_r(array("result"=>$result));die();
		if($result['status'] == 'success' && $result['returnedItems'] > 0){
			unset($result['data'][0]['availableItems']);
			return $result['data'][0];
		}
		else {
			return false;
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

	 protected function getTransactionXProductForSubscriptionsByUserID($userID){
		$tables = array(
			'transactionsXproducts',
			'transactions'
		);
		$where = array(
			'userID' => $userID,
			'productID' => array(MONTHLY_SUBSCRIPTION, ANNUAL_SUBSCRIPTION),
			'transactionsXproducts.transactionID' => '`transactions`.`id`',
			'transactionsXproducts.active' => 1
		);
		$columnsArray = array(
			'transactionsXproducts.id',
			'transactionsXproducts.transactionID',
			'transactionsXproducts.productID',

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
	
		if($result['status'] == 'success' && $result['returnedItems'] > 0){
			unset($result['data'][0]['availableItems']);
			return $result['data'];
		}
		else {
			return false;
		}
	}

	protected function deactivateTransactionXProduct($transactionID){
		$tables = 'transactionsXproducts';
		$where = array(
			'transactionID' => $transactionID
		);
		$result = $this->db->update($tables, array('active' => 0), $where );
		
		return $result;
	}

	protected function deactivateSubscription($transactionID, $userID){
		$deactivatePreviousTransaction = $this->deactivateTransactionRecordByTransactionID($transactionID);
		if($deactivatePreviousTransaction['status'] == 'success'){
			$deactivateTransactionXProduct = $this->deactivateTransactionXProduct($transactionID);
			if($deactivateTransactionXProduct['status'] == 'success'){
				$deactivateSubscription = $this->deactivateSubscriptionByUserID($userID);
				if($deactivateSubscription['status'] == 'success'){
					return true;
				}
				else {
					return $deactivateSubscription;
				}
			}
			else {
				return $deactivateTransactionXProduct;
			}
		}
		else {
			return $deactivatePreviousTransaction;
		}
	}

	protected function deactivateTransactionRecordByTransactionID($transactionID){
		$tables = 'transactions';
		$where = array(
			'id' => $transactionID
		);
		$result = $this->db->update($tables, array('active' => 0), $where );
		return $result;
	
	}

	protected function deactivateSubscriptionByUserID($userID){
		$tables = 'subscriptions';
		$where = array(
			'userID' => $userID
		);
		$result = $this->db->update($tables, array('active' => 0), $where );
		return $result;
	}
	public function verifyApplePurchase($receiptData, $isSandbox = false) {
		$endpoint = $isSandbox
        ? 'https://sandbox.itunes.apple.com/verifyReceipt'
        : 'https://buy.itunes.apple.com/verifyReceipt';

		$postData = json_encode(['receipt-data' => $receiptData, 'password' => APPLE_SHARED_SECRET]);

		$ch = curl_init($endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

		$response = curl_exec($ch);
		curl_close($ch);

		return json_decode($response, true);
	}

	// /**
	//  * Description: Checks if user can join game based on transaction history
	//  *	Phase 2 rules - if user is paid subscriber, they can join any game
	//  *	Phase 2 rules - if user is not paid subscriber, they can join any game if they have game credits
	//  *  Phase 2 rules - if user is not paid subscriber, and haven't paid to join, then they can only join 1 game
	//  * @param 
	//  * @return 
	//  */
	// protected function canUserJoinGameBasedOnTransactionHistory($userSubscriberArray, $userTokenArray, $isUserJoinedToGame){

	// 	if($userSubscriberArray != null ){
	// 		$access =  array('access' => 'granted', 'product' => 'subscription', "details" => $userSubscriberArray);
	// 	}
	// 	elseif($isUserJoinedToGame == false) {
	// 		$access =  array('access' => 'granted', 'product' => 'oneFreeGame');
	// 	}
	// 	elseif ($userTokenArray['playerTokens'] >= 1) { 
	// 		$access =  array('access' => 'granted', 'product' => 'playerTokens', "quantity" => $userTokenArray['playerTokens']);
	// 	}
	// 	else {
	// 		$access =  array('access' => 'denied', 'subscriptionDetails' => $userSubscriberArray, 'tokens' => $userTokenArray['playerTokens']);
	// 	}
	// 	return $access;
		
	// }

	// Function to append failed results to a file
	function appendFailedResultsToFile($failedData, $filePath) {
		// Data to append
		$time = date('Y-m-d H:i:s');

		// Check if the file exists, if not, create it
		// if (file_exists($filePath)) {
		// 	$file = fopen($filePath, 'a'); // 'a' mode opens the file for writing only; creates the file if it doesn't exist
		// 	fclose($file);
		
		// }

		// Append data to the file
		file_put_contents($filePath, "Time: $time - $failedData\n", FILE_APPEND);
	}

	

	public function publishToSocket($dataToSend, $gameID,  $userID, $navigationStatus, $type = "update", $tableName = "users"){
		
		$socketPublisher = new SocketPublisher(null, $this->authJWTstring);
	
		$data = $socketPublisher->createSocketBodyData($dataToSend, $gameID, $navigationStatus, $userID);

		$socketEvent = $socketPublisher->publishEventWithCurl($data, $type, $userID, $tableName);

		$jsonData = json_encode($data);
		$jsonSocketEvent = json_encode($socketEvent);
		$this->storeTestingData('ESINK', $socketEvent['status'], "SentToUserID: $userID, GameID: $gameID, jsonData: $jsonData" ,__FILE__, __LINE__ );

		return $socketEvent; //return array with status and message/details
		
		
	}
	public function getSocketUUIDByUserID($userID){
       $table = 'users';
       $columnArray = array('socketUUID');
       $whereArray = array('id' => $userID);
       $results = $this->db->select($table, $whereArray, array(), array(), $columnArray);
       return $results['data'][0]['socketUUID'];
    }

	protected function createOrUpdateUserMediaStorageDirectoryAndSymbolicLink($userID, $fileToPointTo){
		$alphaNumericUserID = $this->alphaID($this->claimUserID);
		$storageFilePath = $this->createStorageDirectoryIfNeeded($alphaNumericUserID);
		//create a url that will be a symbolic link for the user profile image - this will be updated to latest profile snapshot
		
		$symbolicLinkPath = $this->createOrUpdateUserProfileSymbolicLink($alphaNumericUserID, $fileToPointTo);
		return $symbolicLinkPath;
	}

	protected function createOrUpdateUserProfileSymbolicLink($alphaNumericUserID, $fileToPointTo ){
		$symbolicLinkDirectory = $_SERVER['DOCUMENT_ROOT'] . '/uploads/users/'. $alphaNumericUserID;
		$linkPath = $symbolicLinkDirectory . '/' . $alphaNumericUserID . '-profile.png';

		//check if symbolic link exists
		if(is_link($linkPath)){
			//remove the symbolic link
			unlink($linkPath);
		}

		//create the symbolic link pointing to profile image
		symlink($fileToPointTo, $linkPath);

		return $linkPath;
	}

	
	function addUserXTargetRecord($userID, $targetID, $gameID, $targetStatus, $isActive, $closedDateTime = null){
		$columnsArray = array(
			'userID' => $userID,
			'targetID' => $targetID,
			'gameID' => $gameID,
			'startDateTime' => date('Y-m-d H:i:s'),
			'closedDateTime' => $closedDateTime ?? null,
			'targetStatusID' => $targetStatus,
			'active' => $isActive
		);
		$table = 'usersXtargets';

		$results = $this->db->insert($table, $columnsArray, $requiredFieldsArray = array());

		//check for an error
		if(isset($results['error'])){
			return $results;
		}
		else {
			$result = array("userId"=>$userID, "targetID"=>$targetID);
		}
		return $result;
	}
	public function errorHandling($line){
		$backtraceLine =debug_backtrace()[0]['line'];
		echo "The line for the error is at: $line";
		echo "Using backtrace, the line where this method was called is: ".  $backtraceLine;
		echo "The current line is " . __LINE__;
	}

	/**
	 * Sets a key-value pair in Redis and handles any errors.
	 * If the key exists it will overwrite the value.
	 * 
	 *
	 * @param string $key The key to set in Redis.
	 * @param mixed $value The value to set for the key in Redis.
	 *
	 * @return bool|array Returns `true` if the key is successfully set in Redis, or an array  with an error message if there's an error setting the key.
	 */
	protected function setRedisKey($key, $value) {
		$setResult = $this->redis->setKey($key, $value);
		
		if ($setResult !== 'OK') {
			http_response_code(400);
			$errorMsg = "Oops, there was an error while setting the Redis key. Please contact support with code 95.";
			$debugInfo = array('key' => $key, 'value' => $value, 'setResult' => $setResult);
			$navigation = 'Login';
			return $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation);
		}
		
		return true;
	}

	/**
	 * Updates the users' total active targets in Redis based on the given user targets.
	 *
	 * This method calculates the number of active targets from the provided user targets
	 * and updates the corresponding Redis key with the total count. If there are no active
	 * targets, the Redis key is deleted.
	 *
	 * @param array $userTargets An array containing user targets.
	 * @param int $userID The ID of the user.
	 * 
	 * @return bool|string|null Returns `true` if the update is successful, 
	 *                          or a string representing an error message if the update fails.
	 *                          Returns `null` if the operation is successful but no action is taken.
	 */
	protected function updateRedisActiveTargetsTotal($userTargets, $userID) {
		$numActiveTargets = count($userTargets);
		$key = 'user' . $userID . REDIS_TARGET_STRING;
	
		if ($numActiveTargets > 0) {
			$result = $this->setRedisKey($key, $numActiveTargets);
		} else {
			$result = $this->redis->deleteKey($key);
		}
	
		if ($result !== true) {
			return $result;
		}
	
		return true; // Indicate success
	}

	/**
	 * Description: Adds a new notification (and supporting data) to the database. ONLY USE FOR SINGLE MESSAGE SENDING
	 * NOT FOR SENDING GROUP MESSAGES
	 *
	 * @param string $shortMessage The short message to include in the notification. - like a title
	 * @param string $longMessage The long message to include in the notification. - like a body
	 * @param int $userID The ID of the user to whom the notification is sent.
	 * @param int|null $statusID The ID of the status associated with the notification. Default is `null`.
	 * @param int $notificationTypeID The ID of the notification type. Default is `1`.
	 * @return int $notificationID The ID of the newly added notification.
	 * 
	 * Message is added wiht short (title) and long message (body)
	 * Notification is added with messageID, userID, statusID, sendDate, notificationTypeID (api only adjusts first 2)
	 */
	protected function createSingleNotification($shortMessage, $longMessage, $userID, $statusID = null, $notificationTypeID = 1){
		$messageID = $this->addMessage($shortMessage, $longMessage);
		$notificationID = $this->addNotification($messageID, $userID, $statusID, $notificationTypeID);
		return $notificationID;
	}
/**
 * Description: Adds a new notification to the database. 
 *
 * @param int $messageID
 * @param int $userID
 * @param int $statusID //can be null
 * @param int $notificationTypeID //defaults to 1 - push notification
 * @return int $notificationID The ID of the newly added notification.
 */
	protected function addNotification($messageID, $userID, $statusID = null, $notificationTypeID = 1){
		$table = 'notifications';
		$columnsArray = array(
			'messageID' => $messageID,
			'userID' => $userID,
			'statusID' => $statusID,
			'sendDate' => date('Y-m-d H:i:s'),
			'notificationTypeID' => $notificationTypeID
		);
		$results = $this->db->insert($table, $columnsArray, $requiredFieldsArray = array());
		$notificationID = $results['id'];
		return $notificationID;
	}

	/**
	 * Description: Adds a new message to the database. Used for notifications
	 *	//TODO can turn this into a class as we expand our notifications, but keeping it simple for now
	 * @param string $shortMessage
	 * @param string $longMessage
	 * 
	 * @return int $messageID The ID of the newly added message.
	 */
	protected function addMessage($shortMessage, $longMessage){
		$table = 'messages';
		$columnsArray = array(
			'shortMessage' => $shortMessage,
			'longMessage' => $longMessage
		);
		$results = $this->db->insert($table, $columnsArray, $requiredFieldsArray = array());
		$messageID = $results['id'];

		return $messageID;
	}

	/**
	 * Retrieves the full name of a user by their user ID.
	 *
	 * This method queries the 'users' table in the database to retrieve the first and last name
	 * of the user corresponding to the provided user ID. It constructs and returns the full name
	 * by concatenating the first and last name.
	 *
	 * @param int $userID The ID of the user whose full name is to be retrieved.
	 *
	 * @return string|false The full name of the user if found, or false if the user is not found
	 *                      or an error occurs during the database operation.
	 */
	public function getFullNameByUserID($userID){
		$table = 'users';
		$where = array(
			'id' => $userID
		);
		$columnsArray = array(
			'fName',
			'lName'
		);
		$result = $this->db->select(
			$table,
			$where,
			array(),    // $whereGreater
			array(),    // $whereLess
			$columnsArray, // $columnsArray
			'',         // $whereMore
			false       // $groupByColumn
		);
		if($result['status'] == 'success'){
			return $result['data'][0]['fName'] . " " . $result['data'][0]['lName'];
		}
		else {
			return false;
		}
	}

	protected function httpMethodAccessCheck(){
		$isAccessGranted = false;
		//do we have an admin uuid
		$isAdmin = $this->isAdmin();
				
		//echo($this->accessClientListArray['PUT']['admin'] );
		if($isAdmin){
			
			if($this->accessClientListArray[$this->httpMethod]['admin'] == true){
				
				$isAccessGranted = true;
			}

		}
		else if(!empty($this->authJWTstring)){
			
			if($this->accessClientListArray[$this->httpMethod]['user'] == true){
				
				$jwtInfoArray = $this->jwtValidation($this->authJWTstring);
				if($jwtInfoArray != false){
					
					$isUserActive = $this->checkUserActiveByUserID($jwtInfoArray['claimUserID']);
					if($isUserActive == true){
						$isAccessGranted = true;
					}
				}
			}
		}
		else if($this->accessClientListArray[$this->httpMethod]['all'] == true) { //how to identify public endpoints

			$isAccessGranted = true;
		}
		
		return $isAccessGranted;
	}

	protected function jwtValidation($authJWTstring){
		//TODO check for $this->authJWTstring
			$authJWTstring;
			$key  =  InMemory::base64Encoded(JWT_SECRET_KEY);
			
			/*
			|--------------------------------------------------------------------------
			|        VALIDATE JWT using lcobucci/jwt against signature and expiration
			|--------------------------------------------------------------------------
			*/

			$parser = new Parser(new JoseEncoder());
			$clock = UTCClock::create();

			$token = $parser->parse(
				$authJWTstring // The string representation of the token
			);

			$validator = new Lcobucci\JWT\Validation\Validator();

			try {
				$validator->assert($token, new StrictValidAt($clock)); // doesn't throw an exception
				$validator->assert($token, new SignedWith(new Sha256(), $key));
			} catch (RequiredConstraintsViolated $e) {
				// list of constraints violation exceptions:
				http_response_code(400);
				$jsonData = json_encode($e->violations()[0]->getMessage());
				header('Content-Type: application/json');
				$this->authInfoArray['status'] = JWT_STATUS_INVALID;
				$debugInfo = array(
					'line'=>__LINE__,
					'file'=>__FILE__,
					'error'=>$e->violations()[0]->getMessage()
				);
				$errorMsg = "Oops! There was an error with your JWT. Please log out and log back in. $jsonData";
				$navigation = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
				die(json_encode($response));
				
				// var_dump($e->violations()); //TODO make return more friendly
			}
			//TODO - discover where vars below are used and cleanup.
			$this->userRoles = $token->claims()->get('userRoles');
			$this->claimUserID = $token->claims()->get('userID');
			$this->jwtExpirationTimestamp = $token->claims()->get('exp')->getTimestamp();


			$this->authInfoArray['expires'] = $this->convertJWTEpochToDateTime(); //TODO cut out conversion, just return get('epoch') and work with it
			$this->authInfoArray['status'] = $this->getAuthStatusBasedOnExpire();
			$this->authInfoArray['userRoles'] = $this->userRoles; 
			$this->authInfoArray['claimUserID'] = $this->claimUserID;
			$this->authInfoArray['jwtExpirationTimestamp'] = $this->jwtExpirationTimestamp;
			
			return $this->authInfoArray;

			
	}
	//TODO see if this method is duplicated elsewhere
	protected function checkUserActiveByUserID($userID){
		//check if user from JWT is active
		$response = $this->db->select('users', array('id'=>$userID,'active'=> 1), $this->db->whereGreater,$this->db->whereLess, array('id'), '', true);
		if ($response == false ){
			return $response;
		}
		else {
			return true;
		}
	}
	

}	