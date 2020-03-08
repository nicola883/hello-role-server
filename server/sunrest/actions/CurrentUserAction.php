<?php

/**
 * Restituisce l'utente corrente
 */

class CurrentUserAction extends Action
{

	const METHOD = 'get';
	
	/**
	 * Inserisce i dati e crea un nuovo utente.
	 * Il server conosce gia' i dati in ingresso dal post.
	 * (non-PHPdoc)
	 * @see Action::exec()
	 */
	public function exec($params=null, $data=null) {
		return $this->getServer()->getCurrentUser(false, true);
	}
	
}

?>