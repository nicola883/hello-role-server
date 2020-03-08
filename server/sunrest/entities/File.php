<?php 

/**
 * Una risorsa file e' un file.
 */


abstract class File extends Entity {

	// Il path al file (con il nome)
	private $path;

	
	/**
	 * Costruisce una risorsa file, data la posizione
	 * @param string $name Il nome del file.
	 */	
	function __construct($path) {
		if ($path === null || $path == '')
			return null;
		$this->path = $path;
	}
	
	
	public abstract function save($path);
	
	protected function getPath() {
		return $this->path;
	}
	
	protected function setPath($path) {
		$this->path = $path;
	}
	
	
}


?>