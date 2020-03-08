<?php

$p = 3;

$http_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;

//$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "https://solarreport.eon-energia.com" || $http_origin == "http://localhost:8080" 
    || $http_origin == "ionic://myhost.it") {
    header("Access-Control-Allow-Origin: $http_origin");
}

//header("Access-Control-Allow-Origin: https://host.it", false);

//////  header("Access-Control-Allow-Origin: https://solarreport.eon-energia.com", false);
//      header("Access-Control-Allow-Origin: https://localhost:8080", false);
/*
 $httpOrigin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;
 
 
 if ($httpOrigin == 'http://localhost' || $httpOrigin == 'https://solarreport.eon-energia.com') {
 header("Access-Control-Allow-Origin: $httpOrigin", false);
 } else
 header("Access-Control-Allow-Origin: https://solarreport.eon-energia.com", false);
 */
 
 

header("Access-Control-Allow-Credentials: true", false);
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS', false);
header("Access-Control-Allow-Headers: Sun-App, Eon-App, X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding", false);

if (!function_exists('getallheaders'))
{
	function getallheaders()
	{
		$headers = [];
		foreach ($_SERVER as $name => $value)
		{
			if (substr($name, 0, 5) == 'HTTP_')
			{
				$headers[str_replace(' ', '-',
					ucwords(strtolower(str_replace('_', ' ',substr($name, 5)))))] =
					$value;
			}
		}
		return $headers;
	}
}


function shutdown()
{
	// This is our shutdown function, in
	// here we can do any last operations
	// before the script is complete.
	// At the end of your script
	$s = Factory::createServer();
	$u = $s->getCurrentUser(true);
	$request = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
	$method = $_SERVER['REQUEST_METHOD'];
	Utility::log("user; {$u['id']}; $time s; $method; $request");
}

//register_shutdown_function('shutdown');


// TODO Per ora includo qui la configurazione dell'applicazione
include 'app/set.php';

// TODO e quelle comuni
include 'config.php';

include 'sunrest/index.php';



?>
