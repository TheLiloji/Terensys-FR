<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion des emails et de leur envoi
 *
 * @package SPIP\Core\Fichier
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Envoyer un fichier dont on fourni le chemin, le mime type, en attachment ou non, avec un expire
 *
 * @uses spip_livrer_fichier_entetes()
 * @uses spip_livrer_fichier_entier()
 * @uses spip_livrer_fichier_partie()
 *
 * @param string $fichier
 * @param string $content_type
 * @param array $options
 *   bool|string $attachment
 *   int $expires
 *   int|null range
 * @throws Exception
 */
function spip_livrer_fichier($fichier, $content_type = 'application/octet-stream', $options = []) {

	$defaut = [
		'attachment' => false,
		'expires' => 3600,
		'range' => null,
	];
	$options = array_merge($defaut, $options);
	if (is_numeric($options['expires']) and $options['expires'] > 0) {
		$options['expires'] = gmdate('D, d M Y H:i:s', time() + $options['expires']) . ' GMT';
	}

	if ($options['range'] === null && isset($_SERVER['HTTP_RANGE'])) {
		$options['range'] = $_SERVER['HTTP_RANGE'];
	}

	// vider les buffer et supprimer la compression si besoin
	if (function_exists('ini_set')) {
		@ini_set('zlib.output_compression', '0'); // pour permettre l'affichage au fur et a mesure
		@ini_set('output_buffering', 'off');
		@ini_set('implicit_flush', 1);
	}
	@ob_implicit_flush(true);
	$level = ob_get_level();
	while ($level--) {
		@ob_end_clean();
	}

	// vider les buffer et supprimer la compression si besoin
	if (function_exists('ini_set')) {
		@ini_set('zlib.output_compression', '0'); // pour permettre l'affichage au fur et a mesure
		@ini_set('output_buffering', 'off');
		@ini_set('implicit_flush', 1);
	}
	@ob_implicit_flush(true);
	$level = ob_get_level();
	while ($level--) {
		@ob_end_clean();
	}

	spip_livrer_fichier_entetes(
		$fichier,
		$content_type,
		($options['attachment'] && !$options['range']) ? $options['attachment'] : false,
		$options['expires']
	);

	if (!is_null($options['range'])) {
		spip_livrer_fichier_partie($fichier, $options['range']);
	}
	else {
		spip_livrer_fichier_entier($fichier);
	}
}

/**
 * Envoyer les entetes du fichier, sauf ce qui est lie au mode d'envoi (entier ou par parties)
 *
 * @see spip_livrer_fichier()
 * @param string $fichier
 * @param string $content_type
 * @param bool|string $attachment
 * @param int|string $expires
 */
function spip_livrer_fichier_entetes($fichier, $content_type = 'application/octet-stream', $attachment = false, $expires = 0) {
	// toujours envoyer un content type, meme vide !
	header('Accept-Ranges: bytes');
	header('Content-Type: ' . $content_type);

	if ($fs = stat($fichier)
	  and !empty($fs['size'])
	  and !empty($fs['mtime'])) {
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", $fs['mtime']) . " GMT");
		header(sprintf('Etag: "%x-%x"', $fs['size'], str_pad($fs['mtime'], 16, "0")));
	}

	if ($attachment) {
		$f = (is_string($attachment) ? $attachment : basename($fichier));
		// ce content-type est necessaire pour eviter des corruptions de zip dans ie6
		header('Content-Type: application/octet-stream');

		header("Content-Disposition: attachment; filename=\"$f\";");
		header('Content-Transfer-Encoding: binary');

		// fix for IE caching or PHP bug issue
		header('Expires: 0'); // set expiration time
		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	}
	else {
		$f = (is_string($attachment) ? $attachment : basename($fichier));
		header("Content-Disposition: inline; filename=\"$f\";");
		header('Expires: ' . $expires); // set expiration time
	}
}

/**
 * Envoyer les contenu entier du fichier
 * @param string $fichier
 */
