<?php


/**
 * @SWG\Operation(
 *   partial="crud/create",
 *   method="POST",
 *   summary="Crea un nuovo elemento",
 *   nickname="partial"
 * )
 */
class CreatePageAction extends EntityCollectionAction
{
	
	const METHOD = 'post';

	
	/**
	 * (non-PHPdoc)
	 * @see EntityCollectionAction::exec()
	 */
	public function exec($params, EntityCollection $entityCollection, $data=null) {
		$collection = $entityCollection->getCollectionName();
		$id = $this->getServer()->save($collection, $data, array('url'));
		header(CREATED);
		return Factory::createDb()->getItem($collection, $id);
	}
	
	
}




?>