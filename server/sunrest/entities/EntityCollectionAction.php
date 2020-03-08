<?php

/**
 * Una azione che si applica a una collezione di entita'
 */

abstract class EntityCollectionAction
{
	
	/**
	 * Un elenco di parametri necessari alla richiesta.
	 * usato da Security per validare l'uri. Se il parametro non viene definito qui,
	 * l'uri potrebbe non essere valida.
	 * @var string
	 */
	const PARAMS = '';
	
	
	private $server;
	
	/**
	 * Costruisce un'azione sulla collezione di entita'
	 * @param Server $server
	 */
	public function __construct(Server $server) {
		$this->server = $server;
	}
	
	abstract public function exec($params, EntityCollection $entityCollection, $data=null);
	
	
	/**
	 * Restituisce il singleton Server
	 * @return Server Il singleton Server
	 */
	public function getServer() {
		return $this->server;
	}
	
}

?>