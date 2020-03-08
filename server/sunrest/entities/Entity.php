<?php


/**
* Una entita' e' un impianto, un operatore, un utente, un sito, ecc.
*/
class Entity implements iEntity
{

	/**
	 * Un elenco di parametri necessari alla richiesta.
	 * usato da Security per validare l'uri. Se il parametro non viene definito qui,
	 * l'uri potrebbe non essere valida.
	 * @var string
	 */
	const PARAMS = '';
	
	// La collezione di riferimento
	private $collectionName;
	
	// Il server. Indispensabile per fare qualcosa sul db
	private $server;
	
	// L'id dell'entita'
	private $id;
	
	// I dati dell'entita'
	private $data;
	
	/**
	 * Costruisce un'entita'
	 * @param string $collectionName Il nome della collezione di riferimento (la tabella del db)
	 * @param integer $id L'id che corrisponde al campo id della tabella
	 * @param Server $server
	 */
	function __construct($collectionName, $id, Server $server) {
		$this->collectionName = $collectionName;
		$this->id = $id;
		$this->server = $server;
		return true;
	}
	
	/**
	 * Esegue un'azione
	 * Viene chiamato da index e gli vengono passati i dati in ingresso
	 * @param EntityAction $action Un azione per l'entita'
	 * @param array $params I parametri della query
	 * @param array $data I dati in un array associativo (ad. es. nell'update)
	 */
	public function execAction(EntityAction $action, $params=null, $data=null) {
		return $action->exec($params, $this, $data);
	}	
	
	/**
	 * Restituisce i dati dell'entita'
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * Setta i dati dell'entita'
	 * @param array $data I dati
	 */
	public function setData($data) {
		$this->data = $data;
	}
	
	
	
	/**
	 * Restituisce il nome della collezione di riferimento
	 * @return string Il nome della collezione di riferimento dell'entita'
	 */
	public function getCollectionName() {
		return $this->collectionName;
	}
	
	/**
	 * Restituisce il server
	 * @return Server Il server
	 */
	public function getServer() {
		return $this->server;
	}
	
	/**
	 * L'id dell'entita'
	 * Viene settato dal costruttore
	 */
	public function getId() {
		return $this->id;
	}

}



?>
