<?php 


class ImageProcessing {
	
	/**
	 * Resizes an image and converts it to PNG returning the PNG data as a string
	 * @param string $srcFile Il path del file che contiene l'immagine
	 * @param integer $maxSize La dimensione massima dell'immagine restituita
	 * @param boolean $onSite true se l'immagine data sara' sovrascritta dalla nuova (default false)
	 * @return image | boolean Ritorna l'immagine in una stringa (usando un file temporaneo) se $onSite e' false,
	 * true se l'immagine data e' stata sovrascritta (se $onSite e' false), false in caso di errore
	 * 
	*/
	static public function imageToPng($srcFile, $maxSize = 100, $onSite=false) {
		list($width_orig, $height_orig, $type) = getimagesize($srcFile);
	
		// Get the aspect ratio
		$ratio_orig = $width_orig / $height_orig;
	
		$width  = $maxSize;
		$height = $maxSize;
	
		// resize to height (orig is portrait)
		if ($ratio_orig < 1) {
			$width = $height * $ratio_orig;
		}
		// resize to width (orig is landscape)
		else {
			$height = $width / $ratio_orig;
		}
	
		// Temporarily increase the memory limit to allow for larger images
		ini_set('memory_limit', '32M');

		// create a new blank image
		$newImage = imagecreatetruecolor($width, $height);		
		
		switch ($type)
		{
			case IMAGETYPE_GIF:
				// aggiusto il fondo
				// @see http://stackoverflow.com/questions/2611852/imagecreatefrompng-makes-a-black-background-instead-of-transparent
				// definisco il colore nero (rappresentazione intera)
				$background = imagecolorallocate($newImage, 0, 0, 0);
				// rimuovo il nero dal fondo
				imagecolortransparent($newImage, $background);				
				$image = imagecreatefromgif($srcFile);
				break;
			case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg($srcFile);
				break;
			case IMAGETYPE_PNG:
				// Aggiusto il fondo (vedi sopra)
				// definisco il colore nero
				$background = imagecolorallocate($newImage, 0, 0, 0);
				// rimuovo il colore nero
				imagecolortransparent($newImage, $background);
				// salvo il colore alpha
				imagealphablending($newImage, false);
				imagesavealpha($newImage, true);
				// creo l'immagine principale da abbinare al fondo poi
				$image = imagecreatefrompng($srcFile);
				break;
			default:
				throw new Exception('Unrecognized image type ' . $type);
		}
	
	/*	
		// integer representation of the color black (rgb: 0,0,0)
		$background = imagecolorallocate($newImage, 0, 0, 0);
		// removing the black from the placeholder
		imagecolortransparent($newImage, $background);
		
		// turning off alpha blending (to ensure alpha channel information
		// is preserved, rather than removed (blending with the rest of the
		// image in the form of black))
		imagealphablending($newImage, false);
		
		// turning on alpha channel information saving (to ensure the full range
		// of transparency is preserved)
		imagesavealpha($newImage, true);		
	*/	
				
		// Copy the old image to the new image
		imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

		

				
		if ($onSite) {
			if (!imagepng($newImage, $srcFile))
				return false;
			// Free memory
			imagedestroy($newImage);
			return true;			
		} else {
			// Output to a temp file
			$destFile = tempnam(sys_get_temp_dir(), '_');
			imagepng($newImage, $destFile);
			// Free memory
			imagedestroy($newImage);
	
			if ( is_file($destFile) ) {
				$f = fopen($destFile, 'rb');
				$data = fread($f, filesize($destFile));
				fclose($f);
		
				// Remove the tempfile
				unlink($destFile);
				return $data;
			}
			return false;
		}
		
	}

}



?>