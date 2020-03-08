<?php


/**
 * Cancella una entita'
 */

class DeleteAction extends EntityAction
{
	
	const METHOD = 'delete';

	public function exec($params, Entity $entity, $data=null) {
		$server = $entity->getServer();
		
		// L'id e' settato dal costruttore
		$id = $entity->getId();
		
		// Faccio qualcosa e ritorno il valore
		return $server->delete($entity->getCollectionName(), $id);
	}
}

?>