<?php
/**
 MEDIA
 

 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayMedia() method.
 |--------------------------------------------------------------------------
 */
class Media extends SnapshotDataClass{
	
	//protected $defaultFileName = 'default.png';
	protected $defaultFileName = 'interrupted.png';
	protected $fileName = null;
	protected $subfolder = null;
	protected $dir = null;
	protected $filePath = null;
	
	function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring);

		//$this->accessClientListArray['POST']['admin']   = true;
		//$this->accessClientListArray['POST']['all']     = true;
		//$this->accessClientListArray['POST']['user']    = true;
		$this->accessClientListArray['GET']['admin']    = true;
		// $this->accessClientListArray['GET']['all']      = true;
		$this->accessClientListArray['GET']['user']     = true;
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
			$errorMsg = "Oops! You don't have access to this info. Please contact support at support@connectandconquer.com for assistance. error: " . __LINE__;
			$navigation = "Login";//TODO check this
			$response = $this->createApiResponse(400, null, $errorMsg, $debugInfo, $navigation); //can add $debugInfo as 4th param
			die(json_encode($response));
		}
	
		//TODO! get a parameter called 'media': 'system', etc(there will start with 3 - 'system', 'user','shared','games')
		//if system, has file name, 
		//in url 'snapshotplay.com/media/systemOrUserOrSharedOrGames/nameOfFile.png' the part after media/ is the dir
		//based on id we can do a check - system, not auth check, shared, does not, game and users requires auth check and permission to view that image based on users inovlvement in the game
		//NOTE: When someone wants to share an image we can create a symbolic link to the image in the user folder. This way they wo't be usign the image folder and we wont have to worry about permissions
		//$this->params['id'] == the name of the media file. 
		//var_dump($_GET);
		$this->dir = $_GET['dir'];
		//how to handle if no file name is passed in
		if(empty($_GET['fileName'])){
			unset($_GET['subDir']);
			$GET['dir'] = 'system'; 
			$this->fileName = $this->defaultFileName;
		}
		else{
			$this->fileName  = $_GET['fileName'];
		}
		
		//handle if request from system folder. It has no subdirectory
		if($this->dir == 'system' && (empty($_GET['subDir']) )){
			//if system, build the file path here, else check set subfolder (subdirectory won't exist)
			$this->filePath = MEDIA_UPLOADS_DIR . $this->dir ."/". $this->fileName;
		}
		else {
			//if users, the subDir will be the alphanumericUserID
			$this->subfolder = $_GET['subDir']; //build file path /uploads/dir/subDir/fileName.png
			$this->filePath = MEDIA_UPLOADS_DIR . $this->dir ."/". $this->subfolder ."/". $this->fileName;
		}	
				
		
		
	}
	
	/**
	  _____ ______ _______
	 / ____|  ____|__   __|
	 | |  __| |__     | |
	 | | |_ |  __|    | |
	 | |__| | |____   | |
	  \_____|______|  |_|
	 */
	function displayMedia(){
				
		if(file_exists($this->filePath)){
		
			$this->displayImage($this->filePath);
		}
		else{
			//var_dump($this->defaultFileName);
			http_response_code(200);	
			header("Content-Description: Default File - File Not Found");
			$this->displayImage($this->filePath);
		}
	}
	

	
	public function displayImage($filePath) {
		
	//var_dump($filePath);
		if (file_exists($filePath)) {
			//$imgSize = getimagesize($filePath);
			
			header('Content-Type: ' . image_type_to_mime_type(exif_imagetype($filePath)));
			header('Content-Length: ' . filesize($filePath));
			readfile($filePath);
			exit;
		} else {
			
			header("HTTP/1.0 404 Not Found");
			echo "File not found.";
			
		}
	}
}