<?php

/**
* Un Paper è un documento cartaceo
*/

abstract class Paper
{
	
	// Le variabili da inserire nel documento
	private $properties;
	
	// Il documento in odfphp
	private $odfphp;

	/**
	* Costruisce un documento in odt con i valori e il template dati
	*/
	function __construct($properties, $template) {
		require_once('../../services/lib/odf.php');
		$this->odfphp = new odf($template);
		foreach ($properties as $key => $value) {
			$this->odfphp->setVars($key, $value, false, 'UTF-8');
		}
	}		


	/**
	* Salva il documento generato nella posizione indicata
	*/
	public function savePaper($name) {
		$this->odfphp->saveToDisk($name);
	}

	/**
	* Permette il download del documento generato
	*/
	public function exportPaper() {
		$this->odfphp->exportAsAttachedFile();
	}
}

?>
