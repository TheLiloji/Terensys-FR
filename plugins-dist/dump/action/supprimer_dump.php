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

include_spip('inc/dump');
include_spip('inc/autoriser');

/**
 * Telecharger un dump quand on est webmestre
 *
 * @param string $arg
 */
function action_supprimer_dump_dist($arg = null) {
	if (!$arg) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	$fichier = $arg;

	if (autoriser('webmestre')) {
		// verifier que c'est bien une sauvegarde
		include_spip('inc/dump');
		$dir = dump_repertoire();
		$dumps = dump_lister_sauvegardes($dir);

		foreach ($dumps as $dump) {
			if ($dump['fichier'] == $fichier) {
				spip_unlink($dir . $fichier);
			}
		}
	}
}
