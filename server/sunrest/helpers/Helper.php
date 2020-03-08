<?php


class Helper
{
	
	
	private static function months() {
		return array(null, 
			_('gennaio'), 
			_('febbraio'), 
			_('marzo'),
			_('aprile'),
			_('maggio'),
			_('giugno'),
			_('luglio'),
			_('agosto'),
			_('settembre'),
			_('ottobre'),
			_('novembre'),
			_('dicembre')
		);
	}

	/**
	* Converte una stringa anche con virgola in un numero float
	*/
	static public function str2float($str) {
		if ($str === null)
			return null;
		return (float)str_replace(',', '.', $str);
	}

	/**
	 * Somma dei giorni a una data
	 * @param string $date Una data nel formato ISO es. 2010-1-25
	 * @param integer $days Il numero di giorni da aggiungere 
	 */
	static public function dateAddDays($date, $days) {
		$intervalString = "P{$days}D";
		$date = new DateTime($date);
		$date->add(new DateInterval($intervalString));
		return $date->format('Y-m-d');
	}

	/**
	* Converte una data dal formato italiano a quello per sql
	*/
	static public function date2sql($date){
		if(false === $inar = self::date2array($date))
			return false;			
		// Data in formato italiano?
		if (checkdate($inar[1], $inar[0], $inar[2]))
			// 2:anno, 1:mese, 0:giorno
			return "$inar[2]-$inar[1]-$inar[0]";
		else
			return false;
	}
	
	
	/**
	* Converte una data in timestamp unix.
	* @param $date Una data nel formato gg/mm/aa o gg-mm-aa o aa/mm/gg o aa-mm-gg
	* @return Il timestamp unix che rappresenta la data in ingresso
	*/
	static public function date2ts($date) {
		if(false === $inar = self::date2array($date))
			return false;		
		// Data in formato italiano?
		if (checkdate($inar[1], $inar[0], $inar[2]))
			// 2:anno, 1:mese, 0:giorno
			return mktime(0, 0, 0, $inar[1], $inar[0], $inar[2]);
		elseif (checkdate($inar[1], $inar[2], $inar[0]))
			// 0:anno, 1:mese, 2:giorno
			return mktime(0, 0, 0, $inar[1], $inar[2], $inar[0]);
		else
			return false;
	}
	
	/**
	 * Converte una data in un array con i valori della stessa
	 * @param $input Una data nel formato aa-bb-cc oppure aa/bb/cc
	 * @return Un Array array(aa, bb, cc)
	 */
	static function date2array($input) {
		$input = trim($input);
		// provo con '/' o '-' come separatori di data:
		$inar = explode('/', $input);
		if (count($inar) != 3)
			$inar = explode('-', $input);				
		if (count($inar) != 3)
			return false;
		return $inar;
	}
	
	/**
	 * Restituisce la differenza con segno in mesi tra due date
	 * Date2 - Date1
	 * @param string $date1 La data minima in formato iso Y-m-d
	 * @param string $date2 La data massima in formato iso Y-m-d
	 */
	public static function dateMonthsDiff($date1, $date2) {
		$d1 = new DateTime($date1);
		$d2 = new DateTime($date2);
		
		$diff = $d1->diff($d2);

		$months = round($diff->format('%y') * 12 + $diff->format('%m') + $diff->format('%d') / 30);		
		// aggiungo il segno
		$months = $diff->format('%r') . $months;
		
		return (int) $months;
	}
	
	/**
	 * Sottra n mesi dalla data fornita e la restituisce in formato iso
	 */
	public static function dateSubMonths($date, $months) {
		$interval = new DateInterval("P{$months}M");
		$st = new ServerTime($date);
		return $st->sub($interval)->format('Y-m-d');
	}
	
	
	/**
	 * Restituisce l'url della radice del server
	 * @return NULL|string
	 */
	static function getServerUrl() {
		if (self::getMyUrl() === null)
			return null;
		return Security::getMyUrl() . SERVER_PATH;
	}
	
	/**
	 * Restituisce l'url della radice del client
	 * @return NULL|string
	 */
	static function getClientUrl() {
		if (Security::getMyUrl() === null)
			return null;		
		return Security::getMyUrl() . CLIENT_PATH;
	}
	
	/**
	 * Restituisce il nome del mese in italiano
	 * @param Integer $monthNumber Il numero del mese
	 */
	static function getMonthName($monthNumber) {
		return self::months()[(int)$monthNumber];
	}
	
	
	static function getMonthNumber($monthName) {
		$monthName = strtolower($monthName);
		return array_search($monthName, self::months());
	}
	
	/**
	 * Controlla se il valore e' un id, verificando che sia un intero > 0
	 * @param integer | string $number Un numero che dovrebbe essere un id
	 * @return true se $number e' un numero valido per essere un id.
	 */
	static function isId($number) {
		return filter_var($number, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false ? false : true;
	}
	
	/**
	 * Controlla se il valore e' del tipo dato
	 * @param unknown $value
	 * @param string $type Un tipo usato nei campi di postgresql o 'novalue' che significa '' 
	 * TODO aggiungi i tipi del db mancanti, per ora ho messo solo quelli che mi servono per validare le query GET
	 */
	static function isType($value, $type) {

		switch($type) {
			case 'boolean':
				return filter_var($value, FILTER_VALIDATE_BOOLEAN) === false ? false : true;
				break;
			case 'integer':
				return filter_var($value, FILTER_VALIDATE_INT) === false ? false : true;
				break;
			case 'text':
				return is_string($value) && strlen($value) < MAX_TEXT_LENGTH && $value != '';
				break;
			case 'novalue':
				return $value == '';
			default:
				return false;
		}
	}
	
	/**
	 * Converte un array di un item in json e lo formatta
	 * @param array $item L'item in array
	 * @return string Il json
	 */
	static function item2json($item) {
		return json_encode($item, JSON_PRETTY_PRINT);
	}
	
	/**
	 * Converte un array di una lista in json e lo formatta
	 * @param array $list Una lista di item
	 * @returnstring Il json
	 */
	static function list2json($list) {
		return json_encode($list);
	}
	
	/**
	 * converte una stringa del tipo nome1-nome2.nome3 in Nome1Nome2Nome3
	 * @param unknown $str
	 */
	static public function uri2Camel($str) {
		$str = str_replace('.', '-', $str);
		return str_replace('-', '', mb_convert_case($str,  MB_CASE_TITLE));
	}
	
}

?>
