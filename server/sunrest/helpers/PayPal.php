<?php

/**
 * 
 * @author nicola
 *
 * Implementa il pagamento con PayPal Express Checkout.
 * Procedura: 
 * 1) set() ->
 * 2) redirect al sito paypal all'url data da set() e il token -> 
 * 3) operazioni dell'utente nel sito PayPal -> 
 * 4) ritorno al nostro sito
 * 5) nostra richiesta al sito PayPal con il metodo getDetails dei dati del pagamento da completare ->
 * 6) conferma del pagamento con pay()
 */
/**
 * @SWG\Model(
 * 	id="urlForPayPal",
 * 	@SWG\Property(
 * 		name="confirmUrl",
 * 		type="string"
 *  ),
 *  @SWG\Property(
 *  	name="cancelUrl",
 *  	type="string"
 *  )
 * )
 */
class PayPal {
	
	// True per usare la sandbox
	const SANDBOX = DEV_ENV;
	
	private $sandboxPayPalAccess = array (
			'user' => "",
			'password' => "",
			'api_version' => "117.0",
			'signature' => "",
			'client_url' => "",
			'end_point' => "https://api-3t.sandbox.paypal.com/nvp"
	);
	
	private $payPalAccess = array (
			'user' => "",
			'password' => "",
			'api_version' => "117.0",
			'signature' => "",
			'client_url' => "",
			'end_point' => "https://api-3t.paypal.com/nvp"		
	);
	
	// restituisce i parametri PayPal
	private function getPayPalAccess() {
		if (self::SANDBOX)
			return $this->sandboxPayPalAccess;
		else
			return $this->payPalAccess;
	}
	
	/**
	 * Setta un pagamento con PayPal. E' la prima chiamata a PayPal secondo la procedura
	 * Express Checkout. Inviato importo, id del pagamento, url per errore o di conferma, restituisce
	 * un token che identifica la richiesta di pagamento e che verra' usato per comporre l'url di chiamata al sito
	 * PayPal e la conferma del pagamento.
	 * @param array $paymentRecord
	 * @param array $orderRecord
	 * @param array $urls Un array associativo che contiene il campo 'confirmUrl' (l'url relativo della pagina
	 * 	dove paypal deve redirigere al termine dell'inserimento delle credenziali) 
	 * 	e 'cancelUrl' (l'url relativo della pagina per tornare se l'utente annulla il pagamento"
	 * @return string Un json con "url", l'url del sito di payPal a cui redirigere il browser per il pagamento
	 */
	public function set($paymentRecord, $orderRecord, $urls, $paymentId) {
		// Calcolo l'url del client
		$clientUrl = Helper::getClientUrl();
		$returnUrl = "$clientUrl{$urls['confirmUrl']}";
		$cancelUrl = "$clientUrl{$urls['cancelUrl']}";
		
		$res = $this->setExpressCheckout($paymentRecord['amount'], $returnUrl, $cancelUrl, $paymentId);
		$access = $this->getPayPalAccess();
		$purl = $access['client_url'];
		$url = $purl . $res['TOKEN'];
		return "{\"id\":\"1\", \"token\":\"{$res['TOKEN']}\", \"url\":\"{$url}\"}";
	}
	
	/**
	 * Restituisce i dettagli del pagamento che sta per essere confermato dal cliente
	 * @param string $token
	 * @return array Un array associativo con i parametri recuperati da PayPal
	 */
	public function getDetails($token) {
		return $this->getExpressCheckoutDetails($_GET['token']);
	}
	
	/**
	 * Conferma il pagamento a Paypal e restituisce l'id del record del pagamento
	 * @param integer $paymentId L'id del record del pagamento
	 * @param Array $data L'array di PayPal restituito da getExpressCheckoutDetails (recuperato con getDetails())
	 * @return boolean|integer
	 */
	public function pay($paymentId, $data) {
		
		$ret = $this->doExpressCheckoutPayment($data['token'], $data['details']['AMT'], $data['details']['PAYERID'], $data['details']['CUSTOM']);
		
		// TODO Gestisci gli errori comunicandoli al client
		if (!isset($ret['PAYMENTSTATUS']) || $ret['PAYMENTSTATUS'] != 'Completed') {
			//$ret = array("error" => "Impossibile eseguire il pagamento: {$data['L_SHORTMESSAGE0']}");
			//return json_encode($ret);
			return false;
		} else {
			return $paymentId;
		}
	}

