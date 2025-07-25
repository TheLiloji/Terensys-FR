<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use Spip\Afficher\Minipage\Admin as MinipageAdmin;

/**
 * Gestion de l'action activer_plugins
 *
 * @package SPIP\Core\Action
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


function action_api_transmettre_dist($arg = null) {

	// Obtenir l'argument 'id_auteur/cle/format/fond'
	if (is_null($arg)) {
		$arg = _request('arg');
	}

	$args = explode('/', $arg);

	if (count($args) !== 4) {
		action_api_transmettre_fail($arg);
	}

	[$id_auteur, $cle, $format, $fond] = $args;
	$id_auteur = intval($id_auteur);

	if (preg_match(',[^\w\\.-],', $format)) {
		action_api_transmettre_fail("format $format ??");
	}
	if (preg_match(',[^\w\\.-],', $fond)) {
		action_api_transmettre_fail("fond $fond ??");
	}

	// verifier la cle
	//[(#ENV{id,0}|securiser_acces{#ENV{cle}, voirstats, #ENV{op}, #ENV{args}}|?{1,0})]
	//[(#ENV{id,0}|securiser_acces{#ENV{cle}, voirstats, #ENV{op}, #ENV{args}}|?{1,0})]

	$qs = $_SERVER['QUERY_STRING'];
	// retirer action et arg de la qs
	$contexte = [];
	parse_str($qs, $contexte);
	foreach ($contexte as $k => $v) {
		if (in_array($k, ['action', 'arg', 'var_mode'])) {
			unset($contexte[$k]);
		}
	}
	$qs = http_build_query($contexte);
	include_spip('inc/acces');
	if (!securiser_acces_low_sec(intval($id_auteur), $cle, "transmettre/$format", $fond, $qs)) {
		// si le autoriser low_sec n'est pas bon, on peut valider l'appel si l'auteur est identifie
		include_spip('inc/autoriser');
		$autoriser_type = preg_replace(',\W+,', '', "_{$format}{$fond}");
		if (
			!$id_auteur
			or empty($GLOBALS['visiteur_session']['id_auteur'])
			or $GLOBALS['visiteur_session']['id_auteur'] != $id_auteur
			or !autoriser('transmettre', $autoriser_type, $id_auteur)
		) {
			action_api_transmettre_fail("auth QS $qs ??");
		}
	}

	$contexte['id_auteur'] = $id_auteur;

	$fond = "transmettre/$format/$fond";

	if (!trouver_fond($fond)) {
		$fond = "prive/$fond";
	}

	if (!trouver_fond($fond)) {
		action_api_transmettre_fail("fond $fond ??");
	}

	$res = recuperer_fond($fond, $contexte, ['raw' => true]);
	if (!empty($res['entetes'])) {
		foreach ($res['entetes'] as $h => $v) {
			header("$h: $v");
		}
	}

	$res = ltrim($res['texte']);
	if (empty($res)) {
		spip_log("$arg $qs resultat vide", 'transmettre' . _LOG_INFO_IMPORTANTE);
	}

	echo $res;
	exit();
}

function action_api_transmettre_fail($arg) {
	$minipage = new MinipageAdmin();
	echo $minipage->page('Argument non compris', ['titre' => _T('info_acces_interdit')]);
	exit;
}
