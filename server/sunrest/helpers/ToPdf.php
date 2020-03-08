<?php

/**
 * Converte un file di diversi formati in pdf
 */
class ToPdf {
	
	public static function convert($input) {
		$filename = self::getTmpName();
		$fodt = $filename . '.odt';
		file_put_contents($fodt, $input);
		
		$dir = DOCUMENTS_DIR;
		
		if (getenv('HOME') == '')
			putenv('HOME=/tmp');
		//	Poi nel file /etc/sudoers ho inserito la riga:
		//	www-data ALL=(ALL) NOPASSWD: /usr/bin/libreoffice
		exec("libreoffice --headless --convert-to pdf --outdir $dir $fodt");
		
		$fpdf = $filename . '.pdf';
		$pdf = file_get_contents($fpdf);
		
		unlink($fodt);
		unlink($fpdf);
		
		return $pdf;
	}
	
	static function getTmpName($extension='.odt') {
		while (true) {
			$dir = DOCUMENTS_DIR;
			$filename = "$dir" . uniqid('s_', true);
			$saveTo = $filename . "$extension";
			if (!file_exists("$dir/" . $saveTo)) break;
		}
		return $filename;		
	}

}