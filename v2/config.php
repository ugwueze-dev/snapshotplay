<?php
use Lcobucci\JWT\Signer\Key\InMemory;
//adding send email class... not sure why I need to add it here... Not normal.. please fix if you can figure it out
require_once 'SendEmails.php';
/**
 * Database configuration
 */
//was set to use $_env but that was not working on the server???
define('API_DB_HOST', $_SERVER['API_DB_HOST']);
unset($_SERVER['API_DB_HOST']);
define('API_DB_USER', $_SERVER['API_DB_USER']);
unset($_SERVER['API_DB_USER']);
define('API_DB_PASSWORD', $_SERVER['API_DB_PASSWORD']);
unset($_SERVER['API_DB_PASSWORD']);
define('API_DB_NAME', $_SERVER['API_DB_NAME']);
unset($_SERVER['API_DB_NAME']);

/*/
 |--------------------------------------------------------------------------
 |         REDIS
 |--------------------------------------------------------------------------
 */
define('REDIS_HOST', $_SERVER['REDIS_HOST']);
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', $_SERVER['REDIS_PASS']);
define('REDIS_TARGET_STRING', ':activeTargets:total');
/*/
 |--------------------------------------------------------------------------
 |         ESINK
 |--------------------------------------------------------------------------
 */
define('ESINK_SECURITY_TOKEN', '730B873F-FB26-4342-9BAB-B0BE729D0577');
define('ESINK_API_URL', $_SERVER['API_ESINK_HOST']);

/*/
 |--------------------------------------------------------------------------
 |         Send grid API Key
 |--------------------------------------------------------------------------
 */
define('SENDGRID_API_KEY', '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');

/*/
 |--------------------------------------------------------------------------
 |         ADD Endpoints Here
 |--------------------------------------------------------------------------
 */ 
define('ENDPOINT_OBJECTS', [
    
    'authentications',
    'devices',
    'games',
    'gameModes',
    'gameTypes',  //not needed as endpoint - only calling internally
    'joinGames',
    'media',
    'participantTypes', 
    'pushNotifications', 
    'resetPassword',
    'snapshots', //should only be GET & DELETE - rest is processed thru GamePlays
    'snapshotTypes',
    'targetTypes',
    'targetStatuses',
    'transactions',
    'users',
    'userTargets', //should only be GET & DELETE - rest is processed thru GamePlays
    'push',
    'status',
    'types',
    'stats',
   
]);
/*/
 |--------------------------------------------------------------------------
 |         Defining the endpoints Access levels - CRUD -
 |  8/29/23 - for MVP users can do everything EXCEPT 
 | Host games, get access to gameCodes (to share), and delete games
 | User will have access to all things related to them (so userTargets, and users in games they are in)
 //TODO Client access lists are handled in each endpoint http request method
 |--------------------------------------------------------------------------
 */
define('ENDPOINT_ACCESS_READ_LEVELS', [
    
    'authentications' => array('none'),
    'gameTypes' => array('all'), //if we users need to be able to see types, then make it all for read only
    'games' => array('player'),
    'gameModes' => array('all'),
    'joinGames' => array('player'),
    'media' => array('player'),
    'participantTypes' => array('all'),
    'roles' => array('system'),
    'snapshots' => array('player'),
    'snapshotTypes' => array('all'),
    //'targetsStatuses' => array('system'),
    'targetStatuses' => array('all'),
    'targetTypes' => array('all'),
    'users' => array('player'), //this is not different for host vs player. As a host is also a player - and would have same access as a player
    'userTargets' => array('player'),
    'push' => array('all'),
    'status' => array('all'),
    'types' => array('all'),
    'stats' => array('all'),

    
]);
define('ENDPOINT_ACCESS_UPDATE_LEVELS', [
    
    'authentications' => array('none'),
    'devices' => array('player'),
    'games' => array('player'), //replaces gamePlays for start and ending game
    'gameModes' => array('system'),
    'gameTypes' => array('system'), 
    'joinGames' => array('player'),
    'roles' => array('system'),
    'snapshots' => array('player'),
    'targetsStatuses' => array('system'),
    'users' => array('player'),
    'userTargets' => array('system'),
    'push' => array('system'),
    'status' => array('system'),
    'types' => array('system'),
    'stats' => array('system'),


]);
define('ENDPOINT_ACCESS_CREATE_LEVELS', [
    
    'authentications' => array('all'),
    'games' => array('player'),
    'gameModes' => array('system'),
    'gameTypes' => array('system'),
    'joinGames' => array('player'),
    'pushNotifications' => array('system'),
    'roles' => array('system'),
    'resetPassword' => array('all'),
    'snapshots' => array('player'),
    'targetsStatuses' => array('system'),
    'transactions' => array('player'),
    'users' => array('all'), //can't be logged in to create an account
    'userTargets' => array('system'),
    'push' => array('system'),
    'status' => array('all'),
    'types' => array('all'),
    'stats' => array('all'),


]);
define('ENDPOINT_ACCESS_DELETE_LEVELS', [//might be deactivate
    
    'authentications' => array('none'),
    'games' => array('system'),
    'gameModes' => array('system'),
    'gameTypes' => array('system'),
    'joinGames' => array('system'), //This is handled in the gamePlays endpoint withdraw
    'roles' => array('system'),
    'snapshots' => array('player'),
    'targetsStatuses' => array('system'),
    'users' => array('player'),
    'userTargets' => array('player'),   
    'push' => array('system'),
    'status' => array('system'),
    'types' => array('system'),
    'stats' => array('system'),


]);
define('ENDPOINT_ACCESS_OPTIONS_LEVELS', [
    
    'authentications' => array('all'),
    'gameTypes' => array('all'),
    'games' => array('all'),
    'gameModes' => array('all'),
    'joinGames' => array('all'),
    'roles' => array('none'),
    'snapshots' => array('all'),
    'targetsStatuses' => array('none'),
    'users' => array('all'),
    'userTargets' => array('all'),
    'push' => array('all'),
    'status' => array('all'),
    'types' => array('all'),
    'stats' => array('all'),

]);

