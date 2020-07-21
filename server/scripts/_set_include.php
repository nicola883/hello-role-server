<?php

/**
 * Tutti i set da includere negli script. Sono quelli inclusi da index.php
 * Riporto qui per non fare tanti include negli script che dovrei modificare se cambiano 
 */


include __DIR__ . '/../app/set.php'; // Set dell'applicazione
include __DIR__ . '/../sunrest/set.php'; // Set del framework
include __DIR__ . '/../config.php'; // Set comuni


include __DIR__ . '/../sunrest/autoload.php'; // Autolad delle classi








?>