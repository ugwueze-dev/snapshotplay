<?php

class Types extends SnapshotDataClass {
    private $objectName;
    private $validObjects = ENDPOINT_OBJECTS;
   
    function __construct($params, $authJWTstring = null) {
        parent::__construct($params, $authJWTstring, false);

        //ADMIN USE ONLY, NOT FOR PUBLIC
        $this->accessClientListArray['POST']['admin']   = true;
		//$this->accessClientListArray['POST']['all']     = true;
		//$this->accessClientListArray['POST']['user']    = true;
		$this->accessClientListArray['GET']['admin']    = true;
		$this->accessClientListArray['GET']['all']      = true;
		$this->accessClientListArray['GET']['user']     = true;
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
    function displayTypes() {
        $typeMappings = [
            'games' => ['table' => 'games', 'typeTable' => 'gameTypes', 'tableColumns' => '`gameTypeID`', 'typeColumns' => ['id', 'gameTypeName', 'description']],
            'participantTypes' => ['table' => 'participantTypes', 'typeTable' => 'participantTypes', 'typeColumns' => ['id', 'participantTypeName', 'description']],
            'snapshots' => ['table' => 'snapshots', 'typeTable' => 'snapshotTypes', 'typeColumns' => ['id', 'snapshotTypeName', 'description']],
            'targets' => ['table' => 'targets', 'typeTable' => 'targetTypes', 'typeColumns' => ['id', 'targetTypeName', 'targetTypeDescription']],
            //pushNotifications might be added here but we do not currently store them
        ];
    
        $objectName = $this->params['objectName'];
    
        if (!isset($typeMappings[$objectName])) {
            return ['error' => 'Invalid object name: ' . $objectName];
        }
    
        $mapping = $typeMappings[$objectName];
        $tables = [$mapping['table']];
        if ($mapping['typeTable'] !== null && $mapping['typeTable'] !== $mapping['table']) {
            $tables[] = $mapping['typeTable'];
        }
        $whereArray = [];
        $idWhereArray = [];

        if ($this->id = isset($this->params['id'])) {
            $idWhereArray[$mapping['table'] . '.id'] = $this->params['id']; 
            if ($mapping['typeTable']) {

                $tableOne = $mapping['typeTable'] . '.id';
                $tableTwo  = $mapping['table'] . '.' .$mapping['tableColumns'];
                $idWhereArray = array($tableOne => $tableTwo, 
                                        $mapping['table'] . '.id' => $this->params['id']);//todo fix this 
            }

            $columnsArray = array_map(
            function ($column) use ($mapping) {
                return $mapping['typeTable'] . '.' . $column . " as '$column'";
            },
            $mapping['typeColumns']
            );
            //$testArray = array('games.id'=>);
            $idresponse = $this->db->select($tables, $idWhereArray, null, null, $columnsArray);
        }
        header("Content-Type: application/json");
        echo json_encode([
            "Line " . __LINE__ => __FILE__,
            'function' => debug_backtrace()[1]['function'] ?? 'N/A',
            "zzReturn" => $idresponse,
        ]);
        die();
    
        if ($mapping['typeTable'] !== null) {
            $typeTables = [$mapping['typeTable']];
        } else {
            $typeTables = [$mapping['table']];
        }
        $columnsArray = array_map(
            function ($column) use ($mapping) {
                return $mapping['typeTable'] . '.' . $column;
            },
            $mapping['typeColumns']
        );
        
        $response = $this->db->select($typeTables, $whereArray, null, null, $columnsArray);
    
        $finalresponse = [
            'objectName' => $objectName,
            'types' => [$response['data']],
        ];
    
        if ($idresponse['data'] !== null) {
            $finalresponse['currentType'] = $idresponse['data'];
        }
    
        $this->mainQuery = $this->db->fullQuery;
        $this->availableItems = $this->db->availableItems;
        return $this->prepareReturn($finalresponse);
    }
    
    
  /**
	  _____  _    _ _______
	 |  __ \| |  | |__   __|
	 | |__) | |  | |  | |
	 |  ___/| |  | |  | |
	 | |    | |__| |  | |
	 |_|     \____/   |_|
	 */
    function updateTypes() {
        $typeMappings = [
            'games' => ['table' => 'games', 'typeTable' => 'gameTypes','typeColumns' => ['id', 'gameTypeName', 'description']],
            'participantTypes' => ['table' => 'participantTypes', 'typeTable' => 'participantTypes', 'typeColumns' => ['id', 'participantTypeName', 'description']],
            'snapshots' => ['table' => 'snapshots', 'typeTable' => 'snapshotTypes', 'typeColumns' => ['id', 'snapshotTypeName', 'description']],
            'targets' => ['table' => 'targets', 'typeTable' => 'targetTypes', 'typeColumns' => ['id', 'targetTypeName', 'targetTypeDescription']],
            //pushNotifications might be added here but we do not currently store them
        ];
    
        if (
            isset($this->params['objectName']) && !empty($this->params['objectName']) &&
            !empty($this->id) &&
            isset($this->params['columnName']) &&
            isset($this->params['updateTo']) &&
            array_key_exists($this->params['objectName'], $typeMappings)
        ) {
            $objectName = $this->params['objectName'];
            $columnName = $this->params['columnName'];
            // $id = $this->params['id'];
            $whereArray = [
                'id' => $this->params['id']
            ];
    
            // Check if the provided column is a type column
            if (in_array($columnName, $typeMappings[$objectName]['typeColumns'])) {
                $this->db->requiredFields = [$columnName];
                $this->db->fieldsArray = [$columnName => $this->params['updateTo']];
                $updated = $this->db->update($typeMappings[$objectName]['typeTable'], $this->db->fieldsArray, $whereArray, $this->db->requiredFields);
    
                if ($updated['type'] !== 'error') {
                    return $this->displayTypes();
                } else {
                    return ['error' => 'Could not update record', 'details' => $updated['message']];
                }
            } else {
                return ['error' => 'Invalid column name for type update.'];
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
