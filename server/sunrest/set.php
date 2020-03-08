<?php


/**
 * Configurazione del core
 */

define('DEBUG_ACTIVE', isset($_SERVER['DEBUG_ACTIVE']) && $_SERVER['DEBUG_ACTIVE'] == true ? true : false);

// Il valore e' quello della costante settata in .htaccess per gli script
// eseguiti da apache o in ~/.bash_profile per quelli eseguiti in console
// Se settato attiva il debug log. Scrive piu' messaggi nel file di log
// Usa vtiger locale anziche' quello in produzioner
define('DEBUG_LOG', $_SERVER['DEBUG_LOG'] == true ? true : false);


// True (1) se siamo nell'ambiente di produzione
define('PRODUCTION_ENV', $_SERVER['PRODUCTION'] == true ? true : false);
define('DEV_ENV', !PRODUCTION_ENV); // Il negato di PRODUCTION_ENV

// La lunghezza massima di una stringa per essere considerata text nell'input da GET
// usato in Helper::isType
define('MAX_TEXT_LENGTH', 256);


// Stringhe
define('QUERY_ERROR', 'Errore della query: ');

if (PRODUCTION_ENV)
	date_default_timezone_set("Europe/Rome");
else {
	date_default_timezone_set(date_default_timezone_get());
}


/**
 * Header per errori
 */
define('OK', 'HTTP/1.1 200 OK');
define('CREATED', 'HTTP/1.1 201 CREATED');
define('INTERNAL_ERROR', 'HTTP/1.1 500 Internal Server Error');
define('BAD_REQUEST', 'HTTP/1.1 400 Bad Request');
define('UNAUTHORIZED', 'HTTP/1.1 401 Unauthorized');
define('FORBIDDEN', 'HTTP/1.1 403 Forbidden');
define('CONFLICT', 'HTTP/1.1 409 Conflict');
define('METHOD_NOT_ALLOWED', 'HTTP/1.1 405 Method Not Allowed');
define('ALLOWED_GET', 'Allow: GET');
define('NOT_FOUND', 'HTTP/1.1 404 NOT Found');
define('MEDIA_UNSUPPORTED', 'HTTP/1.1 415 Unsupported Media Type');


// Estensione che definisce il nome della vista: nome_vista = nome_tabella + estensione
define('RESTRICT_VIEW_EXTENSION', '_restrict');

?>