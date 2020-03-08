<?php



class GetAction extends EntityAction
{
	
	const METHOD = 'get';
	
	public function exec($params, Entity $entity, $data=null) {
		return $this->getServer()->getItem($entity->getCollectionName(), $entity->getId(), false);
	}
	
}

?>