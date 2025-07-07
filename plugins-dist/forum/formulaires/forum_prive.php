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

include_spip('inc/forum');

/**
 * Identification du formulaire poste : ne pas tenir compte du retour
 *
 * @param $objet
 * @param $id_objet
 * @param $id_forum
 * @param $forcer_previsu
 * @param $statut
 * @return array
 */
function formulaires_forum_prive_identifier_dist($objet, $id_objet, $id_forum, $forcer_previsu, $statut) {
	return [$objet, $id_objet, $id_forum, $forcer_previsu, $statut];
}

function formulaires_forum_prive_charger_dist($objet, $id_objet, $id_forum, $forcer_previsu, $statut, $retour = '') {

	if (!function_exists($f = 'forum_recuperer_titre')) {
		$f = 'forum_recuperer_titre_dist';
	}
	// si objet, il faut un titre, sinon on est dans un statut privrac/privadm qui permet un forum sans objet
	if ($objet and $id_objet and !$titre = $f($objet, $id_objet, $id_forum, false)) {
		return false;
	}

	$primary = id_table_objet($objet);
	$table = table_objet($objet);

	// Tableau des valeurs servant au calcul d'une signature de securite.
	// Elles seront placees en Input Hidden pour que inc/forum_insert
	// recalcule la meme chose et verifie l'identite des resultats.
	// Donc ne pas changer la valeur de ce tableau entre le calcul de
	// la signature et la fabrication des Hidden
	// Faire attention aussi a 0 != ''
	$ids = [];
	$ids[$primary] = ($x = intval($id_objet)) ? $x : '';
	$ids['id_objet'] = ($x = intval($id_objet)) ? $x : '';
	$ids['objet'] = $objet;
	$ids['id_forum'] = ($x = intval($id_forum)) ? $x : '';

	// par défaut, on force la prévisualisation du message avant de le poster
	if (($forcer_previsu == 'non') or (empty($forcer_previsu) and $GLOBALS['meta']['forums_forcer_previsu'] == 'non')) {
		$forcer_previsu = 'non';
	} else {
		$forcer_previsu = 'oui';
	}

	// pour les hidden
	$script_hidden = '';
	foreach ($ids as $id => $v) {
		$script_hidden .= "<input type='hidden' name='$id' value='$v' />";
	}

	$config = [];
	foreach (['afficher_barre', 'forum_titre', 'forums_texte', 'forums_urlref'] as $k) {
		$config[$k] = ' ';
	}

	return [
		'nom_site' => '',
		'table' => $table,
		'texte' => '',
		'config' => $config,
		'titre' => $titre ?? '',
		'_hidden' => $script_hidden, # pour les variables hidden
		'url_site' => '',
		'forcer_previsu' => $forcer_previsu,
		'id_forum' => $id_forum, // passer id_forum au formulaire pour lui permettre d'afficher a quoi l'internaute repond
		'_sign' => implode('_', $ids),
		'_autosave_id' => $ids,
	];
}


