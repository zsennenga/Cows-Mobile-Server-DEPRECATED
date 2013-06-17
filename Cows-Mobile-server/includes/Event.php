<?php
class Event	{
	private $errors;
	private $ticketID;
	
	private $getString;
	private $getArray;
	private $siteId;
	
	function Event($getArray,$siteid)	{
		$this->siteId = $siteid;
		$this->ticketID = $getArray['ticket'];
		unset($getArray['ticket']);
		$this->getArray = $getArray;
		$this->getString = http_build_query($getArray);
		$this->errors = "";
	}
	
	/**
	 * 
	 * getTicketString
	 * 
	 * @return CAS ticket as a GET parameter
	 */
	public function getTicketString()	{
		return "ticket=" + $this->ticketID;
	}
	
	/**
	 * 
	 * getEventString
	 * 
	 * @return Event Configuration information as a set of POST parameters.
	 */
	public function getEventString()	{
		$retString = $this->getArray;
		$retString += "&SiteId=" + $this->siteId;
		
		return $retString;
	}
	
	/**
	 * 
	 * checkParameters
	 * 
	 * Checks parameters for correctness.
	 * 
	 * @return Bool representing whether or not all parameters are valid.
	 */
	public function checkParameters()	{
		//TODO: Verify ticket.
		return true;
	}
	
	/**
	 * Getter for $this->errors
	 * 
	 * @return Error string
	 */
	public function getErrors()	{
		return $this->errors;
	}
}
?>