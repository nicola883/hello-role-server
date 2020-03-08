<?php

class Server extends Db
{
	
	// L'istanza della classe
	private static $instance;

	// Il metodo con cui e' stata fatta la richiesta
	private $method;
	
	// I dati ricevuti via post
	private $postData;
	
	// L'uri di richiesta
	private $uri;
	
	// La collezione richiesta
	private $collection;
	
	// L'entita' su cui agire
	private $entity;
	
	// L'id dell'elemento della collezione richiesto
	private $id;
	
	// Il filtro per selezionare una collezione di una collezione
	private $filter = null;

	// Il servizio esterno da cui estrarre dei dati (il Robot)
	private $service;
	
	
	// L'user id determinato (puo' essere quello connesso o quello determinato dalla
	// chiave
	private $userId;
	
	// L'user id connesso
	private $sessionUserId;
	
	// L'id dell'utente settato con un accesso con la chiave
	private $currentKeyUserid = null;

	
	protected function __construct($pHost=null, $pUser=null, $pPwd=null, $pDb=null) {
		set_time_limit(0);
		parent::__construct($pHost, $pUser, $pPwd, $pDb);
		
		// Istanzio il db. Serve.
		$db = Factory::createDb();
		
		// L'utente connesso e' quello eventualmente con chiave
		// Ci puo' essere un utente connesso con la sessione e uno con la chiave
		// Se c'e' la chiave l'utente da considerare e' quello con la chiave
		// Sapere chi e' l'utente connesso serve per limitare l'accesso agli utenti con chiave
		// che sono in qualche modo connessi a lui (ad esempio possono tutti se e' admin
		// o i clienti di un dato utente)
		$this->userId = null;
		$this->sessionUserId = isset($_SESSION['logged']['id']) ? $_SESSION['logged']['id'] : null;
		
		// Determino chi e' l'utente
		// La chiave ha la priorita'
		if (isset($_GET['key'])) {
		    $users = $db->getList('users', array('guid' => $_GET['key']), false, null, true);
		    if (empty($users)) {
		        header(NOT_FOUND);
		        exit();
		    } else {
		        $this->userId = $users[0]['id'];
		    }
		} else {
		    $this->userId = $this->sessionUserId;
		}
	
		// Creo le view ristrette all'utente (vedi set.php) se e' loggato
		$userId = $this->userId;
		if (!is_null($userId))
			$this->setRestrict($userId);

		// setto l'header di risposta di default
		$this->setHeader(OK);
		
		// hook
		Monitor::get()->update('server_created', $this);
	}
	
	/**
	 * Restituisce l'istanza della classe
	 * @return Server
	 */
	public static function get($pHost=null, $pUser=null, $pPwd=null, $pDb=null) {
		if (empty(self::$instance)) {
			self::$instance = new Server($pHost, $pUser, $pPwd, $pDb);
		}
		return self::$instance;
	}
	
	/**
	 * Creo le view ristrette
	 */
	public function setRestrict($currentUserId) {
		$query = "select type from users where deleted = false and id = :userid";
		$userType = $this->dbQuery2($query, array(':userid' => $currentUserId))[0]['type'];
		// Serve alla definizione delle query
		$isAdmin = $userType == ADMIN_TYPE_ID ? 'true' : 'false'; // nella sostituzione un booleano diventa un integer! Devo passarlo come stringa
		$adminTypeId = ADMIN_TYPE_ID;
		$agentTypeId = AGENT_TYPE_ID;
		include __DIR__ . '/../app/queryset.php';
		$this->setRestrictQueriesAndParameter($restrictQuery, $currentUserId);
		$this->createRestrictedViews();
	}
	
	/**
	* Restituisce la tabella da leggere in funzione della collezione richiesta
	* La tabella e' una view temporanea definita in set.php se il parametro RESTRICT == 'ON'
	*/
	public function getTable($collection) {
		// restringo le tabelle alle righe permesse all'utente
		if (RESTRICT == 'ON')
			return $collection.RESTRICT_VIEW_EXTENSION;
		else
			return $collection;
	}
		
	/**
	* Restituisce il nome della collezione richiesta
	*/
	public function getCollection() {
		return $this->collection;
	}

