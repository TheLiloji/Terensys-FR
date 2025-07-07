<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use function SpipLeague\Component\Kernel\{app, param};

/**
 * Gestion du formulaire de configuration du compresseur
 *
 * @package SPIP\Compresseur\Formulaires
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement du formulaire de configuration du compresseur
 *
 * @return array
 *     Environnement du formulaire
 **/
function formulaires_configurer_compresseur_charger_dist() {

	$valeurs = [];

	$valeurs['auto_compress_js'] = empty($GLOBALS['meta']['auto_compress_js']) ? '' : $GLOBALS['meta']['auto_compress_js'];
	$valeurs['auto_compress_css'] = empty($GLOBALS['meta']['auto_compress_css']) ? '' : $GLOBALS['meta']['auto_compress_css'];
	$valeurs['url_statique_ressources'] = empty($GLOBALS['meta']['url_statique_ressources']) ? '' : $GLOBALS['meta']['url_statique_ressources'];

	return $valeurs;
}

/**
 * Vérifications du formulaire de configuration du compresseur
 *
 * @return array
 *     Tableau des erreurs
 **/
function formulaires_configurer_compresseur_verifier_dist() {
	$erreurs = [];

	// les checkbox
	foreach (['auto_compress_js', 'auto_compress_css'] as $champ) {
		if (_request($champ) != 'oui') {
			set_request($champ, 'non');
		}
	}

	if ($url = _request('url_statique_ressources')) {
		$url = preg_replace(',/?\s*$,', '', $url);
		if (!tester_url_absolue($url)) {
			$protocole = explode('://', $GLOBALS['meta']['adresse_site']);
			$protocole = reset($protocole);
			$url = $protocole . "://$url";
		}
		set_request('url_statique_ressources', $url);
	}


	return $erreurs;
}

/**
 * Traitement du formulaire de configuration du compresseur
 *
 * @return array
 *     Retours du traitement
 **/
function formulaires_configurer_compresseur_traiter_dist() {
	include_spip('inc/config');
	appliquer_modifs_config();

	return ['message_ok' => _T('config_info_enregistree')];
}

function front_ou_back(string $url): string {
	return $url . (app()->isFrontOffice() ? param('spip.routes.back_office'): '');
}
