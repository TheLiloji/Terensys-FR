<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function inc_verifier_document_mode_image_dist($infos) {

	// Si on veut uploader une image, il faut qu'elle ait ete bien lue
	if ($infos['inclus'] != 'image') {
		return _T('medias:erreur_format_fichier_image', ['nom' => $infos['fichier']]);
	}

	if (isset($infos['largeur']) and isset($infos['hauteur'])) {
		if (!($infos['largeur'] or $infos['hauteur'])) {
			return _T('medias:erreur_upload_vignette', ['nom' => $infos['fichier']]);
		}
	}

	return true;
}
