<?php


/**
* Valutazione dell'energia raggiante ricevuta 
* Procedura indicata dalla norma tecnica UNI 8477
* Le funzioni pubbliche accettano e restituiscono angoli in gradi, quelle private
* tipicamente in radianti.
*/
class SunEnergy

{
	/** 
	* Costante solare
	* Radianza su una superficie estratmosferica perpendicolare ai raggi solari in W/m2
	*/
	const COSTANTE_SOLARE = 1353;
	
	/**
	* Valori per i quali i risultati corrisponderanno con buona approssimazione ai
	* valori medi dei singoli mesi.
	* 'medio' e' il giorno medio del mese, 'anno' il numero del giorno medio nell'anno
	*/
	private $giorniAnno = array(
			1 => array('medio' => 17, 'anno' => 17),
			2 => array('medio' => 16, 'anno' => 47),
			3 => array('medio' => 16, 'anno' => 75),
			4 => array('medio' => 15, 'anno' => 105),
			5 => array('medio' => 15, 'anno' => 135),
			6 => array('medio' => 11, 'anno' => 162),
			7 => array('medio' => 17, 'anno' => 198),
			8 => array('medio' => 16, 'anno' => 228),
			9 => array('medio' => 15, 'anno' => 258),
			10 => array('medio' => 15, 'anno' => 288),
			11 => array('medio' => 14, 'anno' => 318),
			12 => array('medio' => 10, 'anno' => 344)
		);							


	function __construct() {

	}
	
	/**
	* Restituisce l'irraggiamento in un giorno su una superficie comunque inclinata e orientata.
	* @param $irraggiamentoOrizzontale Energia integrale giornaliera della radianza solare globale
	* sul piano orizzontale 
	* @param $latitudine Latitudine in gradi ove si trova la superficie
	* @param $azimuth Azimuth in gradi della superficie. L'angolo formato dalla normale alla
	* superficie e dal piano meridiano del luogo; e' misurato positivamente da sud verso ovest
	* @param $inclinazione Angolo che la superficie forma con l'orizzonte; e' misurato positivamente 
	* dal piano orizzontale verso l'alto
	* @param $riflettanza Valore di riflettanza (Albedo)
	* @param $giorno Il giorno di cui si vuole conoscere l'irraggiamento
	* @return Energia su una superficie nel periodo di un giorno in MJ/m2 * giorno
	*/ 
	public function irraggiamentoGiornaliero($irraggiamentoOrizzontale, $latitudine, $azimuth, $inclinazione, $riflettanza, $giorno) {
		if ($giorno < 1 || $giorno > 366)
			return null;
		
		// Qualche furbo mette l'inclinazione negativa. Non puo' essere (il modulo sarebbe girato al contrario)
		// metto allora il valore assoluto. Chiaro che e' sintomo di errore sbagliato: lasciamo l'utente accorgersi e può correggere
		$inclinazione = abs($inclinazione);
		$G0 = self::COSTANTE_SOLARE;
		$phi = deg2rad($latitudine);
		$gamma = deg2rad($azimuth);
		$beta = deg2rad($inclinazione);	
		$rho = $riflettanza;		
		$Hh = $irraggiamentoOrizzontale;	
		$terraSole = $this->rapportoDistanzaTerraSole($giorno);
		$declinazione = $this->declinazione(null, $giorno);
		$angoloTramonto = $this->angoloTramonto($declinazione, $latitudine);
		$angoliSole = $this->angoliSole($declinazione, $latitudine, $azimuth, $inclinazione, $angoloTramonto);		
		
		// Irraggiamento estratmosferico
		$Hh0 = $this->Hh0($declinazione, $latitudine, $terraSole, $angoloTramonto);

		// Indice di soleggiamento reale. Rapporto fra l'irraggiamento solare globale misurato al
		// suolo su una superficie orizzontale ed il corrispondente valore al limite dell'atmosfera
		$Kt = $Hh / $Hh0;

		// Irraggiamento solare diretto sul piano orizzontale
		$Hbh = $this->Hbh($declinazione, $latitudine, $angoloTramonto);
		
		// Irraggiamento solare diretto sul piano della superfice
		$Hb = $this->Hb($declinazione, $latitudine, $azimuth, $inclinazione, $angoliSole['apparire'], $angoliSole['scomparire']);
		
		// Rapporto di irraggiamento diretto
		$Rb = $Hb / $Hbh;
		
		// Rapporto Hd / Hh, irraggiamento solare diffuso su irraggiamento solare globale orizzontale
		// Come indicato nella norma. Ci sono anche altri metodi ad es. Liu - Jordan che potrebbero 
		// stimare meglio questo rapporto
		$rapportoDiffusa = 0.881 - 0.972 * $Kt;
		
		// Rapporto con energia sul piano orizzontale t.c. energia sulla superficie = $R * $Hh0
		$R = (1 - $rapportoDiffusa) * $Rb + $rapportoDiffusa*((1 + cos($beta))/2)+$rho*((1 - cos($beta))/2);
		
		return $R * $Hh;
	
	}
	
	
	/**
	* Restituisce la radiazione giornaliera media del mese su una superficie comunque inclinata e orientata.
	* @param $irraggiamentoOrizzontale Energia integrale giornaliera della radianza solare globale
	* sul piano orizzontale 
	* @param $latitudine Latitudine in gradi ove si trova la superficie
	* @param $azimuth Azimuth in gradi della superficie. L'angolo formato dalla normale alla
	* superficie e dal piano meridiano del luogo; e' misurato positivamente da sud verso ovest
	* @param $inclinazione Angolo che la superficie forma con l'orizzonte; e' misurato positivamente 
	* dal piano orizzontale verso l'alto
	* @param $riflettanza Valore di riflettanza (Albedo)
	* @param $mese Il mese per il quale si vuole conoscere l'irraggiamento la radiazione giornaliera media
	* @param $anno Opzionale, l'anno del mese dato, per considerare gli anni bisestili
	* @return Energia su una superficie nel periodo di un giorno in MJ/m2
	*/ 
	public function irraggiamentoGiornalieroMensile($irraggiamentoOrizzontale, $latitudine, $azimuth, $inclinazione, $riflettanza, $mese, $anno=2014) {
		if ($mese < 1 || $mese > 12)
			return null;
		$giorno = $this->giornoMedio($mese);
		return $this->irraggiamentoGiornaliero($irraggiamentoOrizzontale, $latitudine, $azimuth, $inclinazione, $riflettanza, $giorno);
	}
	
