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
include_spip('inc/modifier');

/**
 * Inserer en base un message de forum
 * @param int $id_parent
 * @param null $set
 * @return bool|string
 */
function forum_inserer($id_parent = null, $set = null) {

	include_spip('inc/session');
	$champs = [
		'id_parent' => $id_parent ?: 0,
		'id_thread' => $id_parent ? sql_getfetsel('id_thread', 'spip_forum', 'id_forum=' . intval($id_parent)) : 0,
		'date_heure' => date('Y-m-d H:i:s'),
		'ip' => $GLOBALS['ip'],
		'id_auteur' => session_get('id_auteur'),
		'objet' => '',
		'id_objet' => 0,
		'statut' => 'prop',
	];

	if ($set) {
		$champs = array_merge($champs, $set);
	}

	// Envoyer aux plugins
	$champs = pipeline(
		'pre_insertion',
		[
			'args' => [
				'table' => 'spip_forum',
				'id_parent' => $id_parent,
			],
			'data' => $champs
		]
	);

	$id = sql_insertq('spip_forum', $champs);

	if ($id) {
		// initialiser le thread si c'est un nouveau
		if (!$champs['id_thread']) {
			$champs['id_thread'] = $id;
			sql_updateq('spip_forum', $champs, 'id_forum=' . intval($id));
		}

		pipeline(
			'post_insertion',
			[
				'args' => [
					'table' => 'spip_forum',
					'id_parent' => $id_parent,
					'id_objet' => $id,
				],
				'data' => $champs
			]
		);
	}

	return $id;
}


// Nota: quand on edite un forum existant, il est de bon ton d'appeler
// au prealable conserver_original($id_forum)
if (!function_exists('revision_forum')) {
	function revision_forum($id_forum, $c = false) {

		$t = sql_fetsel('*', 'spip_forum', 'id_forum=' . intval($id_forum));
		if (!$t) {
			spip_log("erreur forum $id_forum inexistant");

			return;
		}

		// Calculer l'invalideur des caches lies a ce forum
		if ($t['statut'] == 'publie') {
			include_spip('inc/invalideur');
			$invalideur = ["id='forum/$id_forum'", "id='" . $t['objet'] . '/' . $t['id_objet'] . "'"];
		} else {
			$invalideur = '';
		}

		// Supprimer 'http://' ou 'https://' tout seul
		if (isset($c['url_site'])) {
			include_spip('inc/filtres');
			$c['url_site'] = vider_url($c['url_site'], false);
		}

		$err = objet_modifier_champs(
			'forum',
			$id_forum,
			[
				'nonvide' => ['titre' => _T('info_sans_titre')],
				'invalideur' => $invalideur
			],
			$c
		);

		$id_thread = intval($t['id_thread']);
		$cles = [];
		foreach (['id_objet', 'objet'] as $k) {
			if (isset($c[$k]) and $c[$k]) {
				$cles[$k] = $c[$k];
			}
		}

		// Modification des id_article etc
		// (non autorise en standard mais utile pour des crayons)
		// on deplace tout le thread {sauf les originaux}.
		if (count($cles) and $id_thread) {
			spip_log("update thread id_thread=$id_thread avec " . var_export($cles, 1), 'forum.' . _LOG_INFO_IMPORTANTE);
			sql_updateq('spip_forum', $cles, 'id_thread=' . $id_thread . " AND statut!='original'");
			// on n'affecte pas $r, car un deplacement ne change pas l'auteur
		}

		// s'il y a vraiment eu une modif et que le message est publié ou posté dans un forum du privé
		// on enregistre la nouvelle date_thread
		if ($err === '' and in_array($t['statut'], ['publie', 'prive', 'privrac', 'privadm'])) {
			// on ne stocke ni le numero IP courant ni le nouvel id_auteur
			// dans le message modifie (trop penible a l'usage) ; mais du
			// coup attention a la responsabilite editoriale
			/*
			sql_updateq('spip_forum', array('ip'=>($GLOBALS['ip']), 'id_auteur'=>($GLOBALS['visiteur_session']['id_auteur'])),"id_forum=".intval($id_forum));
			*/

			// & meme ca ca pourrait etre optionnel
			sql_updateq('spip_forum', ['date_thread' => date('Y-m-d H:i:s')], 'id_thread=' . $id_thread);
		}
	}
}