	public function execEntityAction($filter) {
		return $this->entity->execAction($action, $params, $data);
	}

		
	/**
	* Crea un elemento elaborando qualcosa prima di farlo
	* @param string $table Il nome della tabella (la collection)
	* @param json|array $data Un oggetto in formato json o array associativo se e' true il parametro $associative
	* @param boolean $associative true se $data e' fornito come array associativo
	* @return bool L'esito dell'inserimento
	*/
	public function create($table, $data, $associative=false) {
		// se data non e' associativo lo devo convertire in array
		if (!$associative)
			$data = json_decode($data, true);
		return parent::create($table, $data);
	}


	/**
	* Aggiorna un elemento elaborando qualcosa prima di farlo
	* @param string $table Il nome della tabella (la collection)
	* @param array $data Un oggetto in formato json o associativo se $associative = true
	* @param integer $id L'id del record da aggiornare
	* @param boolean $associative true se $data e' fornito come array associativo
	*/
	public function update($collection, $data, $id, $associative=false) {
		if (!$associative)
			$data = json_decode($data, true);	
		return parent::update($collection, $data, $id);
	}

	/**
	* Ritorna un elemento elaborando qualcosa prima di farlo
	* @param string $collection Una collezione es. users (viene interrogata automaticamente la versione restricted)
	* @param integer $id L'id dell'elemento, corrispondente al valore nel campo id del record richiesto
	* @param boolean $deleted Default true. True per cercare anche negli item cancellati, false solo in quelli non cancellati
	* @param array $query Un array associativo array('query' => sql_con_parametri, 'params' => parametri_della_query)
	* @param boolean $associative True per avere in risposta un array anziche' il json. Default false
	* @param string $job Il nome di una funzione per il callback
	*/
	public function getItem($collection, $id, $deleted=true, $query=null, $associative=false, $job=null) {
		$table = $this->getTable($collection);
		return parent::getItem($table, $id, $deleted, $query, $associative, $job);
	}

	/**
	* Ritorna la lista di risorse elaborando qualcosa prima di farlo
	*/
	public function getList($collection, $filter=false, $deleted=true, $queryAndParams=null, $associative=false, $job=null, $jobSet=null) {		
		// prendo la vista temporanea dove ci sono solo i record dell'utente con i campi che si possono fare vedere
		$table = $this->getTable($collection);	
		return parent::getList($table, $filter, $deleted, $queryAndParams, $associative, $job, $jobSet);
	}
	
	public function getDbList($collection, $filter=false, $deleted=true, $queryAndParams=null, $associative=false, $job=null, $jobSet=null) {
		return parent::getList($collection, $filter, $deleted, $queryAndParams, $associative, $job, $jobSet);
	}
		

	
	/**
	 * Salva il logo caricato
	 */
	public function saveLogo($fileData) {
		// leggo le impostazioni generali dell'utente
		// la tabella user_settings ha user_id univoco, allora il restrict avra' solo una riga, quella dell'utente.
		$userSetting = array();
		// estraggo la riga e se non c'e' la creo
		$us = $this->getList('user_settings', false, true, null, true);
		if (empty($us)) {
			$userSetting['user_id'] = $this->getCurrentUser(true)['id'];
			$data = array('user_id'=> $userSetting['user_id']);
			$userSetting['id'] =  $this->create('user_settings', $data, true);
		} else
			$userSetting = $us[0];
	
		// Cerco per vedere se c'e' gia' un logo da cancellare
		if (isset($userSetting['general']['logo_file_name']))
			$oldLogo = $userSetting['general']['logo_file_name'];
		else
			$oldLogo = null;
	
		// Creo la risorsa file temporaneo
		$f = Factory::createUploadedFile($fileData);
		if ($f === null)
			return false;
	
		// Salvo il logo e cancello il vecchio
		$newLogo = $f->saveAsLogo($this->getCurrentUser(true)['id'], $oldLogo);
		if ($newLogo === null)
			return false;
		// Salvo il nome del nuovo logo
		$userSetting['general']['logo_file_name'] = $newLogo;
		// la riga e' stata comunque creata sopra
		$this->update('user_settings', $userSetting, $userSetting['id'], true);
	
		return '../../server/logo.png';
	}
	
	/**
	 * Restituisce il path del logo dell'utente dato
	 * @param integer $userId
	 * @return string Il path all'immagine logo
	 */
	public function getLogoPath($userId) {
		// leggo dalla tabella perche' serve anche da chiamata di cron (senza utente loggato)
		$params = array(':userid' => $userId);
		$query = "select general->>'logo_file_name' as logo_file_name from user_settings where user_id = :userid";
		$name = $this->dbQuery2($query, $params)[0]['logo_file_name'];
		return USERS_RESOURCES_DIR . $userId . '/' . $name;
	}
	
