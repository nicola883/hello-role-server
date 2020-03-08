<?php

/**
 * Implementa il design pattern Observer, ma nello stile tipo Wordpress, con gli hook.
 * Non potevo fare Observer tradizionale perche' questo ha bisogno di caricare gli oggetti Observer nell'oggetto Observable
 * quando questo e' instanziato.
 * In SR ho bisogno che qualunque classe possa essere Observable e non so quando e come viene instanziata (c'e' il Factory, ma non 
 * sempre e' usato...).
 * Ho creato allora una classe singleton Monitor che memorizza tutti gli Observer e ha un metodo update() che chiunque puo' chiamare.
 * In sostanza carico in Monitor degli Observer e da dove voglio chiamo Monitor->update() che chiama update() di tutti gli Observer.
 * Per evitare di fare controllare a ogni Observer che l'update lo riguardi, update() viene chiamato con un parametro che e' il nome dell'evento
 * (detto hook per renderlo simile a WP e perche' puo' essere in futuro anche un filtro, come WP).
 * 
 *  Monitor e' un singleton: viene istanziato la prima volta che viene chiamato Monitor::get().
 *  Per avere l'oggetto Monitor occorre chiamare Monitor::get().
 *  Carico i vari Observer che voglio con
 *  Monitor::get()->addAction($hookName, $function) dove $hookName e' il nome dell'evento e function e' una funzione che viene eseguita
 *  	al verificarsi dell'evento
 *  Comunico l'avvenuto evento con
 *  Monitor->get()->update($hookName, $this) dove $hookName e' il nome dell'evento e $this e' l'oggetto da cui viene chiamato
 */

class Monitor {
	
	private static $instance;
	
	/**
	 * Gli observer registrati con addAction($hookName..)
	 * @var array $actions
	 */
	private $actions;
		
	private function __construct(){}
	
	/**
	 * Restituisce l'istanza della classe
	 * Con la prima istanza carica tutti gli observer indicati nella classe SetObservers
	 * @return Monitor L'istanza di Monitor
	 */
	public static function get() {
		if (empty(self::$instance)) {
			self::$instance = new Monitor();
			SetObservers::addObservers();
		}
		return self::$instance;
	}
	
	/**
	 * Aggiunge un observer: una funzione che viene eseguita quando
	 * viene chiamato il metodo update() di Monitor con il nome dell'evento
	 * coincidente a quello passato qui come parametro
	 * @param string $hookName Il nome dell'evento
	 * @param string $function Il nome della funzione da eseguire quando viene chiamato update
	 */
	public function addAction($hookName, $function) {
		$this->actions[$hookName][] = $function;
	}
	
	/**
	 * Esegue la funzione passata come observer con addAction
	 * @param string $hookName. Un nome univoco: tutti gli addAction che lo indicano eseguono una funzione.
	 * @param object $obj L'oggetto da cui viene chiamato questo metodo on un altro oggetto utile
	 * @param mixed $data Valori addizionali necessari all'hook
	 * @return mixed Quanto restituito dalla funzione hook che ha risposto o $data se non c'e' nessuna funzione
	 */
	public function update($hookName, $obj, $data=null) {
		
		$ret = $data;
		
		if (!isset($this->actions[$hookName])) {
			if (!empty($data))
				return $data;	
			return false;
		}
		
		// Eseguo tutte le azioni dell'hook dato.
		foreach ($this->actions[$hookName] as $action) {
			// Php non si arrabbia se passo un argomento in piu'
			$ret = call_user_func($action, $obj, $data);
		}
		
		// TODO Per ora restituisco solo l'ultimo valore restituito dall'azione
		// senza gestire una priorità.
		// Non importante per le azioni il cui valore di ritorno non interessa,
		// ma necessario per i filtri.
		return $ret;
	}
	
}
?>