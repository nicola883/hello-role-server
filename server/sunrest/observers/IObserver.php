<?php 


interface IObserver
{
	
	/**
	 * Setta gli observer.
	 * Contiene tipicamente una serie di chiamate a Monitor::get()->addAction
	 */
	static public function set(); 
	
	
}



?>