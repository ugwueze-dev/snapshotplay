<?php
global $processTime;

$processTime = microtime(true);

// This code handles API requests and responses.
//check to see if enpoint exists
if (!empty($_REQUEST['endpoint'])) {
   define('ENDPOINT', $_REQUEST['endpoint']);
}
// The following section is responsible for autoloading class files when needed.
spl_autoload_register(function ($class_name) {
    // Include the class file based on the class name.
    if (file_exists($class_name . '.php')){ 
        require_once $class_name . '.php';
    }
     // check if class is API and then require API.class.php 
    elseif($class_name == 'API'){
        require_once 'API.class.php';
    }
    
// I do not think this is used anymore
    // Check if the 'HTTP_ORIGIN' key exists in the server's superglobal array ($_SERVER).
    if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
        // If 'HTTP_ORIGIN' doesn't exist, set it to the server's host name.
        $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
    }
});

try {
    if(defined('ENDPOINT'))
    {
        // Attempt to create an instance of the API class using the 'endpoint' parameter from the request.
        $API = new API(ENDPOINT);
    }
    else{
        $API = new API(null);
    }

    // Process the API request and generate a response by calling the 'processAPI' method.
    echo $API->processAPI();
} catch (Exception $e) {
    // If an exception occurs during API processing, catch it and provide an error response.
    // Encode the exception message into JSON format and echo it.
    echo json_encode(Array('error' => $e->getMessage()));
}
?>
