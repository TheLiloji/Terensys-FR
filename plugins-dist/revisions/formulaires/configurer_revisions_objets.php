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

function formulaires_configurer_revisions_objets_charger_dist() {
	include_spip('inc/config');
	$objets = lire_config('objets_versions/', []);

	$valeurs = [
		'objets_versions' => $objets,
	];

	return $valeurs;
}

function formulaires_configurer_revisions_objets_traiter_dist() {
	include_spip('inc/config');
	ecrire_config('objets_versions/', _request('objets_versions') ?? []);

	return ['message_ok' => _T('config_info_enregistree')];
}

function test_objet_versionable($desc) {
	if (
		!$desc['editable']
		or !isset($desc['champs_versionnes'])
		or !(is_countable($desc['champs_versionnes']) ? count($desc['champs_versionnes']) : 0)
	) {
		return '';
	}

	// regarder si il y a un vrai champ versionne, pas seulement une jointure
	foreach ($desc['champs_versionnes'] as $c) {
		if (strncmp($c, 'jointure_', 9) != 0) {
			return ' ';
		}
	}


	return '';
}
