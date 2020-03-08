<?php

/**
 * Esegue il logout dell'utente
 */

class LogoutAction extends Action
{

	const METHOD = 'post';
	
	/**
	 * (non-PHPdoc)
	 * @see Action::exec()
	 */
	public function exec($params=null, $data=null) {
		$this->getServer()->logout();
		header(OK);
		return '{}';
	}
	
}

?>