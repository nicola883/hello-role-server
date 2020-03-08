<?php

/**
 * Il db 
 */
 
class PdoDb implements iDb
{

	// Le query per definire le view ristrette all'utente
	// restrict['queries'] le query e restric['parameter'] il parametro
	private $restrict;

	// The pointer to the mysql connection
	private $connectionLink;
	
	/**
	 * Una variabile di tipo resource contenente un riferimento al risultato mysql dell'ultima query 
	 */
	private $resultResource;

	// L'utente del db
	private $user;

	// La password del db
	private $dbpwd;
	
	// Il db
	private $db;

	/**
	 * Costruisce una classe db postgresql.
	 * @param $host Il server
	 * @param $user L'id dell'utente
	 * @param password La password di connessione
	 * @param db Il nome del database a cui connettersi
	 */
	protected function __construct ($host, $user, $password, $db, $port) {
	/*
		// Crea una nuova connessione. Se questa è già aperta ne restituisce il link.
				
		// connessione con socket (funziona con autenticazione local md5 messa in pg_hba.conf e non peer)
		//$this->connectionLink = pg_connect("dbname=myhost user=me password=pippo");
		
		// connessione con tcp/ip in locale. Settato localhost come indirizzo accettato. Se vuoi
		// un ip devi settare postgres (vedi 19.1 nota in host)		
		$this->connectionLink = pg_connect("host=$host user=$user password=$password dbname=$db")
			or die('Could not connect: ' . pg_last_error());
		$this->db = $db;
		$this->dbpwd = $password;
		$this->user = $user;
		*/
		
		
	    $dsn = "pgsql:host=$host;dbname=$db;port=$port";
		$option = array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
				PDO::ATTR_PERSISTENT => false
		);
				
