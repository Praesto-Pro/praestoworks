<?php
class Office365_OfficeCalendar_Model {
	
	protected $data = array(
		'id' => '',
		'subject' => '',
		'body' => array( 'contentType' => 'text', 'content' => ''),
		'start' => '',
		'sensitivity' => '',
	    'end' => '',
		'location' => array('displayName' => ''),
		'showAs' => 'Busy',
	);
	
	function __construct($data = array()){
		if(!empty($data)){
			//$this->data = $data;
			$this->data = array_intersect($this->data, $data);
		}
	}
	
	function getData(){
		return $this->data;
	}
	
	function setId($id){
		$this->data['id'] = $id;
	}
	
	function setSubject($val){
		$this->data['subject'] = $val;
	}
	
	function setLocation($val){
		$this->data['location']['displayName'] = $val;
	}
	
	function setDescription($val){
		$this->data['body']['content'] = $val;
	}
	
	function setStart($val){
	    $this->data['start'] = array("dateTime" => $val, "timeZone" => "UTC");
	}
	
	function setEnd($val){
	    $this->data['end'] = array("dateTime" => $val, "timeZone" => "UTC");
	}
	
	function addAttendee($email, $name = ''){
		if($email != ''){
			if($name == '') $name = $email;
			
			$attendee = array(
				'emailAddress' => array(
					'address' => $email, 
					'name' => $name
				),
				'type' => 'required'
			);
			
			$this->data['attendees'][] = $attendee;
		}		
	}
	
	function setSensitivity($val){
	   $this->data['sensitivity'] = 'Normal';
	}
}
