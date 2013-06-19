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
	const CAS_PROXY_PATH = "https://cas.ucdavis.edu:8443/cas/proxy";
	
	/**
	 *
	 * Generates a random filename for the cookie file for cURL
	 *
	 * @return cookieFile name
	 */
	private function genFilename()	{
		$charset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$randString = '';
		for ($i = 0; $i < 15; $i++) {
			$randString .= $charset[rand(0, strlen($charset)-1)];
		}
		return realpath(dirname(__FILE__)) . "/cookies/cookieFile" . $randString;
	}
	
	/**
	 * 
	 * Constructor. Sets up curl handle, basic options, and a few other variables.
	 * 
	 * @param String $baseUrl
	 */
	function __construct($baseUrl)	{
		$this->baseUrl = $baseUrl;
		$this->curlHandle = curl_init();
		$this->cookieFile = $this->genFilename();
		$this->error = "";
		libxml_use_internal_errors(true);
		curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curlHandle, CURLOPT_COOKIEJAR, $this->cookieFile);
		curl_setopt($this->curlHandle, CURLOPT_COOKIEFILE, $this->cookieFile);
		curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYPEER, false);
	}
	
	/**
	 * Destructor. Really just a catch-all to make sure we close the curl handle
	 * and perform all other cleanup logic
	 */
	function __destruct()	{
		$this->destroySession();
	}
	
	/**
	 *
	 * Performes the entire request, from login, to logout. 
	 *
	 * @param Event $event
	 * @return Bool representing "Did this finish successfully as far as cURL is concerned?".
	 */
	public function performEventRegistration($event)	{
		
		//Convert our proxy ticket to a service ticket for COWS
		$out = $this->proxyToServiceTicket($event->getTicket());
		if ($out === false)	{
			return false;
		}

		//Generate URLs used to perform curl requests. Namely: Login, register event, and logout.
		$login = $this->baseUrl . CurlWrapper::LOGIN_PATH . "?" . "returnUrl=" . $this->baseUrl . '&ticket=' . trim($out);
		$execute = $this->baseUrl . CurlWrapper::EVENT_PATH;
		$logout = $this->baseUrl . CurlWrapper::LOGOUT_PATH;
		
		if(!$this->executeCurlRequest($login))	{
				$this->destroySession();
				return false;
		}
		
		//get __RequestVerificationToken from the event creation form
		$token = $this->getRequestToken();
		if ($token == "ERROR")	{
			$this->destroySession();
			return false;
		}
		
		//Setup POST options
		curl_setopt($this->curlHandle, CURLOPT_POST, true);
		curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $token . $event->getEventString());
		
		if(!$this->executeCurlRequest($execute))	{
			$this->destroySession();
			return false;
		}
		
		//Change everything back to GET and perform logout.
		curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, "");
		curl_setopt($this->curlHandle, CURLOPT_HTTPGET, true);
		
		if(!$this->executeCurlRequest($logout))	{
			$this->destroySession();
			return false;
		}
		
		//Finish query, destroy handle/cookie
		$this->destroySession();
		return true;
	}
	
	/**
	 * 
	 * Executes a generic curl request, sets error value if necessary.
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
	 * Takes in a TGT (Ticket Granting Ticket), also known as a proxy ticket, and uses it to generate a 
	 * ST (service ticket) using the UC Davis CAS system.
	 * 
	 * @param String $proxyTicket
	 * @return String $serviceTicket
	 */
	private function proxyToServiceTicket($proxyTicket)	{
		$url =  CurlWrapper::CAS_PROXY_PATH . "?" .
				"service=" . $this->baseUrl . Curl_Wrapper::LOGIN_PATH . "?returnUrl=" . $this->baseUrl .
				"&pgt=" . $proxyTicket;
		curl_setopt($this->curlHandle, CURLOPT_URL, $url);
		$out = curl_exec($this->curlHandle);
		
		if ($out === FALSE) {
			$this->error = curl_error($this->curlHandle);
			return false;
		}
		
		//Quick and dirty parsing of the CAS response
		if (strpos($out,"proxyFailure") === false)	{
			$out = strip_tags($out);
			$out = str_replace(' ', '', $out);
			$out = str_replace('\n','', $out);
			$out = str_replace('\t','', $out);
			$out = str_replace('\r', '', $out);
			return $out;
		}	
		else	{
			$this->error = "Invalid Proxy Ticket. Please Reauthenticate to CAS.";
			return false;
		}
		
	}
	
	/**
	 * 
	 * Scrapes a given body of html text for errors from COWS
	 * 
	 * @param $htmlOutput
	 * @return Bool representing "Were there no COWS errors found?"
	 */
	private function errorScrape($htmlOutput)	{
		//Generate Xpath from the html output of a cURL query
		$doc = new DOMDocument();
		$doc->loadHTML($htmlOutput);
		$xp = new DOMXPath($doc);
		$div = $xp->query('//div[@class="validation-summary-errors"]');
		
		//Any results means cows threw an error
		if ($div->length > 0)	{
			$div = $div->item(0);
			$error = str_replace('may not be null or empty', '', $div->nodeValue);
			$this->error = "COWS Error: " . strip_tags(htmlspecialchars_decode($error));
			return false;
		}
		
		//Cows likes to throw generic errors sometimes for no reason
		//Well okay there is usually a reason
		if (strstr($htmlOutput,"Error") !== false)	{
			$this->error = "COWS Error: Unknown Problem occurred.";
			return false;
		}
		
		return true;
	}
	
	/**
	 * 
	 * @return String containing POST paramter containing the correct __RequestVerificationToken
	 * from COWS, or the string "ERROR" if there is an error.
	 * 
	 */
	private function getRequestToken()	{
		//Get event creation page
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
				
		return "__RequestVerificationToken=" . urlencode($val) . '&';
	}
	
	/**
	 * 
	 * Closes the class instances' curl handle, and unlinks the cookie jar.
	 * 
	 */
	public function destroySession()	{
		//Attempt to logout
		curl_setopt($this->curlHandle, CURLOPT_HTTPGET, true);
		curl_exec($this->curlHandle,$this->baseUrl . CurlWrapper::LOGOUT_PATH);
		//Close cURL handle
		curl_close($this->curlHandle);
		//Destroy cookie file
		unlink($this->cookieFile);
	}
	
	/**
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