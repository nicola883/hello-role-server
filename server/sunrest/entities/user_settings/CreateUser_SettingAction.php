<?php


/**
 * @SWG\Operation(
 *   partial="crud/create",
 *   method="POST",
 *   summary="Crea un nuovo elemento",
 *   nickname="partial"
 * )
 */
class CreateUser_SettingAction extends EntityCollectionAction
{
	
	const METHOD = 'post';

	
	/**
	 * (non-PHPdoc)
	 * @see EntityCollectionAction::exec()
	 */
	public function exec($params, EntityCollection $entityCollection, $data=null) {
		$collection = $entityCollection->getCollectionName();
		$id = $this->getServer()->create($collection, $data, true);
		
		// Hook
		$this->id = $id;
		Monitor::get()->update('user_setting_updated', $this);
		
		header(CREATED);
		return $this->getServer()->getItem($collection, $id);
	}
	
	/**
	 * Restituisce l'id del record delle impostazioni dell'utente
	 */
	public function getId() {
		return $this->id;
	}	
	
	
}




?>