<?php
/**
  ____  _             _      _______                    
 |  _ \| |           | |    |__   __|                   
 | |_) | | __ _ _ __ | | __    | |_   _ _ __   ___  ___ 
 |  _ <| |/ _` | '_ \| |/ /    | | | | | '_ \ / _ \/ __|
 | |_) | | (_| | | | |   <     | | |_| | |_) |  __/\__ \
 |____/|_|\__,_|_| |_|_|\_\    |_|\__, | .__/ \___||___/
                                   __/ | |              
                                  |___/|_|              
 */
/*/
 |--------------------------------------------------------------------------
 |      Commented out or deleted methods if you do not want the endpoints 
 |        to support the HTTP method. i.e. if you do not want to allow GET,
 |        then delete the displayBlankTypes() method.
 | 
 |	 	For endpoints where the table is a type. IE: gameTypes, targetTypes
 |      GET is allowed without authentication for frontend to use
 |		Make sure to add as 'all' in Access Level Read permissions in config.php
 |--------------------------------------------------------------------------
 */
class BlankTypes extends SnapshotDataClass{
	
	function __construct($params) {
		parent::__construct($params);
		$this->db->requiredFields = array(
				'blankTypeName',
		);
		$this->db->fieldsArray = array(
				'id'				=> @$this->params['id'],
				'blankTypeName'				=> @$this->params['blankTypeName'],
				'blankTypeDescription'		=> @$this->params['blankTypeDescription'],
				
				'active'			=> @$this->params['active'],
		);
		$this->id = isset($this->params['id']) ? $this->params['id'] : null;
		$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
			return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
		});

		
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
	function displayBlankTypes(){
	
		$typeOptions = $this->getAllTypes('blankTypes');
		$results = $typeOptions['data'];
		$this->mainQuery		 	= $this->db->fullQuery;
		$this->availableItems = $this->db->availableItems;

		return  $this->prepareReturn($results);
	}
}