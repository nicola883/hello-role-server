<?php

class Db extends PdoDb
{
	
	// Il nome del database
	private static $dbName;
	
	// L'istanza della classe
	private static $instance;
	
	/**
	 * La chiave privata per decriptare i campi criptati
	 * @var string
	 */
	private $privateKey;
	
	/**
	 * Una eventuale frase segreta per aprire la chiave privata
	 */
	private $passphrase;
	
	/**
	 * Costruisce un db. I parametri null vengono sovrascritti da quelli contenuti
	 * nel file secret.php: di default viene costruito il db li' indicato
	 * @param unknown $pHost
	 * @param unknown $pUser
	 * @param unknown $pPwd
	 * @param unknown $pDb
	 */
	protected function __construct($pHost=null, $pUser=null, $pPwd=null, $pDb=null, $pPort=null) {
		include(__DIR__ . '/../secret.php');
		$host = $pHost === null ? $host : $pHost;
		$user = $pUser === null ? $user : $pUser;
		$password = $pPwd === null ? $password : $pPwd;
		$port = $pPort === null ? $port : $pPort;
		$db = $pDb === null ? $db : $pDb;
		parent::__construct($host, $user, $password, $db, $port);
		if (defined('SEARCH_PATH')) {
			$searchPath = SEARCH_PATH;
			$this->dbQuery2("SET search_path TO $searchPath", null);
		}
	}
	
	/**
	 * Restituisce l'istanza della classe
	 * @return Db
	 */
	public static function get($pHost=null, $pUser=null, $pPwd=null, $pDb=null) {
		if (empty(self::$instance) || ($pDb !== null && self::$dbName != $pDb)) {
			self::$instance = new Db($pHost, $pUser, $pPwd, $pDb);
			self::$dbName = $pDb;
		}
		return self::$instance;
	}

	/**
	* Crea un elemento
	* @param $table Il nome della tabella (la collection)
	* @param $data Un'array associativo nome_campo => valore
	* @return L'id della riga inserita
	*/
	public function create($table, $data) {
		return $this->insert($table, $data);
	}
	
	/**
	* Aggiorna un elemento
	* @param $collection Il nome della tabella (la collection)
	* @param $data Un'array associativo nome_campo => valore
	* @param integer $id L'id del record 
	*/
	public function update($collection, $data, $id) {
		$set = $this->array2set($data, $collection);
		$set['params'][':id'] = $id;
		$update = "UPDATE $collection SET {$set['query_set']} WHERE id = :id";
		$this->execQuery2($update, $set['params']);
	}
	
	/**
	 * Esegue l'insert o l'aggiornamento se il valore di un campo unicoa sarebbe duplicato
	 * Occore passare il campo da controllare. Se serve controllare piu' campi occorre creare
	 * un indice generale per farlo funzionare:
	 * create unique index unique_index_nome_campi_on_nome_tabella on tabella (campo1, campo2, ...);
	 * @see https://stackoverflow.com/questions/35888012/use-multiple-conflict-target-in-on-conflict-clause
	 * L'operazione e' atomica: l'id ritornato e' quello dell'inserimento vedi:
	 * http://stackoverflow.com/questions/19167349/postgresql-insert-from-select-returning-id
	 * http://www.postgresql.org/docs/9.1/static/sql-insert.html
	 * @param string $table il nome della tabella
	 * @param array $array Un array associativo con nome campo e valore
	 * @param array $fields Un array con i nomi dei campi da controllare come unici
	 */
	public function save($table, $array, $fields) {
		if (empty($table) || empty($array))
			return null;
		$fu = implode (", ", $fields);
		$data = $this->array2insert($array, $table);
		$set = $this->array2set($array, $table);
		$insert = "INSERT INTO $table {$data['fields']} VALUES {$data['paramsname']} 
				ON CONFLICT ($fu) DO UPDATE SET {$set['query_set']}
				RETURNING id";
		$fch = $this->dbQuery2($insert, $data['params']);
		return($fch[0]['id']);
	}
	
	
	/**
	* Cancella un elemento
	* @param $collection Il nome della colezione (tabella o vista temporanea)
	* @param $id L'id del record
	* @param $hard Se false (default) il delete e' il set a true del flag deleted, altrimenti 
	* e' un delete vero
	*/
	public function delete($collection, $id) {
		$this->dbQuery2("update $collection set deleted = true where id = :id", 
				array(':id' => $id));
	}
		
