<?php 


class OpenSsl {

	private static $instance;	
	
	private $certificate;
	private $privateKey;
	private $dn = array();
	private $x509 = array();
	private $sigheader = "\n-----BEGIN OpenSsl.php SIGNATURE-----\n";
	private $sigfooter = "-----END OpenSsl.php SIGNATURE-----\n";

	
	private function __construct(){}
	
	/**
	 * Restituisce l'istanza della classe
	 * @return OpenSsl, l'istanza di OpenSsl
	 */
	public static function get() {
		if (empty(self::$instance)) {
			self::$instance = new OpenSsl();
			
			self::get()->setCertificate(CERTIFICATE);
		}
		return self::$instance;
	}	
	
	
	/**
	 * Crea una nuova coppia di chiavi e le carica in $this->certificate
	 * e in $this->privateKey
	 */
	public function makeKeys($distinguishedName, $passphrase = NULL) {
		$this->dn = $distinguishedName;
		
		// genero la chiave pem-encoded
		$config = array(
					'digest_alg' => 'sha1',
					'private_key_bits' => 2048,
					'encrypt_key' => TRUE
				);
		$key = openssl_pkey_new($config);
		// genero la richiesta del certificato
		$csr = openssl_csr_new($this->dn, $key, $config);
		// Faccio un certificato self-signed
		$cert = openssl_csr_sign($csr, NULL, $key, 365, $config, time());
		
		// esporto le chiavi
		openssl_pkey_export($key, $this->privateKey, $passphrase, $config);
		openssl_x509_export($cert, $this->certificate);
		
		// parso il certificato
		$this->x509 = openssl_x509_parse($cert);
		
		return true;
	}
	
	/**
	 * Restituisce la chiave privata
	 */
	public function getPrivateKey() {
		return $this->privateKey;
	}

	/**
	 * Setta la chiave privata
	 */
	public function setPrivateKey($key) {
		$this->privateKey = $key;
	}
	
	/**
	 * Restituisce il certificato
	 */
	public function getCertificate() {
		return $this->certificate;
	}
	
	/**
	 * Setta il certificato
	 * @param string $cert
	 */
	public function setCertificate($certificate) {
		$this->certificate = $certificate;
		// TODO deve settare x509
	}
	
	/**
	 * Usa $this->certificate per codificare con RSA
	 * la stringa data 
	 * @param string $string Una stringa di massimo 54 caratteri
	 * @return boolean|string La stringa codificata, false se errore
	 */
	public function encrypt($string) {
		if (empty($string))
			return false;
		
		if (strlen($string) > 56)
			return false;
		
		$cert = openssl_get_publickey($this->certificate);
		openssl_public_encrypt($string, $out, $cert);
		openssl_free_key($cert);
		
		// codifico base 64
		$out = chunk_split(base64_encode($out), 64);
		
		return $out;
	}
	
	public function decrypt($string, $passphrase=null) {
		if (empty($this->privateKey))
			return false;
		
		$string = base64_decode($string);
		
		// Prendo la chiave e creo la risorsa
		$key = openssl_get_privatekey($this->privateKey, $passphrase);
		
		// Decodifico
		openssl_private_decrypt($string, $out, $key);
		
		openssl_free_key($key);
		
		return $out;
	}
	
	/**
	 * Firma una stringa con la chiave pubblica
	 */
	public function sign($string, $passphrase=null) {
		if (empty($this->privateKey))
			return false;
		$key = openssl_get_privatekey($this->privateKey, $passphrase);
		
		$signature = null;
		openssl_sign($string, $signature, $key);
		openssl_free_key($key);
		
		$signature = chunk_split(base64_encode($signature), 64);
		
		$signedString = $string . $this->sigheader . $signature . $this->sigfooter;
		
		return $signedString;
		
	}
	
	/**
	 * Usa la chiave per verificare una firma fatta con il certificato
	 *  
	 */
	public function verify($signedString) {
		if (empty($this->privateKey))
			return false;
		
		// Estrai la firma
		$sigpos = strpos($signedString, $this->sigheader);
		if ($sigpos === false)
			return false;
		
		$signature = substr($signedString, ($sigpos + strlen($this->sigheader)), (0 - strlen($this->sigfooter)));
		$string = substr($signedString, 0, $sigpos);
		
		// decodifica la firma
		$signature = base64_decode($signature);
		
		$cert = openssl_get_publickey($this->certificate);
		
		// verifica la firma
		$success = openssl_verify($string, $signature, $cert);
		
		openssl_free_key($cert);
		
		if ($success)
			return $string;
		
		return false;
	}
	
	/**
	 * Restituisce il Common Name
	 */
	public function getCommonName() {
		if (isset($this->x509['subject']['CN']))
			return $this->x509['subject']['CN'];	
		return null;
	}
	
	/**
	 * Restituisce i dettagli del certificato
	 */
	public function getDN() {
		if (isset($this->x509['subject']))
			return $this->x509['subject'];
		return null;
	}
	
	/**
	 * Restiturisce il nome comune di chi ha emesso il certificato
	 */
	public function getCACommonName() {
		if (isset($this->x509['issuer']['CN']))
			return $this->x509['issuer']['CN'];
		return null;
	}
	
	/**
	 * Restituisce tutti i dettagli di chi ha emesso il certificato
	 */
	public function getCA() {
		if (isset($this->x509['issuer']))
			return $this->x509['issuer'];
		return null;
	}
	
	/*
	public function setX509() {
		openssl_x509_export($cert, $this->certificate);
		var_dump($cert);
		$this->x509 = openssl_x509_parse($cert);
		openssl_free_key($cert);
	}
	*/
	
		
	
}




?>