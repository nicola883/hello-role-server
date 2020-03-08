<?php


/**
 * Aggiorna le impostazioni utente
 * Necessario farlo diverso dal default per fare l'update del Monitor (per aggiornare il sommario degli impianti)
 */
class UpdateUser_SettingAction extends EntityAction
{
	
	const METHOD = 'put';
	
	private $id;
	
	public function exec($params, Entity $entity, $data=null) {

		$server = $entity->getServer();
		
		// L'id e' settato dal costruttore
		$id = $entity->getId();
		
		// Aggiorno e restituisco il record
		$server->update($entity->getCollectionName(), $data, $id, true);
		
		// aggiornate le impostazioni utente
		$this->id = $id;
		// Hook
		Monitor::get()->update('user_setting_updated', $this);
		
		
		return $server->getItem($entity->getCollectionName(), $id);		
		
	}
	
	/**
	 * Restituisce l'id del record delle impostazioni dell'utente
	 */
	public function getId() {
		return $this->id;
	}
}



?>