	private function doExpressCheckoutPayment($token, $amount, $payer_id, $custom) {
		return $this->paypal_call("DoExpressCheckoutPayment",
				array(
						"TOKEN" => $token,
						"PAYERID" => $payer_id,
						"PAYMENTACTION" => "Sale",
						"AMT" => $amount,
						"CURRENCYCODE" => 'EUR',
						"CUSTOM" => $custom,
						"DESC" => 'Ordine'		
				)
		);
	}	
	
	private function setExpressCheckout($amount, $return_url, $cancel_url, $paymentId) {
		return $this->paypal_call("SetExpressCheckout",
			array(
					"AMT" => $amount,
					"RETURNURL" => $return_url,
					"CANCELURL" => $cancel_url,
					"CUSTOM" => $paymentId,
					"CURRENCYCODE" => 'EUR',
					"DESC" => 'Ordine',
					"HDBACKCOLOR" => 'F6F6F6',
					"HDRIMG" => 'https://myhost.com/images/logo.png',
					"LANDINGPAGE" => 'Billing',
					"LOCALECODE" => 'IT',
					"PAYFLOWCOLOR" => 'F6F6F6',
					"CARTBORDERCOLOR" => 'F6F6F6',
					"L_BILLINGAGREEMENTDESCRIPTION0" => 'Pagamento'
			)
		);
	}
	
	private function getExpressCheckoutDetails($token) {
		return $this->paypal_call("GetExpressCheckoutDetails",
				array(
						"TOKEN" => $token
				)
		);
	}	
	
	/**
	 * Esegue una chiamata a PayPal. Il parametro è una map
	 * con i parametri da passare.
	 *
	 * $method è la funzione api da chiamare
	 * $data contiene i parametri di chiamata
	 */
	private function paypal_call($method, $data) {
		$payload = "METHOD=". urlencode($method) . "&";
	
		foreach ($data as $key => $value) {
			$payload .= $key .
			'=' .
			urlencode($value) .
			"&";
		}
	
		return $this->deformatNVP($this->_paypal_call($payload));
	}	
	
	/**
	 * Esegue una chiamata a PayPal passando la stringa
	 * di payload richiesta
	 */
	private function _paypal_call($payload) {
		$access = $this->getPayPalAccess();
		
		$pp_user = $access['user'];
		$pp_pwd = $access['password'];
		$pp_api_version = $access['api_version'];
		$pp_signature = $access['signature'];
			
		$payload =
		"USER=" . urlencode($pp_user) . "&" .
		"PWD=". urlencode($pp_pwd) . "&" .
		"VERSION=". urlencode($pp_api_version) . "&" .
		"SIGNATURE=". urlencode($pp_signature) . "&" .
		$payload;
	
		return $this->http_call(
				$access['end_point'],
				//"https://api-3t.sandbox.paypal.com/nvp", // Sandbox
				//"https://api-3t.paypal.com/nvp", // produzione
				$payload);
	}
	
	private function deformatNVP($text) {
		$intial = 0;
		$result = array();
	
		while(strlen($text) != 0) {
			$keypos = strpos($text,'=');
			$valuepos = strpos($text,'&') ?
			strpos($text,'&') :
			strlen($text);
	
			$keyval = substr($text, $intial, $keypos);
			$valval = substr($text,
					$keypos + 1,
					$valuepos - $keypos - 1);
			$result[urldecode($keyval)] = urldecode( $valval);
			$text = substr($text, $valuepos + 1, strlen($text));
		}
	
		return $result;
	}
	
	private function curl_call($endpoint, $payload) {
		$ch = curl_init();
		
		// per test locale
		//curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);
	
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	
		$response = curl_exec($ch);
		if( curl_errno($ch) != 0 ) {
			$response = curl_errno($ch) . ":" . curl_error($ch);
		}
		curl_close($ch);
	
		return $response;
	}
	
	/**
	 * Esegue una chiamata HTTP passando un payload
	 */
	private function http_call($endpoint, $payload) {
		//return file_post_contents($endpoint.'?'.$payload);
		return $this->curl_call($endpoint, $payload);
	}	
	
}




?>