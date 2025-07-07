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
 * Gestion du formulaire de configuration des documents
 *
 * @package SPIP\Medias\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement du formulaire de configuration des documents
 *
 * @return array
 *     Environnement du formulaire
 **/
function formulaires_configurer_documents_charger_dist() {
	$valeurs = [];
	foreach (
		[
			'documents_objets',
			'documents_date',
		] as $m
	) {
		$valeurs[$m] = $GLOBALS['meta'][$m] ?? '';
	}
	$valeurs['documents_objets'] = explode(',', $valeurs['documents_objets']);

	return $valeurs;
}

/**
 * Traitement du formulaire de configuration des documents
 *
 * @return array
 *     Retours du traitement
 **/
function formulaires_configurer_documents_traiter_dist() {
	$res = ['editable' => true];
	if (!is_null($v = _request($m = 'documents_date'))) {
		ecrire_meta($m, $v == 'oui' ? 'oui' : 'non');
	}
	if (!is_null($v = _request($m = 'documents_objets'))) {
		ecrire_meta($m, is_array($v) ? implode(',', $v) : '');
	}

	$res['message_ok'] = _T('config_info_enregistree');

	return $res;
}
