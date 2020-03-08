<?php

/**
 * Test se la password e' corretta (serve per il cambio password)
 */

class PasswordOkAction extends Action
{

	const METHOD = 'post';
	
	public function exec($params=null, $data=null) {
		$server = $this->getServer();
		$ret = isset($server->getPostData(true)['pwd']) ? $server->isPassword($server->getPostData(true)['pwd']) : false;
		return json_encode(array('result' => $ret));
	}
	
}

?>