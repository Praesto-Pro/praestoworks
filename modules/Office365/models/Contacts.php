<?php

class Office365_Contacts_Model extends Office365_SyncRecord_Model {
    
    /**
     * return id of Office Record
     * @return <string> id
     */
    public function getId() {
        return $this->data['entity']['id']; 
    }
    
	function get($key){
    	if($key != '' && isset($this->data['entity'][$key])){
    		return $this->data['entity'][$key];
    	}
    	return '';
    }
    
    function getEntityData(){
    	if(isset($this->data['entity'])){
    		return $this->data['entity'];
    	}
    	return array();
    }

    /**
     * return modified time of Office Record
     * @return <date> modified time 
     */
    public function getModifiedTime() {
        return date('Y-m-d H:i:s',strtotime($this->data['entity']['lastModifiedDateTime'])); 
    } 


    function getInitials() {
        $val = $this->data['entity']['initials']; 
        return $val;
    }
    

    /**
     * return first name of Office Record
     * @return <string> $first name
     */
    function getFirstName() {
        $fname = $this->data['entity']['givenName']; 
        return $fname;
    }

    /**
     * return Lastname of Office Record
     * @return <string> Last name
     */
    function getLastName() {
        $lname = $this->data['entity']['surname']; 
        return $lname;
    }

    /**
     * return Emails of Office Record
     * @return <array> emails
     */
    function getEmails() {
        $emails = $this->data['entity']['emailAddresses']; 
        return $emails; 
    }

    function getEmailAddress1(){
     
        $emails = $this->getEmails();
        
        if(!empty($emails))
            return $emails[0]['address'];
        
        return false;
    }
    
 	function getMobilePhone() {
        return $this->data['entity']['mobilePhone'];
    }
    
 	function getBusinessPhones() {
 		$phones = array();
 		$phones = $this->data['entity']['businessPhones']; 
 		return $phones;
    }
    
    function getBusinessPhone(){
        $businessPhones = $this->getBusinessPhones();
        $bPhone = "";
        if(!empty($businessPhones)){
            if( !empty($businessPhones) ){
                for( $k=0; $k<count($businessPhones); $k++ ){
                    if( isset($businessPhones[$k]) && $businessPhones[$k] != '' ){
                        $bPhone = $businessPhones[$k];
                        break;
                    }
                }
            } 
        }
        return $bPhone;
    }
    
 	function getHomePhones() {
 		$phones = array();
        $phones = $this->data['entity']['homePhones']; 
        return $phones; 
    }
    
    function getHomePhone(){
        $homePhones = $this->getHomePhones();
        $hPhone = "";
        if(!empty($homePhones)){
            if( !empty($homePhones) ){
                for( $k=0; $k<count($homePhones); $k++ ){
                    if( isset($homePhones[$k]) && $homePhones[$k] != '' ){
                        $hPhone = $homePhones[$k];
                        break;
                    }
                }
            }
        }
        return $hPhone;
    }
    
	function getHomeAddress() {
        $home_address = $this->data['entity']['homeAddress'];
        return $home_address;
	}
	
	function  getBusinessAddress(){
        $business_address = $this->data['entity']['businessAddress'];        
        return $business_address;
	}
	
	function getOtherAddress(){        
        $address = $this->data['entity']['otherAddress'];        
        return $address;
    }
    
    function getUserDefineFieldsValues() {
        $fieldValues = array();
        return $fieldValues;
    }
    
    function getUrlFields() {
        $urls = array();
        return $urls;
    }
    
    function getBirthday() {
        return $this->data['entity']['birthday'];
    }
    
    function getTitle() {
        return $this->data['entity']['title']; 
    }
    
	function getJobTitle(){
		return $this->data['entity']['jobTitle']; 
    }
    
    function getAccountName($userId, $returnAccountName = false) {
        
        $description = false;
        
        $orgName = $this->data['entity']['companyName'];
        
        if($returnAccountName){
            return $orgName;
        }
        
        if(empty($orgName)) {
            $contactsModel = Vtiger_Module_Model::getInstance('Contacts');
            $accountFieldInstance = Vtiger_Field_Model::getInstance('account_id', $contactsModel);
            if($accountFieldInstance->isMandatory()) {
                $orgName = '????';
                $description = 'This Organization is created to support MSExchange Contacts Synchronization. Since Organization Name is mandatory !';
            }
        }
        if(!empty($orgName)) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery("SELECT crmid FROM vtiger_crmentity WHERE label = ? AND deleted = ? AND setype = ?", array($orgName, 0, 'Accounts'));
            if($db->num_rows($result) < 1) {
                try {
                    $accountModel = Vtiger_Module_Model::getInstance('Accounts');
                    $recordModel = Vtiger_Record_Model::getCleanInstance('Accounts');
                    
                    $fieldInstances = Vtiger_Field_Model::getAllForModule($accountModel);
                    foreach($fieldInstances as $blockInstance) {
                        foreach($blockInstance as $fieldInstance) {
                            $fieldName = $fieldInstance->getName();
                            $fieldValue = $recordModel->get($fieldName);
                            if(empty($fieldValue)) {
                                $defaultValue = $fieldInstance->getDefaultFieldValue();
                                if($defaultValue) {
                                    $recordModel->set($fieldName, decode_html($defaultValue));
                                }
                                if($fieldInstance->isMandatory() && !$defaultValue) {
                                    $randomValue = Vtiger_Util_Helper::getDefaultMandatoryValue($fieldInstance->getFieldDataType());
                                    if($fieldInstance->getFieldDataType() == 'picklist' || $fieldInstance->getFieldDataType() == 'multipicklist') {
                                        $picklistValues = $fieldInstance->getPicklistValues();
                                        $randomValue = reset($picklistValues);
                                    }
                                    $recordModel->set($fieldName, $randomValue);
                                }
                            }
                        }
                    }
                    $recordModel->set('mode', '');
                    $recordModel->set('accountname', $orgName);
                    $recordModel->set('assigned_user_id', $userId);
                    $recordModel->set('source', 'MSExchange');
                    if($description) {
                        $recordModel->set('description', $description);
                    }
                    $recordModel->save();
                    $account_id = $recordModel->getId();
                } catch (Exception $e) {
                    //TODO - Review
                }
            } else{
                $account_id = $db->query_result($result,0,'crmid');
            }
            return vtws_getWebserviceEntityId('Accounts',$account_id);
        }
        return false;
    }
    
    function getDescription() { 
        return $this->data['entity']['personalNotes']; 
    }

    /**
     * Returns the Office365_Contacts_Model of Office Record
     * @param <array> $recordValues
     * @return Office365_Contacts_Model
     */
    public static function getInstanceFromValues($recordValues) {
        $model = new Office365_Contacts_Model($recordValues);
        return $model;
    }

    /**
     * converts the Office Format date to 
     * @param <date> $date Office Date
     * @return <date> Vtiger date Format
     */
    public function vtigerFormat($date) {
        list($date, $timestring) = explode('T', $date);
        list($time, $tz) = explode('.', $timestring);

        return $date . " " . $time;
    }
    
	public function getSyncIdentificationKey(){
		return $this->data['_syncidentificationkey'];
	}

	public function getAssistant(){
	    return $this->data['entity']['assistantName'];
	}
	
	public function getDepartment(){
	    return $this->data['entity']['department'];
	}
}

?>
