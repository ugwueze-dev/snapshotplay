<?php
/**
  _______ _____            _   _  _____         _____ _______ _____ ____  _   _  _____ 
 |__   __|  __ \     /\   | \ | |/ ____|  /\   / ____|__   __|_   _/ __ \| \ | |/ ____|
    | |  | |__) |   /  \  |  \| | (___   /  \ | |       | |    | || |  | |  \| | (___  
    | |  |  _  /   / /\ \ | . ` |\___ \ / /\ \| |       | |    | || |  | | . ` |\___ \ 
    | |  | | \ \  / ____ \| |\  |____) / ____ \ |____   | |   _| || |__| | |\  |____) |
    |_|  |_|  \_\/_/    \_\_| \_|_____/_/    \_\_____|  |_|  |_____\____/|_| \_|_____/ 
 
 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayTransactions() method.
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
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Google\Service\AndroidPublisher;
use Google\Service\AndroidPublisher\Resource\PurchasesProducts;
require_once 'GoogleConnectors.php';


class Transactions extends SnapshotDataClass{
	public $client;
	protected $quantity;
	protected $productID;    
	protected $platformProductID;
	protected $platformTransactionID;    
	protected $purchaseDate;    
	protected $purchaseDateMS;    
	protected $expiresDate;   
	protected $ownershipType;   
	protected $paymentPlatform;
	protected $isPaymentDebug = true;
	function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring);

		$this->accessClientListArray['POST']['admin']   = true; //THIS WILL ALLOW ADMIN TO ADD/GIFT TOKENS TO PLAYERS
		$this->accessClientListArray['POST']['all']     = true; //THIS PERMISSION IS TO ALLOW ACCESS FOR ALL TO CREATE A USER
		$this->accessClientListArray['POST']['user']    = true;
		//$this->accessClientListArray['GET']['admin']    = true; //NOT AVAILABLE ON THIS ENDPOINT - IMPLEMENT IN FUTURE FOR ADMIN LOOKUP
		// $this->accessClientListArray['GET']['all']      = true;
		//$this->accessClientListArray['GET']['user']     = true;
		//$this->accessClientListArray['PUT']['admin']    = true;
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

		//SETUP FOR PARAMS
		if($this->params['isDebug'] == 'true'){
			$this->isPaymentDebug = true;
		}
		
		$deviceType = strtolower($this->params['deviceType']);
		if($deviceType == 'android'){
			$this->paymentPlatform = PLATFORM_GOOGLE;
		}
		else if($deviceType == 'ios'){
			$this->paymentPlatform = PLATFORM_APPLE;
		}
		else if($deviceType == 'stripe'){
			$this->paymentPlatform = PLATFORM_STRIPE;
		}
		else if($deviceType == 'system'){
			$this->paymentPlatform = PLATFORM_SYSTEM;
		}
		else {
			$this->paymentPlatform = null;
		}

		

		
		$this->id = isset($this->params['id']) ? $this->params['id'] : null;
		// $this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
		// 	return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
		// });
	
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
// 	function displayTransactions(){
// 		/*/
// 		|--------------------------------------------------------------------------
// 		|         Access is limited to ADMIN Only
// 		|--------------------------------------------------------------------------
// 		*/

