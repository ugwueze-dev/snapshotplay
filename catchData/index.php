<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	require 'includes/php_file_tree.php';
	?>
	<html>
		<head>
			<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
			<script src="includes/php_file_tree_jquery.js" type="text/javascript"></script>
		</head>
	</html>
	<?php
$method = $_SERVER['REQUEST_METHOD'];
$fqdn = $_SERVER['HTTP_HOST'];
switch($method) {
	case 'DELETE':
		$data = $_GET;
		break;
	case 'GET':
		$data = $_GET;
		break;
	case 'POST': //Same as Put now
		if ($_FILES) {
            $requestData = $_REQUEST;
            foreach ($requestData as $key => $item) {
             $decoded = json_decode($item, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $parsedItems[$key] = $decoded;
                } else {
                    $parsedItems[$key] = $item;
                }
            }

            
            $countFiles = count($_FILES);
            foreach ($_FILES as $file) {
                $uploadDir = './img/';
                $uploadFile = $uploadDir . basename($file['name']);
                if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                    $data[] = array('result' =>"SUCCESS - File uploaded.",
                                    "temp_name"=>$file['tmp_name'],
                                    "uploadFile"=>$uploadFile,
                                    "file"=>$file,
                                "imageURL"=>"https://$fqdn/$uploadFile");
                } else {
                    $data[] = array("result"=>"FAILED to upload file.",
                                    "temp_name"=>$file['tmp_name'],
                                    "uploadFile"=>$uploadFile,
                                    "file"=>$file);
                }
                //merge $_GET and $dat
                $data = array_merge($parsedItems, $data);
            }
        } else {
            $data = file_get_contents("php://input");
        }
		break;
	case 'PUT':
		$json = file_get_contents("php://input");
		$data = json_decode($json,TRUE);
		break;
	case 'OPTIONS':
	default:
		break;
}
$time = date('Ymd-hi');
$path = "./";
$file = $path.$method."/$time.php";
//echo $file;
$header = ' <?php header("Content-Type: application/json");?> ';
if(file_put_contents($file, $header.json_encode($data)) == false){
    echo "Error";
};

echo php_file_tree('./','[link]');

function calculate_signature($string, $private_key) {
	$hash = hash_hmac("sha1", $string, $private_key, true);
	$sig = rawurlencode(base64_encode($hash));
	return $sig;
}
function getURL($form,$entry){
	$api_key = "3c796b3ca0";
	$private_key = "7bfc7051e7205c5";
	$method  = "GET";
	$route    = "forms/$form/entries";
	$expires = strtotime("+60 days");
	$string_to_sign = sprintf("%s:%s:%s:%s", $api_key, $method, $route, $expires);
	$sig = calculate_signature($string_to_sign, $private_key);
// 	var_dump($sig);
	return $sig;
}

// $route    = "forms/$form/entries";
// $expires = strtotime("+60 days");
// $string_to_sign = sprintf("%s:%s:%s:%s", $api_key, $method, $route, $expires);
// $sig = calculate_signature($string_to_sign, $private_key);
// var_dump($sig);

