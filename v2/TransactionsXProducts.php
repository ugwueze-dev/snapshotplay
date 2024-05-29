<?php
/**                         
                                                                                                           
 Internal use only - not an endpoint
 
 */
class TransactionsXProducts extends SnapshotDataClass{
	
	function __construct($params, $authJWTstring = null) {
		parent::__construct($params, $authJWTstring, $enforceAccessPermissions = false);
        $this->db->table = 'transactionsXproducts';
		$this->db->requiredFields = array(
				'transactionID',
				'productID',
				
		);
		$this->db->fieldsArray = array(
				//'id'				=> @$this->params['id'],
				'transactionID'		=> @$this->params['transactionID'],
				'productID'		    => @$this->params['productID'],
				'quantityPurchased'	=> @$this->params['quantityPurchased'],
				'active'			=> @$this->params['active'],
		);
		//$this->id = isset($this->params['id']) ? $this->params['id'] : null;
		$this->db->fieldsArray = array_filter($this->db->fieldsArray,function($value) {
			return ($value !== null && $value !== false && $value !== '');//annoumous function that cleans the array
		});
    }

    /**
     * Description: Add a record to the TransactionsXProducts table
     *
     * Field data set using $this->db->fieldsArray $transactionID, $productID,  $quantityPurchased
     * @return array $response or $error
     */
        public function addTransactionXProductRecord() {
            /*/
            |--------------------------------------------------------------------------
            |         INTERNAL USE ONLY - NOT AN ENDPOINT
            |--------------------------------------------------------------------------
            */
           
            $this->params['active'] = 1;
           
            $addResult = $this->db->addRecord();
            if (!empty($addResult['id']) && $addResult['id'] > 0) {
                $this->id = $addResult['id'];
                return array('status' => 'success', "id" => $this->id, 'message' => 'Your TransactionXProduct record was added successfully.');
            } else {
                return array('status' => 'error',  'message' => 'Error creating record.', 'details' => $addResult);
                
            }
        }

        
	



}