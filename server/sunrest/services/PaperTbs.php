<?php

/**
* Un PaperTbs è un documento cartaceo costruito con la libreria OpenTbs Open Tiny but strong
*/

abstract class PaperTbs
{
	
	// Le variabili da inserire nel documento
	private $properties;
	
	// le variabili di controllo da inserire nel documento
	private $ctrlVars = array();
	
	// Il documento in tbs
	private $tbs;

	/**
	* Costruisce un documento in odt con i valori e il template dati.
	* Nel template vengono indicate le variabili da riportare.
	* Attenzione: se si vedono strani comportanmenti, nel template tagliare il testo con le variabili e reincollare con "incolla speciale"
	* @param $properties Un array di array di array con le proprietà 
	* 	array('nome_campo_nel_template' => array('nome_campo-template' => array(array('nome_campo1' => valore, ...), array('nome_campo1' => valore, ...), ...) per gestire piu' record
	* @param $template Il template
	* @param $ctrlVars Un array associativo di variabili di controllo, del tipo array('var1' => valore, 'var2' => valore...)
	*/
	function __construct($properties, $template, $ctrlVars=null) {
		$this->ctrlVars = $ctrlVars;
		include_once(__DIR__ . '/../services/lib/tbs/tbs_class.php');
		include_once(__DIR__ . '/../services/lib/tbs/tbs_plugin_opentbs.php');
		
		// Non ferma lo script in caso di errore
		//$this->tbs->SetOption('noerr', true);
		
		// prevent from a PHP configuration problem when using mktime() and date()
		if (version_compare(PHP_VERSION,'5.1.0')>=0) {
			if (ini_get('date.timezone')=='') {
				date_default_timezone_set('UTC');
			}
		}		
		$this->tbs = new clsTinyButStrong;
		$this->tbs->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);
		
		// Esclude la lettura delle variabili da $GLOBAL
		$this->tbs->ResetVarRef(false);
		//$this->tbs->VarRef['x'] = 'ciao x';
		
		$this->tbs->SetOption('noerr', true);
		
		// Setto delle variabili da visualizzare nel template
		// con [var.key]
		if (isset($ctrlVars)) {
			foreach ($this->ctrlVars as $key => $value) {
					$this->setCtrlVar($key, $value);
			}
		}

		// Dico che tutti i documenti sono gia' in utf-8
		$this->tbs->LoadTemplate($template, OPENTBS_ALREADY_UTF8);
		
		foreach ($properties as $key => $value) {
			$this->tbs->MergeBlock($key, $value);
		}
	}

	/**
	 * Restituisce il documento in una stringa
	 */
	public function getPaper() {
		$this->tbs->Show(OPENTBS_STRING);
		return $this->tbs->Source;
	}

	/**
	* Salva il documento generato nella posizione indicata
	*/
	public function savePaper($name) {
		$this->tbs->Show(OPENTBS_FILE, $name);
	}

	/**
	* Permette il download del documento generato
	*/
	public function exportPaper($name='aName') {
		$this->tbs->Show(OPENTBS_DOWNLOAD, $name);
	}
	
	protected function setGraph($ref, $serie, $values, $legend=false) {
		$this->tbs->PlugIn(OPENTBS_CHART, $ref, $serie, $values, $legend);
	}
	
	
	public function setImage($placeholder, $image) {
		//echo getcwd() . "\n";
		$prms = array('unique' => true, 'adjust' => '10%');
		$this->tbs->Plugin(OPENTBS_CHANGE_PICTURE, $placeholder, $image);
	}
	
	// setta le variabili che usero' per il controllo
	protected function setCtrlVar($name, $value) {
		//$this->ctrlVars[]= array($name => $value);
		$this->tbs->VarRef[$name] = $value;
	}
	
	protected function setMergeBlockVar($name, $value) {
		$this->tbs->MergeBlock($name, $value);
	}
	
	private function isAssociative($array) {
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}
}

?>
