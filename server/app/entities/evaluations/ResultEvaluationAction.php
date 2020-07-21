<?php


/**
 * Retrives the pages of the ground truth. We should use only pages
 * that have at least two roles defined and all the three landmarks.
 * Therefore parameters should be found = 3 and minroles = 2
 * @author nicola
 *
 */
class ResultEvaluationAction extends EntityCollectionAction
{
	
	const METHOD = 'get';
	
	const PARAMS = 'found, nroles, reverse, perrole';
	
	public function exec($params, EntityCollection $entity, $data=null) {
		$s = $entity->getServer();
		$reverse = isset($params['reverse']) ? true : false;
		$perrole = isset($params['perrole']) ? true : false;
		unset($params['reverse']);
		unset($params['perrole']);
		$table = $reverse ? 'results_reverse' : 'results';

		$table = 'results';
		if ($perrole) {
			$table = 'perrole';
		}

		if ($reverse) {
			$table = $table . '_reverse';
		}

		return $s->getList($table, $params, false);



	}

	
}

?>