	/**
	* Ritorna un elemento
	* In json o come array associativo se $associative = true
	* @param string $collection Una collezione es. users (viene interrogata automaticamente la versione restricted)
	* @param integer $id L'id dell'elemento, corrispondente al valore nel campo id del record richiesto
	* @param boolean $deleted Default true. True per cercare anche negli item cancellati, false solo in quelli non cancellati
	* @param array $queryAndParams Un array associativo array('query' => sql_con_parametri, 'params' => parametri_della_query)
	* @param boolean $associative True per avere in risposta un array anziche' il json. Default false
	* @param string $job Il nome di una funzione per il callback
	* TODO testa cose come http://localhost/progetti/checkouts/host/server/resource/plants/1?operator_id=2
	*/
	public function getItem($collection, $id, $deleted=true, $queryAndParams=null, $associative=false, $job=null) {
		if ($queryAndParams === null) {
			$and = $deleted ? '' : " AND deleted = '0'";
			$params = array(':id' => $id);
			$query = "SELECT * FROM $collection WHERE id = :id".$and;
		} else {
			$params = $queryAndParams['params'];
			$query = $queryAndParams['query'];
		} 
		
		$tab = $this->dbQuery2($query, $params);
		if ($tab === null)
			return null;	
		$ris = $this->unserializeField($tab[0], $collection);	
		
		// se e' data una funzione, la risposta sara' il valore ritornato da questa
		if ($job !== null)
			$ris = $job($ris);	
		if ($associative) 
			return $ris;
		
		// TODO Non posso usare JSON_NUMERIC_CHECK perche' mi trasforma anche quello che non vorrei (es. numeri partita iva) e non 
		// ce n'e' bisogno: i numeri escono bene come numeri e le stringhe con le virgolette.
		// Piuttosto i valori numerici dovrebbero gia' entrare nel db come numeri cosa che non avviene (es. gli ancoli della superficie (inclinazione
		// e orientamento). Occorre lavorare nel connector o robot per farlo.
		return json_encode($ris, JSON_PRETTY_PRINT);
	}
	
	/**
	* Ritorna la lista degli elementi
	* @param string $collection Il nome della collezione da restituire collezione = nome tabella per nostra convenzione. NECESSARIO
	* per estrarre i campi serializzati
	* @param array $filter Un array con il nome del campo e valore con cui filtrare il risultato array('nome_campo' => 'valore)
	* @param boolean $deleted False per avere solo i record non cancellati, default True (restituisce tutti)
	* @param array $queryAndParams Una query e parametri per fare la richiesta. array('query' => $query, 'params' => $params)
	* @param boolean $associative Se true restituisce il risultato come array associativo anziche' in json. Default false
	* @param string $job Una callback da eseguire per ogni singolo risultato. Se settata la lista in uscita sara' la sequenza
	* @param undefined $jobSet Un parametro da passare a $job
	* di tutti i risultati della callback
	* TODO Fare nuova query e testa cose come
	* http://localhost/progetti/checkouts/host/server/resource/operators/1/plants?operator_id
	* @return Una lista di elementi della risorsa richiesta o il risultato della query data con $custom
	*/
	public function getList($collection, $filter=false, $deleted=true, $queryAndParams=null, $associative=false, $job=null, $jobSet=null) {
		
		$query = isset($queryAndParams['query']) ? $queryAndParams['query'] : null;
		$params = isset($queryAndParams['params']) ? $queryAndParams['params'] : null;
		
		// inserito multifiltri
		$post = '';
		$sendTotalRows = false;
		
		if (isset($filter['total_rows_number'])) {
			$sendTotalRows = true;
			unset($filter['total_rows_number']);
		}
		
		if (!isset($queryAndParams)) {
			// filtro su uno o piu' campi se esistono
			if ($filter) {
				$filtersWhere = '';
				$i = 0;
				foreach ($filter as $key => $value) {
					if (!$this->fieldExists($key, $collection))
						return null;
					$textAnd = ($i == 0) ? '' : 'AND';
					
					$keyNameValue = ':' . 'param' . $i;
					$params[$keyNameValue] = $value;
					
					$filtersWhere = "$filtersWhere $textAnd $key = $keyNameValue";
					$i++;
				}
			}
		
		
			if (!$deleted && $filter)
				$post = " WHERE deleted = false AND $filtersWhere";
			if (!$deleted && !$filter) 
				$post = " WHERE deleted = false";
			if ($deleted && $filter)
				$post = " WHERE $filtersWhere";
			// fine modifica
			
			if ($query === null && !$sendTotalRows) 
				$query = "SELECT * FROM $collection".$post;
			elseif ($query === null && $sendTotalRows) {
				$query = "SELECT count(id) total_rows_number FROM $collection".$post;
			}
		}
		
		$rows = $this->dbQuery2($query, $params);
		
		if ($rows === false)
			return null;
		
		$ris = array(); $i = 0;
		if ($rows !== null) {
			foreach ($rows as $riga) {
				// faccio creare il risultato alla funzione $job passata, che chiede in ingresso un 
				// array associativo con i dati della risorsa
				if ($job !== null) {
					$ris[$i++] = $job($this->unserializeField($riga, $collection), $jobSet);
				} else 
					$ris[$i++] = $this->unserializeField($riga, $collection);
			}
		}
		
		if ($associative)
			return $ris;
		return json_encode($ris, JSON_NUMERIC_CHECK);
	}


