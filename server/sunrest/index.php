<?php

ini_set('html_errors', 'off');


/**
 * Salvo la sessione in una directory personalizzata per evitare che piu' applicazioni diverse sullo
 * stesso server condividano questa sessione
 * @see https://www.dev-metal.com/prevent-php-sessions-shared-different-apache-vhosts-different-applications/
 * Il problema ce l'ho ovviamente anche se sono sulla stessa directory
 */
// controllo se e' stata definito un percorso per la sessione
if (defined('SESSION_DIR') && !empty(SESSION_DIR)) {
	// Se non esiste lo creo
	if (!file_exists(SESSION_DIR)) {
		//mkdir(SESSION_DIR, 0777, true);
	}
	// controllo che la directory esista e sia scrivibile
	if (is_writable(SESSION_DIR)) {
		ini_set('session.save_path', SESSION_DIR);
		// Setto il garbage collector
		// @see http://php.net/manual/en/function.session-save-path.php
		ini_set('session.gc_probability', 1);
	}
}



//xdebug_disable();
// ini_set('xdebug.halt_level', E_NOTICE);
//sleep(1);

session_start();

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
	return true;


include_once(__DIR__ . '/set.php');
include(__DIR__ . '/autoload.php');



/*
 * Prima di tutto i controlli di sicurezza
 */
$uriSafe = Security::filterUri($_SERVER['REQUEST_URI']);


if (!Security::isSintaxSafeUri($uriSafe)) {
	header(NOT_FOUND);
	exit();
}



// La chiave potrebbe essere arrivata via header

$h = getallheaders();
if (!isset($_GET['key']) && isset($h['Sun-User-Key'])) {
    $_GET['key'] = $h['Sun-User-Key'];
}


// Se e' una chiave vedo se e' valida
if (isset($_GET['key'])) {
    $db = Factory::createDb();
    $users = $db->getList('users', array('guid' => $_GET['key']), false, null, true);
    if (empty($users)) {
        header(NOT_FOUND);
        exit();
    } 
}

// ora posso istanziare il server
// Perche' ho fatto un po' di controlli
$server = Factory::createServer();

// Sostituisco il tratto uid dell'url con l'id utente (metodo addUid)
// Parso l'uri e ottengo tutto quello che mi serve. D'ora in poi l'uri non si guarda piu'
// se non e' valida non si entra
if (!$input = Security::parseUri($server, $server->addUid($uriSafe, $server->getUserId()))) {
	header(NOT_FOUND);
	exit();
}

// Qui devo controllare gli accessi con Authorization e mandare fuori se si chiede
// un accesso non consentito. Sara' sicuramente /signup per gli anonimi
// ATTENZIONE dai accesso alle Action una per una. PasswordOkAction solo l'utente!
// e con una white list, niente di ricorsivo! Permessi a signup?step=user-data <> signup?step=load-gse !
if(!Authorization::isAuthorized($server, $input)) {
	header(NOT_FOUND);
	exit();	
}

unset($input['uri']);


/*
* L'url e' ben formata e sicura. Se sono qui e' perche' posso accedere a cio' che e' indicato nella uri.
* Posso allora istanziare ed eseguire le azioni. Piu' avanti le risorse
*/

// TODO da filtrare!
$server->setPostData(file_get_contents("php://input"));

// TODO DA TOGLIERE: nessuno deve avere bisogno dell'uri pura
if (!$server->setUri($_SERVER['REQUEST_URI'])) {
	header(NOT_FOUND);
	exit();
}

if (defined('DEBUG_LOG') && DEBUG_LOG === true && !empty($server->getPostData()))
	Utility::log($server->getPostData());

// Eseguo le azioni
if (isset($input['action-class'])) {
	$action = new $input['action-class']($server);
	if(($ret = $action->exec($input['filter'], $server->getPostData(true)))) {
		echo $ret;
	}
	exit();
} else if (isset($input['collection'])) {
	$id = isset($input['id']) ? $input['id'] : null;
	$entityProposedName = Helper::uri2Camel(rtrim($input['collection'], 's'));
	$entity = Factory::createEntity($input['collection'], $entityProposedName, $server, $id);
	$actionEntityClass = isset($input['action-entity-class']) ? $input['action-entity-class'] : null;

	$actionEntity = Factory::createEntityAction($actionEntityClass, $input['method'], $id, $entityProposedName, $server);
	
	if ($actionEntity !== false && !empty($actionEntity)) {
		$ret = $entity->execAction($actionEntity, $input['filter'], $server->getPostData(true));		
		echo Monitor::get()->update('entity_output_filter', $entity, $ret);
	} else
		header(NOT_FOUND);
	exit();
} else 
	exit();


?>
