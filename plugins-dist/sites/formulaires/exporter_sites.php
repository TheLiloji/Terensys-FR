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

function formulaires_exporter_sites_charger_dist() {

	if (!autoriser('exporter', '_sites')) {
		return false;
	}

	return [
		'id_parent' => 0,
		'exporter_publie_seulement' => 0,
		'exporter_avec_mots_cles' => 1,
	];
}

function formulaires_exporter_sites_traiter_dist() {
	$id_parent = intval(_request('id_parent'));
	$exporter_publie_seulement = _request('exporter_publie_seulement') ? 1 : 0;
	$exporter_avec_mots_cles = _request('exporter_avec_mots_cles') ? 1 : 0;

	include_spip('inc/actions');
	$redirect = generer_action_auteur(
		'exporter_bookmarks',
		"$id_parent-$exporter_publie_seulement-$exporter_avec_mots_cles"
	);

	return ['redirect' => $redirect];
}
