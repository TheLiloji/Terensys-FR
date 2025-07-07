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
 * Gestion du formulaire de configuration des groupes de mots
 *
 * @package SPIP\Mots\Formulaires
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/presentation');

/**
 * Chargement du formulaire de configuration des mots
 *
 * @return array
 *     Environnement du formulaire
 **/
function formulaires_configurer_mots_charger_dist() {
	foreach (
		[
			'articles_mots',
			'config_precise_groupes',
			'mots_cles_forums',
		] as $m
	) {
		$valeurs[$m] = $GLOBALS['meta'][$m];
	}

	return $valeurs;
}

/**
 * Traitement du formulaire de configuration des mots
 *
 * @return array
 *     Retours du traitement
 **/
function formulaires_configurer_mots_traiter_dist() {
	$res = ['editable' => true];
	foreach (
		[
			'articles_mots',
			'config_precise_groupes',
			'mots_cles_forums',
		] as $m
	) {
		if (!is_null($v = _request($m))) {
			ecrire_meta($m, $v == 'oui' ? 'oui' : 'non');
		}
	}

	$res['message_ok'] = _T('config_info_enregistree');

	return $res;
}
