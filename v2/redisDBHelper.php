<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);


require_once 'config.php';
require 'vendor/autoload.php'; // Include Predis autoload file

use Predis\Client;
/** 
 * @author sherihansen
 * RedisDBHelper class
 * This class contains all the needed functions for our use of Redis
 * 
 * For more details on specific Redis commands go to http://redis.io/commands
 *************** HOW THE INFO IS STORED***************
 * 
 * For our data storage we are using the following :
 * user1000:activeTargets:Total = 3 
 * REDIS_TARGET_STRING = ':activeTargets:Total'
 * This total is being sent in each socket response to the client
 * 
 * This will also at some point include data storage to work with stats
 */
class redisDBHelper {
    private $redis;

    public function __construct( $database = 0) {
        $parameters = [
            'scheme' => 'tcp',
            'host'   => REDIS_HOST,
            'port'   => REDIS_PORT,
            'password' => REDIS_PASSWORD
        ];     

        $this->redis = new Client($parameters);
        
        if ($database !== 0) {
            $this->redis->select($database);
        }
    }


    /**
     * Adds a new active target for the user and initializes the total count.
     *
     * This method is used to add a new active target for a user and initialize the total count.
     * It creates a key in Redis with the specified user ID and initializes it with the value 1.
     *
     * @param int $userID The ID of the user for whom the active target is being added.
     *
     * @return string 'OK' if the key was successfully created with the initial value, false otherwise.
     */
    public function addNewUserActiveTargetTotal($userID) {
        $key = 'user' . $userID . REDIS_TARGET_STRING;
        return $this->createKey($key, 1); // 1 target for the initial game requiring capturing your profile image
    }

    /**
     * Creates a new key with the specified value in Redis.
     *
     * This method creates a new key with the specified value in Redis.
     *
     * @param string $key The name of the key to create.
     * @param mixed $value The value to set for the key.
     *
     * @return string 'OK' if the key was successfully created with the specified value, error or other data otherwise.
     */
    public function createKey($key, $value) {
        // Create a key with the specified value
        $result = $this->redis->set($key, $value);
        return $result->getPayload();
    }


    /**
     * Checks if a key exists in the Redis database.
     *
     * This method checks if the specified key exists in the Redis database.
     *
     * @param string $key The name of the key to check.
     *
     * @return bool Returns true if the key exists, false otherwise.
     */
    public function keyExists($key) {
        // Check if the key exists
        return $this->redis->exists($key);
    }

    /**
     * Sets the value of a key in the Redis database.
     *
     * This method sets the value of the specified key in the Redis database.
     * If key exists, value will be overwritten
     * 
     *
     * @param string $key The name of the key to set.
     * @param mixed $value The value to set for the key.
     *
     * @return string Returns 'OK' if the key was set successfully. If value is not a string, will return error and abort set.
     */
    public function setKey($key, $value) {
        $result = $this->redis->set($key, $value);
        return $result->getPayload();

    }

    /**
     * Retrieves the value associated with a key from the Redis database.
     *
     * This method retrieves the value associated with the specified key from the Redis database.
     *
     * @param string $key The name of the key to retrieve.
     *
     * @return mixed|null Returns the value associated with the key, or null if the key does not exist.
     */
    public function getKey($key) {
        return $this->redis->get($key);
    }

    /**
     * Deletes a key from the Redis database.
     *
     * This method deletes the specified key from the Redis database.
     *
     * @param string $key The name of the key to delete.
     *
     * @return int Returns the number of keys that were deleted (0 or 1).
     */
    public function deleteKey($key) {
    return $this->redis->del($key);
    }

        /**
     * Sets the value of a field in a hash stored in the Redis database.
     *
     * This method sets the value of the specified field in the hash identified by the given key in the Redis database.
     *
     * @param string $key The name of the hash key.
     * @param string $field The name of the field within the hash.
     * @param mixed $value The value to set for the specified field.
     *
     * @return int Returns 1 if the field is a new field in the hash and value was set, or 0 if the field already exists in the hash and the value was updated.
     */
    public function setHashKey($key, $field, $value) {
        return $this->redis->hSet($key, $field, $value);
    }

