<?php

/**
 * Implementa strumenti vari per la sicurezza degli accessi
 */

class Security
{
	// la massima lunghezza dell'url dopo SERVER_PATH
	// usato anche per la massima lunghezza dell'url competa
	const MAX_URI_LENGTH = 1000;
	
	// Il numero di parti di una uri che chiede una action
	// Se e' uno => /server/action e non /server/action/seconda_parte
	const ACTION_URI_PART = 1;
	
	const RESOURCE_NAME = 'resource';
	
	/**
	 * Restituisce l'uri della richiesta, dopo avere verificato che sia sicura.
	 * @param string $uri Un url da verificare
	 * @return boolean | string L'uri o false se non e' valida
	 */
	static public function isSintaxSafeUri($uri) {
				
		// Primo controllo la lunghezza
		if (strlen($uri) > self::MAX_URI_LENGTH)
			return false;
		
		// Escludo le uri che contengono caratteri diversi da questi elencati
		$regex = "/[^A-Za-z0-9\?\=\:\&\/\:\-\%\.\_]/";
		if (preg_match($regex, $uri)) {
			return false;
		}		
		
		return true;
	}
	

	/**
	 * Filtra un uri. Da chiamare prima di tutto.
	 * @param unknown $uri
	 */
	static public function filterUri($uri) {		
		$uri = filter_var($uri, FILTER_SANITIZE_URL);
		$parsed = parse_url($uri);
		return $parsed['path'] . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
	}
	
	/**
	* Chiamare prima di questo filterUri.
	* Restituisce azioni, risorse e campi dopo averle verificate
	* La struttura e':
	* /action/[?params_for_action] | [ [action/] [:id] [/collection] ... [?resource_action[&params]] [&filters]]
	* dove:
	* action = il nome di una classe Action | resource
	* params_for_action = parametri per la Action
	* collection = nome di una classe Entity, di una classe EntityCollection o una tabella del db
	* resource_action = il nome di una classe EntityAction, action che agisce su una entita'
	* params = parametri per la resource_action
	* filters = filtri per l'estrazione della collezione
	* 
	* Restituisce un array con
	* method
	* action
	* collection
	* id
	* filter
	* action-entity
	* action-entity-class
	* action-class
	* uri: l'url relativa con base server/
	* @param Server $server Il server
	* @param string $uriSafe L'uri processata da filterUri()
	*/
	static public function parseUri(Server $server, $uriSafe) {

		$ret = array();
		
		$ret['uri'] = explode(SERVER_PATH, $uriSafe)[1];

		$uri = parse_url($ret['uri'])['path'];		
		
		$ret['method'] = $_SERVER['REQUEST_METHOD'];
		
		// Le richieste con una chiave sono permesse solo da locale
		//if (isset($_GET['key']) && $_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR'])
		//	return false;
		
		// Leggo la chiave se settata e poi la cancello perche'
		// e' un parametro riservato e non deve potere essere usato per altre cose
		$key = null;

		if (isset($_GET['key'])) {
			$ret['key'] = $_GET['key'];
			unset($_GET['key']);
		}

		$params = $_GET;		
		
		//$uri = explode(SERVER_PATH, $uri)[1];
		if (empty($uri))
			return false;
		$parts = explode("/", $uri);
		// Non accetto uri che finiscono con /
		if (end($parts) == '')
			return false;
		else 
			reset($parts);

		// estraggo l'action
		$ret['action'] = $parts[0];
		if(!self::validAction($ret['action'], count($parts), $ret['method'], $server))
			return false;

		// Se l'azione e' la richiesta di una risorsa, setto la collezione
		// e l'eventuale azione su di essa
		if ($ret['action'] == self::RESOURCE_NAME) {
			if (isset($parts[1]) && self::validCollection($parts[1], $server)) {
				// Setto la collezione richiesta e parametri
				$subParts = $parts; // rimuovo l'action
				unset($subParts[array_search($ret['action'], $subParts)]);
				if (null === ($cp = self::parseCollections($subParts)))
					return false;
				if (isset($cp['id']))
					$ret['id'] = $cp['id'];
				$ret['collection'] = $cp['collection'];

				// Cerco al massimo una eventuale azione per l'entita'
				if (!empty($params)) {
					$eActions = array_keys($params);
					// Alla fine provo con le azioni standard
					switch (strtoupper($ret['method'])) {
						case 'GET':
							$crudName = empty($ret['id']) ? 'Query' : 'Get';
							break;
						case 'POST':
							$crudName = 'Create';
							break;
						case 'PUT':
							$crudName = 'Update';
							break;
						case 'DELETE':
							$crudName = 'Delete';
					}
					array_push($eActions, $crudName);
					foreach ($eActions as $eAction) {
						if(self::validEntityAction($eAction, $ret['collection'])) {
							$ret['action-entity'] = $eAction;
							$ret['action-entity-class'] = Helper::uri2Camel($eAction) . Helper::uri2Camel(rtrim($ret['collection'], 's')) . 'Action';
							unset($params[$eAction]);
							break;
						}
					}
				}
				
				// Aggiungo i parametri. Non l'ho fatto prima di avere cercato l'azione per l'entita'
				// per non dare da provare con i vari user_id
				if (isset($cp['params']))
					$params = array_replace_recursive($params, $cp['params']);				
				
			} else 
				return false;
		} else 
			$ret['action-class'] = Helper::uri2Camel($ret['action']) . 'Action';

		// Controllo che i parametri siano solo campi di tabelle o dichiarati all'interno di una action
		// oppure ci sia una key con la richiesta di una risorsa o azione permessa per una richiesta con key
		// L'accesso con key puo' avere tutti i permessi di una richiesta normale (con sessione) se l'utente
		// loggato ha certe caratteristiche definite in Server::keyHasStandardPermits()
		$fields = array_keys($params);
		if (isset($ret['key']) && !Factory::createServer()->keyHasStandardPermits()) {
		    $keyPermits = unserialize(KEY_PERMITS);
			if (
					!(isset($ret['collection']) && isset($keyPermits['resources'][$ret['collection']])) &&
					!(isset($ret['action-class']) && isset($keyPermits['actions'][$ret['action']])) 
			) {
				return false;
			}
		}

		foreach ($fields as $field) {
			$tableRestrict = isset($ret['collection']) ? $server->getTable($ret['collection']) : null;
			$actionClass = isset($ret['collection']) ? null : Helper::uri2Camel($ret['action']) . 'Action';
			$collectionClass = isset($ret['collection']) ? Helper::uri2Camel($ret['collection']) : null;
			$actionEntityClass = isset($ret['action-entity-class']) ? $ret['action-entity-class'] : null;
			
			$fieldInClass = self::validStaticParams($field, $actionClass, $collectionClass, $actionEntityClass);

			//if (!$fieldInClass && (isset($ret['collection']) && !($server->fieldExists($field, $tableRestrict) || $server->fieldExists($field, $ret['collection']))))
			if (!$fieldInClass && (!isset($ret['collection']) || isset($ret['collection']) && !($server->fieldExists($field, $tableRestrict) || $server->fieldExists($field, $ret['collection']))))				
				return false;
		}

		$ret['filter'] = empty($params) ? null : $params;
		
		return $ret;
	}
	