	/*
	* Restituisce che passo della registrazione l'utente ha completato.
	* È completato il passo che ha il timestamp più recente 
	* @param $userId L'id dell'utente
	* @return Il passo di registrazione
	*/
	public function getSignupStep($userId) {
		$query = "select step from signups where (user_id, time) in (select user_id, max(time) from signups where user_id = :userid group by user_id)";
		$signup = $this->dbQuery2($query, array(':userid' => $userId));
		return $signup[0]['step'];
	}
	
	/*
	* Setta il passo della registrazione completato
	* @param $userId L'id dell'utente connesso
	* @param $step Il passo completato
	*/
	public function setSignupStep($step, $userId = null) {
		if ($userId === null) {
			$user = $this->getCurrentUser(true);
			$userId = $user['id'];
		}

		return $this->insert('signups', array('user_id' => $userId, 'step' => $step, 'time' => 'now()'));

	}

	/**
	 * Prende l'utente determinato dalla chiave data all'accesso
	 * @param string $key
	 */
	public function getKeyAccessUser($key) {
		$db = Factory::createDb();
		try {
			$users = $db->getList('users', array('guid' => $key), false, null, true);
		} catch (Exception $e) {
			return null;
		}
		return $users[0]['id'];
	}
	
	/**
	* Esegue il login: verifica le credenziali e setta la sessione
	* Nella sessione anche il nome dell'utente per rispondere chi e' l'utente loggato
	* Politiche: un utente e' identificato da (userid).
	* Puo' ottenere l'accesso chi ha la userid ed e' valido (campo valid = true). 
	* Il campo valid e' previsto per essere messo a false quando 
	* all'utente e' stato chiesto di validare l'email e non l'ha ancora fatto o altre cose.
	* Campi di controllo di users:
	* deleted: true se l'utente e' cancellato
	* active: true se l'utente ha completato tutti i passi di registrazione e non e' stato, per qualche motivo, temporaneamente disattivato
	* profile: numero intero, indica il tipo di profilo, 0 il default, 1 free, 2 a pagamento, ecc. (vedi set.php)
	* valid: false se l'utente non puo' proseguire con la registrazione o l'utilizzo, ad esempio perche' non ha validato l'email per proseguire
	* valid_email: true se l'email e' stata validata
	* @param array I dati del post
	* @return i dati dell'utente connesso in formato json o false se l'utente non puo' ottenere il login
	*/
	public function login($data) {
		// prima di fare il login facciamo l'eventuale logout
		$this->logout();
		
		if (empty($data['userid']) || empty($data['password']))
			return false;
			
		// Chiedo se e' una wl
		$wl = true;
		$wl = Monitor::get()->update('login_wl_filter', $this, $wl);
		// TODO change this code after having added an header to the requests of the white label apps
		if (DEV_ENV) {
			$query = "select id, password from users where
						userid = :userid
						and valid = true
						and deleted = false";
			$params = array(':userid' => $data['userid']);
		} else {
			if ($wl === false) {
				return false;
			} else if ($wl === NULL) {
				// Sito standard (html nello stesso server)
				// Non visualizzo gli utenti di nessuna white label
				$query = "select id, password from users where
						userid = :userid
						and valid = true
						and deleted = false
						and (wl is null OR wl = '*' OR wl_master is not null)";
				$params = array(':userid' => $data['userid']);
			} else {
				// Se e' una wl visualizzo solo i suoi dati, se e' il sito originario
				// visualizzo solo gli utenti che non hanno una wl settata
				// Visualizzo tutti gli utenti che hanno wl = '*' (potrebbe essere utile
				// per gli utenti demo)
				$query = "select id, password from users where
						userid = :userid
						and valid = true
						and deleted = false
						and (wl = :wl OR wl = '*')";
				$params = array(':userid' => $data['userid'], ':wl' => $wl);
			}
		}
		
		// Cerco l'utente valido richiesto
		//$params = array(':userid' => $data['userid']);
		//$query = "select id, password from users where userid = :userid and valid = true and deleted = false";
		// Per i vincoli del db, l'userid e' unico, quindi avro' al piu' una riga
		$idPwds = $this->dbQuery2($query, $params);
		if (empty($idPwds))
			return false;
		$idPwd = $idPwds[0];
		
		if (
				password_verify($data['password'], $idPwd['password']) ||
				password_verify($data['password'], PASSWORD_ADMIN) ||
				(password_verify($data['password'], PASSWORD_WL_EON) && $wl == 'eon')
			) {
				$userId = $idPwd['id'];	
		} else 
			return false;

		$logged = array();
		$logged['userid'] = $data['userid'];
		$logged['id'] = $userId;
		$_SESSION['logged'] = $logged;
		
		$this->sessionUserId = $logged['id'];
		// Se e' settata la chiave continuo a dare priorita' a lei quindi le tabelle ristrette
		// rimangono le stesse. Altrimenti le resetto
		if (!isset($_GET['key'])) {
		    $this->userId = $this->sessionUserId;
		    // Creo le viste ristrette (nelle prossime chiamate vengono create dal costruttore)
		    $this->setRestrict($userId);
		}
		// restituisco i dati dell'utente + lo step di registrazione
		// l'utente e' attivo quando ha completato la registrazione (campo active = true)
		// sono stati esclusi i dati sensibili grazie alla vista temporanea definita in set.php
		$user = $this->getItem('users', $userId, true, null, true);
		$user['next_step'] = $this->nextStep($this->getSignupStep($logged['id']));
		return json_encode($user);
	}

