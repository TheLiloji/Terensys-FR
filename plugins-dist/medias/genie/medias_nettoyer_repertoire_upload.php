<?php

/**
 * Tache de nettoyages du répertoire tmp/upload
 *
 * @plugin     Medias
 * @licence    GNU/GPL
 * @package    SPIP\Medias\Genie
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Enlève les fichiers trop vieux du répertoire tmp/upload
 *
 * @param int $last
 * @return int
**/
function genie_medias_nettoyer_repertoire_upload_dist($last) {
	include_spip('inc/invalideur');
	purger_repertoire(_DIR_TMP . 'upload', ['mtime' => time() - 180 * 24 * 3600, 'subdir' => true]);
	return 1;
}
