<?php
class Office365_OfficeContacts_Model {
	
	protected $data = array();
	
	function __construct($data = array()){
		if(!empty($data)){
			$this->data = $data;
		}
	}
	
	function getData(){
		return $this->data;
	}
	
	function setId($id){
		$this->data['id'] = $id;
	}
	
	function setFirstName($val){
		$this->data['givenName'] = $val;
	}
	
	function setLastName($val){
		$this->data['surname'] = $val;
	}
	
	function setDisplayName($val){
		$this->data['displayName'] = $val;
	}
	
	function setBirthday($val){
	    $this->data['birthday'] = $val;
	}
	
	function setEmails($emails, $emptyPrev = false){
	    
		if($emails != '' && !is_array($emails)) $emails = array($emails);
		
		if( is_array($emails) && !empty($emails) ){
		    
		    if($emptyPrev)
		        $this->data['emailAddresses'] = array();
		    
			foreach($emails as $email){
				if($email != '')
					$this->data['emailAddresses'][] = array('address' => $email, 'name' => $email);
			}
		}
	}
	
	function setMobile($val){
	    $this->data['mobilePhone'] = $val;
	}
	
	function setHomePhone($val){
		$this->data['homePhones'] = array($val);
	}
	
	function setBusinessPhone($val){
		$this->data['businessPhones'] = array($val);
	}
	
	function setHomeAddress($street, $city, $state, $country_region, $postal){
		$this->data['homeAddress'] = array(
		   "street" => $street,
			"city" => $city,
			"state" => $state,
			"countryOrRegion" => $country_region,
			"postalCode" => $postal,
		);
	}
	

	function setOtherAddress($street, $city, $state, $country_region, $postal){
		$this->data['otherAddress'] = array(
		    "street" => $street,
			"city" => $city,
			"state" => $state,
			"countryOrRegion" => $country_region,
			"postalCode" => $postal,
		);
	}
	
	function setBusinessAddress($street, $city, $state, $country_region, $postal){
		$this->data['businessAddress'] = array(
		    "street" => $street,
			"city" => $city,
			"state" => $state,
			"countryOrRegion" => $country_region,
			"postalCode" => $postal,
		);
	}
	
	function setTitle($title) {
	    return $this->data['title'] = $title;
	}
	
	function setJobTitle($title) {
	    return $this->data['jobTitle'] = $title;
	}
	
	function setDepartment($value){
	    return $this->data['department'] = $value;
	}
	
	function setAssistantName($value){
	    return $this->data['assistantName'] = $value;
	}
	
	function setCompanyName($value){
	    return $this->data['companyName'] = $value;
	}
	
	function setDescription($desc){
	    return $this->data['personalNotes'] = (!empty($desc))?decode_html($desc):$desc;
	}
}