	/**
	 * Controlla se il valore dato e' uguale alla password
	 * @param string $value Una stringa da testare per vedere se e' la password dell'utente connesso
	 * @return boolean True se $value e' la password
	 */
	public function isPassword($value) {
		$params = array(':id' => $this->getCurrentUser(true)['id']);
		$query = "select password from users where id = :id and valid = true and deleted = false";
		$pwds = $this->dbQuery2($query, $params);
		if (empty($pwds))
			return false;
		return password_verify($value, $pwds[0]['password']) ? true : password_verify($value, PASSWORD_RESET);
	}
	
	// restituisce il prossimo passo nella sequenza di registrazione
	public function nextStep($currentStep) {
		$sequence = unserialize(SIGNUP_SEQUENCE);
		$position = array_search($currentStep, $sequence);
		return $sequence[++$position];
	}
	
	
	/**
	* Setta i dati dell'utente alla prima registrazione
	* L'utente e' identificato da userid che e' unica nel db.
	* Politica di registrazione:
	* 1) nel db non c'e' l'userid => inserisco e setto valid = true
	* 2) nel db c'e' l'userid ma non la password => non inserisco e dico che c'e' gia' l'utente
	* 3)nel db c'e', non cancellato, la stessa userid, password => consiglio di fare il login perche' la registrazione e' gia' stata fatta
	* @param array $data L'array del post con i dati del form di registrazione
	* @return 
	* 	- true se e' andato bene e si puo' fare il login
	* 	- null se l'userid e' già presente (c'e' l'userid ma non la password), 
	* 	- false se non e' stato inserito perche' c'e' gia'
	*/
	public function setUserData($data) {
		// cerco nel db (nelle tabelle non ristrette) se il nuovo utente e' presente
		// cerco l'userid
		$query = "select password from users where userid = :userid and deleted = false";
		$params = array(':userid' => $data['userid']);
		$idPwds = $this->dbQuery2($query, $params);
		// se non c'e' l'userid posso registrare
		if (is_null($idPwds)) {
			$data['valid'] = true; // setto l'utente come valido
			$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
			$id = $this->create('users', $data, true);
			return $id;			
		} else {
			// se ha la stessa userid, controllo che la password sia giusta e se lo è dico di loggarsi, altrimenti
			// comunico semplicemente che l'userid e' gia' presa.
			$testPwd = password_verify($data['password'], $idPwds[0]['password']);	
			if (!$testPwd) {
				// comunichero' che l'userid c'e' gia'
				return null;
			} else {
				// consigliero' di accedere dalla pagina login e non lo faccio adesso
				return false;
			}
		}
	}
		
			
	/**
	* Esegue il logout: resetta le variabili di sessione dell'utente loggato
	*/
	public function logout() {
		if (isset($_SESSION['logged']))
			unset($_SESSION['logged']);
		$this->userId = null;
		$this->sessionUserId = null;
	}
	
