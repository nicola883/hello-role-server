<?php

/**
 * Fa il login
 */
class LoginAction extends Action
{

	const METHOD = 'post';
	
	/**
	 * Esegue il login. Ritorna false se e' negato.
	 * Il server conosce gia' i dati in ingresso dal post.
	 * (non-PHPdoc)
	 * @see Action::exec()
	 */
	public function exec($params=null, $data=null) {
		if (!($ret = $this->getServer()->login($data))) {
			header(UNAUTHORIZED);
			return false;
		} else
			return $ret;
	}
	
}

?>