<?php


/**
 * Retrives the pages of the ground truth. We should use only pages
 * that have at least two roles defined and all the three landmarks.
 * Therefore parameters should be found = 3 and minroles = 2
 * @author nicola
 *
 */
class QueryPageAction extends EntityCollectionAction
{
	
	const METHOD = 'get';
	
	const params = 'found, nroles, url';
	
	public function exec($params, EntityCollection $entity, $data=null) {
	    $s = Factory::createServer();
	    // If url is set, filtering found and roles with >= is not requested.
	    if (isset($params['url'])) {
	        return $s->getList($entity->getCollectionName(), $params, false);
	    }
	    
	    
	    if (!isset($params['found'])) {
	        $params['found'] = 0;
	    }
	    if (!isset($params['nroles'])) {
	        $params['nroles'] = 0;
	    }
	    $table = $s->getTable($entity->getCollectionName());
	    $query = "select * from $table";
		return $s->getList($table, null, false, array('query' => $query, 'params' => null));
	}
	
}

?>