<?php

/*
 * Una action e' una richiesta di qualcosa di speciale dal server
 * Viene chiamata da una uri che ha nel primo tratto, un nome diverso da resource
 * L'azione chiamata e' quella il cui nome di classe corrisponde al tratto di url in camel case.
 * Deve avere la costante METHOD settata e con valore 
 * get | post | put | delete corrispondente al metodo con cui si vuole chiamarla.
 * Se il metodo alla chiamata e' diverso da quello settato, la uri non verra' accettata dal filtro
 * in Authorization
 */

abstract class Action
{
	
	/**
	 * Un elenco di parametri necessari alla richiesta.
	 * usato da Security per validare l'uri. Se il parametro non viene definito qui,
	 * l'uri potrebbe non essere valida.
	 * @var string
	 */
	const PARAMS = '';
	
	
	// Il server
	private $server;
	
	
	/**
	 * Costruisce un'azione
	 * @param Server $server Il server
	 */
	function __construct(Server $server) {
		$this->server = $server;
	}
	
	/**
	 * Esegue l'azione.
	 * Dopo averla eseguita il server termina
	 * @param array $params Lalla query dell'uri
	 * @param Server $server Il server
	 * @param array $data Eventuali dati
	 */
	abstract function exec($params=null, $data=null);
	
	/**
	 * Ritorna il server, assegnato nel costruttore
	 * @return Server
	 */
	public function getServer() {
		return $this->server;
	}
	
	
}


?>