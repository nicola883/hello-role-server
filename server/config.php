<?php

/**
 * Le configurazioni dell'istanza
 *
 */

// La directory per salvare la sessione
define('SESSION_DIR', '/tmp/helloroles/');

// La directory dei file di log
define('DIR_LOG', getenv('DIR_LOG'));

// il log di debug
define('DIR_LOG_DEBUG_FILE', DIR_LOG . '/debug.log');

// l'uri del server
define('SERVER_PATH', 'server/');

// l'uri del client
define('CLIENT_PATH', 'client/app/index.html#/');

// il search path del db
define('SEARCH_PATH', 'public, ecommerce');

// Una directory per i documenti, usata anche come temporanea
define('DOCUMENTS_DIR', '/tmp/');

// Una directory per le risorse degli utenti
//define('USERS_RESOURCES_DIR', '/var/www/sundata/resources/users/');
define('USERS_RESOURCES_DIR', getenv('RESOURCE_DIR') . '/users/');

// Una directory per le risorse
//define('RESOURCES_DIR', '/var/www/sundata/resources/app/');
define('RESOURCES_DIR', getenv('RESOURCE_DIR') . '/app/');

// La lingua di defaul
define('DEFAULT_LANGUAGE', 'it_IT');

//
define('BACKUPS_DIRECTORY', 'backups/');

// La dimensione massima per gli upload
define('MAX_UPLOAD_SIZE', 1000000);

// La misura di resize del logo in px
define('LOGO_RESIZE', 500);

// Tipo di utenti
define('ADMIN_TYPE_ID', 1);
define('CUSTOMER_TYPE_ID', 2);
define('AGENT_TYPE_ID', 3);

/**
 * Sequenza passi di registrazione
 * Definisci qui le richieste da fare in sequenza per registrare un utente al servizio
 * Piu' sequenze parziali intervallate da 'end'
 */
define('SIGNUP_SEQUENCE', serialize(array('user-data','account-data','loading', 'user-more-data', 'end',
		'load-operators', 'account-data', 'end',
		'load-gse', 'user-more-data', 'end')));

define('USERID_ADMIN_TYPE_ID', 1);


// una email di test
define('TEST_EMAIL', 'test@test.com');


// Nomi di risorce file fidati con cui si puo' fare l'upload (nome nell'input html)
define('TRUSTED_FILE', serialize(array(
		'logo' => true
)));


/**
 * Elenco delle risorse permesse con autenticazione a mezzo chiave
 */
define('KEY_PERMITS', serialize(array(
		'actions' => array(
				'logo.png' => true
		),
		'resources' => array(
				'wls' => array('get', 'post'),
		        'pages' => array('get', 'post', 'put'),
		        'evaluations' => array('get', 'post', 'put'),
		        'gt_blocks' => array('get', 'post', 'put')
		        //'plants' => array('get'),
		        //'users' => array('get')
		)
)));

/**
 * Elenco delle risorse che l'utente loggato puo' modificare
 * e delle Action che puo' eseguire
 */
define('UPDATE_PERMITS', serialize(array(
		'accounts' => true,
		'carts' => true,
		'payments' => true,
		'orders' => true,
		'user_settings' => true,
		'users' => true,
		'report_settings' => true,
		'devices' => true
)));

define('ACTION_PERMITS', serialize(array(
		'current-user' => true,
		'login' => true,
		'logo.png' => true,
		'logout' => true,
		'password-ok' => true,
		'setp' => true,
		'signup' => true,
		'time' => true,
		'upload' => true,
		'jobs-status' => true
)));

/**
 * Elenco delle risorse che l'amministratore loggato puo' modificare
 */
define('ADMIN_UPDATE_PERMITS', serialize(array(
		'accounts' => true,
		'orders' => true,
		'users' => true,
		'coupons' => true,
		'payments' => true
)));

/**
 * Elenco delle risorse a cui l'utente loggato puo' inserire elementi
 * (tabelle in cui e' ammesso l'insert)
 */
define('INSERT_PERMITS', serialize(array(
        'pages' => true,
        'evaluations' => true
)));

define('ADMIN_INSERT_PERMITS', serialize(array(
		'users' => true,
		'coupons' => true
)));

/**
 * Gestione viste ristrette:
 * Per proteggere la lettura di tutti i record delle tabelle, permetto di fare le query soltanto sulle delle viste
 * create ad hoc. Tipicamente le viste saranno delle select sulle tabelle che selezionano soltanto i record collegati
 * all'utente connesso.
 * Le viste vengono create alla costruzione di Server chiamando i metodi setRestrictQueriesAndParameter() e createRestrictedViews di Db.
 * Il metodo getTable() definito in Server e chiamato prima di ogni richiesta definita nei metodi in Server che accedono al db,
 * restituisce il nome della view da leggere.
 */
// Abilito la funzione delle viste ristrette (se attivata le query verranno fatte sulle viste ristrette anziche' sulla tabelle originarie
// se vuoi disabilitare scrivi OFF, ON per abilitare
define('RESTRICT', 'ON');


// l'elenco dei campi che devono essere serializzati
// Inserire nome_tabella.nome_campo_da_serializzare dove nome_tabella
// e' il nome della tabella temporanea definita dalla query in define('RESTRICT_QUERY'..)
// TODO fai una tabella con i tipi di campi che si popola con il primo post o meglio
// fai salvare e estrarre in json se il campo e' di tipo json cosi' eviti anche di dovere scrivere
// qui cose dei plugin
define ('TO_SERIALIZE' , serialize(array(
		'users.privacy' => 'object',
		'users.agents' => 'array',
		'users.coupons' => 'array',
        'users.wl_master' => 'object',
        'pages.roles' => 'object',
        'pages.tags' => 'array'
)));

/**
 * L'elenco dei campi da criptare
 */
define ('TO_ENCRYPT' , serialize(array(
		'accounts.userid' => true,
		'accounts.password' => true,
		'operators.gse_id_operatore' => true
)));



/* Certificato (chiave pubblica) */
define('CERTIFICATE',
		'-----BEGIN CERTIFICATE-----

-----END CERTIFICATE-----'
		);



?>