	/**
	* Calcola l'irraggiamento solare diretto sul piano della superficie Hb.
	* Formula 6 della norma
	* @param $declinazione La declinazione in gradi
	* @param $latitudine La latitudine in gradi
	* @param $azimuth L'azimuth della superficie in gradi
	* @param $inclinazione L'inclinazione della superficie in gradi
	* @param $apparire L'angolo in gradi dell'apparire del sole sul piano dei moduli
	* @param $scomparire L'angolo in gradi dello scomparire del sole sul piano dei moduli
	* @return L'energia irraggiata sul piano dei moduli in un giorno in MJ/m2 * giorno
	*/
	public function Hb($declinazione, $latitudine, $azimuth, $inclinazione, $apparire, $scomparire) {
		$G0 = self::COSTANTE_SOLARE;
		$delta = deg2rad($declinazione);
		$phi = deg2rad($latitudine);
		$gamma = deg2rad($azimuth);
		$beta = deg2rad($inclinazione);
		$T = sin($delta)*(sin($phi)*cos($beta)-cos($phi)*sin($beta)*cos($gamma));
		$U = cos($delta)*(cos($phi)*cos($beta)+sin($phi)*sin($beta)*cos($gamma));
		$V = cos($delta)*(sin($beta)*sin($gamma));		
		$omega_primo = $apparire; // la formula lavora in gradi
		$omega_secondo = $scomparire;
		return $G0*($T*M_PI/180*($omega_secondo - $omega_primo) + $U*(sin(deg2rad($omega_secondo)) - sin(deg2rad($omega_primo))) - $V*(cos(deg2rad($omega_secondo)) - cos(deg2rad($omega_primo))));
	}	
	
	/**
	* Restituisce gli angoli dell'apparire e dell scomparire del sole relativamente
	* alla superficie considerata
	* @param $declinazione La declinazione in gradi
	* @param $latitudine La latitudine in gradi
	* @param $azimuth L'azimuth della superficie in gradi
	* @param $inclinazione L'inclinazione della superficie in gradi
	* @param $angoloTramonto L'angolo orario in gradi al tramonto sulla superficie orizzontale
	* @return Un array contenente l'angolo dell'apparire e dello scomparire del sole array('apparire' => valore, 'scomparire' => valore)
	*/
	public function angoliSole($declinazione, $latitudine, $azimuth, $inclinazione, $angoloTramonto) {
		$delta = deg2rad($declinazione);
		$phi = deg2rad($latitudine);
		$gamma = deg2rad($azimuth);
		$beta = deg2rad($inclinazione);
		$omega_s = deg2rad($angoloTramonto);
		$T = sin($delta)*(sin($phi)*cos($beta)-cos($phi)*sin($beta)*cos($gamma));
		$U = cos($delta)*(cos($phi)*cos($beta)+sin($phi)*sin($beta)*cos($gamma));
		$V = cos($delta)*(sin($beta)*sin($gamma));
		
		$radicando = pow($U, 2) + pow($V, 2) - pow($T, 2);
		
		if ($radicando <= 0) {
			if (($T + $U) >= 0) {
				$apparire = -$omega_s;
				$scomparire = $omega_s;
			} else
				// la superficie non e' mai esposta
				$apparire = $scomparire = -$omega_s;
		} else {
			// risolvo l'equazione 8 della norma
			$sol1 = 2 * atan((-$V + sqrt($radicando)) / ($T - $U));
			$sol2 = 2 * atan((-$V - sqrt($radicando)) / ($T - $U));

			// condizione 8a della norma
			$omega_1 = $V*cos($sol1) > $U*sin($sol1) ? $sol1 : $sol2;
			$omega_2 = $V*cos($sol1) <= $U*sin($sol1) ? $sol1 : $sol2;

			// caso radici reali del 3.2 della norma			
			$apparire = -min(abs($omega_1), abs($omega_s));
			$scomparire = min(abs($omega_2), abs($omega_s));
		}
		
		return array('apparire' => rad2deg($apparire), 'scomparire' => rad2deg($scomparire));
			
	}
		

