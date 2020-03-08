<?php 

include_once '../../set.php';
include_once '../autoload.php';


$db = Factory::createDb();

// leggo tutte le coordinate presenti in plants_geos_satradiations e 
// popolo satradiations con queste senza ripetizioni, poi do' il riferimento

$query = "select id, geo from plants_geos_satradiations";
$rows = $db->dbQuery2($query, null);

foreach ($rows as $row) {
	$geo = $row['geo'];
	$query = "select id from satradiations where geo ~= :geo";
	$ret = $db->dbQuery2($query, array(':geo' => $geo));
	if (!isset($ret)) {
		$id = $db->create('satradiations', array('geo' => $geo));
	} else {
		$id = $ret[0]['id'];
		Utility::debug($ret);
	}
	
	$query = "update plants_geos_satradiations set satradiation_id = :rid where id = :pgsid";
	$db->dbQuery2($query, array(':rid' => $id, ':pgsid' => $row['id']));
	
	//Utility::debug($row['geo']);
}




?>