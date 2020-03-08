<?php

class Utility
{

	function __construct() {
		if (version_compare(PHP_VERSION,'5.1.0')>=0) {
			if (ini_get('date.timezone')=='') {
				date_default_timezone_set('UTC');
			}
		}
	}
	
	/**
	 * Print a message where the constant LOG_FILE defines.
	 * LOG_FILE can be a name of a file or the std out (php://stdout). Also, it could
	 * indicate to save the messages in a field in the db. To have this behaviour 
	 * the constant has to have the format 
	 * table:table_name:field:field_name:id:id_number
	 * @param string $msg The message to print
	 * @param boolean $date If true it will loged also the date, not only the time
	 */
	static public function log($msg, $date=true, $noDb=false) {
		
		$id = "";
		if (isset($_SESSION['logged']['id']))
			$id = $_SESSION['logged']['id'];
		
		
		$call = debug_backtrace();
		if (count($call) > 2) {
			$call = $call[1];
			$call = " {$call['class']}.{$call['function']}()";
		} else
			$call = "";
    	//date_default_timezone_set('Europe/Rome');
    	if ($date)
			$date = date('d.m.Y G:i:s');
		else
			$date = date('G:i:s');

		$out = '';
        // Add the pid
		if (defined('LOG_THE_PID') && function_exists('posix_getpid')) {
		    $out = 'pid: ' . posix_getpid() . "; ";
		}
		
		$out .= "user_id: $id $date:$call: $msg" . PHP_EOL;
		
		$saveToDb = false;
		// If it has to save on the db the LOG_FILE has been
		// defined as table:table_name:field:field_name:id:id_number
		if (defined('LOG_FILE')) { 
		    $logFile = LOG_FILE;
		} else {
		    $logFile = DIR_LOG_DEBUG_FILE;
		}
		
		if (defined('LOG_DB')) {
		    $t = explode(':', LOG_DB);
		    if ($t[0] == 'table' && !$noDb) {
		        $saveToDb = true;
		    }
		}

		if ($saveToDb) {
		    $db = Factory::createDb();
		    $query = "UPDATE $t[1] set $t[3] = CONCAT($t[3], :out::text) where id = :id";
		    $params = array(':id' => $t[5], ':out' => $out);
		    $db->dbQuery2($query, $params);
		} else {
		    error_log($out, 3, $logFile);
		}
    } 
	
    
    static public function caller() {
    	$call = debug_backtrace();
    	if (count($call) > 2) {
    		$call = $call[2];
    		$call = "{$call['class']}.{$call['function']}()";
    	} else
    		$call = "";
    	
    	$out = $call == "" ? 'chiamante non noto' : $call;
    	self::debug($out);
    	return $out;
    }
    
    
	static public function debug($array) {
		echo '<pre>';
		var_dump($array);
		echo '</pre>';
		return print_r($array, true);
	}
	
	static public function jsonError() {
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$msg = ' - No errors';
				break;
			case JSON_ERROR_DEPTH:
				$msg = ' - Maximum stack depth exceeded';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$msg = ' - Underflow or the modes mismatch';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$msg = ' - Unexpected control character found';
				break;
			case JSON_ERROR_SYNTAX:
				$msg = ' - Syntax error, malformed JSON';
				break;
			case JSON_ERROR_UTF8:
				$msg = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
			default:
				$msg = ' - Unknown error';
				break;
		}
		
		return $msg;		
	}
	
	/**
	 * Converte un json in array inviando l'eventuale errore al log
	 */
	static function jsonDecodeSafe($json) {
		$ret = json_decode($json, true);
		if ($ret === null) {
			Utility::log(Utility::jsonError());
			return null;
		}
		return $ret;
	}
	
	/**
	 * Controlla se un array ha stringhe come indici, indicazione del fatto che sia associativo
	 */
	static public function hasStringKeys(array $array) {
		return count(array_filter(array_keys($array), 'is_string')) > 0;
	}
	
	/**
	 * Unisce due array ASSOCIATIVI. Nel caso di campi uguali assegna il valore del primo
	 * o, se il nome del campo e' indicato in $toAdd, assegna la somma dei valori dei due.
	 * @param array $array1 Un array da associare
	 * @param array $array2 Un altro array da associare
	 * @param array $toAdd Un array con nomi di campi da sommare. e prendere invece il valore del primo 
	 */
	static public function mergeAdd($array1, $array2, $toAdd=null) {
		/**
		 * Somma ricorsivamente i valori di due array
		 * restituendone uno che ha i valori pari alla somma dei campi
		 * con lo stesso nome o il primo se il suo nome e' contenuto in $skip
		 */
		$add = function ($m, $r) use (&$add, $toAdd) {
			foreach($m as $key => $value) {
				if (is_array($value) && !self::hasStringKeys($value)) {
					if (!empty($toAdd) && array_search($key, $toAdd) !== false)
						$r[$key] = array_sum($value);
					else
						$r[$key] = $value[0];
				} else {
					if (is_array($value))
						$r[$key] = $add($value, $value);
					else
						$r[$key] = $value;
				}
			}
			return $r;
		};
			
		$m = array_merge_recursive($array1, $array2);
		// Sommo i campi uguali che array_merge_recursive ha messo in array
		return $add($m, array());
	}	

	static public function print_mem()
	{
	    /* Currently used memory */
	    $mem_usage = memory_get_usage(true);
	    
	    /* Peak memory usage */
	    $mem_peak = memory_get_peak_usage(true);
	    
	    echo 'The script is now using: ' . round($mem_usage / 1024) . "KB of memory.\n";
	    echo 'Peak usage: ' . round($mem_peak / 1024) . "KB of memory.\n";
	}
	
}
