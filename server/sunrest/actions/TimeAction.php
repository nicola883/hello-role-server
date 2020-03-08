<?php

/**
 * L'ora del server
 */

class TimeAction extends Action
{
	
	const METHOD = 'get';
	
	/**
	 * Restituisce l'ora corrente del server
	 */
	public function exec($params=null, $data=null) {
		$time = new ServerTime();
		return json_encode(array('now' => str_replace(' ', 'T', $time->__toString())));
	}
	
	
}





?>