		$this->connectionLink = new PDO($dsn,$user,$password, $option);
		//$this->connectionLink->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		
		
	}
	
	
	
	/**
	 * Chiude la connessione al database
	 * @return true se la chiusura ha successo, false altrimenti
	 */
	private function close() {
		// da implementare
	}
	
	public function beginTransaction() {
		$this->connectionLink->beginTransaction();
	}
	
	public function commit() {
		$this->connectionLink->commit();
		
	}

	/**
	 * Esegue una query SQL sul database e setta $this->result con il risultato 
	 * @param $query Una query in SQL
	 * @return Il risultato della query, false se c'e' stato un errore
	 */
	public function execQuery($query) {
		if ($ret = $this->connectionLink->query($query)) {
			$this->resultResource = $ret;
		} else
			print_r($this->connectionLink->errorInfo());
		return $ret;
	}
	
	
	/**
	 * Esegue una query con parametri
	 * @param string $query La query con i parametri
	 * @param array $params Un array del tipo array(':nome_parametro' => valore, ...)
	 * @param boolean $unsafe Se true viene usata la simulazione della preparazione della query, che risulta non sicura
	 * perche' la query viene comunque preparata come unica stringa dai parametri e inviata anziche' inviare prima la query
	 * e poi i valori dei parametri. Default false
	 */
	public function execQuery2($query, $params, $unsafe=false) {
		$p = $this->connectionLink->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY, PDO::ATTR_EMULATE_PREPARES => $unsafe));
		try {
			if ($ret = $p->execute($params)) {
				$this->resultResource = $p;			
			} else {
				Utility::log($this->connectionLink->errorInfo(), true, true);
			}
		} catch (Exception $e) {
			Utility::log($e->getMessage(), true, true);
			$ret = false;
		}
		return $ret;
	}
		
	
	/**
	 * Esegue una query sicura con parametri e restituisce una tabella con i risultati
	 * @params string $query La query da eseguire
	 * @params array $params I parametri della query
	 * @params boolean $array true se si vuole il risultato in un array, false se si vuole un array associativo. Default false
	 * @return null se il risultato e' nullo o false se c'e' stato un errore nell'esecuzione
	 */
	public function dbQuery2($query, $params, $array=false) {
		if (!$this->execQuery2($query, $params)) 
			return false;
		if ($array)
			return $this->getAllRows(true);
		return $this->getAllRows();
	}
	
	
	// Crea le view ristrette all'utente come definite dalle query settate in set.php
	public function createRestrictedViews() {
		if (null !== $userId = $this->restrict['parameter']) {
			$userId = (int)$userId;
			$queries = $this->restrict['queries'];
			foreach ($queries as $table => $query) {
				$query = "CREATE OR REPLACE TEMP VIEW {$table}".RESTRICT_VIEW_EXTENSION." AS $query";
				$params = array(':id' => $userId);
				$this->execQuery2($query, $params, true);
			}
		}
	}

	/**
	* Setta tutte le query per creare le view ristrette all'utente
	* @params $queries Un array di query del tipo nome_tabella_1 => query1, nome_tabella_2 => query2...
	*/
	public function setRestrictQueriesAndParameter($queries, $param) {
		$this->restrict['queries'] = $queries;
		$this->restrict['parameter'] = $param;
	}
	
	/**
	* Esegue l'insert e restituisce l'id della riga inserita
	* L'operazione e' atomica: l'id ritornato e' quello dell'inserimento vedi:
	* http://stackoverflow.com/questions/19167349/postgresql-insert-from-select-returning-id
	* http://www.postgresql.org/docs/9.1/static/sql-insert.html
	*/
	public function insert($table, $array) {
		if (empty($table) || empty($array))
			return null;
		$data = $this->array2insert($array, $table);
		$insert = "INSERT INTO $table {$data['fields']} VALUES {$data['paramsname']} RETURNING id";
		$fch = $this->dbQuery2($insert, $data['params']);
		return($fch[0]['id']);
	}

	/**
	 * Restituisce un'array contenente una riga del risultato.
	 * L'array è associativo, del tipo $valore = $array['campo'].
	 * Ad ogni chiamata di questo metodo corrisponde un avanzamento del puntatore al risultato
	 * @return Un array contenente una riga del risultato dell'ultima query oppure null se il risultato è vuoto
	 */
	public function getRow() {
		$ret = null;
		if ($res = $this->getResultResource())
			$ret = $res->fetch(PDO::FETCH_ASSOC);
		if ($ret == false) $ret = null; // in pdo ritorna false, in mysql null
		return $ret;
	}

	/**
	 * Restituisce un array di array con le righe dei risultati. Null in caso di errore o risultato nullo.
	 * @return Array
	 */
	public function getAllRows($array=false) {
		$ret = null;
		if ($res = $this->getResultResource()) {
			if ($array)
				$ret = $res->fetchAll(PDO::FETCH_NUM);
			else
				$ret = $res->fetchAll(PDO::FETCH_ASSOC);
		}
		if ($ret == false) $ret = null; // in pdo ritorna false, in mysql null
		return $ret;		
	}
	
	/**
	* Restituisce il numero di righe del risultato
	*/
	public function numRows() {
		$res = $this->getResultResource();
		return $res->rowCount();
	}
	
	/**
	 * Verifica se la tabella esiste nel db
	 * @param string $tableName
	 */
	public function tableExists($table) {
		$params = array(':table' => $table);
		$query = '
		SELECT EXISTS(
		SELECT *
		FROM information_schema.tables
		WHERE
		table_name = :table
		)';
		return $this->dbQuery2($query, $params)[0]['exists'];
	}
	
	/**
	 * Verifica se un campo esiste nella tabella
	 */
	public function fieldExists($field, $table) {
		$params = array(':table' => $table, ':field' => $field);
		$query = 'SELECT EXISTS (
		SELECT column_name
		FROM information_schema.columns
		WHERE table_name=:table and column_name=:field)';
		return $this->dbQuery2($query, $params)[0]['exists'];
	}
	
	/**
	 * Restituisce il tipo del campo
	 * @return string il nome del tipo del campo
	 */
	public function getFieldType($field, $table) {
		$params = array(':table' => $table, ':field' => $field);
		$query = 'select column_name, data_type from information_schema.columns where table_name = :table and column_name = :field';
		return $this->dbQuery2($query, $params)[0]['data_type'];
	}

	/**
	* Dato un array associativo nome_campo_1 => valore1, nome_campo_2 => valore2..., restituisce
	* la stringa (nome_campo1, nome_campo2, ..) VALUES (valore1, valore2, ...). Utile per insert
	* Converte automaticamente in json i valori dei campi indicati in TO_SERIALIZE e cripta quelli
	* indicati in TO_ENCRYPT
	* @param array L'array dei valori
	* @param string Il nome della tabella
	* 
	* @return array Un array utile per l'insert
	*/
	protected function array2insert ($array, $table) {
		if (empty($array)) {
			return null;
		}
		
		$toSerialize = unserialize(TO_SERIALIZE);
		$toEncrypt = unserialize(TO_ENCRYPT);
		$fields = '';
		$paramsName = '';
		
		foreach ($array as $key => $value) {
			$fields = "$fields $key,";
			$paramsName = "$paramsName :$key,";
			// controllo se il campo e' tra quelli da convertire in json e lo faccio se serve
			$keyToTest = "$table.$key";
			if (array_key_exists($keyToTest, $toSerialize) && ($toSerialize[$keyToTest] == 'array' || $toSerialize[$keyToTest] == 'object'))
				$value = json_encode($value);
			else {
				// devo fare cosi' altrimenti PDO da' errore su valori booleani
				if($value === false)
					$value = 'false';
				else if($value === true)
					$value = 'true';				
			}
			
			// Controllo se il campo deve essere criptato (e' un insert) e cripto se richiesto
			if (array_key_exists($keyToTest, $toEncrypt) && ($toEncrypt[$keyToTest] === true)) {
				$value = OpenSsl::get()->encrypt($value);
			}
			
			$params[":$key"] = $value;
		}
		
		$fields = '(' . ltrim(rtrim($fields, ','), ' ') . ')';
		$paramsName = '(' . ltrim(rtrim($paramsName, ','), ' ') . ')';
		return array('fields' => $fields, 'paramsname' => $paramsName, 'params' => $params);
	}
	
	/**
	 * Restituisce il risultato dell'ultima query
	 * @return Un risultato di tipo resource MYSQL
	 */
	private function getResultResource() {
		return $this->resultResource;
	}

	/**
	* inserisce gli escape come richiesto dal db
	*/
	public function escapeString($string) {
		return $this->connectionLink->quote($string);
	}

	/*
	* Esegue il backup del database
	*/
	public function backup($filename) {
		//TODO
	}
	
	public function closeConnection() {
		//pg_close($this->connectionLink);
	}
		
}	

?>
