<?php
/**
 * CowsMobileProxy.php
 * 
 * Proxy used as a go-between for the mobile app and COWS.
 * 
 * Takes ticket (as a CAS TGT ID), a site id and a whole smattering of event 
 * configuration information as inputs.
 * 
 */

require_once 'includes/CurlWrapper.php';
require_once 'includes/Event.php';

/**
 * Defines
 */
define("LOGIN_PATH","Account/LogOn");
define("EVENT_PATH","Event/Create");
define("LOGOUT_PATH","Account/LogOff");
define("CAS_PROXY_PATH","https://cas.ucdavis.edu:8443/cas/proxy");
define("COWS_BASE_PATH","http://cows.ucdavis.edu/");
define("ERROR_GENERIC", "-1");
define("ERROR_CAS", "-2");
define("ERROR_EVENT", "-3");
define("ERROR_CURL", "-4");

/**
 *
 * Quick wrapper to handle the construction of
 * error messages if an error condition is met.
 *
 * @param Boolean $bool
 * @param String $errorMessage
 */
function checkError($bool, $errorMessage)	{
	if (!$bool)	{
		echo $errorMessage;
		exit(0);
	}
}

if (!isset($_GET['siteid']))	{
	exit(0);
}
else	{
	define("SITE_ID",$_GET['siteid']);
	unset($_GET['siteid']);
}
//Create an event object and use it to verify all our ducks are in a row regarding the request parameters.
$event = new Event($_GET,SITE_ID);

checkError($event->constructParameters(),$event->getErrors());

$curlWrapper = new CurlWrapper(SITE_ID);

//Login, do the event request, and Logout. If any errors happen along the way, stop and send a message to the user.
checkError($curlWrapper->performEventRegistration($event), 	$curlWrapper->getError());

echo "0:0";

?>