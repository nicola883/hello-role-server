<?php


/**
 * @SWG\Operation(
 *   partial="crud/create",
 *   method="POST",
 *   summary="Crea un nuovo elemento",
 *   nickname="partial"
 * )
 */
class CreateEvaluationAction extends EntityCollectionAction
{
	
	const METHOD = 'post';

	const PARAMS = 'delete, url';
	
	/**
	 * (non-PHPdoc)
	 * @see EntityCollectionAction::exec()
	 */
	public function exec($params, EntityCollection $entityCollection, $data=null) {

	    $s = $this->getServer();
	    $collection = $entityCollection->getCollectionName();
	    
	    if (isset($params['delete'])) {
    		$query = "DELETE from evaluations WHERE url = :url";
    		$s->dbQuery2($query, array(':url' => $params['url']));
    		return '{"result":"deleted"}';
	    }
		// $id = $s->save($collection, $data, array('tool', 'url', 'role'));
		$id = $s->insert($collection, $data);
		header(CREATED);
		return Factory::createDb()->getItem($collection, $id);
	}
	
	
}




?>