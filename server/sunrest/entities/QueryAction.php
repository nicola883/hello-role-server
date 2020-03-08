<?php

class QueryAction extends EntityCollectionAction
{
	
	const METHOD = 'get';
	
	public function exec($params, EntityCollection $entity, $data=null) {
		return $this->getServer()->getList($entity->getCollectionName(), $params, false);
	}
	
}

?>