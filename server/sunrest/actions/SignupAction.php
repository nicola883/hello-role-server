<?php

/**
 * Esegue un passo di registrazione, determinato dal parametro step della uri
 * Solo il passo step=user-data e' possibile senza il login 
 * 
 * Esegue per il primo passo di registrazione (/signup/user-data)
 * l'unico possibile senza login (il form nella pagina pubblica) e setta il passo completato
 */

class SignupAction extends Action
{

	const METHOD = 'post';
	
	const PARAMS = 'step';
	
	/**
	 * Inserisce i dati e crea un nuovo utente.
	 * Il server conosce gia' i dati in ingresso dal post.
	 * (non-PHPdoc)
	 * @see Action::exec()
	 */
	public function exec($params=null, $data=null) {
		
		$server = $this->getServer();
		$signups = new Signups();
		$ret = $signups->signup($params['step'], $data);
		// se il passo non e' uno di quelli definiti in signup viene ritornato null
		// e header NOT_FOUND		
		header($server->getHeader());
		return $ret;
	}
	
	
}

?>