<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);


require_once 'config.php'; // Database setting constants [API_DB_HOST, API_DB_NAME, API_DB_USER, API_DB_PASSWORD]
class dbHelper {
    protected $db;
    protected $err;
    public $ip;
    public $requiredFields;
    public $fieldsArray;
    public $table;
    public $tableJoins;
    public $id;
    public $where;
    public $whereGreater;
    public $whereLess;
    public $like = 0;
    public $data;
    public $booleans;
    public $ints;
    public $floats;
    public $filters;
   	public $pageNumber = 1;
    public $pages;
    public $availableItems;
    public $numberPerPage = 500;
    public $startRecordNumber = 0;
    public $endRecordNumber;
    public $returnedItems;
    public $fullQuery;
    
    
    function __construct() {
        $dsn = 'mysql:host='.API_DB_HOST.';dbname='.API_DB_NAME.';charset=utf8';
        try {
            $this->db = new PDO($dsn, API_DB_USER, API_DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (PDOException $e) {
            $response["status"] = "error";
            $response["message"] = 'Connection failed: ' . $e->getMessage();
            $response["data"] = null;
            exit;
        }

        $this->tableJoins = array();
        $this->where = array();
        $this->booleans[] = 'active';
        $this->ints[] = 'id';
        $this->filters[] = 'active';

        if (isset($data['id'])){
        	$this->id = $this->data['id'];
        }
        elseif (isset($_GET['id'])){
        	$this->id = $_GET['id'];
        }
    }
    function select($tables, 
                    $whereArray,
                    $whereGreaterArray = array(),
                    $whereLessArray = array(),
                    $columnsArray = array(), 
                    $whereMoreString = '',
                    $groupByColumnBoolean = false, 
                    $justDataBoolean = false, 
                    $numberPerPage = 'all', 
                    $pageNumber = null, 
                    $orderBy = 'id',
                    $order = 'ASC'){
                        
        //check types before running query
       $errors = array();

        if (!is_array($whereArray)) {
            $errors[] = '$whereArray must be an array (Type: ' . gettype($whereArray) . ')';
        }

        if (!is_array($whereGreaterArray) && $whereGreaterArray !== null) {
            $errors[] = '$whereGreaterArray must be an array (Type: ' . gettype($whereGreaterArray) . ')';
        }

        if (!is_array($whereLessArray) && $whereLessArray !== null) {
            $errors[] = '$whereLessArray must be an array (Type: ' . gettype($whereLessArray) . ')';
        }

        if (!is_array($columnsArray)) {
            $errors[] = '$columnsArray must be an array (Type: ' . gettype($columnsArray) . ')';
        }

        if (!is_string($whereMoreString) && $whereMoreString !== null) {
            $errors[] = '$whereMoreString must be a string (Type: ' . gettype($whereMoreString) . ')';
        }

        if (!is_bool($groupByColumnBoolean)) {
            $errors[] = '$groupByColumnBoolean must be a boolean (Type: ' . gettype($groupByColumnBoolean) . ')';
        }

        if (!is_bool($justDataBoolean)) {
            $errors[] = '$justDataBoolean must be a boolean (Type: ' . gettype($justDataBoolean) . ')';
        }

        if (!is_numeric($numberPerPage) && $numberPerPage !== null ) {
            if ( $numberPerPage !== 'all'){
                $errors[] = '$numberPerPage must be numeric or the string `all` (Type: ' . gettype($numberPerPage) . ')';
            }
        }

        if (!is_numeric($pageNumber) && $pageNumber !== null) {
            $errors[] = '$pageNumber must be numeric (Type: ' . gettype($pageNumber) . ')';
        }

        if (!is_string($orderBy)) {
            $errors[] = '$orderBy must be a string (Type: ' . gettype($orderBy) . ')';
        }

        if (!is_string($order)) {
            $errors[] = '$order must be a string (Type: ' . gettype($order) . ')';
        }
/*/
 |--------------------------------------------------------------------------
 |         check for errors and return the string if any
 |--------------------------------------------------------------------------
 */
        if (!empty($errors)) {
            $response['status'] = "error";
            $response['message'] = 'Select Failed: ' . implode("\n", $errors);
            $response['data'] = null;
            return $response;
        } 

        try{
            $a = array();
            $w = "";
            $f = "";
            $i = 0;
            $table = '';
            $select = '';
            $selectAll ='';
/*/
 |--------------------------------------------------------------------------
 |         TABLES TO USE
 |--------------------------------------------------------------------------
 */
            if (is_array($tables)){
	            foreach ($tables as $value) {
	            	if ($i ==0){
	            		$table = "$value";
	            	}
	            	else {
	            		$table .= ", $value";
	            		
	            	}
	            	$selectAll = ", $value.*";
	            	$i++;
	            }
            }
            else {
            		$table = $tables;
            		$selectAll = "$table.*";
            }
            /*/
 |--------------------------------------------------------------------------
 |         WHERE CLAUSE
 |--------------------------------------------------------------------------
 */

            foreach ($whereArray as $key => $value) {

                if (is_string($value) && strlen($value) >= 1) {
                    if ($this->like == 1) {
                        $w .= " AND $key LIKE '%$value%'";
                    } else {
                        if (strpos($value, '`') !== false && strpos($value, '`', 2) > 0) {
                            $w .= " AND $key = $value";
                        } else {
                            $w .= " AND $key = '$value'";
                        }
                    }
                } elseif (is_array($value) && count($value) > 0) {
                    $inList = implode("','", $value);
                    $w .= " AND $key IN ('$inList')";
                }
                // $value is numeric
                elseif (is_numeric($value) && strlen($value) >= 1) {
                    $w .= " AND $key = $value";
                } 
                else {// trouble... you should not get here. 
                    return array('error'=>"You should not get here. Check your where clause. The value: $value is not a string, array, or numeric.");
                }
                
                if (is_countable($whereLessArray) && count($whereLessArray) > 0) {
                    foreach ($whereLessArray as $key => $value) {
                       // if (strpos($value, '`',2)){
                        if (strpos($value, '`') > 0){
                            $w .= " and $key < $value";
                        }
                        else {
                            $w .= " and $key < '$value'";
                        }
                        
                    }
                }
            }
            if (is_countable($whereGreaterArray) && count($whereGreaterArray) > 0) {
            	foreach ($whereGreaterArray as $key => $value) {
            		if (strpos($value, '`',2)){
            			$w .= " and $key > $value";
            		}
            		else {
            			$w .= " and $key > '$value'";
            		}
            	 }
            }
/*/
 |--------------------------------------------------------------------------
 |         COLUMNS TO SELECT
 |--------------------------------------------------------------------------
 */            
            if (empty($columnsArray)){//all Coulmns
                $columnCount = 0;//all columns
                $select = ltrim($selectAll,',');
                $select = rtrim($select,',');
            }
            else {
                $columnCount = count($columnsArray);
                foreach ($columnsArray as $column){
                    $select .= "$column,";
                }
                $select = rtrim($select,',');
            }
/*/
 |--------------------------------------------------------------------------
 |         if $numberPerPage is set to all, then return all records
 |--------------------------------------------------------------------------
 */           

            if($numberPerPage == 'all'){
                $limit = null;
            }
            else {
                  //allows to set LIMIT
                if($numberPerPage != null){
                 $this->numberPerPage = $numberPerPage;
                }
                if($pageNumber != null){
                    $this->pageNumber = $pageNumber;
                }
                $this->startRecordNumber = ($this->pageNumber -1) * $this->numberPerPage;
                $limit = " LIMIT $this->startRecordNumber, $this->numberPerPage ";
            }
            
           
            
            if ($groupByColumnBoolean == false){
                $countSubQuery = "(SELECT COUNT(*) FROM $table WHERE 1 $w) AS 'availableItems', ";
            }
            else {
                $countSubQuery = '';
            }
            //Order by stuff
            if(!isset($select['id']) && $orderBy == 'id'){
                $orderClause = null;
            }
            else {
                $orderClause = " ORDER BY $orderBy $order ";
            }
            $this->fullQuery = "SELECT $countSubQuery  $select  FROM $table  WHERE 1 $w $whereMoreString  $orderClause  $limit";
            $stmt = $this->db->prepare($this->fullQuery);
            $stmt->execute($a);
            $resultData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->returnedItems = count($resultData);
            //  $resultData = $stmt->fetchALL(PDO::FETCH_COLUMN,0);
/*/
 |--------------------------------------------------------------------------
 |         GROUP BY COLUMN - does this after the query is run, not in mySQL
 |--------------------------------------------------------------------------
 */
            if ($groupByColumnBoolean ) { 
                //loop through and grab the column names and group them into an array
                $groupedArray = array();
                foreach ($resultData as $key1 => $value1) {
                    //check if it is a array
                    if (is_array($value1)){
                        //loop through the array and grab the column names
                        foreach ($value1 as $key2 => $value2) {
                            $groupedArray[$key2][] = $value2;
                        }
                    }
                    else {
                        $groupedArray[$key1][] = $value1;
                    }
                    # code...
                }
                $resultData = $groupedArray;
                $this->returnedItems = count($resultData);
            }
            /*/
 |--------------------------------------------------------------------------
 |         return the straight data if $justDataBoolean is true
 |--------------------------------------------------------------------------
 */
           if($justDataBoolean == true){
            //unset($availableItems);
            return $resultData;
           }
            $this->availableItems = (int)@$resultData[0]['availableItems'];
            if($this->returnedItems <=0){
                $response['status'] = "warning";
                $response['message'] = "No data found.";
                $response['fullQuery'] = $this->fullQuery;
            }else{
                $response['returnedItems'] = $this->returnedItems;
                $response['status'] = "success";
                $response['message'] = "Data selected from database";
//                 $response['query'] = $this->fullQuery;
                $response['pageNumber'] = $this->pageNumber;
                $response['pages'] = ceil($this->availableItems/$this->numberPerPage);
                $response['availableItems'] = $this->availableItems;
                $response['numberPerPage'] = $this->numberPerPage;
                $response['startRecordNumber'] = $this->startRecordNumber;
                $response['endRecordNumber'] = $this->startRecordNumber + $this->returnedItems;
                $response['mainQuery'] = $this->fullQuery;
            }
                $response['data'] = $resultData;
                $response['columns'] = $columnsArray;
                $response['columnCount'] = $columnCount;
                $response['bindingPDO'] = $a;
        }catch(PDOException $e){
            $response['status'] = "error";
            $response['message'] = 'Select Failed: ' .$e->getMessage();
            $response['mainQuery'] = $this->fullQuery;
            $response['data'] = null;
        }
        return $response;
    }
    function insert($table, $columnsArray, $requiredFieldsArray = array()) {
        
        $requiredFieldsCheck = $this->verifyRequiredParams($columnsArray, $requiredFieldsArray);
        if ($requiredFieldsCheck!==true){
            return $this->verifyRequiredParams($columnsArray, $requiredFieldsArray);
        }
        // $this->verifyRequiredParams($columnsArray, $requiredFieldsArray);
        
        try{
            $table = lcfirst($table);
            $a = array();
            $c = "";
            $v = "";
            foreach ($columnsArray as $key => $value) {
                $c .= $key. ", ";
                $v .= ":".$key. ", ";
                $a[":".$key] = $value;
            }
            $c = rtrim($c,', ');
            $v = rtrim($v,', ');
            $this->fullQuery = "INSERT INTO $table($c) VALUES($v)";
            $stmt =  $this->db->prepare($this->fullQuery);
            $stmt->execute($a);
            $affected_rows = $stmt->rowCount();
            $response['status'] = "success";
            $response['message'] = $affected_rows." row inserted into database";
            $response['id'] = $this->db->lastInsertId();
            
        }catch(PDOException $e){
            $response['status'] = "error";
            $response['message'] = 'Insert Failed: ' .$e->getMessage();
            $response['query'] = $this->fullQuery;
            $response['insertData'] = $columnsArray;
            $response['setColumns'] = $c;
            $response['setValue'] = $a;
        }
        return $response;
    }
    function update($tableName, $columnsUpdateArray, $whereClause, $requiredFieldsArray = array()){ 
        if(!empty($requiredFieldsArray)){
             $requiredFieldsCheck = $this->verifyRequiredParams($columnsUpdateArray, $requiredFieldsArray);
            if ($requiredFieldsCheck !== true){
                return $requiredFieldsCheck;
            }
        }
    $tableName = lcfirst($tableName);
    try {
        $params = array();
        $where = "";
        $setClause = "";
        
        foreach ($whereClause as $key => $value) {
            $where .= " AND " . $key . " = :" . $key;
            $params[":" . $key] = $value;
        } 
        foreach ($columnsUpdateArray as $key => $value) {
            $setClause .= $key . " = :" . $key . ", ";
            $params[":" . $key] = $value;
        }
        
        $setClause = rtrim($setClause, ", ");
        $updateQuery = "UPDATE $tableName SET $setClause WHERE 1=1 $where";
        // $this->mainQuery = $updateQuery;
        $stmt =  $this->db->prepare($updateQuery);
 
        if(isset($params[':id']) && is_array($params[':id'])){
            $tempArray = $params;
            unset($tempArray[':id']);
            $updatedRecords = 0;
            foreach($params[':id'] AS $value){//Key is not used
                $newParams = array(':id'=>$value);
                //combine the $newParams array with new params array
                $flatArray = $tempArray+$newParams;
                $stmt->execute($flatArray);
                $updatedRecords += $stmt->rowCount();
            }
            $affectedRows = $updatedRecords;

        }
        else {
            $stmt->execute($params);
            $affectedRows = $stmt->rowCount();
        }
        if ($affectedRows <= 0) {
            $response["status"] = "warning";
            $response["message"] = "No row(s) updated";
            $response["query"] = $updateQuery; // This will print out the MySQL query for troubleshooting
        } else {
            $response["status"] = "success";
            $response["message"] = $affectedRows . " row(s) updated in database";
        }
    } 
    catch(PDOException $e) {
        $response["status"] = "error";
        $response["message"] = "Update Failed: " . $e->getMessage();
        $response["info"] = array(
            "columnsUpdateArray" => $columnsUpdateArray,
            "whereClause" => $where,
            "params" => $params,
            "setClause" => $setClause,
            "query" => $updateQuery
        );
    };
    return $response;
}

    function verifyRequiredParams($inArray, $requiredFields) {
        $error = false;
        $errorColumns = "";
        foreach ($requiredFields as $field) {
           
            if (!isset($inArray[$field]) || strlen(trim($inArray[$field])) <= 0) {
                $error = true;
                $errorColumns .= $field . ', ';
            }
        }
        if ($error == true) {
            $response = array();
            $response["status"] = "error";
            $response["message"] = 'Required field(s) ' . rtrim($errorColumns, ', ') . ' seem(s) to be missing or empty';
            return $response;
//             exit;
        }
        else {
            return true;
        }   
    }
	function deactivate($id){
        if (empty($id)){
            return array("error"=> __LINE__ . ": ID must not be empty when deactivating");
        }
	    $this->table = $this->tableJoins[0];
		$columnsArray = array('active'=>0);
		$where = array('id'=>$id);
		$requiredFieldsArray = array();
        $updateResult = $this->update($this->table, $columnsArray, $where, $requiredFieldsArray);
        $return = array('id'=>$id,'status'=>'deactivated');
		return $return;
	}
    function deactivateUserRole($id){
        if (empty($id)){
            return array("error"=> __LINE__ . ": ID must not be empty when deactivating");
        }
	    $this->table = 'userXuserRoles';
		$columnsArray = array('active'=>0);
		$where = array('id'=>$id);
		$requiredFieldsArray = array();
        $updateResult = $this->update($this->table, $columnsArray, $where, $requiredFieldsArray);
        $return = array('id'=>$id,'status'=>'deactivated');
		return $return;
	}
 
	function activate($table,$id){
		$columnsArray = array('active'=>1);
		$where = array('id'=>$id);
		$requiredFieldsArray = array();
		return $this->update($table, $columnsArray, $where, $requiredFieldsArray);
	}
	function getRecord($id){
        // The Select method now supports an array of ids
		$result = $this->select($this->tableJoins, array('id'=>$id));
		if ($result['status'] == 'success'){
			http_response_code(200);
			return (array)$result;
		}
		else {
			http_response_code(400);
			return array('error'=>$result['message']);
		}
	}
	public	function cleanPhone($phone){
	    $stripped_phone = preg_replace('/[^0-9]/','', $phone);
	    return (int)$stripped_phone;
	}
	public 	function query($query){
		try {
			$stmt = $this->db->prepare($query);
			$stmt->execute();
			$resultData = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $resultData;
		}
		catch(PDOException $e){
			$response["status"] = "error";
			$response["message"] = "Query Failed: " .$e->getMessage();
			$response["info"] = $query;
		}
	}
	public function addRecord(){
        $result = $this->insert($this->table, $this->fieldsArray, $this->requiredFields);
        return $result;
	}
	public function updateRecord($table, $recordID){
       
		$result = $this->update($table, $this->fieldsArray, array('id'=>$recordID),$this->requiredFields);
		return $result;
	}
	public function deleteRecordsEqualTo($table,$andWhereArray){
		$andWhere = '';
		foreach ($andWhereArray AS $column=>$value){
			if(is_array($value)){
				$inList = implode("','", $value);
				$andWhere .= " AND $column IN ('$inList') ";
			}
			else {
				$andWhere .= " AND $column = '$value' ";
			}
		}
		$delQuery = "DELETE FROM `$table`
						WHERE 1
						$andWhere ";
						$numDeleted = $this->db->exec($delQuery);
						return array("numberDeletedRows"=>$numDeleted);
	}

    function prepareAndExecuteFetchAll($sql, $bindParam, $bindName){
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($bindName => '$bindParam'));
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
}