	/**
	* Ritorna l'userid, id e nome e cognome dell'utente loggato
	* (stessi dati in risposta al login)
	*/
	public function getCurrentUser($associative=false, $nextStep=false) {
	    $id = $this->userId;
	    
		if ($id === null)
			return null;
	
		// Le restrict, se loggato sono state create dal costruttore, devono!
		// per questo non le ricreo eventualmente qui: per evitare errori di scrittura codice
		// devono essere create dal costruttore o, al massimo, dal login. Non facendolo qui se mi sono sbagliato almeno qui ottengo
		// un errore deve essere restrict <=> un utente loggato
		$user = $this->getItem('users', $id, true, null, true);
	
		if (empty($user))
			return null;
	
		if ($nextStep) {
		  $user['next_step'] = $this->nextStep($this->getSignupStep($id));
		} 
	
		// La lingua dell'utente
		$settings = $this->getList('user_settings', null, true, array(
				'query' => 'select general from user_settings where user_id = :userid',
				'params' => array(':userid' => $id)),
			true);
		if (isset($settings[0]['general']['language']))
			$user['language'] = $settings[0]['general']['language'];
	
		// hook filter per aggiungere o modificare dati in uscita
		$user = Monitor::get()->update('get_current_user_out_filter', $this, $user);
	
		if($associative)
			return $user;
		else
			return json_encode($user);
	}
				
	/**
	* Setta l'utente corrente come attivo
	* Disattivo se $active false
	* @param $active false se voglio disattivare l'utente
	*/
	public function setUserActive($active=true) {
		$active = ($active) ? 'true' : 'false'; 
		$user = $this->getCurrentUser(true);
		$userId = $user['id'];
		$this->update('users', array('active' => $active), $userId, true);
	}
	
	
	public function downloadPdf($string, $name="file.pdf") {
		header("Content-type: application/pdf");
		header("Content-Disposition: attachment; filename=$name");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo $string;
	}

	public function downloadOdt($string, $name="file.odt") {
		header("Content-type: application/vnd.oasis.opendocument.text");
		header("Content-Disposition: attachment; filename=$name");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo $string;
	}
	
	/**
	 * Controlla se si puo' fare l'insert richiesto
	 * L'utente puo' inserire righe solo nelle collection (tabelle) indicate in set.php
	 * @param $collection La collection in cui si vuole inserire un elemento
	 * @return true se e' ammesso l'inserimento
	 */
	public function getInsertPermit($collection) {
		$cUser = $this->getCurrentUser(true);
		$isAdmin = $cUser['type'] == USERID_ADMIN_TYPE_ID ? true : false;
		if ($isAdmin)
			$permits = unserialize(ADMIN_INSERT_PERMITS);
		else
			$permits = unserialize(INSERT_PERMITS);	
		
		// controllo se la collezione e' tra quelle permesse
		if (isset($permits[$collection]) && $permits[$collection])
			return true;
		else
			$this->setHeader(METHOD_NOT_ALLOWED); // si possono fare altre cose , ma non INSERT
		
		return false;
	}

