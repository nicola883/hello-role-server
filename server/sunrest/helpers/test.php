<?php


	include_once('../server/autoload.php');

	$energia = new SunEnergy();

	$mese = 6;
	$irraggiamentoOrizzontale = 23.1;
	$riflettanza = 0.2;
		
	$giorno = $energia->giornoMedio($mese);
	$terraSole = $energia->rapportoDistanzaTerraSole($giorno);
	$declinazione = $energia->declinazione($mese);
	$azimuth = 54;
	$inclinazione = 15;
	$latitudine = 44.5739575;
	$angoloTramonto = $energia->angoloTramonto($declinazione, $latitudine);
	$angoliSole = $energia->angoliSole($declinazione, $latitudine, $azimuth, $inclinazione, $angoloTramonto);
	
	echo $energia->irraggiamentoMensile($irraggiamentoOrizzontale, $latitudine, $azimuth, $inclinazione, $riflettanza, $mese);




?>
