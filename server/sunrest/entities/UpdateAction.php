<?php


/**
 * Aggiorna una entita'
 */

/**
 * @SWG\Operation(
 * 	partial="crud/update",
 * 	method="PUT",
 * 	summary="Aggiorna l'elemento che ha l'id dato",
 * 	nickname="partial"
 * )
 */
class UpdateAction extends EntityAction
{
	
	const METHOD = 'put';

	public function exec($params, Entity $entity, $data=null) {
		
		$server = $entity->getServer();
		
		// L'id e' settato dal costruttore
		$id = $entity->getId();
		
		// Aggiorno e restituisco il record
		$server->update($entity->getCollectionName(), $data, $id, true);
		
		return $server->getItem($entity->getCollectionName(), $id);
	}
	
	
}

?>