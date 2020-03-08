<?php
/**
 * Creo un array con le directory eventualmente scansionate ricorsivamente.
 * Con questo creo una costante che verra' letta dalla funzione autoload.
 * Fare cosi permette di fare la scansione una sola volta all'avvio dell'applicazione e non ad
 * ogni chiamata della funzione autoload
 */

require_once __DIR__ . '/../composer-libs/vendor/autoload.php';


// Le directory da scansionare ricorsivamente
//$dirsToScan = array('entities', 'observers', 'plugins', 'app', 'sunrest');
$dirsToScan = array('../app', '../sunrest');

// Le directory da scansionare a un livello
//$dirsSingle = array('connector', 'connector/lib', 'sunrest/helpers', 'sunrest/actions', 'sunrest/services', 'app/services/sunmeteo');
$dirsSingle = array();

// Cerco le directory ricorsivamente
function getDirs($root='.', $result=array('.')) {
	$dirs = scandir($root);
	foreach ($dirs as $dir) {
		if ($dir != '.' && $dir != '..') {
			if (is_dir($root . DIRECTORY_SEPARATOR . $dir)) {
				$result[] = $root . DIRECTORY_SEPARATOR . $dir;
				$result = getDirs($root . DIRECTORY_SEPARATOR . $dir, $result);
			}
		}
	}
	 
	return $result;
}

// Aggiungo le directory su cui non cercare ricorsivamente
foreach ($dirsToScan as $dir) {
	$dirs = getDirs(__DIR__ . DIRECTORY_SEPARATOR .  $dir, array(__DIR__ . DIRECTORY_SEPARATOR . '.'));
	$dirs[] = __DIR__ . DIRECTORY_SEPARATOR .  $dir;
	foreach($dirsSingle as $dirSingle)
		$dirs[] = __DIR__ . DIRECTORY_SEPARATOR . $dirSingle;
	foreach ($dirs as $dir)
		$allDirs[$dir] = $dir;
}

$dirs = serialize(array_values($allDirs));

define('DIRS', $dirs);


function myAutoloader($class) {
	$dirs = unserialize(DIRS);
	foreach ($dirs as $dir) {
		if (is_file("$dir/$class.php")) {
			require_once("$dir/$class.php");
			break;
		}
	}
}
spl_autoload_register('myAutoloader');

	
?>