	/**
	 * Il nome dell'azione deve corrispondere a un nome di classe che finisce con Action e ha come const METHOD corrispondente
	 * al metodo richiesto oppure il nome e' 'resource' che indica che e' chiesta una risorsa 
	 * @param string $action Il nome dell'azione da testare
	 * @param integer $nUriParts Il numero di parti della uri
	 * @param  string $method
	 * @return boolean True se e' valida
	 */
	static private function validAction($action, $nUriParts, $method) {
		$className = Helper::uri2Camel($action) . 'Action';
		return class_exists($className) && $nUriParts == self::ACTION_URI_PART && strtoupper($className::METHOD) == $method || $action == self::RESOURCE_NAME && $nUriParts > 1;
	}
	
	/**
	 * Verifica se una collezione e' valida.
	 * Lo e' se il nome e' tra le tabelle del db o esiste una classe con il nome dell'entita corrispondente
	 * @param string $collection 
	 * @param Server $server
	 * @return boolean True se e' valida
	 */
	static private function validCollection($collection, Server $server) {

		// La collezione deve essere un nome al plurale
		if (substr($collection, -1) != 's')
			return null;
		$className = Helper::uri2Camel(rtrim($collection, 's'));
		$table = $server->getTable($collection);
		//return $server->tableExists($table) || isset($vRes[$collection]) ? true : null;
		return $server->tableExists($table) || $server->tableExists($collection) || class_exists($className) ? true : null;
	}
	
	/**
	 * Verifica se una azione su una entita' e' valida
	 * Lo e' se esiste una classe con nome NomeAzioneNomeEntitaAction
	 * dove NomeEntita e' il nome della collezione al singolare e in camel case
	 * @param unknown $entityAction
	 */
	static private function validEntityAction($entityAction, $collection) {
		$className = Helper::uri2Camel($entityAction) . Helper::uri2Camel(rtrim($collection, 's')) . 'Action';
		return class_exists($className);
	}
	
