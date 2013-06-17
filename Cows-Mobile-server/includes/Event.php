<?php
class Event	{
	private $errors;
	private $ticketID;
	
	private $getString;
	private $getArray;
	private $siteId;
	
	function Event($getArray,$siteid)	{
		$this->siteId = $siteid;
		$this->getArray = $getArray;
		$this->errors = "";
	}
	
	/**
	 * 
	 * getTicketString
	 * 
	 * @return CAS ticket as a GET parameter
	 */
	public function getTicketString()	{
		return "ticket=" . $this->ticketID;
	}
	
	/**
	 * 
	 * getEventString
	 * 
	 * @return Event Configuration information as a set of POST parameters.
	 */
	public function getEventString()	{
		$retString = $this->getArray;
		$retString .= "&SiteId=" . $this->siteId;
		
		return $retString;
	}
	
	/**
	 * 
	 * checkParameters
	 * 
	 * Checks parameters for correctness and seperates out necessary ones.
	 * 
	 * @return Bool representing whether or not all parameters are valid.
	 */
	public function constructParameters()	{
		
		$noErrors = true;
		
		if (!isset($this->getArray['ticket']))	{
			$this->errors .= "Ticket not set";
			$noErrors = false;
		}
		else	{
			$this->ticketID = $getArray['ticket'];
			unset($getArray['ticket']);
			//TODO: Verify ticket.
		}
		
		$this->getString = http_build_query($this->getArray);
		
		return $noErrors;
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