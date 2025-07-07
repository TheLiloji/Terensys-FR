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
include_spip('inc/autoriser');

/**
 * enlever les scripts de html si necessaire
 * on utilise safehtml
 *
 * @param string $file
 * @return array
 */
function medata_html_dist($file) {
	$meta = [];

	// Securite si pas autorise : virer les scripts et les references externes
	// sauf si on est en mode javascript 'ok' (1), cf. inc_version
	if (
		$GLOBALS['filtrer_javascript'] < 1
		and !autoriser('televerser', 'script')
	) {
		$texte = spip_file_get_contents($file);
		include_spip('inc/texte');
		$new = trim(safehtml($texte));
		// petit bug safehtml
		if ($new != $texte) {
			ecrire_fichier($file, $new);
		}
	}

	return $meta;
}
