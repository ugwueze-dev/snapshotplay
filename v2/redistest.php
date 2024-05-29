 <?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// require 'vendor/autoload.php';
// define('REDIS_HOST', $_SERVER['REDIS_HOST']);
// define('REDIS_PORT', 6379);
// define('REDIS_PASS', $_SERVER['REDIS_PASS']);

// // Connecting to Redis
// $client = new Predis\Client([
//     'scheme' => 'tcp',
//     'host'   => REDIS_HOST, 
//     'port'   => REDIS_PORT,
//     'password' => REDIS_PASS 
// ]);

// $key = 'user:targets:total';

// // Check if the key exists and is numeric
// if ($client->exists($key) && is_numeric($client->get($key))) {
//     // Increment the value 1000 times
//     for ($i = 0; $i < 5670; $i++) {
//         $client->incrby($key, 1); // Increments the number stored at key by 1000
//     }

//     // Read and echo the value
//     $value = $client->get($key);
//     echo "The updated value of {$key} is: {$value}";
// } else {
//     // If the key does not exist or is not numeric, initialize it
//     $client->set($key, 1000);
//     echo "The key '{$key}' was initialized with the value: 1000";
// } 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'vendor/autoload.php';
define('REDIS_HOST', $_SERVER['REDIS_HOST']);
define('REDIS_PORT', 6379);
define('REDIS_PASS', $_SERVER['REDIS_PASS']);

// Connecting to Redis
$client = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => REDIS_HOST, 
    'port'   => REDIS_PORT,
    'password' => REDIS_PASS 
]);

$key = 'user:targets:total';

// Check if the key exists and is numeric
if ($client->exists($key) && is_numeric($client->get($key))) {
    // Increment the value 1000 times
    for ($i = 0; $i < 5670; $i++) {
        $client->incrby($key, 1); // Increments the number stored at key by 1000
    }

    // Read and echo the value
    $value = $client->get($key);
    echo "The updated value of {$key} is: {$value}";
} else {
    // If the key does not exist or is not numeric, initialize it
    $client->set($key, 1000);
    echo "The key '{$key}' was initialized with the value: 1000";
}

// Now, let's set a number in the hash 'totalhoursplayed'
$totalHoursKey = 'totalhoursplayed';
$totalHoursValue = 500; // You can change this value as needed

// Set the value in the hash
$client->hset($totalHoursKey, 'hours', $totalHoursValue);

echo "\nThe hash '{$totalHoursKey}' was initialized with the value: {$totalHoursValue}";
// Define game information
$gameInfo = [
    'name' => 'Example Game',
    'code' => 'ABC123',
    'timestamp' => time(), // Current timestamp
    'eventType' => 'Single Player', // Event type of the game
    'numberOfPeople' => 4,
    'gameStart' => '2024-04-04 15:30:00', // Game start time
];

// Key for the game hash
$gameKey = 'game:' . $gameInfo['code'];

// Set the game information in the hash
$client->hmset($gameKey, $gameInfo);

// Example of accessing individual fields from the hash
$name = $client->hget($gameKey, 'name');
$timestamp = $client->hget($gameKey, 'timestamp');

echo "Game '{$name}' with code '{$gameInfo['code']}' was stored in Redis with timestamp '{$timestamp}'.";

