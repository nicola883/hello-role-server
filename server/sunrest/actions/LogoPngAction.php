<?php

/**
 * Restituisce il logo dell'utente
 */

class LogoPngAction extends Action
{

	const METHOD = 'get';
	
	const PARAMS = 'cb';
	
	/**
	 * (non-PHPdoc)
	 * @see Action::exec()
	 */
	public function exec($params=null, $data=null) {
		$server = $this->getServer();
		header('Content-Type: image/png');
		return readfile($server->getLogoPath($server->getCurrentUser(true)['id']));
	}
	
}

?>