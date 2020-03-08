<?php 

/**
 * Funzioni e costanti per calcolare il gruppo dell'utente connesso.
 * Da implementare per ogni applicazione.
 */


// Un'associazione gruppi / nome gruppi nell'applicazione 
const appGroups = '[
		{
			"customer_group_id":1,
			"profile":1,
			"has_orders":false,
			"group":"a-member-121"
		},
		{
			"customer_group_id":1,
			"profile":1,
			"has_orders":true,
			"group":"a-subscriber-121"
		},
		{
			"customer_group_id":1,
			"profile":2,
			"has_orders":true,
			"group":"a-subscriber-121"
		},		
		{
			"customer_group_id":4,
			"profile":1,
			"has_orders":false,
			"group":"a-member-pro"
		},
		{
			"customer_group_id":4,
			"profile":1,
			"has_orders":true,
			"group":"a-subscriber-pro"
		},
		{
			"customer_group_id":4,
			"profile":2,
			"has_orders":true,
			"group":"a-subscriber-test"
		}
		
]';

/**
 * Restituisce il gruppo.
 * È una funzione da implementare che dipende da ogni applicazione. Fa i conti
 * che vuole usando Server e restituisce il gruppo dell'utente connesso.
 * @param Server $server
 * @return string Il nome del gruppo
 */
$getAppGroup = function (Server $server) {
	
	// Se non e' loggato ritorno null perche' non posso assegnare alcun gruppo
	// Verra' dato poi il ruolo anonymous
	$c = $server->getCurrentUser(true);

	if ($c === null)
		return null;
	
	$appGroups = Utility::jsonDecodeSafe(appGroups);
	
	if ($appGroups === null)
		return false;

	foreach ($appGroups as $appGroup) {
		if ($c['customer_group_id'] == $appGroup['customer_group_id'] && $c['profile'] == $appGroup['profile'])
			return $appGroup['group'];
	}
	
	return false;
};


$getAppParams = function($server) {
	
	$params = array();
	
	// Se non e' loggato ritorno null perche' non posso ragionare su nessun gruppo
	// Verra' dato poi il ruolo anonymous
	$c = $server->getCurrentUser(true);
	if ($c === null)
		return null;	
	
	$params['uid'] = $c['id'];
	
	$orders = $server->getList('orders', false, true, null, $associative=true);
	foreach ($orders as $order) {
		foreach($order['items'] as $item) {
			if (isset($item['product']['options']['plant']['id']))
				$params['plants_subscribed_id'][] =$item['product']['options']['plant']['id'];
		}
	}
	
	return $params;
};


?>