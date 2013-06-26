<?php
/**
 * Event
 * 
 * Used to store and validate event information
 * 
 * @author Zachary Ennenga
 *
 */
class Event	{
	private $errors;
	private $ticketID;
	
	private $getString;
	private $getArray;
	private $siteId;
	
	function __construct($getArray,$siteid)	{
		$this->siteId = $siteid;
		$this->getArray = $getArray;
		$this->errors = "";
	}
	
	/**
	 * 
	 * Getter for the CAS ticket id
	 * 
	 * @return CAS ticket as a GET parameter
	 */
	public function getTicket()	{
		return $this->ticketID;
	}
	
	/**
	 * 
	 * Getter for the event string
	 * 
	 * @return Event Configuration information as a set of POST parameters.
	 */
	public function getEventString()	{
		$retString = http_build_query($this->getArray);
		$retString .= "&SiteId=" . urlencode($this->siteId);
		
		return $retString;
	}
	
	/**
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
			$this->ticketID = $this->getArray['ticket'];
			unset($this->getArray['ticket']);
		}
		$cat = $this->getArray['Categories'];
		$loc = $this->getArray['Locations'];
		if (strlen($cat) > 0) $cat = split("&",$cat);
		if (strlen($loc) > 0) $loc = split("&",$loc);
		$this->getString = http_build_query($this->getArray);
		foreach($cat as $str)	{
			$this->getString .= "&Categories=" . urlencode($str);
		}
		foreach($loc as $str)	{
			$this->getString .= "&Locations=" . urlencode($str);
		}
		return $noErrors;
	}
	
	/**
	 * 
	 * Getter for $this->errors
	 * 
	 * @return Error string
	 */
	public function getErrors()	{
		return ERROR_EVENT  . ":" .  $this->errors;
	}
	
	/**
	 * 
	 * Getter for $this->siteId
	 *
	 * @return the siteid
	 */
	public function getSiteId()	{
		return $this->siteId;
	}
}
?>