	/**
	 * Dato un array associativo, nome_campo1 => valore1, nome_campo2 => valore2 restituisce la sequenza
	 * nome_campo1 = 'valore1', nome_campo2 = 'valore2', .... Utile per UPDATE
	 * Converte automaticamente in json i valori dei campi indicati in TO_SERIALIZE e cripta quelli
	 * indicati in TO_ENCRYPT
	 * 
	 * @param array L'array dei valori
	 * @param collection Il nome della tabella
	 * 
	 * @return array Un array utile per l'update
	 * 
	 */
	private function array2set($array=null, $collection=null) {
		$toSerialize = unserialize(TO_SERIALIZE);
		$toEncrypt = unserialize(TO_ENCRYPT);
		if (empty($array) || empty($collection)) 
			return null;
		
		$set = ""; 
		$params = array();
		$ret = array();
		foreach ($array as $field => $value) {
			// controllo se il campo e' tra quelli da inserire come json
			$key = "$collection.$field";
			if ($value !== null && array_key_exists($key, $toSerialize) && ($toSerialize[$key] == 'array' || $toSerialize[$key] == 'object'))
				$value = json_encode($value);
			else {
				// devo fare cosi' altrimenti PDO da' errore su valori booleani
				if($value === false)
					$value = 'false';
				else if($value === true)
					$value = 'true';
			}
			
			//Controllo se il campo deve essere criptato (e' un insert) e cripto se richiesto
			if (array_key_exists($key, $toEncrypt) && ($toEncrypt[$key] === true)) {
				$value = OpenSsl::get()->encrypt($value);
			}
			
			$params[":$field"] = $value;			
			$set = "$set $field = :$field,";
		}
		return array('query_set' => ltrim(rtrim($set, ', ')), 'params' => $params);
	}
	

	/**
	 * Inserisce in forma di array, nell'array della risposta, i campi che contengono json
	 * e decripta i campi criptati se e' settata la chiave privata
	 * 
	 * @param array $array Un array associativo nome_campo => valore, tipicamente restituito come riga dal db
	 * @param string $table Il nome della tabella
	 */ 
	private function unserializeField($array, $table) {
		// Rimuovo l'estensione delle view ristrette se presente per potere fare il match con il settaggio
		$table = explode(RESTRICT_VIEW_EXTENSION, $table);
		$table = $table[0];
		$ts = unserialize(TO_SERIALIZE);
		$td = unserialize(TO_ENCRYPT);
		
		// Controlla se uno dei campi dell'elenco di quelli da decriptare e' nella risposta
		// e se e' settata la chiave privata decripta
		foreach ($td as $key => $value) {
			// la chiave contiene anche il nome della tabella
			$tableKey = explode('.', $key);
			if ($tableKey[0] == $table && array_key_exists($tableKey[1], $array) && ($value == 'array' || $value == 'object')) {
				// Se c'e' la chiave decripto, altrimenti setto a null perche' non voglio cmq fare vedere il valore criptato
				if (isset($this->privateKey)) {
					OpenSsl::get()->setPrivateKey($this->privateKey);
					$array[$tableKey[1]] = OpenSsl::get()->decrypt($array[$tableKey[1]], $this->passphrase);
				} else 
					$array[$tableKey[1]] = null;
			}
		}
		
		// controlla se uno dei campi dell'elenco di quelli in json e' nella risposta 
		foreach ($ts as $key => $value) {
			// la chiave contiene anche il nome della tabella
			$tableKey = explode('.', $key);
			if ($tableKey[0] == $table && array_key_exists($tableKey[1], $array) && ($value == 'array' || $value == 'object'))
				$array[$tableKey[1]] = json_decode($array[$tableKey[1]], true);
		}
		return $array;
	}
	
	/**
	 * Setta la chiave privata per decriptare i campi criptati
	 * @param string $key La chiave privata
	 */
	public function setPrivateKey($key) {
		$this->privateKey = $key;
	}
	
	/**
	 * Setta la frase segreta per aprire la chiave privata
	 * @param unknown $pass
	 */
	public function setPassphrase($pass) {
		$this->passphrase = $pass;
	}

}


?>