	/**
	 * Verifica se il termine dato e' dichiarato nella costante PARAMS all'interno dell'action, 
	 * action-entity o entity
	 */
	static private function validStaticParams($param, $action, $collection, $actionEntity) {
		
		$searchParam = function($class, $param) {
			$ps = array_map('trim', explode(',', $class::PARAMS));
			return array_search($param, $ps) !== false;
		};
		
		if (!empty($action) && defined("$action::PARAMS") && $searchParam($action, $param))
			return true;
		if (!empty($collection) && defined("$collection::PARAMS") && $searchParam($collection, $param)) {
			return true;
		}
		
		//Utility::debug($param);
		//Utility::debug($actionEntity::PARAMS);
		//Utility::debug(strpos(' token EC-9YT89449E07321819', 'token'));
			
		if (!empty($actionEntity) && defined("$actionEntity::PARAMS") && $searchParam($actionEntity, $param))
			return true;
	}
	
	/**
	 * Ritorna la collezione richiesta e gli id con cui selezionare i record
	 * Se dato (combinando le parti):
	 * collection1/id1/collection2/id2 (uri originale da cui sono state estratte le parti)
	 * restituisce
	 * collection: collection2
	 * id: id2
	 * params: array('collection1_id => id1)
	 * Se dato:
	 * collection1/id1/collection2 (uri originale da cui sono state estratte le parti)
	 * restituisce
	 * collection: collection2
	 * id: non settato
	 * params: array('collection1_id => id1)
	 * @param array $parts Le parti dell'uri
	 * @return array
	 */
	static private function parseCollections($parts) {
		if (empty($parts))
			return null;
		$ret = array();
		$lenght = count($parts);
	
		// controllo se finisce con un id. E' cosi' se le parti sono pari
		// Se sono dispari l'ultima e' la collezione che ci interessa
		if (($lenght % 2) == 0) {
			if (Helper::isId($parts[$lenght]))
				$ret['id'] = (int)$parts[$lenght];
			else
				return null;
			$ret['collection'] = $parts[$lenght - 1];
		} else 
			$ret['collection'] = $parts[$lenght];
		
		// Scorro dall'inizio fino ad arrivare all'ultima collezione
		// Se la ritrovo prima o non e' rispettata la sequenza collezione/id/collezione esco con null
		$n = 0;
		$field = null;
		foreach ($parts as $part) {
			$n++;
			// Se trovo la collezione prima della fine l'uri non e' valida
			// deve essere nell'ultima o penultima posizione per essere valida
			if ($part == $ret['collection']) {
				if ($n >= $lenght - 1)
					break;
				else 
					return null;
			}
			// Il primo tratto e' una collection, poi un id e cosi' via
			// se non e' cosi' ritorno null
			if (($n % 2) != 0) {
				$field = substr_replace($part, '', -1).'_id';
			} else {
				if (!Helper::isId($part))
					return null;
				$ret['params'][$field] = $part;
			}
			
		}
		return $ret;
	}

	
	
	
	
	/**
	 * Restituisce l'url in cui sta girando lo script
	 */
	static function getMyUrl() {
	
	    // TODO In attesa di fix
	    if (constant('DEV_ENV')) {
	        return 'http://localhost:82/';
	    } else {
	        return 'https://myhost.com/';
	    }
	    
	    
		$path = explode(SERVER_PATH, $_SERVER['REQUEST_URI']);
	
		$http = isset($_SERVER['HTTPS']) ? 'https' : 'http';
	
		// Occorre settare la direttiva UseCanonicalName On in virtual host in apache
		// per avere il nome configurato nel server e non quello ricevuto nella richiesta
		// L'ho fatto sui server di produzione e test
		// http://php.net/manual/en/reserved.variables.server.php - Tonin
		$url = "$http://{$_SERVER['SERVER_NAME']}{$path[0]}";
	
		/*
			// testo comunque se e' davvero l'url di questo script controllando che ci sia un file da me definito
			// e se non c'e' ritorno null
			$serverPath = SERVER_PATH;
			$testFile = "{$url}{$serverPath}6cce38c40c9a887ddf6645de2312232f";
			$test = @file_get_contents($testFile);
			if ($test === false || $test != 'b6a5f8ed156d3000a6aa441ffd58134f')
				return null;
				*/
	
			return $url;
	}
	
	
}



?>