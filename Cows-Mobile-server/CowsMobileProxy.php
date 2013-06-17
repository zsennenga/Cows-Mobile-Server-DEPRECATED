<?php
/**
 * CowsMobileProxy.php
 * 
 * Proxy used as a go-between for the mobile app and COWS.
 * 
 * Takes ticket (as a CAS ticket ID) and a whole smattering of event 
 * configuration information as inputs.
 * 
 */
require_once 'includes/CurlWrapper.php';
require_once 'includes/Event.php';

/**
 * checkError
 * 
 * Quick wrapper to handle the construction of 
 * error messages if an error condition is met.
 * 
 * @param Boolean $bool
 * @param String $errorMessage
 */
function checkError($bool, $errorMessage)	{
	if (!$bool)	{
		echo "-1:" + $return;
		exit(0);
	}
}

//Create an event object and use it to verify all our ducks are in a row regarding the request parameters.
$event = new Event($_GET);
checkError($event->checkParameters(),$event->getErrors());

$curlWrapper = new CurlWrapper("http://cows.ucdavis.edu/its/");

//Login, do the event request, and Logout. If any errors happen along the way, stop and send a message to the user.
checkError($curlWrapper->performEventRegistration($event), 	$curlWrapper->getError());

echo "0:0";
?>