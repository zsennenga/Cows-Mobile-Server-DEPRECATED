<?php
/**
 * CurlWrapper.php
 * 
 * Used to execute Curl Queries in a more ordered/abstracted way.
 * 
 * @author its-zach
 *
 */
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
		$this->error = "";
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
	private function genFilename()	{
		$charset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$randString = '';
		for ($i = 0; $i < 15; $i++) {
			$randstring = $charset[rand(0, strlen($characters))];
		}
		return realpath(dirname(__FILE__)) . "cookies/cookieFile" . $randString;
	}
	
	/**
	 *
	 * performEventRegistration
	 *
	 * Performes the entire request, from login, to logout. 
	 *
	 * @param Event $event
	 * @return Bool representing "Did this finish successfully as far as cURL is concerned?".
	 */
	public function performEventRegistration($event)	{
		//Generate URLs used to perform curl requests. Namely: Login, register event, and logout.
		$login = $baseUrl . CurlWrapper::LOGIN_PATH . "?" . $event->getTicketString();
		$execute = $baseUrl . CurlWrapper::EVENT_PATH . "?";
		$logout = $baseUrl . CurlWrapper::LOGOUT_PATH;
		
		if(!$this->executeCurlRequest($login))	{
				$this->destroySession();
				return false;
		}
		
		//Execute Event Request as POST
		curl_setopt($this->curlHandle, CURLOPT_POST, true);
		curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $event->getEventString() . $token);
		$token = $this->getRequestToken();
		if ($token == "ERROR")	{
			$this->destroySession();
			return false;
		}
		
		if(!$this->executeCurlRequest($execute))	{
			$this->destroySession();
			return false;
		}
		
		//Change everything back to GET and perform logout.
		curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, "");
		curl_setopt($this->curlHandle, CURLOPT_POST, false);
		if(!$this->executeCurlRequest($logout))	{
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
	 * @param String $url
	 * @return Bool representing "Did this finish successfully?"
	 */
	private function executeCurlRequest($url)	{
		curl_setopt($this->curlHandle, CURLOPT_URL, $url);
		$out = curl_exec($this->curlHandle);
		
		if ($out === FALSE) {
			$this->error = curl_error($this->curlHandle);
			return false;
		}
		
		return $this->errorScrape($out);
		
	}
	
	/**
	 * 
	 * errorScrape
	 * 
	 * Scrapes a given body of html text for errors from COWS
	 * 
	 * @param $htmlOutput
	 * @return Bool representing "Were there no COWS errors found?"
	 */
	private function errorScrape($htmlOutput)	{
		$doc = new DOMDocument();
		$doc->loadHTML($htmlOutput);
		
		$xp = new DOMXPath($doc);
		$div = $xp->query('//div[@class="validation-summary-errors"]');
		if ($div->length > 0)	{
			$div = $div->item(0);
			$this->error = "COWS Error:" . htmlspecialchars_decode(strip_tags($div->nodeValue()));
			return false;
		}
		
		return true;
	}
	
	/**
	 * 
	 * getRequestToken
	 * 
	 * @return String containing POST paramter containing the correct __RequestVerificationToken
	 * from COWS, or the string "ERROR" if there is an error.
	 * 
	 */
	private function getRequestToken()	{
		curl_setopt($this->curlHandle, CURLOPT_URL, $this->baseUrl . CurlWrapper::EVENT_PATH);
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
		if ($nodes->length == 0)	{
			$this->error = "Unable to obtain __RequestVerificationToken";
			return "ERROR";
		}
		$node = $nodes->item(0);
		
		$val = $node->getAttribute('value');
		
		return "__RequestVerificationToken=" . $val;
	}
	
	/**
	 * 
	 * destroySession
	 * 
	 * Closes the class instances' curl handle, and unlinks the cookie jar.
	 * 
	 */
	public function destroySession()	{
		curl_close($this->curlHandle);
		unlink($this->cookieFile);
	}
	
	/**
	 * 
	 * getError
	 * 
	 * Getter for $this->error
	 * 
	 * @return Error text
	 */
	public function getError()	{
		return $this->error;
	}
}
?>