<?php
class CurlWrapper	{
	private $baseUrl;
	private $curlHandle;
	private $error;
	private $cookieFile;
	const LOGIN_PATH = "Account/LogOn";
	const EVENT_PATH = "Event/Create";
	const LOGOUT_PATH = "Account/LogOff";
	
	function CurlWrapper($baseUrl)	{
		$this->baseUrl = $baseURL;
		$this->curlHandle = curl_init();
		$this->cookieFile = genFilename();
		curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curlHandle, CURLOPT_COOKIEJAR, $this->cookieFile);
		curl_setopt($this->curlHandle, CURLOPT_COOKIEFILE, $this->cookieFile);
	}
	
	/**
	 * 
	 * genFilename
	 * 
	 * Generates a random filename for the cookie file for cURL
	 * 
	 * @return cookieFile name
	 */
	function genFilename()	{
		$charset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$randString = '';
		for ($i = 0; $i < 15; $i++) {
			$randstring = $charset[rand(0, strlen($characters))];
		}
		return realpath(dirname(__FILE__)) + "cookies/cookieFile" + $randString;
	}
	
	/**
	 *
	 * performEventRegistration
	 *
	 * Performes the entire request, from login, to logout. 
	 * 
	 * Returns a bool representing "Did this finish successfully as far as cURL is concerned?".
	 *
	 * @param Event $event
	 */
	
	public function performEventRegistration($event)	{
		
		//Generate URLs used to perform curl requests. Namely: Login, register event, and logout.
		$login = $baseUrl + CurlWrapper::LOGIN_PATH + "?" + $event->getTicketString();
		$execute = $baseUrl + CurlWrapper::EVENT_PATH + "?";
		$logout = $baseUrl + CurlWrapper::LOGOUT_PATH;
		
		if(!$this->executeCurlRequest($login,false))	{
				$this->destroySession();
				return false;
		}
		
		//Execute Event Request as POST
		curl_setopt($this->curlHandle, CURLOPT_POST , true);
		
		$token = $this->getRequestToken();
		if ($token == "ERROR")	{
			$this->destroySession();
			return false;
		}
		curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $event->getEventString() + $token);
		
		if(!$this->executeCurlRequest($login,true))	{
			$this->destroySession();
			return false;
		}
		
		//Change everything back to GET and perform logout.
		curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, "");
		curl_setopt($this->curlHandle, CURLOPT_POST , false);
		if(!$this->executeCurlRequest($logout,false))	{
			$this->destroySession();
			return false;
		}
		
		//Finish query, destroy handle/cookie
		$this->destroySession();
		return true;
	}
	
	/**
	 * executeCurlRequest
	 * 
	 * Executes a generic curl request
	 * 
	 * Returns a bool representing "Did this finish successfully?"
	 * 
	 * @param String $url
	 * @param Bool $scrapeForErrors
	 */
	
	private function executeCurlRequest($url, $scrapeForErrors)	{
		
		curl_setopt($this->curlHandle, CURLOPT_URL, $url);
		$out = curl_exec($this->curlHandle);
		
		if ($out === FALSE) {
			$this->error = curl_error($this->curlHandle);
			return false;
		}
		
		if ($scrapeForErrors) return $this->errorScrape($out);
		
		else return true;
	}
	
	private function errorScrape($htmlOutput)	{
		$doc = new DOMDocument();
		$doc->loadHTML($htmlOutput);
		
		//TODO actually scrape for errors
		return true;
	}
	
	/**
	 * 
	 * getRequestToken
	 * 
	 * Returns a string representing a POST paramter containing the correct __RequestVerificationToken
	 * from COWS
	 * 
	 */
	private function getRequestToken()	{
		curl_setopt($this->curlHandle, CURLOPT_URL, $this->baseUrl + CurlWrapper::EVENT_PATH);
		$out = curl_exec($this->curlHandle);
		
		if ($out === FALSE) {
			$this->error = curl_error($this->curlHandle);
			return "ERROR";
		}
		
		//Grab token
		$doc = new DOMDocument();
		$doc->loadHTML($out);
		$xp = new DOMXPath($doc);
		$nodes = $xp->query('//input[@name="__RequestVerificationToken"]');
		$node = $nodes->item(0);
		
		$val = $node->getAttribute('value');
		
		return "__RequestVerificationToken=" + $val;
	}
	
	public function destroySession()	{
		curl_close($this->curlHandle);
		unlink($this->cookieFile);
	}
	
	public function getError()	{
		return $this->error;
	}
}
?>