<?php 

/**
 * Una collection e' un'insieme di entita'
 */

class EntityCollection implements iEntity
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
	
	
	function __construct($collectionName, Server $server) {
		$this->collectionName = $collectionName;
		$this->server = $server;
	}
	
	/**
	 * Esegue un'azione
	 * @param EntityCollectionAction $action Un azione per l'entita' collection
	 * @param unknown $params I parametri della query
	 * @param unknown $data I dati in un array associativo (ad. es. nell'update)
	 */
	public function execAction(EntityCollectionAction $action, $params=null, $data=null) {
		return $action->exec($params, $this, $data);
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
	
	
	
}

?>