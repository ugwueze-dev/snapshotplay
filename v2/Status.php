<?php
//todo currently this qill query the db twice instead of once.IDK if that is an issue.
class Status extends SnapshotDataClass {
    private $objectName;
    private $validObjects = ENDPOINT_OBJECTS;

    private $statusColumns = [
        'active',
        'gameStatus',
        'targetStatusName',
        'targetStatusDescription'
    ];
    function __construct($params, $authJWTstring = null) {
        parent::__construct($params, $authJWTstring, false);

        $this->accessClientListArray['POST']['admin']   = true;
		//$this->accessClientListArray['POST']['all']     = true;
		//$this->accessClientListArray['POST']['user']    = true;
		$this->accessClientListArray['GET']['admin']    = true;
		// $this->accessClientListArray['GET']['all']      = true;
		//$this->accessClientListArray['GET']['user']     = true;
		$this->accessClientListArray['PUT']['admin']    = true;
		// $this->accessClientListArray['PUT']['all']      = true;
		//$this->accessClientListArray['PUT']['user']     = true;
		//$this->accessClientListArray['DELETE']['admin'] = true; //manually do to avoid any accidental deactivations
		// $this->accessClientListArray['DELETE']['all']   = true;
		//$this->accessClientListArray['DELETE']['user']  = true;
        
    }
    
      /**
        _____ ______ _______
       / ____|  ____|__   __|
      | |  __| |__     | |
      | | |_ |  __|    | |
      | |__| | |____   | |
       \_____|______|  |_|
     */
    function displayStatus() {
        $statusMappings = [
            'games' => [
                'table' => 'games',
                'tableRelation' => 'gameStatusID',
                'statusTable' => 'gameStatuses',
                'statusTableColumns' => ['id', 'gameStatusName', 'gameStatusDescription'],
                'tableColumns' => ['active']
            ],
            'targets' => [
                'table' => 'usersXtargets',
                'tableRelation' => 'relationColumn', // Update with actual relation column name
                'statusTable' => 'targetStatuses',
                'statusTableColumns' => ['targetID', 'statusID', 'statusName', 'statusDescription'],
                'tableColumns' => ['active']
            ],
            'users' => [
                'table' => 'users',
                'statusTable' => null, // Ensure proper handling if statusTable is null
                'tableColumns' => ['active']
            ],
        ];
    
        $objectName = $this->params['objectName'];
    
        if (!isset($statusMappings[$objectName])) {
            return ['error' => 'Invalid object name: ' . $objectName];
        }
    
        $mapping = $statusMappings[$objectName];
        
        // Extract relevant information
        $table = $mapping['table'];
        $tableRelation = $mapping['tableRelation'];
        $statusTable = $mapping['statusTable'];
        $statusTableColumns = (array)$mapping['statusTableColumns']; // Ensure it's an array
        $tableColumns = (array)$mapping['tableColumns']; // Ensure it's an array
    
        // Build the SQL query string
        $currentStatusQuery = "(
            SELECT 
                " . implode(', ', array_map(function($col) use ($table) {
                    return "$table.$col";
                }, $tableColumns)) . ",
                " . implode(', ', array_map(function($col) use ($statusTable) {
                    return "$statusTable.$col";
                }, $statusTableColumns)) . "
            FROM 
                $table
            " . ($statusTable ? "JOIN $statusTable ON $table.$tableRelation = $statusTable.id" : "") . "
            WHERE 
                $table.id = 1
        )";
        header("Content-Type: application/json");
        echo json_encode([
            "Line " . __LINE__ => __FILE__,
            'function' => debug_backtrace()[1]['function'] ?? 'N/A',
            "zzReturn" => $currentStatusQuery,
        ]);
        die();
        $columnsArray = ["$currentStatusQuery AS 'status_options'"];
    
        $whereArray = [];
            
        $result = $this->db->select($table, $whereArray, [], [], $columnsArray);
    
        return $result;
    }
    
    
    
/**
	  _____  _    _ _______
	 |  __ \| |  | |__   __|
	 | |__) | |  | |  | |
	 |  ___/| |  | |  | |
	 | |    | |__| |  | |
	 |_|     \____/   |_|
	 */
  
    function updateType() {

        if (isset($this->params['objectName']) && !empty($this->params['objectName']) && !empty($this->id) && isset($this->params['columnName']) && isset($this->params['updateTo'])) {
            $objectName = $this->params['objectName'];
            $this->db->requiredFields = array(
                $this->params['columnName']
            );
            $this->db->fieldsArray = array(
                 $this->params['columnName']=> @$this->params['updateTo']
            );

            // Update the record with the given ID
            $updated = $this->db->update($objectName, $this->db->fieldsArray , $this->params['id'], $this->db->requiredFields);
            header("Content-Type: application/json");
            echo json_encode([
                "Line " . __LINE__ => __FILE__,
                'function' => debug_backtrace()[1]['function'] ?? 'N/A',
                "fields" => $this->db->fieldsArray ,
                "required" => $this->db->requiredFields ,

            ]);
            die();
            
            
            if ($updated['status'] !== 'error') {
                return $this->displayStatus();
            } else {
                return ['error' => 'Could not update record', 'details' => $updated['message']];
            }
        } else {
            return ['error' => 'Invalid object name, ID, or column name given.'];
        }
    }
    
}
// header("Content-Type: application/json");
// echo json_encode([
//     "Line " . __LINE__ => __FILE__,
//     'function' => debug_backtrace()[1]['function'] ?? 'N/A',
//     "zzReturn" => $this->params['objectName'],
// ]);
// die();

