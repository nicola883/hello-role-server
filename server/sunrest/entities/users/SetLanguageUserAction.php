<?php


/**
 * Aggiorna la lingua dell'interfaccia dell'utente
 */
class SetLanguageUserAction extends EntityAction
{
	
	const METHOD = 'put';
	
	public function exec($params, Entity $entity, $data=null) {
		
		$server = $this->getServer();

		// Ritorna se non c'e' la lingua settata o se questa non e' nel formato ISO tipo it_IT
		if (!isset($data['language']) || !strpos($data['language'], '_')) {
			header(BAD_REQUEST);			
			return null;
		}
		
		// leggo le impostazioni generali dell'utente
		// la tabella user_settings ha user_id univoco, allora il restrict avra' solo una riga, quella dell'utente.
		$userSetting = array();
		// estraggo la riga e se non c'e' la creo
		$us = $server->getList('user_settings', false, true, null, true);
		if (empty($us)) {
			// L'id e' quello dell'utente settato dal costruttore. Se non e' l'utente connesso
			// i filtri di sicurezza di ingresso hanno fatto restituire 404
			$userSetting['user_id'] = $entity->getId();
			$userSetting['id'] =  $server->create('user_settings', array('user_id'=> $userSetting['user_id']), true);
		} else
			$userSetting = $us[0];
		
		// Salvo la lingua
		$userSetting['general']['language'] = $data['language'];
		// la riga e' stata comunque creata sopra
		$server->update('user_settings', $userSetting, $userSetting['id'], true);		
		
		return $server->getItem($entity->getCollectionName(), $entity->getId());		
		
	}
}



?>