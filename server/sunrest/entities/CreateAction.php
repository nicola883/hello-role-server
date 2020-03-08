<?php


/**
 * @SWG\Operation(
 *   partial="crud/create",
 *   method="POST",
 *   summary="Crea un nuovo elemento",
 *   nickname="partial"
 * )
 */
class CreateAction extends EntityCollectionAction
{
	
	const METHOD = 'post';

	
	/**
	 * (non-PHPdoc)
	 * @see EntityCollectionAction::exec()
	 */
	public function exec($params, EntityCollection $entityCollection, $data=null) {
		
		$collection = $entityCollection->getCollectionName();
		$id = $this->getServer()->create($collection, $data, true);
		header(CREATED);
		return $this->getServer()->getItem($collection, $id);
	}
	
	
}




?>