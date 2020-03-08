<?php 


/** 
 * Definisco il ruolo dei gruppi: associo a ogni gruppo e posizione un ruolo.
 * Ho introdotto alcune variabili:
 * {uid} = l'id dell'utente connesso. {uid} prende il valore dell'utente connesso
 * * = qualunque numero intero. Qualunque numero dell'uri data fa match
 * {nome_variabile} = prende il valore o la lista di valori assegnati in Authorization a una variabile.
 * 	Ad esempio se è assegnato array('nome_variabile' => array(1, 10, 25)) ed e' /resource/plants/{nome_variabile}
 * 	sara' come:
 * 	/resource/plants/1
 *  /resource/plants/10
 *  /resource/plants/25
 * Ricordati che /resource/users/3/plants sono tutti gli impianti dell'utente 3, pertanto non e' necessario
 * limitare la visione degli impianti a quelli dell'utente: esistono solo i suoi, stai chiedendo con quell'uri solo i suoi
 * se chiedi /resource/users/3/plants/2 e 2 non e' un suo impianto e' lecito rispndere con resource not found anziche' limitare
 * l'accesso.
 * / significa che fa match con /qualunque_cosa, ma non con / da solo, esempio:
 * se location = /, uri = / non fa match, uri = /pippo lo fa, analogamente se location /resource/users/1/, uri = /resource/users/1/ non fa match,
 * uri = /resource/users/1/pippo lo fa.
 *   
 */

$groups = '{
		"a-member-121": [
			{"location":"/", "role":"member"},
			{"location":"/resource/", "role": "none"},
			{"location":"/resource/products", "role":"member"},
			{"location":"/resource/users/{uid}", "role":"editor"},
			{"location":"/resource/users/{uid}/", "role":"member"},
			{"location":"/resource/users/{uid}/plants/*?report", "role":"none"},
			{"location":"/resource/users/{uid}/carts", "role":"editor"}
		],
		"a-subscriber-121": [
			{"location":"/", "role":"member"},
			{"location":"/resource", "role":"none"},
			{"location":"/resource/users/{uid}", "role":"editor"},
			{"location":"/resource/users/{uid}/", "role":"member"},
			{"location":"/resource/users/{uid}/carts", "role":"editor"}
		],
		"a-member-pro": [
			{"location":"/", "role":"member"},
			{"location":"/resource", "role":"none"},
			{"location":"/resource/users/{uid}", "role":"editor"},
			{"location":"/resource/users/{uid}/", "role":"member"},
			{"location":"/resource/users/{uid}/plants", "role":"none"},
			{"location":"/resource/users/{uid}/plants?summary", "role":"member"},
			{"location":"/resource/users/{uid}/carts", "role":"editor"}
		],
		"a-subscriber-pro": [
			{"location":"/", "role":"member"},
			{"location":"/resource", "role":"none"},
			{"location":"/resource/products", "role":"member"},
			{"location":"/resource/users/{uid}", "role":"editor"},
			{"location":"/resource/users/{uid}/", "role":"member"},
			{"location":"/resource/users/{uid}/plants", "role":"none"},
			{"location":"/resource/users/{uid}/plants?summary", "role":"member"},
			{"location":"/resource/users/{uid}/plants?info", "role":"member"},
			{"location":"/resource/users/{uid}/plants/{plants_subscribed_id}", "role":"member"},
			{"location":"/resource/users/{uid}/carts", "role":"editor"}			
		],
		"a-subscriber-test": [
			{"location":"/", "role":"member"},
			{"location":"/resource", "role":"none"},
			{"location":"/resource/products", "role":"member"},
			{"location":"/resource/users/{uid}", "role":"editor"},
			{"location":"/resource/users/{uid}/", "role":"member"},
			{"location":"/resource/users/{uid}/plants", "role":"none"},
			{"location":"/resource/users/{uid}/plants?summary", "role":"member"},
			{"location":"/resource/users/{uid}/plants?info", "role":"member"},		
			{"location":"/resource/users/{uid}/plants/*", "role":"member"},
			{"location":"/resource/users/{uid}/carts", "role":"editor"}		
		]
}';

?>