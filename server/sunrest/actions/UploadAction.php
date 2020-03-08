<?php

/**
 * Carica il logo
 */

class UploadAction extends Action
{

	const METHOD = 'post';
	
	/**
	 * @see Action::exec()
	 */
	public function exec($params=null, $data=null) {
		
		$server = $this->getServer();
		$ret = $server->saveLogo($_FILES['logo']);
		if ($ret !== false)
			return json_encode(array('path' => $ret));
		else
			header(MEDIA_UNSUPPORTED);
		
		return false;
	}
	
}

?>