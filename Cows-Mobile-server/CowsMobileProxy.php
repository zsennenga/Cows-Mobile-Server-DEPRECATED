<?php
/**
 * CowsMobileProxy.php
 * 
 * Proxy used as a go-between for the mobile app and cows.
 * 
 * Takes ticket (as a CAS ticket ID) and a whole smattering of event configuration information as inputs.
 * 
 */
require_once 'includes/CurlWrapper.php';

/**
 * 
 * @param Boolean $bool
 * @param Status Code $return
 */
function checkError($bool, $return)	{
	if ($bool)	{
		echo $return;
		exit(0);
	}
}

function checkEventParameters($getArray)	{
	return false;
}

checkError(!isset($_GET['ticket']),"1");
checkError(checkEventParameters($_GET),"2");

$curlWrapper = new curlWrapper("http://cows.ucdavis.edu/its/");

$curlWrapper->login($_GET['ticket']);
checkError($curlWrapper->error,"3");

$curlWrapper->setParameters($_GET);
checkError($curlWrapper->error,"4");

$curlWrapper->logout();
checkError($curlWrapper->error,"5");

echo "0";
exit(0);

?>