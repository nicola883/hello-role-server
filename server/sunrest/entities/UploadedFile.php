<?php 

/**
 * Una risorsa file e' un file.
 */


class UploadedFile extends File {

	
	// I dati del file temporaneo ricavati da $_FILES['nome']
	// vedi 
	private $fileData;
	
	
	/**
	 * Costruisce un file temporaneo con parametri estratti dall'array $_FILES
	 * @see http://php.net/manual/en/reserved.variables.files.php
	 * @param string $fileData E' l'array $_FILES['name'] di php dove name e' nome proveniente dal form settato con name.
	 */	
	function __construct($fileData) {
		if (!$this->validate($fileData))
			return null;
		$this->fileData = $fileData;
		parent::__construct($fileData['tmp_name']);
	}
	
	
	/**
	 * Esegue controlli sul file temporaneo
	 */
	private function validate($fileData) {
		if (!isset($fileData['size']) || $fileData['size'] > MAX_UPLOAD_SIZE)
			return false;
		else
			return true;
	}
	
	/**
	 * Salva il file come logo dell'utente
	 * @param integer $userId l'id dell'utente
	 * @param string $oldFileName Il nome del logo eventualmente gia' salvato.
	 * @return null | string Il nome del logo o null se non e' stato possibile crearlo
	 */
	public function saveAsLogo($userId, $oldFileName=null) {
		
		$name = null;
			
		// copio il file caricato in un tmp (e' gia' stato validato dal costruttore
		$tmp = tempnam(sys_get_temp_dir(), '_');
		if (!$this->saveTmp($tmp))
			return null;	
		
		// controllo che sia un'immagine
		$allowedTypes = array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
		$detectedType = @exif_imagetype($tmp);
		if (!$detectedType || !in_array($detectedType, $allowedTypes))
			return null;

		// converto alla dimensione e tipo di immagine che voglio e salvo
		if (!ImageProcessing::imageToPng($tmp, LOGO_RESIZE, true)) {
			return null;
		}

		// Creo il path per salvare il logo
		$path = USERS_RESOURCES_DIR . $userId . '/';
		if (!file_exists($path))
			mkdir($path);

		// Aggiorno la posizione di questa risorsa
		$this->setPath($tmp);
				
		// cancello il vecchio logo				
		if ($oldFileName !== null) {
			if (file_exists("$path{$oldFileName}"))
				unlink("$path{$oldFileName}");
		}		
		// Salvo nella directory dei logo dell'utente
		$name = uniqid('', true) . '.png';
		$path = $path . $name;
		if (!$this->save($path))
			return null;
		
		return $name;
	}
	
	// Salvo la risorsa nel path dato
	public function save($dst) {
		return rename($this->getPath(), $dst);
	}
	
	/** 
	 * Salva il file temporaneo alla posizione indicata
	 * @params string $path
	 * @see File::save()
	 */
	private function saveTmp($path) {
		$ret = move_uploaded_file($this->getPath(), $path);
		return $ret;
	}
	
	
	
	
}


?>