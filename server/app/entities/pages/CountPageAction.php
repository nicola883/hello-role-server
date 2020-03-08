<?php


/**
 * @SWG\Operation(
 *   partial="crud/create",
 *   method="POST",
 *   summary="Crea un nuovo elemento",
 *   nickname="partial"
 * )
 */
class CountPageAction extends EntityCollectionAction
{
	
	const METHOD = 'get';

	const params = 'found, nroles';
	
	/**
	 * (non-PHPdoc)
	 * @see EntityCollectionAction::exec()
	 */
	public function exec($params, EntityCollection $entityCollection, $data=null) {
        $s = Factory::createServer();
        if (!isset($params['found'])) {
            $params['found'] = 0;
        }
        if (!isset($params['nroles'])) {
            $params['nroles'] = 0;
        }
        $collection = $entityCollection->getCollectionName();
	    $table = $s->getTable($collection);
	    
		$selected = $s->getItem($table, null, false, array(
		    'query' => "select count(id) selected_pages from $table where deleted is false and found >= :found and nroles >= :nroles", 'params' => $params), true);

		$total = $s->getItem($collection, null, false, array('query' => "select count(id) total_pages from $collection where deleted is false", 'params' => null), true);
		
		return json_encode(array('selected_pages' => $selected['selected_pages'], 'total_pages' => $total['total_pages']));
	}
	
	
}




?>