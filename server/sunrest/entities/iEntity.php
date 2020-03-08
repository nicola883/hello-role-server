<?php

interface iEntity {
	
	/**
	 * Il nome della collezione di riferimento (il nome della tabella del db associata) 
	 * della entita' o collezione 
	 * @return string Il nome della collezione
	 */
	public function getCollectionName();
	
	/**
	 * Il server passato all'entita' alla costruzione
	 * @return Server Il server
	 */
	public function getServer();
	
	
}


?>