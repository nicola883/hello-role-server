<?php

/**
 * Setta gli observers e li aggiunge al monitor
 */

class SetObservers {
	
	/**
	 * Aggiunge gli observer al monitor chiamando il loro metodo set() che lo fa.
	 * Per ogni observer che vuoi aggiungere al monitor instanzialo e chiama il metodo set().
	 * Questo metodo viene chiamato sempre all'avvio dell'applicazione (da Monitor quando e' istanziato)
	 */
	static function addObservers() {
		
		// Quelli dei plugin
		// Cerca nelle directory plugins/*/observer
		// tutte le classi che implementano IObserver e chiamano il metodo statico ::set()
		$path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR; 
		$dir =  $path . 'plugins' . DIRECTORY_SEPARATOR;
		$plugins = scandir($dir);
		foreach ($plugins as $plugin) {
			$obsDir = "{$dir}$plugin/observers";
			if ($plugin != '.' && $plugin != '..' && is_dir($obsDir)) {
				$observers = scandir($obsDir);
				foreach ($observers as $observerFile) {
					$observer = explode('.', $observerFile)[0];
					if ($observer != '.' && $observer != '..' && is_subclass_of($observer, 'IObserver')) {
						$observer::set();
					}
				}
			}
		}
	}
	
}

?>