// 		$isAdmin = $this->isUserAdmin();
// 		if(!$isAdmin){
// 			http_response_code(401);
// 			$errorMsg = "Oops! You don't have permission to access this endpoint.";
// 			$debugInfo = array("details" => "Only admins have access to  ".ENDPOINT ." endpoint" . __LINE__ . " " . __FILE__);
// 			$navigation = null;
// 			$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo,  $navigation); //can add $debugInfo as 4th param
// 			return $response;
// 		}
// 		$return =array();
// 		if (@$this->id > 0){
// 			$response = $this->db->getRecord($this->id);
// 			$this->availableItems = $this->db->availableItems;
// 			//  return $this->db->getRecord($this->id);
// 		}
// 		else {
// 			$this->processWhere($this->params);
// 			$response = $this->db->select($this->db->tableJoins,$this->db->where,$this->db->whereGreater,$this->db->whereLess);
// 		}
// 		$results = $response['data'];
// 		$this->mainQuery		 	= $this->db->fullQuery;
// 		$this->availableItems = $this->db->availableItems;
// 		/*/
// 		 |--------------------------------------------------------------------------
// 		 |Add other details as needed
// 		 |--------------------------------------------------------------------------
// 		 */
// // 		foreach ($results AS $keyIndex=>$row){
// // 			$listID = $row['id'];
// // 			$results[$keyIndex]['admins']	= $this->getAdminsForLists($listID);
// // 			$results[$keyIndex]['contacts']	= $this->getContactsForLists($listID);
// // 		}
// 		return  $this->prepareReturn($results);
// 	}
	/**
	  _____   ____   _____ _______
	 |  __ \ / __ \ / ____|__   __|
	 | |__) | |  | | (___    | |
	 |  ___/| |  | |\___ \   | |
	 | |    | |__| |____) |  | |
	 |_|     \____/|_____/   |_|
	 */
	function addTransactions() {
		/*/
		|--------------------------------------------------------------------------
		|         Access is limited to Admin and User
		|--------------------------------------------------------------------------
		*/
		/*
		 1. Check if user is admin
		 2. Check if user is logged in
		 3. Add transaction
		 4. Add transactionXproducts record
		 5. Add either gameTokens or subscriptions record (based on product)

		*/

		$isAdmin = $this->isAdmin();
		if($isAdmin == true){ //remove once activating admin ability to add transactions
			$errorMsg = "This functionality has not been implemented for Admins.";
			$debugInfo = [
				"message" => "This functionality has not been implemented for Admins.",
				"location" => __FILE__ . " line " . __LINE__,
			]; 
			$response = $this->createApiResponse(400, null, "This functionality has not been implemented for Admins.", $debugInfo, null); //can add $debugInfo as 4th param
			return $response;
		}
		//This check is in place to ensure that only logged in users can access this endpoint or admin, see accessClientListArray in constructor
		if(!$isAdmin && !isset($this->claimUserID)){
			
			http_response_code(401);
			$errorMsg = "Oops! You don't have permission to access this endpoint.";
			$debugInfo = array("details" => "Only admins and logged in users have access to  ".ENDPOINT ." endpoint" . __LINE__ . " " . __FILE__);
			$navigation = null;
			$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo,  $navigation); //can add $debugInfo as 4th param
			return $response;
		}
		else if($isAdmin == true){
			
		}
	
		$this->params['active'] = 1;
		/*
		 |--------------------------------------------------------------------------
		 |  VERIFY GOOGLE PURCHASE
		 |--------------------------------------------------------------------------
		*/

		if($this->paymentPlatform == PLATFORM_GOOGLE){
			
			//set variables
			$dataAndroid = json_decode($this->params['dataAndroid']);
			$orderID = $dataAndroid->orderId;
			$platformProductID = $dataAndroid->productId;
			$purchaseTime = $dataAndroid->purchaseTime;
			$purchaseDate = date('Y-m-d H:i:s', $purchaseTime / 1000);
			$purchaseState = $dataAndroid->purchaseState;
			$purchaseToken = $dataAndroid->purchaseToken;
			$autoRenewing = $dataAndroid->autoRenewing;
			$packageName = $dataAndroid->packageName;
			$this->quantity = $dataAndroid->quantity;

			$this->setProductIdFromPlatformVerification($platformProductID);
			
			// Path to your service account key file
			$serviceAccountKeyFilePath = 'oauthCredentials/playAdminJsonKey.json';
			
			$client = GoogleConnectors::connectToGoogleAndroidPublisher($serviceAccountKeyFilePath, $this->productID, $packageName, $platformProductID, $purchaseToken);

			if ($client) {
				$requestUrl = $client->getConfig('url');
				
				// Get the response using the constructed URL
				$res = $client->get($requestUrl);
				if ($res instanceof \Psr\Http\Message\ResponseInterface) {
					//HANDLE SUCCESSFUL RESPONSE
					if($res->getStatusCode() == 200){
						$res = json_decode($res->getBody(), true);
						$this->db->fieldsArray = array(
				
							'userID'				=> $this->claimUserID,
							'transactionPlatformID'	=> $this->paymentPlatform,
							'transactionPlatformTransactionID'	=> $orderID, //set after receipt validation
							'platformTokenOrReceipt'			=> $purchaseToken,
							'datePurchased'			=> $purchaseDate, //we get these from the transaction platform
							'notes'					=> @$this->params['notes'],
							'refunded'				=> 0,
							'active'				=> 1,
						);
						$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
							return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
							
						});
					}
					else {//HANDLE UNSUCCESSFUL RESPONSE
						$res = json_decode($res->getBody(), true);
						http_response_code(400);
						$errorMsg = "Oops! Something went wrong, your Transaction was not added.";
						$debugInfo = array("details" => $res . __LINE__ . " " . __FILE__);
						$navigation = null;
						$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
						return $response;
					}
				} 
				else {
					// Handle unexpected object type
					
					http_response_code(400);
						$errorMsg = "Oops! Something went wrong, your Transaction was not added.";
						$debugInfo = array("details" => $res . __LINE__ . " " . __FILE__);
						$navigation = null;
						$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
						return $response;
				}
			} else {
				// Handle connection failure
				http_response_code(400);
				$errorMsg = "Oops! Something went wrong, there was an issue verifying the purchase through Google Play. Contact support@connectandconquer.com for help.";
				$debugInfo = array("details" => "Connection to Google Play failed" . __LINE__ . " " . __FILE__ , 'client' => $client);
				$navigation = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
			}
			
			
			
			
		}
		/*
		 |--------------------------------------------------------------------------
		 |  VERIFY APPLE PURCHASE
		 |--------------------------------------------------------------------------
		*/
		else if($this->paymentPlatform == PLATFORM_APPLE){
			//if apple store verify through apple
			$receipt = $this->params['transactionReceipt'];
			if(empty($receipt) || !isset($receipt) || $receipt == null){
				http_response_code(400);
				$errorMsg = "Oops! A transaction receipt is required to verify purchase.";
				$debugInfo = array("details" => "Transaction receipt not set correctly" . __LINE__ . " " . __FILE__);
				$navigation = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
				return $response;
			}
					
			$verifyResult = $this->verifyApplePurchase($receipt, $this->isPaymentDebug);

	
			if($verifyResult['status'] == 0){
				/*
				SAMPLE Response for IOS
				"verifyResult": {
				 "environment": "Sandbox",
					"receipt": {
						"receipt_type": "ProductionSandbox",
						"adam_id": 0,
						"app_item_id": 0,
						"bundle_id": "com.snapshotplay",
						"application_version": "18",
						"download_id": 0,
						"version_external_identifier": 0,
						"receipt_creation_date": "2024-02-01 20:11:43 Etc/GMT",
						"receipt_creation_date_ms": "1706818303000",
						"receipt_creation_date_pst": "2024-02-01 12:11:43 America/Los_Angeles",
						"request_date": "2024-02-09 15:53:19 Etc/GMT",
						"request_date_ms": "1707493999395",
						"request_date_pst": "2024-02-09 07:53:19 America/Los_Angeles",
						"original_purchase_date": "2013-08-01 07:00:00 Etc/GMT",
						"original_purchase_date_ms": "1375340400000",
						"original_purchase_date_pst": "2013-08-01 00:00:00 America/Los_Angeles",
						"original_application_version": "1.0",
						"in_app": [
							{
								"quantity": "1",
								"product_id": "SP_1",
								"transaction_id": "2000000514753269",
								"original_transaction_id": "2000000514753269",
								"purchase_date": "2024-02-01 20:11:34 Etc/GMT",
								"purchase_date_ms": "1706818294000",
								"purchase_date_pst": "2024-02-01 12:11:34 America/Los_Angeles",
								"original_purchase_date": "2024-02-01 20:11:42 Etc/GMT",
								"original_purchase_date_ms": "1706818302000",
								"original_purchase_date_pst": "2024-02-01 12:11:42 America/Los_Angeles",
								"expires_date": "2024-02-01 20:16:34 Etc/GMT",
								"expires_date_ms": "1706818594000",
								"expires_date_pst": "2024-02-01 12:16:34 America/Los_Angeles",
								"web_order_line_item_id": "2000000050356797",
								"is_trial_period": "false",
								"is_in_intro_offer_period": "false",
								"in_app_ownership_type": "PURCHASED"
							}
						]
					},
						}
				*/
				if(isset($verifyResult['receipt']['in_app'][1]) && $verifyResult['receipt']['in_app'][1] != null){
					//handle receipt with multiple products purchased
					die('multiple products purchased');
				}
				else {
					//todo! refactor this to a method
					/* Convert the purchase date string to a DateTime object */
					$purchaseDateStr = $verifyResult['receipt']['in_app'][0]['purchase_date'];
					
					// Extract the timezone part
					list($datePart, $timePart, $timezonePart) = explode(' ', $purchaseDateStr, 3);

					// Concatenate date and time without the 'Etc/' prefix
					$purchaseDateStr = "$datePart $timePart $timezonePart";

					// Convert the purchase date string to a DateTime object
					$purchaseDate = DateTime::createFromFormat('Y-m-d H:i:s e', $purchaseDateStr);
					/* Date Conversion complete */ //todo! end refactor method

					//$this->setVariablesFromAppleVerify($verifyResult);
					
					$this->quantity = $verifyResult['receipt']['in_app'][0]['quantity'];
					$this->platformProductID = $verifyResult['receipt']['in_app'][0]['product_id'];
					$this->platformTransactionID = $verifyResult['receipt']['in_app'][0]['transaction_id'];
					$this->purchaseDate = $purchaseDate->format('Y-m-d H:i:s');

					$this->purchaseDateMS = $verifyResult['receipt']['in_app'][0]['purchase_date_ms'];
					$this->expiresDate = $verifyResult['receipt']['in_app'][0]['expires_date'];
					$this->ownershipType = $verifyResult['receipt']['in_app'][0]['ownership_type'];
					
					$this->setProductIdFromPlatformVerification($this->platformProductID);
	
					$this->setDBFieldsArrayFromPlatformVerification();
			
					$this->db->fieldsArray = array(
			
						'userID'				=> $this->claimUserID,
						'transactionPlatformID'	=> $this->paymentPlatform,
						'transactionPlatformTransactionID'	=> $this->platformTransactionID, //set after receipt validation
						'platformTokenOrReceipt'			=> $receipt, 
						'datePurchased'			=> $this->purchaseDate, //we get these from the transaction platform
						'notes'					=> @$this->params['notes'],
						'refunded'				=> 0,
						'active'				=> 1,
					);
					$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
						return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
					});
				}
				
				
			}
			else {
				http_response_code(400);
				$errorMsg = "Oops! Something went wrong, your Transaction was not successfully completed.";
				$debugInfo = array("details" => $verifyResult . __LINE__ . " " . __FILE__);
				$navigation = null;
				$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
				return $response;
			}
		}
		
		else {
			http_response_code(400);
			$errorMsg = "Oops! That transaction platform is not currently supported. Please contact support@connectandconquer.com for assistance.";
			$debugInfo = array("details" => "Transaction Platform ID not set correctly" . __LINE__ . " " . __FILE__);
			$navigation = "Menu";
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
			return $response;
		}

		

		//check result and process accordingly
		$addResult = $this->db->addRecord();

		if (!empty($addResult['id']) && $addResult['id'] > 0) {
			$this->id = $addResult['id'];
			//check if product is subscription, do they already have an active subscription? If so, if mark old one as inactive and create new one
			if($this->productID == MONTHLY_SUBSCRIPTION || $this->productID == ANNUAL_SUBSCRIPTION){
				//check if user has an active subscription
				$transactionXproductRecord = $this->getTransactionXProductForSubscriptionsByUserID($this->claimUserID);

				if($transactionXproductRecord != false){
				
					$transactionID = $transactionXproductRecord[0]['id'];
					//deactivate old subscription (including transactionXproduct record and subscriptions record)
					$deactivateSubscriptionResult = $this->deactivateSubscription($transactionID, $this->claimUserID);
					if($deactivateSubscriptionResult['status'] == 'error'){
						http_response_code(400);
						$errorMsg = "Oops! Something went wrong deactivating your old subscription. Please contact support@connectandconquer.com for help.";
						$debugInfo = array("details" => $deactivateSubscriptionResult , 'Location' => __LINE__ . " " . __FILE__);
						$navigation = 'Menu';
						$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
					}
				}
				//continue on to create new subscription record.
				/*****
				 * //TODO! HOW TO HANDLE UPGRADE FROM MONTHLY TO ANNUAL SUBSCRIPTION NEEDS TO BE DECIDED BASED ON HOW FRONT END HANDLES THINGS
				 * IE: DO THEY NEED TO CANCEL MONTHLY SUBSCRIPTION FIRST? OR CAN THEY UPGRADE AND IT WILL AUTOMATICALLY CANCEL MONTHLY SUBSCRIPTION
				 * DO THEY ADJUST THE END DATE OF THE MONTHLY SUBSCRIPTION TO THE DATE OF THE UPGRADE? OR DO THEY LET IT RUN OUT?
				 */
				
			}
			//Add transactionXproducts record
			
			$transactionID = $this->id;
			$addRecordResult = $this->processTransactionXProducts($transactionID, $this->productID, $this->quantity);


			//do I need the error message here? I think it will be handled in the processTransactionXProducts method
			if($this->productID == PLAYER_TOKEN || $this->productID == HOST_TOKEN){
				
				$expirationDateTime = date("Y-m-d H:i:s", strtotime($this->purchaseDate . ' +101 year'));
			    //Add gameTokens record
					$addTokenResult = $this->processGameTokens($this->claimUserID, $transactionID, $expirationDateTime);
			
			}
			else {
					//add record to subscriptions table
					$addSubscriptionResult  = $this->processSubscriptions();
			}
		
		    //Add either gameTokens or subscriptions record (based on product)
			$result = array('status' => 'success', "id" => $this->id, 'message' => 'Your Transaction record was added successfully.');
			return $this->prepareReturn($result);

		} else {
			http_response_code(400);
			$errorMsg = "Oops! Something went wrong, your Transaction was not added.";
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
	// function updateTransactions(){
	// 	/*/
	// 	|--------------------------------------------------------------------------
	// 	|         Access is limited to (Fill in the blank) Only
	// 	|--------------------------------------------------------------------------
	// 	*/
	// 	if (!empty($this->id)) {
	// 		// Update the record with the given ID
	// 		$updated = $this->db->updateRecord($this->db->table, $this->id);

	// 		if ($updated['status']!=='error') {
	// 			// If the update is successful, return the displayed Transactions
	// 			return $this->displayTransactions();
	// 		} else {
	// 			// If there is an error during update, return an error message
	// 			$errorMsg = "Oops! Something went wrong, your Transaction was not updated.";
	// 			$debugInfo = array("details" => $updated['message'] . __LINE__ . " " . __FILE__);
	// 			$navigation = null;
	// 			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo,  $navigation); //can add $debugInfo as 4th param
	// 			return $response;
				
	// 		}
	// 	} else {
	// 		// If no valid ID is given, return an error message
	// 		$errorMsg = "Oops! No valid ID was given: $this->id";
	// 		$navigation = null;
	// 		$debugInfo = null;
	// 		$response = $this->createApiResponse(401, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
	// 		return $response;
			
	// 	}
	// }
	
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
	// function removeTransactions(){
	// 	/*/
	// 	|--------------------------------------------------------------------------
	// 	|         Access is limited to (Fill in the blank) Only
	// 	|--------------------------------------------------------------------------
	// 	*/
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

	//PROCESSING HERE TO CLEANUP POST 


	public function processTransactionXProducts($transactionID, $productID, $quantity){
	
		$params = array(
			'transactionID' => $transactionID,
			'productID' => $productID,
			'quantityPurchased' => $quantity,
			'active' => 1,
		);
		
		$transactionXproducts = new TransactionsXProducts($params, $this->authJWTstring );
		$addRecordResult = $transactionXproducts->addTransactionXProductRecord();
		
		if($addRecordResult['status'] == 'error'){
			http_response_code(400);
			$errorMsg = "Oops! Something went wrong adding your transaction record. Please contact support@connectandconquer.com";
			$debugInfo = array("details" => $addRecordResult , 'Location' => __LINE__ . " " . __FILE__);
			$navigation = 'Menu';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
			return $response;
		}
		else {
			return true;
		}
	}

	protected function setVariablesFromAppleVerify($verifyResult){
		$this->quantity = $verifyResult['in_app']['quantity'];
		$this->productID = $verifyResult['in_app']['product_id'];
		$this->platformTransactionID = $verifyResult['in_app']['transaction_id'];
		$this->purchaseDate = $verifyResult['in_app']['purchase_date'];
		$this->purchaseDateMS = $verifyResult['in_app']['purchase_date_ms'];
		$this->expiresDate = $verifyResult['in_app']['expires_date'];
		$this->ownershipType = $verifyResult['in_app']['ownership_type'];
		// header("Content-Type: application/json");
		// echo json_encode([
		// 	"Line " . __LINE__ => __FILE__,
		// 	'function' => debug_backtrace()[1]['function'] ?? 'N/A',
		// 	"quantity" => $verifyResult['receipt']['in_app'],
		// 	"productID" => $this->productID,
		// 	"platformTransactionID" => $this->platformTransactionID,
		// 	"purchaseDate" => $this->purchaseDate,
		// 	"purchaseDateMS" => $this->purchaseDateMS,
		// 	"expiresDate" => $this->expiresDate,
		// 	"ownershipType" => $this->ownershipType,
		// ]);
		// die();
	}
	
	
	public function processGameTokens($userID, $transactionID, $expirationDate, $numberOfTokens){// we could adjust this to reruire the number of tokens then process realated info

		$params = array(
			'userID' => $userID,
			'transactionID' => $transactionID,
			'expirationDateTime' => $expirationDate,
			'active' => 1,
		);
		
		$gameTokens = new GameTokens($params, $this->authJWTstring);
		// Loop through the number of tokens and add each token record
		for ($i = 0; $i < $numberOfTokens; $i++) {
			$addRecordResult = $gameTokens->addGameTokenRecord($userID, $transactionID, $expirationDate);
			
			// Handle the result if necessary
		}
		//$addRecordResult = $gameTokens->addGameTokenRecord($userID, $transactionID, $expirationDate);
	
		if($addRecordResult['status'] == 'error'){
			http_response_code(400);
			$errorMsg = "Oops! Something went wrong adding your gameToken record. Please contact support@connectandconquer.com";
			$debugInfo = array("details" => $addRecordResult , 'Location' => __LINE__ . " " . __FILE__);
			$navigation = 'Menu';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
			return $response;
		}
		else {
			return true;
		}
	}

	protected function processSubscriptions(){
		//TODO! how to handle upgrades in subscriptions - each user should only have 1 subscription record

		$purchaseDate = new DateTime($this->purchaseDate);
		if($this->productID == MONTHLY_SUBSCRIPTION){
			$expirationDate = $purchaseDate->add(new DateInterval('P1M'));
		}
		else if($this->productID == ANNUAL_SUBSCRIPTION){
			$expirationDate = $purchaseDate->add(new DateInterval('P12M'));
		}
		
		$params = array(
			'userID' => $this->claimUserID,
			'transactionID' => $this->id,
			'expirationDate' => $expirationDate->format('Y-m-d H:i:s'),
			'active' => 1,
		);
		$subscriptionRecord = new Subscriptions($params);
		$addRecordResult = $subscriptionRecord->addSubscriptionRecord();
		if($addRecordResult['status'] == 'error'){
			http_response_code(400);
			$errorMsg = "Oops! Something went wrong adding your gameToken record. Please contact support@connectandconquer.com";
			$debugInfo = array("details" => $addRecordResult , 'Location' => __LINE__ . " " . __FILE__);
			$navigation = 'Menu';
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param- only show if debug is on
			return $response;
		}
		else {
			return true;
		}
	}

	

	public function verifyGooglePurchase($packageName, $productID, $purchaseToken ) {
		$endpoint = "https://www.googleapis.com/androidpublisher/v3/applications/{$packageName}/purchases/products/{$productID}/tokens/{$purchaseToken}";

		$ch = curl_init($endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		return json_decode($response, true);
	}

	protected function setProductIdFromPlatformVerification($platformProductID){
		//set the productID from the platform verification
		if(isset($platformProductID) && !empty($platformProductID)) {
			
			switch (strtolower($platformProductID)) {
				case 'spc_2': //player gameToken
					$this->productID = PLAYER_TOKEN;
					break;
				case 'spc_1': //host gameToken
					$this->productID = HOST_TOKEN;
					break;
				case 'sp_1': //player gameToken
					$this->productID = MONTHLY_SUBSCRIPTION;
					break;
				case 'sp_2': //player gameToken
					$this->productID = ANNUAL_SUBSCRIPTION;
					break;
				default:
					$this->productID = null;
					break;
			}
			
		}
		else {
			$this->productID = null;
		}
	}

				
	protected function setDBFieldsArrayFromPlatformVerification(){
		$this->db->fieldsArray = array(
			
			'userID'				=> $this->claimUserID,
			'transactionPlatformID'	=> $this->paymentPlatform,
			'transactionPlatformTransactionID'	=> $this->platformTransactionID, //set after receipt validation
			//'platformTokenOrReceipt'			=> $this->purchaseToken, //not sure if this works with apple, if not leave blank
			'datePurchased'			=> $this->purchaseDate, //we get these from the transaction platform
			'notes'					=> @$this->params['notes'],
			'refunded'				=> 0,
			'active'				=> 1,
		);
		$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
			return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
		});
	}
	public function addTransactionRecord($params){
		
			$this->db->fieldsArray = array(
				
				'userID'				=> @$this->params['userID'],
				'transactionPlatformID'	=> @$this->params['transactionPlatformID'],
				'transactionPlatformTransactionID'	=> @$this->params['transactionPlatformTransactionID'], //set after receipt validation
				'platformTokenOrReceipt'			=> @$this->params['platformTokenOrReceipt'],
				'datePurchased'			=> @$this->params['datePurchased'], //we get these from the transaction platform
				'notes'					=> @$this->params['notes'],
				'refunded'				=> 0,
				'active'				=> 1
			);
			$table = 'transactions'; 
			$addResult = $this->db->addRecord($table);
			if (!empty($addResult['id']) && $addResult['id'] > 0) {
				
				return $addResult['id'];
			} else {
				return false; 
				
			}
		}

	
}