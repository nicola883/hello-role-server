<?php


class ScriptHelper
{
	
	static public function setDbKey($db) {
		echo "Termina con ]\n";
		$command = "/bin/bash -c 'read -s -r -d ]; echo \"\$REPLY\"'";
		$k = shell_exec($command);
		$db->setPrivateKey($k);
	}
	
	
}


?>