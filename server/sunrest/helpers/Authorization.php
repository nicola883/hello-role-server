<?php

/**
 * Verifica se e' permesso l'accesso alla risorsa richiesta dall'utente
 * @author Nicola Raffaele Di Matteo
 *
 */
class Authorization
{
	
	/**
	 * Verifica se l'utente connesso e' autorizzato a proseguire in funzione
	 * dell'uri richiesta
	 * TODO RESTITUISCE TRUE SE LOGGATO O SE NON LOGGATO E CHIEDE LOGIN. PER ORA NON FA ALTRO. VEDI TODO DENTRO
	 * @param Server $server
	 * @param array $input L'uri parsata con Security::parseUri
	 */
	static public function isAuthorized($server, $input) {
		$uri = '/' . $input['uri'];
		
		$method = $input['method'];
		
		// Prendo il gruppo a cui appartiene l'utente
		// Per ora si hanno i gruppi determinati solo da users.profile
		// users.customer_group_id, e dalla presenza di ordini (anche non attivi)
		include(__DIR__ . '/../../app/auth/groupsApp.php');

		if (false === $groupName = $getAppGroup($server))
			return false;

		// TODO Per adesso controllo solo che sia loggato, che faccia il login o che si voglia registrare
		if (($groupName === null && ($uri == '/login' || $uri == '/signup?step=user-data')))
			return true;
		else if ($groupName !== null) {
		    // Se e' una chiave controllo a cosa puo' accedere. Se e' una chiave con un utente loggato amministratore
		    // non limito l'accesso a solo quello definito per gli accessi con chiave
		    if (isset($input['key']) && !Factory::createServer()->keyHasStandardPermits()) {
				if ($input['action'] != 'resource') {
					return isset(unserialize(KEY_PERMITS)['actions'][$input['action']]);
				}
				$keyMethods = array_map('strtolower', unserialize(KEY_PERMITS)['resources'][$input['collection']]);
				return in_array(strtolower($method), $keyMethods, true);

			}

			if ($input['action'] != 'resource')
				return isset(unserialize(ACTION_PERMITS)[$input['action']]);
			if ($method == 'PUT' && isset($input['id']))
				return $server->getUpdatePermit($input['collection'], $input['id']);
			if ($method == 'POST')
				return $server->getInsertPermit($input['collection']);

			return true;
		}

		return false;
		
		// TODO per adesso continuo come con la vecchia versione			
		
			
			
			
		return false;
		
		
		/// Implementare ruoli
		
		// Prendo i valori dei parametri per la definizione completa della location
		// ad esempio gli id degli impianti sottoscritti
		$params = $getAppParams($server);
		
		// Leggo il ruolo dell'utente
		if ($groupName === null)
			$roleName = 'anonymous';
		else if (!$roleName = self::getGroupRole($groupName, $uri, $params))
			return false;
			
		
		//Utility::debug($methods = self::getRoleMethods($roleName, $uri));
		
		// Leggo i metodi permessi dal ruolo
		if (!$methods = self::getRoleMethods($roleName, $uri))
			return false;
		
		// Controllo che il metodo della richiesta sia tra quelli permessi dal ruolo
		if (empty(preg_grep( "/$method\b/i" , $methods)))
			return false;
		
		// Se sono qui ho passato i test e do' il permesso
		return true;
		//return (empty($connected) && (($action == 'login' && $method = 'POST') || ($action == 'signup' && $method = 'POST'))) || !empty($connected);
	}

	
	/**
	 * Restituisce il ruolo per il gruppo dato, leggendolo da groups.php
	 * Il ruolo e' definito ricorsivamente nel file.
	 * Se nella location /name/ il gruppo ha ruolo member
	 * e in /name/name2 ha ruolo none allora il ruolo nella
	 * location /name/name2 e' member e in /name/qualcosa e' member
	 * @param string $groupName Il nome univoco del gruppo 
	 * @param string $uri L'uri che indica la posizione, tipicamente quella richiesta dal client
	 * @param array $params Eventuali parametri da sostituire nella location. Ad esempio array('uid' => 1), per sostituire
	 * 	il parametro {uid} con 1 (l'id dell'utente) o anche array('plant_id' => array(1, 10, 25)). 
	 * @return string|boolean Il nome del ruolo o false se non e' stato fatto alcun match
	 */
	static public function getGroupRole($groupName, $uri, $params=null) {
		// L'elenco dei gruppi con i ruoli per ogni posizione
		include(__DIR__ . '/../../app/auth/groups.php');
		$config = $groups;
		$ret = self::getConfigMatch($config, $groupName, 'role', $uri, $params);
		return empty($ret) ? false : $ret;
	}
	
