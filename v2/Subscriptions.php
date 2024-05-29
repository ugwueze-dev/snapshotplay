<?php
/**                         
    

 Internal use only - not an endpoint
 
 */

class Subscriptions extends SnapshotDataClass{
	
	function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring, $enforceAccessPermissions = false);
        
		$this->db->requiredFields = array(
				'userID',
				'transactionID',
				
		);
		$this->db->fieldsArray = array(

				'userID'		              => @$this->params['userID'],
				'transactionID'           => @$this->params['transactionID'],
				'expirationDate'		      => @$this->params['expirationDate'],
				'active'			            => @$this->params['active'],
		);
	
		$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
			return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
		});
    }

    /**
     * Description: Add a record to the Subscriptions table
     *
     * @param 
     * @return array $response or $error
     */
        public function addSubscriptionRecord() {
            /*/
            |--------------------------------------------------------------------------
            |         INTERNAL USE ONLY - NOT AN ENDPOINT
            |--------------------------------------------------------------------------
            */
           
            $this->params['active'] = 1;

            $addResult = $this->db->addRecord();
            if (!empty($addResult['id']) && $addResult['id'] > 0) {
                $this->id = $addResult['id'];
                return array('status' => 'success', "id" => $this->id, 'message' => 'Your subscription record was added successfully.');
            } else {
                return array('status' => 'error',  'message' => 'Error creating record.', 'details' => $addResult);
                
            }
        }

        
	



}