function formulaires_forum_prive_verifier_dist($objet, $id_objet, $id_forum, $forcer_previsu, $statut, $retour = '') {
	include_spip('inc/acces');
	include_spip('inc/texte');
	include_spip('inc/forum');
	include_spip('inc/session');
	include_spip('base/abstract_sql');

	$erreurs = [];

	$min_length = (defined('_FORUM_LONGUEUR_MINI') ? _FORUM_LONGUEUR_MINI : 10);
	if (
		strlen($texte = _request('texte')) < $min_length
		and !_request('ajouter_mot') and $GLOBALS['meta']['forums_texte'] == 'oui'
	) {
		$erreurs['texte'] = _T(
			$min_length == 10 ? 'forum:forum_attention_dix_caracteres' : 'forum:forum_attention_nb_caracteres_mini',
			['min' => $min_length]
		);
	} else {
		if (
			defined('_FORUM_LONGUEUR_MAXI')
			and _FORUM_LONGUEUR_MAXI > 0
			and strlen($texte) > _FORUM_LONGUEUR_MAXI
		) {
			$erreurs['texte'] = _T(
				'forum:forum_attention_trop_caracteres',
				[
					'compte' => strlen($texte),
					'max' => _FORUM_LONGUEUR_MAXI
				]
			);
		}
	}

	if (
		strlen($titre = _request('titre')) < 3
		and $GLOBALS['meta']['forums_titre'] == 'oui'
	) {
		$erreurs['titre'] = _T('forum:forum_attention_trois_caracteres');
	}

	if (array_reduce($_POST, 'reduce_strlen', (20 * 1024)) < 0) {
		$erreurs['erreur_message'] = _T('forum:forum_message_trop_long');
	}

	if ($url = _request('url_site') and !tester_url_absolue($url)) {
		$erreurs['url_site'] = _T('info_url_site_pas_conforme');
	}

	if (!count($erreurs) and !_request('envoyer_message') and !_request('confirmer_previsu_forum')) {
		$previsu = inclure_forum_prive_previsu(
			$texte,
			$titre,
			_request('url_site'),
			_request('nom_site'),
			_request('ajouter_mot')
		);
		$erreurs['previsu'] = $previsu;
		$erreurs['message_erreur'] = ''; // on ne veut pas du message_erreur automatique
	}

	return $erreurs;
}


function inclure_forum_prive_previsu($texte, $titre, $url_site, $nom_site, $ajouter_mot, $doc = '') {
	$bouton = _T('forum:forum_message_definitif');
	include_spip('public/assembler');
	include_spip('public/composer');
	// supprimer les <form> de la previsualisation
	// (sinon on ne peut pas faire <cadre>...</cadre> dans les forums)
	return preg_replace(
		"@<(/?)form\b@ism",
		'<\1div',
		inclure_balise_dynamique(
			[
			'formulaires/inc-forum_prive_previsu',
			0,
			[
				'titre' => safehtml(typo($titre)),
				'texte' => safehtml(propre($texte)),
				'notes' => safehtml(calculer_notes()),
				'url_site' => vider_url($url_site),
				'nom_site' => safehtml(typo($nom_site)),
				'ajouter_mot' => (is_array($ajouter_mot) ? $ajouter_mot : [$ajouter_mot]),
				'ajouter_document' => $doc,
				#'erreur' => $erreur, // kesako ? non définie ?
				'bouton' => $bouton
			]
			],
			false
		)
	);
}


function formulaires_forum_prive_traiter_dist($objet, $id_objet, $id_forum, $forcer_previsu, $statut, $retour = '') {

	$forum_insert = charger_fonction('forum_insert', 'inc');
	$id_reponse = $forum_insert($objet, $id_objet, $id_forum, $statut);
	if ($id_reponse) {
		// En cas de retour sur (par exemple) {#SELF}, on ajoute quand
		// meme #forum12 a la fin de l'url, sauf si un #ancre est explicite
		if ($retour) {
			if (!strpos($retour, '#')) {
				$retour .= '#forum' . $id_reponse;
			}
		} else {
			// le retour par defaut envoie sur le thread, ce qui permet
			// de traiter elegamment le cas des forums moderes a priori.
			// Cela assure aussi qu'on retrouve son message dans le thread
			// dans le cas des forums moderes a posteriori, ce qui n'est
			// pas plus mal.
			if (function_exists('generer_url_forum')) {
				$retour = generer_url_forum($id_reponse);
			} else {
				$thread = sql_fetsel('id_thread', 'spip_forum', 'id_forum=' . $id_reponse);
				spip_log('id_thread=' . $thread['id_thread'], 'forum');
				$retour = generer_objet_url($thread['id_thread'], 'forum');
			}
		}

		$res = ['redirect' => $retour, 'id_forum' => $id_forum];
	} else {
		$res = ['message_erreur' => _T('forum:erreur_enregistrement_message')];
	}

	return $res;
}
