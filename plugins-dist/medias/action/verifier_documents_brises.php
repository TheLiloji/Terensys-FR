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

/**
 * Verifier tous les fichiers brises
 *
 */
function action_verifier_documents_brises_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	include_spip('inc/autoriser');
	if (autoriser('voir', '_documents')) {
		include_spip('inc/documents');
		$res = sql_select('fichier,brise,id_document', 'spip_documents', "distant='non'");
		while ($row = sql_fetch($res)) {
			if (($brise = !@file_exists(get_spip_doc($row['fichier']))) != $row['brise']) {
				sql_updateq('spip_documents', ['brise' => $brise], 'id_document=' . intval($row['id_document']));
			}
		}
	}
}
