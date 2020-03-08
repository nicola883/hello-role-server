<?php

class TotalRowsNumberUserAction extends EntityCollectionAction
{
	
	const METHOD = 'get';
	
	public function exec($params, EntityCollection $entity, $data=null) {
		
		$server = $this->getServer();
		
		$customerTypeId = CUSTOMER_TYPE_ID;
		
		$query = "select count(id) as total_rows_number from users_restrict where active = true and deleted = false and type = $customerTypeId";
		
		return $this->getServer()->getList($entity->getCollectionName(), $params, false, array('query' => $query, ':params' => null));
	}
	
}

?>