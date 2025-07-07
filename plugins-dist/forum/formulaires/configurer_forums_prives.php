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

function formulaires_configurer_forums_prives_charger_dist() {

	return [
		'forum_prive_objets' => explode(',', $GLOBALS['meta']['forum_prive_objets']),
		'forum_prive' => $GLOBALS['meta']['forum_prive'],
		'forum_prive_admin' => $GLOBALS['meta']['forum_prive_admin'],
	];
}

function formulaires_configurer_forums_prives_traiter_dist() {
	$res = ['editable' => true];

	if (!is_null($v = _request($m = 'forum_prive_objets'))) {
		ecrire_meta($m, is_array($v) ? implode(',', $v) : '');
	}
	if (!is_null($v = _request($m = 'forum_prive'))) {
		ecrire_meta($m, $v == 'oui' ? 'oui' : 'non');
	}
	if (!is_null($v = _request($m = 'forum_prive_admin'))) {
		ecrire_meta($m, $v == 'oui' ? 'oui' : 'non');
	}

	$res['message_ok'] = _T('config_info_enregistree');

	return $res;
}