	static public function getRoleMethods($roleName, $uri, $params=null) {
		// L'elenco dei ruoli con i metodi per ogni posizione
		include(__DIR__ . '/../../app/auth/roles.php');
		$config = $roles;
		$ret = self::getConfigMatch($config, $roleName, 'methods', $uri, $params);
		return empty($ret) ? false : $ret;	
	}
	
	
	/**
	 * Legge il file di configurazione dato e restituisce il contenuto del campo
	 * dato corrispondente alla location che fa match con l'uri data
	 * @param json $config Un oggetto json che contiene oggetti con location
	 * @param string $definitionObjectName Il nome dell'oggetto che contiene le location con cui fare match
	 * @param string $fieldName Il nome del campo del ruolo (vedi file roles.php)
	 * @param string $uri L'uri con cui le location devono fare match
	 * @param array $params Eventuali parametri da sostituire nella location. Ad esempio array('uid' => 1), per sostituire
	 * 	il parametro {uid} con 1 (l'id dell'utente) o anche array('plant_id' => array(1, 10, 25))
	 * @return string|boolean Il nome del ruolo o false se non e' stato fatto alcun match
	 */
	static public function getConfigMatch($config, $definitionObjectName, $fieldName, $uri, $params=null) {
		
		// estraggo il config richiesto
		$config = Utility::jsonDecodeSafe($config);
		if ($config === null)
			return false;
		
		$assignements = $config[$definitionObjectName];
		
		// ordino per lunghezza dell'uri nel campo location
		$sortByLenght = function($a,$b) {
			return strlen($b['location'])-strlen($a['location']);
		};
		usort($assignements,$sortByLenght);

		// Il ruolo e' definito per ogni location e sue sotto location
		// e definizioni piu' annidate sovrascrivono quella del parent
		// Cerco allora un match partendo dall'uri piu' lunga
		foreach ($assignements as $ass) {
			$location = $ass['location'];
			
			// Quoto perche' la usero' come regesp
			$location = preg_quote($location, '/');
			
			// Sostituisco l'* con \d che rappresenta nella reg exp qualunque numero intero
			$location = str_replace('*', 'd+', $location);
			
			// Sostituisco eventuali parametri. Se i valori sono un array
			// li sostituisco e per ognuno controllo se fa match con l'uri
			$p1 = strpos($location, '{');
			$p2 = strpos($location, '}');
			$areParams = $p1 !== false && $p2 !== false && $p1 < $p2 ? true : false;
			if (!empty($params) && $areParams) {
				foreach ($params as $key => $value) {
					$values = $value;
					if (!is_array($value)) {
						$values = array();
						$values[] = $value;
					}
					foreach ($values as $replace) {
						$search = '\{' . $key . '\}';
						$location = str_replace($search, $replace, $location);
						
						//if ($fieldName == 'methods')
						//	Utility::debug($newLocation);
						
						if (self::matchUri($location, $uri))
							return $ass[$fieldName];
					}
				}
			}
			
			if (self::matchUri($location, $uri))
				return $ass[$fieldName];
		}
		
		return false;
	}
	
	
	/**
	 * Verifica se $str e' contenuto in $uri
	 * @param string $str
	 * @param string $uri
	 * @return boolean True se $str e' incluso in $uri, false diversamente
	 */
	private static function matchUri($str, $uri) {
		// Preparo l'espressione regolare che dovra' cercare di fare match con l'uri
		// Cerco tutti i numeri interi e li delimito con \b che significa che devono fare match
		// compresa la fine della parola, cioe' 10 non fa match con 100. Aggiungo anche \b alla fine per fare in modo
		// che report non faccia match con reporti
		$str = preg_replace('/(\b[0-9]+\b)/', '\b${1}\b', $str);
		
		// vedo se c'e' una location per l'uri data
		// C'e' se la location e' inclusa nell'uri
		return preg_match("/^$str\b/", $uri) == 1 ? true : false;		
	} 
	
}



?>