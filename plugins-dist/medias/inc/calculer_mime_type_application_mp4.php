<?php

/**
 * SPIP, Système de publication pour l'internet
 *
 * Copyright © avec tendresse depuis 2001
 * Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James
 *
 * Ce programme est un logiciel libre distribué sous licence GNU/GPL.
 */

/**
 * Gestion des modes de documents
 *
 * @package SPIP\Medias\Mime_type
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function inc_calculer_mime_type_application_mp4_dist(int $id_document, string $extension, string $mime_type) {

	$media = sql_getfetsel('media', 'spip_documents', 'id_document=' . intval($id_document));
	if (in_array($media, ['audio', 'video'])) {
		$mime_type = str_replace('application/', $media . '/', $mime_type);
		return $mime_type;
	}
	return null;
}
