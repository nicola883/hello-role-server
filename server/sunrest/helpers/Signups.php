<?php


/**
 * Metodi per la registrazione dell'utente
 */
class Signups {

	/**
	 * Registra un nuovo cliente.
	 * Esegue tutte le azioni necessari corrispondenti al passo indicato nella url
	 * salvando gli eventuali dati ricevuti in post o update
	 * Aggiorna il passo della registrazione a quello appena completato.
	 * Al passo 1 fa il primo login
	 * @param $step Il passo della registrazione
	 * @return il return dell'ultima azione del passo
	 */
	public function signup($step, $data) {
		
		$server = Factory::createServer();
		
		// Sovrascrivo il tipo utente, per evitare che qualcuno provi a registrarsi come ammnistratore
		if (isset($data['type']))
			$data['type'] = CUSTOMER_TYPE_ID;
			
			// non serve a user-data che fa il login lui
			if ($step !== 'user-data') {
				$user = $server->getCurrentUser(true);
				// non dovrebbe capitare, ma se dovesse arrivare una richiesta
				// che non sia user-data e non sono loggato, deve uscire
				if (!isset($user['id'])) {
					$server->setHeader(METHOD_NOT_ALLOWED);
					return null;
				}
				$userId = $user['id'];
			}
			
			switch ($step) {
				case 'user-data':

					// faccio il logout dell'eventuale utente connesso, altrimenti rischio di registrare tutto il seguito
					// a un altro se questa chiamata ha un errore e sono loggato gia' con un altro utente
					// TODO fai il logout sempre quando qualcosa va storto da qualche parte
					$server->logout();
					
					$data['type'] = CUSTOMER_TYPE_ID;
					$resultId = $server->setUserData($data); // verifica se c'e' l'utente e setta se puo'
					/**
					 * Hook: record utente creato
					 */
					// Il plugin wl2 lo intercetta e fa le cose necessarie alla wl
					Monitor::get()->update('user_record_created', $server, array('user_id' => $resultId));
					
					if (!empty($resultId)) {
						// situazione normale: l'utente e' nuovo ed e' stato inserito
						$server->setHeader(CREATED);
						// Fai il login con i dati nel post e ritorno i dati dell'utente
						$u = $server->login($data);
						/**
						 * Hook: utente creato e loggato
						 */
						Monitor::get()->update('user_created', $server, array('user_id' => json_decode($u, true)['id']));
						
						// Ritorna i dati dell'utente appena loggato
						$us = json_decode($u, true);
						// Cancello il next_step perche' non avendo ancora salvato lo step attuale 
						// quando ho fatto il login questo non e' aggiornato
						unset($us['next_step']);
						$ret = array('user' => $us, 'next_step' => $server->nextStep($step));

						// registro il passo di registrazione completato
						$server->setSignupStep($step, $resultId);
						// Hook
						// Per aggiornare il CRM mettendo il valore nella coda. Da completare
						//$element = $ret['user'];
						//$element['registration_step'] = 'user-data';
						//$element['leadstatus'] = 'user-data';
						//Monitor::get()->update('signup_user_data', $server, $element);
						break;
					} else if ($resultId === false) {
						// non e' stato inserito perche' c'e' gia': il passo sara' stato completato in precedenza, allora non
						// scrivo di nuovo che e' stato completato. Invio un messaggio invitando a loggarsi. Non setto l'header created
						$ret = array('info' => 'please_login', 'next_step' => $server->nextStep($step));
						break;
					} else {
						// null
						// Userid presente ma password diversa: non faccio il login
						$ret = array('info' => 'userid_used', 'next_step' => $server->nextStep($step));
						break;
					}
					
				case 'account-data':
					// controllo se le credenziali sono valide accedendo al portale
					$gse = Factory::createRobot(); // Il Robot
					$userGse = $gse->loginOk($data['userid'], $data['password']);
					if ($userGse === false) {
						$ret = array('info' => 'not_valid', 'next_step' => 'account-data');
						// Hook
						$element = $user;
						$element['registration_step'] = 'account-data-credentials-error';
						Monitor::get()->update('signup_account_data', $server, $element);
						break;
					}
					// registro i dati del gse (il metodo setta anche l'header)
					$id = $this->setAccountData($userGse);
					$ret = array('account' => array('id' => $id), 'next_step' => $server->nextStep($step));
					$server->setSignupStep($step);
					// Hook
					$element = $user;
					$element['registration_step'] = 'account-data';
					Monitor::get()->update('signup_account_data', $server, $element);
					// Inserite le credenziali GSE valide
					Monitor::get()->update('signup_gse_data', $server, array('user' => $user, 'account_data' => $data));
					
					// Carico gli impianti
					$accountToLoad = $data;
					$accountToLoad['id'] = $id;
					(new LoadGseAction($server))->exec(array('signup' => SIGNUP_SUBAGENT), $accountToLoad);
					
					break;
					
				case 'user-more-data':
					$user = $server->getCurrentUser(true);
					$userId = $user['id'];
					$server->update('users', $data, $userId, true);
					$server->setHeader(OK);
					$ret = array('next_step' => $server->nextStep($step));
					$server->setSignupStep($step);
					// TODO per ora finisce qui e allora setto attivo l'utente qui
					// andrebbe testato a ogni passo se sono alla fine e, se lo sono, faccio quello
					// che va fatto alla fine
					$server->setUserActive();
					// Hook
					$element = $server->getCurrentUser(true, true);
					$element['registration_step'] = 'user-more-data';
					$element['leadstatus'] = 'user-more-data';
					Monitor::get()->update('signup_user_more_data', $server, $element);
					break;
				case 'user-profile':
					// TODO Per ora disabilitato
					$user = $server->getCurrentUser(true, true);
					$userId = $user['id'];
					$server->update('users', $data, $userId, true);
					$server->setHeader(OK);
					$ret = array('next_step' => $server->nextStep($step));
					$server->setSignupStep($step);
					// ho finito la registrazione: setto l'utente come attivo
					$server->setUserActive();
					// Se il cliente non ha settato la proprieta' profile nel post, il profilo di default dell'utente e' 1.
					break;
				default:
					$server->setHeader(NOT_FOUND);
					return null;
			}
			$ret['last_step'] = $step;
			return json_encode($ret);
	}
	
