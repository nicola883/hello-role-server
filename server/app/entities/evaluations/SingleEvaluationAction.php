<?php


/**
 * @SWG\Operation(
 *   partial="crud/create",
 *   method="POST",
 *   summary="Crea un nuovo elemento",
 *   nickname="partial"
 * )
 */
class SingleEvaluationAction extends EntityCollectionAction
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
		$p = array(':url' => $data['url'], ':tool' => $data['tool'], ':role' => $data['role']);
	    if (isset($params['delete'])) {
    		$query = "DELETE from evaluations WHERE url = :url AND tool = :tool AND role = :role";
    		$s->dbQuery2($query, $p);
    		return '{"result":"deleted"}';
		}
		$q = "SELECT id from evaluations where url = :url AND tool = :tool AND role = :role";
		$id = $s->dbQuery2($q, $p)[0]['id'] ?? null;
		//$id = $s->save($collection, $data, array('tool', 'url', 'role'));
		if (empty($id)) {
			$id = $s->insert($collection, $data);
		} else {
			$s->update($collection, $data, $id, true);
		}

		header(CREATED);
		return Factory::createDb()->getItem($collection, $id);
	}
}




?>