	/**
	* Calcola Hbh, l'irraggiamento solare diretto sul piano orizzontale in un giorno
	* @param $declinazione La declinazione in radianti
	* @param $latitudine La latitudine in radianti
	* @param $angoloTramonto L'angolo orario al tramonto sulla superficie orizzontale
	* @return L'irraggiamento in MJ/m2 * d
	*/
	public function Hbh($declinazione, $latitudine, $angoloTramonto) {
		$G0 = self::COSTANTE_SOLARE;
		$Th = sin(deg2rad($declinazione))*sin(deg2rad($latitudine));
		$Uh = cos(deg2rad($declinazione))*cos(deg2rad($latitudine));
		$omega_s = deg2rad($angoloTramonto);
		return 2*$G0*($Th*M_PI/180*rad2deg($omega_s) + $Uh * sin($omega_s));
	}

	/**
	* Calcola Hh0, l'irraggiamento solare globale estratmosferico misurato su un piano parallelo
	* al piano orizzontale terrestre.
	* @param $declinazione La declinazione in gradi
	* @param $latitudine La latitudine in gradi
	* @param $terraSole Il quadrato del rapporto fra distanza media e distanza al giorno n
	* tra Terra e Sole
	* @param $angoloTramonto L'angolo orario in gradi al tramonto sulla superficie orizzontale	
	* @return L'irraggiamento in MJ/m2 * giorno
	*/
	public function Hh0($declinazione, $latitudine, $terraSole, $angoloTramonto) {
		$G0 = self::COSTANTE_SOLARE;
		$r = $terraSole;
		$Th = sin(deg2rad($declinazione))*sin(deg2rad($latitudine));
		$Uh = cos(deg2rad($declinazione))*cos(deg2rad($latitudine));
		$omega_s = deg2rad($angoloTramonto);
		return 24*3600*pow(10,-6)/M_PI*$G0*$r*($Th*$omega_s+$Uh*sin($omega_s));		
	}

	/**
	* Quadrato del rapporto fra distanza media e distanza al giorno n tra Terra e Sole
	* @param $giorno Il giorno per il quale si vuole calcolare il quadrato del rapporto
	* return Il quadrato del rapporto fra distanza media e distanza tra Terra e Sole al giorno n
	*/
	public function rapportoDistanzaTerraSole($giorno) {
		return 1 + 0.033 * cos(2 * M_PI * $giorno / 365);
	}
	
	/**
	* Calcola l'angolo orario al tramonto sulla superficie orizzontale
	* @param $declinazione La declinazione in gradi
	* @param $latitudine La latitudine in gradi
	* @return L'angolo orario al tramonto in gradi
	*/
	public function angoloTramonto($declinazione, $latitudine) {
		$declinazione = deg2rad($declinazione);
		$latitudine = deg2rad($latitudine);
		return rad2deg(acos(-tan($declinazione) * tan($latitudine)));
	}
	
	/**
	* Dato il mese o il giorno progressivo dell'anno restituisce la declinazione solare:
	* angolo che la retta tracciata dal centro della terra al sole forma con il piano equatoriale.
	* Il valore medio mensile viene calcolato con il giorno medio dello stesso.
	* La varianza della declinazione nel giorno e' trascurabile e pertanto non ne viene tenuto conto.
	* Obbligatorio uno dei due parametri
	* @param $mese Se fornito, indica il mese per cui si richiede la declinazione media
	* @param $giornoAnno Se fornito, indica la declinazione del giorno
	* @return La declinazione in gradi media del mese o del giorno
	*/
	public function declinazione($mese=null, $giornoAnno=null) {
		if ($giornoAnno === null)
			$giornoAnno = $this->giornoMedio($mese);
		return $ret = 23.5 * sin(2 * M_PI * (284 + $giornoAnno) / 365);	
	}
		
	
	/**
	* Restituisce il giorno progressivo dell'anno rappresentativo del mese dato
	* @param $mese Il mese per cui si vuole il giorno rappresentativo
	* @return Il giorno progressivo dell'anno
	*/	
	public function giornoMedio($mese) {
		return $this->giorniAnno[$mese]['anno'];
	}	
	


}


?>