	/**
	 * Completa la registrazione dell'utente loggato inserendo le credenziali dell'account da cui
	 * verranno estratti i dati (es. GSE)
	 * @param array $gseUser Un array associativo array('gse_user_id' => idUtenteGse base 64, 'gse_user_name' => stringa nome utemte)
	 */
	public function setAccountData($gseUser, $data=null, $user=null) {
		
		$server = Factory::createServer();
		
		// se inserisco l'account del servizio questo e'
		// sempre associato a un utente. Lo scrivo e poi scrivo il suo id
		// e quello dell'utente sulla tabella di relazione n - n
		// l'account gse potrebbe già esserci perché utilizzato da un altro utente 
		if (empty($user))
			$user = $server->getCurrentUser(true);
			$userId = $user['id'];
			if (empty($data))
				$data = $server->getPostData(true);
			// se l'account c'e' l'unica cosa che devo fare e' creare la relazione con il nuovo utente
			// accetto che un account gse possa essere usato da piu' utenti. Il concetto post del rest rimane salvo
			// perche' sto comunque creando un nuovo account per l'utente dato.
			// l'account c'e' se corrisponde servizio e userid
			$query = "select id from accounts a where a.gse_id_utente = :gseidutente and a.gse_id_utente is not null and deleted = false";
			$exists = $server->dbQuery2($query, array(':gseidutente' => $gseUser['gse_user_id']));
			
			// se la query ha dato errore ritorno
			if ($exists === false)
				return false;
			// creo l'account se non esiste
			if (is_null($exists)) {
				$data['gse_id_utente'] = $gseUser['gse_user_id'];
				$data['gse_user_name'] = $gseUser['gse_user_name'];
				$accountId = $server->create('accounts', $data, true);
			} else {
				$accountId = $exists[0]['id'];
				// Aggiorno campi che potrebbero essere cambiati
				$server->update('accounts', array('gse_user_name' => $gseUser['gse_user_name'], 'password' => $data['password']), $accountId, true);
			}
			
			// setto la relazione: setto cosi un nuovo account dell'utente. Se esiste non lo faccio
			$query = "SELECT user_id FROM users_accounts WHERE user_id = :userid AND account_id = :accountid";
			$exists = $server->dbQuery2($query, array(':userid' => $userId, ':accountid' => $accountId));
			
			if ($exists === false)
				return false;
			if (is_null($exists)) {
				$server->create('users_accounts', array('user_id' => $userId, 'account_id' => $accountId), true);
				$server->setHeader(CREATED);
			} else
				$server->setHeader(OK);
				
				
			// ritorno l'id dell'accounts
			return $accountId;
	}
	
	
	/**
	 * True se l'utente e' uno di una white label nuova versione
	 */
	public function isWl($userId) {
		$db = Factory::createDb();
		$wl = $db->dbQuery2("select wl from users where id = :id and deleted = false", array(':id' => $userId));
		return empty($wl[0]['wl']) ? false : true; 
	}
	

}


?>