//JWT secret key
define('JWT_SECRET_KEY',
    'iLoveSaraWatsonAndHaveBeenSoForThePast25Years0SheIsMyEVERYTHING1'
);

define('RESET_PASSPHRASE_UUID', '3BC257F0-B7E8-42B4-A539-8D6F87F336A4');

define('SYSTEM_KEY', '018d7f24-9d55-7e3e-ac52-8ddb055557ef'); 



/*/
 |--------------------------------------------------------------------------
 |         Media Uploads Directory
 |--------------------------------------------------------------------------
 */
    define('MEDIA_UPLOADS_DIR', $_SERVER['DOCUMENT_ROOT'] .'/uploads/');
    define('API_MAJOR_VERSION', '2');
    define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST']);
    define('API_URL', SITE_URL . '/v' . API_MAJOR_VERSION );
/*/
 |--------------------------------------------------------------------------
 |        Symbolic Link for Media Profile
 |--------------------------------------------------------------------------
 */
define('MEDIA_PROFILE_DIR', $_SERVER['DOCUMENT_ROOT'] .'/media/users');

/*/
 |--------------------------------------------------------------------------
 |         define RoleTypes for users Define constants for roles
 |--------------------------------------------------------------------------
 */
define('ROLE_USER', 'user');
define('ROLE_ASSIGNED', 'asAssigned');
define('ROLE_RELATED_USERS', 'relatedUsers');

define('ROLE_PLAYER', 'player');
define('ROLE_HOST', 'host');
define('ROLE_SPECTATOR', 'spectator');

/*/
 |--------------------------------------------------------------------------
 |         define Types for User Roles Table
 |--------------------------------------------------------------------------
 */
define('USER_ROLE_PLAYER', 1);
define('USER_ROLE_HOST', 2);
/*/
 |--------------------------------------------------------------------------
 |         Authorization JWT status's defined
 |--------------------------------------------------------------------------
 */
define('JWT_STATUS_VALID', 'valid');
define('JWT_STATUS_EXPIRED', 'expired');
define('JWT_STATUS_REQUEST_NEW', 'newRequest');
define('JWT_STATUS_VALID_EXPIRES_SOON', 'validExpiresSoon');
define('JWT_STATUS_INVALID', 'invalid');

/*/
 |--------------------------------------------------------------------------
 |         Participant Status Codes
 |--------------------------------------------------------------------------
 */

define ('PARTICIPANT_TYPE_SPECTATOR', 1);
define ('PARTICIPANT_TYPE_ACTIVE_PLAYER', 2);
define ('PARTICIPANT_TYPE_CAPTURED_PLAYER', 3);
define ('PARTICIPANT_TYPE_HOST', 4);
define ('PARTICIPANT_TYPE_MODERATOR', 5);
/*/
 |--------------------------------------------------------------------------
 |        TARGET STATUS CODES
 |--------------------------------------------------------------------------
 */
define ('TARGET_STATUS_GAME_STARTED', 1);
define ('TARGET_STATUS_INITIAL_ASSIGNMENT', 1);
define ('TARGET_STATUS_CLOSED_INACTIVE', 2);
define ('TARGET_STATUS_CLOSED_COMPLETE', 3);
define('TARGET_STATUS_CLOSED_USER_CAPTURED', 4); //Player who has the was captured
define('TARGET_STATUS_CLOSED_GAME_ENDED', 5); //Player who captured the target
define('TARGET_STATUS_CLOSED_PLAYER_WITHDRAWN', 6); //Player who captured the target
define('TARGET_STATUS_CLOSED_WINNER', 7); //Player who captured the target
define('TARGET_STATUS_TARGET_TRANSFERRED', 8);
define('TARGET_STATUS_HOST_ELIMINATED', 9);
define('TARGET_STATUS_HOST_SWAPPED', 10);
define('TARGET_STATUS_TARGET_ELIMINATED_BY_HOST', 11);