    /**
     * Retrieves the value associated with a field in a hash stored in the Redis database.
     *
     * This method retrieves the value associated with the specified field within the hash identified by the given key from the Redis database.
     *
     * @param string $key The name of the hash key.
     * @param string $field The name of the field within the hash.
     *
     * @return mixed|null Returns the value associated with the specified field in the hash, or null if the key or field does not exist.
     */
    public function getHashKey($key, $field) {
        return $this->redis->hGet($key, $field);
    }

    /**
     * Deletes a field from a hash stored in the Redis database.
     *
     * This method deletes the specified field from the hash identified by the given key in the Redis database.
     *
     * @param string $key The name of the hash key.
     * @param string $field The name of the field within the hash to delete.
     *
     * @return int Returns the number of fields that were removed from the hash, not including specified but non-existent fields.
     */
    public function deleteHashKey($key, $field) {
        return $this->redis->hDel($key, $field);
    }

    /**
     * Appends a value to the end of a list stored in the Redis database.
     *
     * This method appends the specified value to the end of the list identified by the given key in the Redis database.
     *
     * @param string $key The name of the list key.
     * @param mixed $value The value to append to the list.
     *
     * @return int Returns the length of the list after the push operation.
     */
    public function setList($key, $value) {
        return $this->redis->rPush($key, $value);
    }

    /**
     * Retrieves all elements of a list stored in the Redis database.
     *
     * This method retrieves all elements of the list identified by the given key from the Redis database.
     *
     * @param string $key The name of the list key.
     *
     * @return array Returns an array containing all elements of the list.
     */
    public function getList($key) {
        return $this->redis->lRange($key, 0, -1);
    }

    /**
     * Increments the integer value of a key by one.
     *
     * This method increments the integer value of the specified key by one in the Redis database.
     * If the key does not exist, it is created and set to the specified value to increase by
     *
     * @param string $key The name of the key to increment.
     *
     * @return int Returns the value of the key after the increment operation.
     */
    public function increaseByOne($key) {
        return $this->redis->incr($key);
    }

    /**
     * Increments the integer value of a key by the specified amount.
     *
     * This method increments the integer value of the specified key by the specified amount in the Redis database.
     * If the key does not exist, it is created and set to the specified value to increase by
     *
     * @param string $key The name of the key to increment.
     * @param int $value The amount by which to increment the key's value.
     *
     * @return int Returns the value of the key after the increment operation.
     */
    public function increaseByValue($key, $value) {
        return $this->redis->incrby($key, $value);
    }

    /**
     * Decrements the integer value of a key by one.
     *
     * This method decrements the integer value of the specified key by one in the Redis database.
     *
     * @param string $key The name of the key to decrement.
     *
     * @return int  Returns the value of the key after the decrement operation, if 0 key has been deleted due to no active users.
     */
    public function decreaseByOne($key) {
        $result = $this->redis->decr($key);
        
        if ($result <= 0) {
            $this->redis->del($key);
            return 0;
        }
    
        return $result;
    }

    /**
     * Decrements the integer value of a key by the specified amount.
     *
     * This method decrements the integer value of the specified key by the specified amount in the Redis database.
     *
     * @param string $key The name of the key to decrement.
     * @param int $value The amount by which to decrement the key's value.
     *
     * @return int Returns the value of the key after the decrement operation.
     */
    public function decreaseByValue($key, $value) {
        $result =  $this->redis->decrby($key, $value);
        
        if ($result <= 0) {
            $this->redis->del($key);
            return 0;
        }
    
        return $result;
    }

    /**
     * Destructor to disconnect from the Redis server.
     *
     * This destructor disconnects from the Redis server when the object is destroyed.
     */
    public function __destruct() {
        if ($this->redis) {
            $this->redis->disconnect();
        }
    }

   
}
