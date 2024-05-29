<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'vendor/autoload.php';
require_once 'config.php';


 class API
{
	/**
	 * Property: method
	 * The HTTP method this request was made in, either GET, POST, PUT or DELETE
	 */
	protected $method 	= '';
	/**
	 * Property: endpoint
	 * The Model requested in the URI. eg: /files
	 */
	protected $endpoint = '';
	/**
	 * Property: verb
	 * An optional additional descriptor about the endpoint, used for things that can
	 * not be handled by the basic methods. eg: /files/process
	 */
	protected $verb 	= '';
	protected $id 	= '';
	
	/**
	 * Property: args
	 * Any additional URI components after the endpoint and verb have been removed, in our
	 * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
	 * or /<endpoint>/<arg0>
	 */
	protected $args 		= Array();
	/**
	 * Property: file
	 * Stores the input of the PUT request
	*/
	protected $file 		= Null;
	protected $httpBody 	= Null;
	protected $domain 	= NULL;
	protected $data;
	protected $endpointObjects= array();
	protected $ip;
	public $request;
	public $authJWTstring;
	public $queryParameters; //GET
	protected $json = null;
	protected $uploadedFile = null;
	
	/**
	 * Constructor: __construct
	 * Allow for CORS, assemble and pre-process the data
	 */
	public function __construct($endpoint,$authJWTstring = null) {

		$this->endpointObjects = ENDPOINT_OBJECTS;

		if (is_string($endpoint)) {
			$endpoint = rtrim($endpoint, '/');
			$this->args = explode('/', $endpoint);
		} elseif (is_array($endpoint)) {
			$this->args = $endpoint;
		} else {
			$this->args = [];
		}
		$this->endpoint = $endpoint;
		$this->method = $_SERVER['REQUEST_METHOD'];
		if($this->endpoint == 'selfies' && $this->method == 'GET'){
			header('Content-Type: image/png');
			// die();	
		}
		else {
			header('Content-Type: application/json');
		}
		if (isset($_SERVER['HTTP_AUTHORIZATION'])){
    	$authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
		$this->authJWTstring = str_replace('Bearer ', '', $authorizationHeader);
		}
		
		$this->ip = $_SERVER['REMOTE_ADDR'];
		if (isset($_SERVER['HTTP_ORIGIN'])){
			$this->domain = $_SERVER['HTTP_ORIGIN'];
		}
		else {
			$this->domain = '*';
		}
		if (!headers_sent()) {
		header("Access-Control-Allow-Origin: $this->domain");
		}
// 		header("Access-Control-Allow-Methods: POST, PUT, DELETE, GET, OPTIONS",FALSE);
#######################		
		if ($this->method == 'OPTIONS' && !empty($endpoint)){
			// e.g. GET, POST, PUT, DELETE
			if (is_string($endpoint)) {
				$endpoint = ucfirst($endpoint);
			}
			$file = $endpoint.".php";
			require_once ($file);
			
			$endPointClass = new $endpoint($this->data, $this->authJWTstring);
			$classMethods = get_class_methods($endPointClass);
			$methodArray = array(
				'display'.$endpoint=>'GET',
				'add'.$endpoint=>'POST',
				'update'.$endpoint=>'PUT',
				'remove'.$endpoint=>'DELETE');
			//check $classMethods for the methods 'display'.$Endpoint, 'add'.$Endpoint, 'update'.$Endpoint, 'delete'.$Endpoint
			// if they exist, then add them to the $allowedMethods string
			$allowedMethods = '';
			foreach ($methodArray AS $method=>$type){
				if (in_array($method,$classMethods)){
					$allowedMethods .= $type.', ';
				}
			}
			$allowedMethods .= 'OPTIONS';
			header("Access-Control-Allow-Methods: $allowedMethods");
			header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
			header("Access-Control-Max-Age: 86400");
			echo json_encode(explode(', ', $allowedMethods));
			die();
		}
		
		elseif ($this->method == 'OPTIONS' && empty($endpoint)) {
			$allowedMethods = 'POST, ';
			$allowedMethods .= 'OPTIONS';
			header("Access-Control-Allow-Methods: $allowedMethods");
			header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
			header("Access-Control-Max-Age: 86400");
			echo json_encode(explode(', ', $allowedMethods));
			die();# code...
		}
		if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
			if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
				$this->method = 'DELETE';
			} elseif ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
				$this->method = 'PUT';
			} else {
				throw new Exception("Unexpected Header");
			}
		}
		switch($this->method) {
			case 'DELETE':
				$this->request = $_GET;
				$this->data = $this->request;
				break;
			case 'GET':
				if (!headers_sent()) {
					header("Content-Type: application/json");
				}
				// header("Content-Type: application/json");
				$this->queryParameters = ($_GET);
				$this->data = $this->queryParameters;
				
				break;
			case 'POST': //Same as Put now
			case 'PUT':
				header("Content-Type: application/json");
				$this->request = ($_GET);
				//Check to see if there is a file upload. If there is, then expect the json data as request['json']
				// change the error message to be more specific to say that the json data is missing
				if (isset($_FILES['snapshot'])){
					$this->uploadedFile = $_FILES['snapshot'];
					//add to if statement to check if endpoint is Snapshots and if method is POST
					if((!isset($_POST['json'])&& $endpoint == 'snapshots' && $this->method == 'POST' )){
						//NOTE: This is only created because json data is required to move the project forward
						//HACK - this is to get around the fact that we can't receive a file and json data in the same request
						$testData = array('testJson'=>true,'ReasonLocation'=>array('File'=>'API.Class.php','Line'=>168));
						$json = json_encode($testData);
						$this->httpBody = $json;
					}
					elseif((isset($_POST['json']) )){
						$this->httpBody = $_POST['json'];
						// unset($_POST['json']);
						$this->data = $this->request;
					}
					else {
						$errorMsg =  json_encode(array(
							"error"=>  __LINE__ . ": JSON missing, you attempted to upload a file without any additional info. ". 
							"Make sure you send JSON as part of the HTTP query named 'json'.", 
							"example"=> "{\"targetID\":123,\"snapshotLocation\":{\"lat\":123.123,\"lng\":123.123}}"));
						echo  str_replace(["\\n", "\n", "\r", "\\"], '', $errorMsg);
						die();	
					}
					
					
				}
				else {
					$this->httpBody = file_get_contents("php://input");
				}

				$this->data = (array)$this->request; // Start with $this->request data

			$jsonData = $this->httpBody; 
			$arrayData = json_decode($jsonData, true);
			if ($arrayData === null && json_last_error() !== JSON_ERROR_NONE) {
				$errorMessage = json_last_error_msg();
				// Handle the error, log it, or display an error message
				$errorMsg =  json_encode(array("error"=>  __LINE__ . ": JSON decoding error, $errorMessage - look for missing or extra commas in your payload",'payload'=>$jsonData));
				echo  str_replace(["\\n", "\n", "\r", "\\"], '', $errorMsg);
				die();
			} else {
				$this->data = array_merge($this->data, $arrayData);
			}				
				$this->data = array_merge((array)$this->request, 
								(array)json_decode($this->httpBody,true));
				$this->data['auth'] = $this->authJWTstring;
				break;

			default:
				$this->_response('Invalid Method', 405);
				break;
		}
		if (isset($this->data['request'])){
			unset($this->data['request']);
		}
	}
	public function processAPI() {
		
		//Unlikely to ever be true, but just in case
		if (method_exists($this, $this->endpoint)) {
			//The $this->{$this->method} allows calling a method as $var... 
			//In this case it is what is returned in the endpoint. e.g. businesses
			//the ($this->args) is passing data to the method 
			
			return $this->_response($this->{$this->endpoint}($this->args, $this->authJWTstring),http_response_code());
		}
		elseif (in_array($this->endpoint, $this->endpointObjects)) {
		
			$method = $this->method;
			$name = ucfirst($this->endpoint);
// 			$file = $name.".php";
// 			require_once ($file);
			//print_r(array("endpoint"=>$this->endpoint,"method"=>$method,"name"=>$name));die();
			$data = new $name($this->data,$this->authJWTstring);

			if(!empty($data->error)){
				http_response_code(400);
			}


			return $this->_response($data->$method(),http_response_code());
		}
		else{
			$errorArray = array('error'=>__LINE__.": No Endpoint Given","Available Endpoints"=> $this->endpointObjects, "Endpoint Given"=>$this->endpoint);
			return $this->_response($errorArray, 404);
			}
		}

	private function _response($data, $status = 200) {
		if (is_array($data)){
			if (!empty($data['error'])){
				if ($status < 400){
					$status = 400;
					http_response_code(400);
				}
			}		
			header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
			$data['debug']['totalProcessTime'] = round(microtime(true) - $GLOBALS['processTime'], 7);
			//return json_encode($data, 10);	//FIXME not sure how this is working.  Not sure what 10 is for, it is not a valid option or flag
			return json_encode($data, JSON_PRETTY_PRINT);	
		}
		else {
			$status = 400;
			http_response_code(400);
			header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
			return '{"status": 400, "data": null, "error": "'.__LINE__.' - Internal issue. The data type given was not an array. Method may not have returned anything."}'; 
		}
	}

	public function _requestStatus($code) {
		$status = array(
			200 => 'OK',
			201 => 'CREATED',
			204 => 'NO CONTENT',
			250 => 'NO ERROR AND NO DATA RETURNED',
			400	=> 'USER INPUT ERROR',
			404 => 'NOT FOUND',
			405 => 'METHOD NOT ALLOWED',
			500 => 'INTERNAL SERVER ERROR',
		);
		return ($status[$code])?$status[$code]:$status[500];
	}

	
	
	
}