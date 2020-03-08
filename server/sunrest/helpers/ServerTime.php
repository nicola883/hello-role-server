<?php

class ServerTime extends DateTime {

    /**
     * Restituisce la data nel formato ISO8601
     *
     * @return String
     */
    public function __toString() {
        return $this->format('Y-m-d H:i');
    }

    /**
     * Restituisce il nome del mese della data
     * @return string
     */
    public function getMonth() {
    	return Helper::getMonthName(intval($this->format('m')));
    }
    
    /**
     * Restituisce l'anno della data
     */
    public function getYear() {
    	return $this->format('Y');
    }
    
    /**
     * Ritorna la differenza tra $this and $now
     *
     * @param Datetime|String $now
     * @return DateInterval
     */
    public function diff($now='NOW', $absolute = false) {
        if(!($now instanceOf DateTime)) {
            $now = new DateTime($now);
        }
        return parent::diff($now, $absolute);
    }

    /**
     * Ritorna la differenza in anni
     *
     * @param Datetime|String $now
     * @return Integer
     */
    public function getAge($now = 'NOW') {
        return $this->diff($now)->format('%y');
    }
    
    /**
     * Ritorna la differenza in mesi
     * data parametro - data oggetto
     * Il risultato e' negativo se la data nel parametro e' precedente 
     * alla data dell'oggetto e il parametro $absolute = false
     * @param Datetime|String $date La data con cui fare la differenza con la data dell'oggetto
     * @return Integer
     */
    public function getMonthsDiff($date = 'NOW', $absolute=false) {
    	$diff = $this->diff($date, $absolute);
    	$sign = $diff->invert == 0 ? 1 : -1;
    	return $sign * $diff->format('%m');	
    }
    
    /**
     * Ritorna la differenza in minuti fino a $now con frazioni decimali per i secondi
     * @param $now Il tempo attuale
     */
    public function getMinutesDiff($now = 'NOW') {
    	$diff = $this->diff($now);
    	$minutes = $diff->days * 24 * 60;
    	$minutes += $diff->h * 60;
    	$minutes += $diff->i;
    	$minutes += $diff->s / 60;
    	return $minutes;
    }

}

?>
