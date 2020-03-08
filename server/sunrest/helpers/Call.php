<?php

class Call
{
	// Numbers of time curl will try to make the connection
	const MAX_TRY = 2;
	
	// Timeout
	const TIMEOUT = 120;

	// The access token
	private $token;
	
	// The url to connect
	private $url;
	
	/**
	 * Instatiate a Call object to make connections
	 * @param string $url
	 * @param string $token
	 */
	function __construct($url, $token=null) {
		$this->url = $url;
		$this->token = $token;
	}
	
	
	/**
	 * Setta il metodo http nell'array di configurazione
	 */
	private function setMethod($ch, $method) {
		$method = strtoupper($method);
		switch ($method) {
			case 'GET':
				curl_setopt($ch, CURLOPT_HTTPGET, 1);
				break;
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, 1); // setta un post con codifica application/x-www-form-urlencoded
				break;
			case 'PUT':
				//curl_setopt($ch, CURLOPT_PUT, $method);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				break;
			default:
				curl_setopt($ch, CURLOPT_HCUSTOMREQUEST, $method);
		}
		return $ch;
	}
	
	/**
	 * Get connection
	 * @return string Json data retrived
	 */
	public function get($path) {
		return $this->exec('GET', $path);
	}
	
	/**
	 * Post connection
	 * @param array $content Array content to send
	 * @return NULL|string Json data retrived
	 */
	public function post($path, $content) {
		return $this->exec('POST', $path, $content, $path);
	}
	
	/**
	 * Put connection
	 * @param array $content Array content to send
	 * @return NULL|string Json data retrived
	 */
	public function put($path, $content) {
		return $this->exec('PUT', $path, $content);
	}
	

	
	private function exec($method, $path, $content=null, $json=true) {
	    $content = empty($content) ? null : json_encode($content);

	    $method = strtoupper($method);	
		$headers = array();

		$ch = curl_init();
		
		// Method
		$ch = self::setMethod($ch, $method);
		
		// Options
		$url = rtrim($this->url, '/') . '/' . ltrim($path, '/');
		curl_setopt($ch, CURLOPT_URL, $url);
		if ($method == 'POST' || $method == 'PUT') {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// Max time to make the connection
		$timeout = self::TIMEOUT;
		$curlTimeout = $timeout * 2;
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		// Max time to complete the curl function
		curl_setopt($ch, CURLOPT_TIMEOUT, $curlTimeout);

		// Headers
		if (empty($this->token)) {
			$headers[] = 'x-access-token: abcde';
		} else {
		    $headers[] = 'x-access-token:' . $this->token;
		}

		if ($json) {
			if (empty($content)) {
				$headers[] = 'Content-Type: application/json';
			} else {
				$headers[] = 'Content-Type: application/json';
				$headers[] = 'Content-Length: ' . strlen($content);
			}
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		// Connection
		$r = '';
		$try = 0;
		// Eseguo la richiesta fino a MAX_TRY volte in caso di errore
		// Se l'errore e' un timeout, raddoppio il tempo di attesa a ogni tentativo
		while ((!$r = curl_exec($ch)) && $try < self::MAX_TRY) {
			Utility::log("\nRiprovo perche' ho ottenuto un errore.");
			Utility::log("\nIl valore di timeout e': $timeout");
			// riprovo solo se ho avuto un errore di timeout
			if (curl_errno($ch) != CURLE_OPERATION_TIMEOUTED) {
				Utility::log("\nErrore definitivo diverso da curle_operation_timeouted\n");
				break;
			} else {
				$timeout *= 2;
				$curlTimeout *= 2;
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_TIMEOUT, $curlTimeout);
			}
			$try++;
		}
		if (!$r) {
			Utility::log('Non ha funzionato<br />Errore: ' . htmlentities(curl_error($ch)));
			Utility::log(print_r(curl_getinfo($ch), true));
			return null;
		}

		curl_close($ch);

		if ($json) {
			return json_decode($r, true);
		} else 
			return $r;
	}

}

?>