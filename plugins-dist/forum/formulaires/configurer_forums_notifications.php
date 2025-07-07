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
$GLOBALS['liste_des_forums']['forum:bouton_radio_modere_posteriori'] = 'pos';
$GLOBALS['liste_des_forums']['forum:bouton_radio_modere_priori'] = 'pri';
$GLOBALS['liste_des_forums']['forum:bouton_radio_modere_abonnement'] = 'abo';
$GLOBALS['liste_des_forums']['forum:info_pas_de_forum'] = 'non';

function formulaires_configurer_forums_notifications_charger_dist() {
	$valeurs = [];
	$m = $GLOBALS['meta']['prevenir_auteurs'];
	$l = $GLOBALS['liste_des_forums'];
	unset($l['forum:info_pas_de_forum']);
	foreach ($l as $desc => $val) {
		$valeurs['prevenir_auteurs_' . $val] = (($m == 'oui') or strpos($m, (string) ",$val,") !== false);
	}

	return $valeurs;
}

function formulaires_configurer_forums_notifications_traiter_dist() {
	include_spip('inc/meta');

	$res = [];
	foreach ($GLOBALS['liste_des_forums'] as $desc => $val) {
		if (_request('prevenir_auteurs_' . $val)) {
			$res[] = $val;
		}
	}
	ecrire_meta('prevenir_auteurs', $res ? (',' . join(',', $res) . ',') : 'non');

	return ['message_ok' => _T('config_info_enregistree')];
}