function spip_livrer_fichier_entier($fichier) {
	if (!file_exists($fichier)) {
		throw new \Exception(sprintf('File not found: %s', $fichier));
	}

	if (!is_readable($fichier)) {
		throw new \Exception(sprintf('File not readable: %s', $fichier));
	}

	// Définition du temps de téléchargement (en secondes) pour un fichier de 512 Mio
	if (!defined('_LIVRER_FICHIER_BASE_TEMPS_TELECHARGEMENT')) {
		define('_LIVRER_FICHIER_BASE_TEMPS_TELECHARGEMENT', 600);
	}

	$download_time = _LIVRER_FICHIER_BASE_TEMPS_TELECHARGEMENT;
	if ($size = filesize($fichier)) {
		header(sprintf('Content-Length: %d', $size));
		// Pour les fichiers de taille supérieure à 512 Mio
		// on adapte le temps maxi de telechargement en fonction de la taille du fichier
		// La constante `_LIVRER_FICHIER_BASE_TEMPS_TELECHARGEMENT` indique le nombre de secondes qu'il faut pour livrer 512 Mio
		// Sa valeur par défaut est de 600 s, c'est-à-dire 10 mn.
		// 512 Mio / 10mn correspondant à un wifi pas super rapide
		$gio = 1024 * 1024 * 1024;
		if ($size > $gio / 2) {
			$download_time = intval(round($download_time * 2 * $size / $gio));
		}
	}
	if (function_exists('set_time_limit')) {
		set_time_limit($download_time);
	}

	if (function_exists('fpassthru')) {
		$handle = fopen($fichier, 'rb');
		fpassthru($handle);
	} else {
		// If it's a large file, readfile might not be able to do it due to memory_limit
		$chunksize = 1 * (1024 * 1024); // how many bytes per chunk
		if (!$size || $size > $chunksize || !function_exists('readfile')) {
			$handle = fopen($fichier, 'rb');
			while (!feof($handle)) {
				$buffer = fread($handle, $chunksize);
				echo $buffer;
				ob_flush();
				flush();
			}
			fclose($handle);
		} else {
			readfile($fichier);
		}
	}

	exit();
}

/**
 * Envoyer une partie du fichier
 * Prendre en charge l'entete Range:bytes=0-456 utilise par les player medias
 * source : https://github.com/pomle/php-serveFilePartial/blob/master/ServeFilePartial.inc.php
 *
 * @param string $fichier
 * @param string $range
 * @throws Exception
 */
function spip_livrer_fichier_partie($fichier, $range = null) {
	if (!file_exists($fichier)) {
		throw new \Exception(sprintf('File not found: %s', $fichier));
	}

	if (!is_readable($fichier)) {
		throw new \Exception(sprintf('File not readable: %s', $fichier));
	}


	// Par defaut on envoie tout
	$byte_offset = 0;
	$byte_length = $file_size = filesize($fichier);

	// Parse Content-Range header for byte offsets, looks like "bytes=11525-" OR "bytes=11525-12451"
	if ($range and preg_match('%bytes=(\d+)-(\d+)?%i', $range, $match)) {
		### Offset signifies where we should begin to read the file
		$byte_offset = (int) $match[1];

		### Length is for how long we should read the file according to the browser, and can never go beyond the file size
		if (isset($match[2])) {
			$finish_bytes = (int) $match[2];
			$byte_length = $finish_bytes + 1;
		} else {
			$finish_bytes = $file_size - 1;
		}

		$cr_header = sprintf('Content-Range: bytes %d-%d/%d', $byte_offset, $finish_bytes, $file_size);
	} else {
		// si pas de range valide, on delegue a la methode d'envoi complet
		spip_livrer_fichier_entier($fichier);
		// redondant, mais facilite la comprehension du code
		exit();
	}

	// Remove headers that might unnecessarily clutter up the output
	header_remove('Cache-Control');
	header_remove('Pragma');

	// partial content
	header('HTTP/1.1 206 Partial content');
	header($cr_header);  ### Decrease by 1 on byte-length since this definition is zero-based index of bytes being sent

	$byte_range = $byte_length - $byte_offset;

	header(sprintf('Content-Length: %d', $byte_range));

	// Variable containing the buffer
	$buffer = '';
	// Just a reasonable buffer size
	$buffer_size = 512 * 16;
	// Contains how much is left to read of the byte_range
	$byte_pool = $byte_range;

	if (!$handle = fopen($fichier, 'r')) {
		throw new \Exception(sprintf('Could not get handle for file %s', $fichier));
	}

	if (fseek($handle, $byte_offset, SEEK_SET) == -1) {
		throw new \Exception(sprintf('Could not seek to byte offset %d', $byte_offset));
	}

	while ($byte_pool > 0) {
		// How many bytes we request on this iteration
		$chunk_size_requested = min($buffer_size, $byte_pool);

		// Try readin $chunk_size_requested bytes from $handle and put data in $buffer
		$buffer = fread($handle, $chunk_size_requested);

		// Store how many bytes were actually read
		$chunk_size_actual = strlen($buffer);

		// If we didn't get any bytes that means something unexpected has happened since $byte_pool should be zero already
		if ($chunk_size_actual == 0) {
			// For production servers this should go in your php error log, since it will break the output
			trigger_error('Chunksize became 0', E_USER_WARNING);
			break;
		}

		// Decrease byte pool with amount of bytes that were read during this iteration
		$byte_pool -= $chunk_size_actual;

		// Write the buffer to output
		print $buffer;

		// Try to output the data to the client immediately
		flush();
	}

	exit();
}