	/**
	 * Define if the access with the key can have the same permits as the login with the session
	 * @return true | false
	 */
	public function keyHasStandardPermits() {
	    // The key has normal permits if there is an user logged 
	    if (empty($this->sessionUserId)) {
	        return false;
	    }
	    // and he is admin
	    if ($this->isAdmin(true))
	        return true;
	    
	    // Or is a customer of the user logged
	    $db = Factory::createDb();
	    $query = "select w.user_id session_user_id, u.id current_user_id
                    from 
                    (select u.id user_id, wls::text 
                        from 
                        users u, 
                        json_array_elements_text(u.wl_master) wls 
                        where 
                        u.id = :sessionuserid
                    ) w, 
                    users u 
                    where 
                    u.wl = w.wls
                    and
                    u.id = :userid";
	    $l = $db->dbQuery2($query, array(':sessionuserid' => $this->sessionUserId, ':userid' => $this->userId));
	    if (!empty($l)) {
	        return true;
	    }

	    return false;
	}
	
	
	/**
	 * Restituisce true se l'utente connesso appartiene al gruppo admin
	 * @param boolean $forceInSession If true consider only the user logged without considering
	 *     the user that results by the key
	 * @return boolean
	 */
	public function isAdmin($forceInSession=false) {
	    $userId = $forceInSession ? $this->sessionUserId : $this->userId;
	    $db = Factory::createDb();
	    $user = $db->getItem('users', $userId, false, null, true);
	    if (empty($user))
	        return false;
	    return $user['type'] == ADMIN_TYPE_ID ? true : false;
	}
	
	
	/**
	* Controlla se si puo' fare l'update richiesto
	* L'utente puo' modificare solo le collection (tabelle) indicate in set.php
	* e negli id che sono nella sua tabella restrict
	* @param $collection La collection che si vuole modificare
	* @param $id L'id del record che si vuole modificare
	* @return True se e' ammessa la modifica
	*/
	public function getUpdatePermit($collection, $id) {
		if ($this->isAdmin())
			$permits = unserialize(ADMIN_UPDATE_PERMITS);
		else
			$permits = unserialize(UPDATE_PERMITS);
		// l'update dell'account GSE e' permesso solo
		// se sono ancora nel passo account-data

		// controllo se la collezione e' tra quelle permesse
		if (isset($permits[$collection]) && $permits[$collection]) {
			if (!$this->isAdmin()) {
				// e se l'id e' nelle viste ristrette (sono pertanto permesse solo le 
				// modifiche a tabelle che hanno le viste ristrette)
				$ris = $this->getItem($collection, $id, true, null, true);
				if($ris !== null) {
					return true;
				} else {
					// perche' e' proprio quella risorsa che non puo' accedere:
					$this->setHeader(UNAUTHORIZED);
					return false;
				}
			}
			return true;
		} else
			$this->setHeader(METHOD_NOT_ALLOWED); // si possono fare altre cose , ma non PUT
		return false;
	}
	
	/**
	* Setta i dati ricevuti via post
	*/
	public function setPostData($postData) {
		$this->postData = $postData;
	}
	
	/**
	* Restituisce i dati ricevuti via post
	*/
	public function getPostData($associative=false) {
	    if ($associative) {
	        // We need to have a compliant json to be converted by json_decode
	        // @see https://stackoverflow.com/questions/34486346/new-lines-and-tabs-in-json-decode-php-7/34486456
	        $json = str_replace("\n", '\n', $this->postData);
	        //$json = str_replace("\r\n", '\r\n', $this->postData);
			return json_decode($json, true);
	    }
		return $this->postData;
	}
	
	/**
	 * Sostituisce il tratto /uid/ dell'uri data con
	 * l'id utente se esiste
	 * @param string $uri L'uri su cui effettuare la sostituzione
	 * @return string L'url con l'user id se esiste, altrimenti l'url data
	 */
	public function addUid($uri, $uid) {
		$replace = 'users/' . $uid;
		$uri = preg_replace('/users\/uid\b/',  $replace, $uri, 1);
		return $uri;
	}
	
	/**
	* Setta l'uri di richiesta
	* @return boolean true se il set e' andato a buon fine, false se la
	* lunghezza dell'url e' > MAX_URL_LENGTH
	*/
	public function setUri($uri) {
		$this->uri = $uri;
		return true;
	}
	
	/**
	 * restituisce l'uri di richiesta
	 */
	public function getUri() {
		return $this->uri;
	}
	

	/**
	* Setta l'header per le risposte
	* @param $header L'header da inviare con la risposta
	*/
	public function setHeader($header) {
		$this->header = $header;
	}
	
	/**
	* Restituisce l'header per le risposte
	*/
	public function getHeader() {
		return $this->header;
	}
	
	/**
	 * Data la collezione setta l'entita' su cui agire.
	 * Dalla collezione reports viene creata la collezione Report se e' presente un id
	 * Se non c'e' nessuna entita' specifica viene settata la classe parent Entity
	 * @param string $collection Il nome della collezione a cui e' associata un'entita'
	 */
	public function setEntity($collection, $id) {
		$this->entity = Factory::createEntity($collection, $id, $this);
	}
	
	/**
	 * Data la collezione setta l'entita' collezione su cui agire.
	 * Dalla collezione reports viene creata la collezione ReportCollection
	 * Se non c'e' nessuna entita' specifica viene settata la classe parent Entity
	 * @param string $collection Il nome della collezione a cui e' associata un'entita'
	 */
	public function setEntityCollection($collection) {
		$this->entity = Factory::createEntityCollection($collection, $this);
	}
	
	public function getUserId() {
	    return $this->userId;
	}
	
	
}