define('SYSTEM_GAME_SET_SNAPSHOT', 1);

/*/
 |--------------------------------------------------------------------------
 |         Game Type Codes 
 |--------------------------------------------------------------------------
 */
define ('GAME_TYPE_GLOBAL', 1);
define ('GAME_TYPE_SINGLE_TARGET', 2);
define ('GAME_TYPE_MULTIPLE_TARGET', 3);
define ('GAME_TYPE_TEAM_SINGLE_TARGET', 4);
define ('GAME_TYPE_TEAM_MULTIPLE_TARGET', 5);
define ('GAME_TYPE_ELIMINATION', 6);

/*/
 |--------------------------------------------------------------------------
 |         Game Status  
 |--------------------------------------------------------------------------
 */
define ('GAME_STATUS_CREATED', 1);
define ('GAME_STATUS_STARTED', 2);
define ('GAME_STATUS_ENDED', 3);
define ('GAME_STATUS_RESET', 4);
define ('GAME_STATUS_SUSPENDED', 5);
define ('GAME_STATUS_EXPIRED', 6);
define ('GAME_STATUS_ONGOING', 7);
/*/
 |--------------------------------------------------------------------------
 |         Snapshot Type Codes 
 |--------------------------------------------------------------------------
 */
define ('SNAPSHOT_TYPE_USER', 1);
define ('SNAPSHOT_TYPE_ITEM', 2);
define ('SNAPSHOT_TYPE_USER_DELETED', 998);
define ('SNAPSHOT_TYPE_UNDEFINED', 999);
/*/
 |--------------------------------------------------------------------------
 |         Game 
 |--------------------------------------------------------------------------
 */
 define ('LAUNCH_GAME', 1); //This gameID might change, but LAU-NCH will be the code, the demo game all users must participate in
 define ('REXBUR_GAME', 2); //This gameID might change, but REX-BUR will be the code

/*/
 |--------------------------------------------------------------------------
 |         Products 
 |--------------------------------------------------------------------------
 */
define ('PLAYER_TOKEN', 1); 
define ('HOST_TOKEN', 2);
define ('MONTHLY_SUBSCRIPTION', 3);
define ('ANNUAL_SUBSCRIPTION', 4);
define ('LIFETIME_SUBSCRIPTION', 999);
define ('PLATFORM_GOOGLE_MONTHLY_SUBSCRIPTION', 'sp_1');
define ('PLATFORM_GOOGLE_ANNUAL_SUBSCRIPTION', 'sp_2');
define ('PLATFORM_APPLE_MONTHLY_SUBSCRIPTION', 'SP_1');
define ('PLATFORM_APPLE_ANNUAL_SUBSCRIPTION', 'SP_2');
/*/
 |--------------------------------------------------------------------------
 |         Transaction Platforms 
 |--------------------------------------------------------------------------
 */
define ('PLATFORM_GOOGLE', 1); 
define ('PLATFORM_APPLE', 2);
define ('PLATFORM_STRIPE', 3);
define ('PLATFORM_SYSTEM', 4);

/*/
 |--------------------------------------------------------------------------
 |        PURCHASE STATES FOR GOOGLE PLAY
 |--------------------------------------------------------------------------
 */
define ('GOOGLE_PURCHASE_STATE_PURCHASED', 0);
define ('GOOGLE_PURCHASE_STATE_CANCELED', 1);
define ('GOOGLE_PURCHASE_STATE_PENDING', 2);

/*/
 |--------------------------------------------------------------------------
 |        LOGGING TEST DATA FOR SNAPSHOT PROCESSING
 |--------------------------------------------------------------------------
 */
define('LOGGING_TEST_DATA', true);
/*/
 |--------------------------------------------------------------------------
 |         Transaction Platforms Secret Keys 
 |--------------------------------------------------------------------------
 */
define ('APPLE_SHARED_SECRET', 'd515c3c6964a4fe19c6e6f228a4e0705' );

/*/
 |--------------------------------------------------------------------------
 |        FIREBASE NOTIFICATIONS
 |--------------------------------------------------------------------------
 */

define('FIREBASE_CLIENT_ID', '116413792838766431057');
define('FIREBASE_SERVICE_ACCOUNT', 'snapshotplay@connect-and-conquer-1.iam.gserviceaccount.com');