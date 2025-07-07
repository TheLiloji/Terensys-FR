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

function formulaires_site_charger_dist($id_rubrique) {

	return ['nom_site' => '', 'url_site' => 'http://', 'description_site' => ''];
}

function formulaires_site_verifier_dist($id_rubrique) {

	$erreurs = [];
	if (!$nom = _request('nom_site')) {
		$erreurs['nom_site'] = _T('info_obligatoire');
	} else {
		if ((strlen($nom) < 2) or (strlen(_request('nobot')) > 0)) {
			$erreurs['email_message_auteur'] = _T('form_prop_indiquer_nom_site');
		}
	}
	if (!$url = _request('url_site')) {
		$erreurs['url_site'] = _T('info_obligatoire');
	}

	if (!count($erreurs)) {
		// Tester l'URL du site
		include_spip('inc/distant');
		if (!recuperer_url($url)) {
			$erreurs['url_site'] = _T('form_pet_url_invalide');
		}
	}

	return $erreurs;
}

function formulaires_site_traiter_dist($id_rubrique) {
	$res = ['message_erreur' => _T('titre_probleme_technique')];

	$nom = _request('nom_site');
	$url = _request('url_site');
	$desc = _request('description_site');

	include_spip('base/abstract_sql');
	if (
		$id_syndic = sql_insertq('spip_syndic', [
		'nom_site' => $nom,
		'url_site' => $url,
		'id_rubrique' => $id_rubrique,
		'id_secteur' => sql_getfetsel('id_secteur', 'spip_rubriques', 'id_rubrique=' . sql_quote($id_rubrique)),
		'descriptif' => $desc,
		'date' => date('Y-m-d H:i:s'),
		'date_syndic' => date('Y-m-d H:i:s'),
		'statut' => 'prop',
		'syndication' => 'non'
		])
	) {
		$res = ['message_ok' => _T('form_prop_enregistre'), 'id_syndic' => $id_syndic];
	}

	return $res;
}
