<?php

	include_once '../server/set.php';
	include_once '../server/autoload.php';
	
	$db = Factory::createDb();
	
	$r = new Robot($db);
	
	$plants = $db->getList('plants', false, true, null, true);

	foreach ($plants as $p) {
		$comune = $p['info']['valori']['stato_impianti'][0]['cmb_comune'];
		$provincia = $p['info']['valori']['stato_impianti'][0]['cmb_provincia'];
		$ret = $r->getTownId($comune, $provincia);

		$db->update('plants', array('townid' => $ret), $p['id']);
	}


		//$ret = $r->getTownId("aosta", "valle d'aosta");	
	//echo 'ciao', "\r\n";
	//echo "<br/>";
	//echo 'ancora', "\r\n";
	
?>
	
