<?php


/**
 * Aggiorna l'utente
 * Necessario farlo diverso dal default per vietare di modificare dei campi
 */
class UpdateUserAction extends EntityAction
{
	
	const METHOD = 'put';
	
	public function exec($params, Entity $entity, $data=null) {
		
		unset($data['deleted']);
		unset($data['active']);
		unset($data['type']);
		unset($data['customer_group_id']);
		unset($data['profile']);
		unset($data['privacy']);
		
		$server = $entity->getServer();
		
		// L'id e' settato dal costruttore
		$id = $entity->getId();
		
		if (isset($data['password'])) {
			$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
			$pwdReset = $server->getCurrentUser(true)['pwd_reset'];
			$data['pwd_reset'] = ++$pwdReset;
		}
		
		
		// Aggiorno e restituisco il record
		$server->update($entity->getCollectionName(), $data, $id, true);
		
		$element = $server->getCurrentUser(true);
		
		// Hook
		Monitor::get()->update('user_updated', $this, $element);
		
		return $server->getItem($entity->getCollectionName(), $id);		
		
	}
}



?>