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

include_spip('inc/diff');

/**
 * Afficher le diff d'un champ texte generique
 *
 * @param string $champ
 * @param string $old
 * @param string $new
 * @param string $format
 *   apercu, diff ou complet
 * @return string
 */
function afficher_diff_jointure_dist($champ, $old, $new, $format = 'diff') {
	$join = substr($champ, 9);
	$objet = objet_type($join);

	$old = explode(',', $old);
	$new = explode(',', $new);

	$liste = [];

	// les communs
	$intersection = array_intersect($new, $old);
	foreach ($intersection as $id) {
		if ($id = intval(trim($id))) {
			$liste[$id] = "<a href='" . generer_objet_url($id, $objet) . "' title='" . _T(objet_info($objet, 'texte_objet')) . " $id'>"
				. generer_objet_info($id, $objet, 'titre')
				. '</a>';
		}
	}

	// les supprimes
	$old = array_diff($old, $intersection);
	foreach ($old as $id) {
		if ($id = intval(trim($id))) {
			$liste[$id] = "<span class='diff-supprime'>"
				. "<a href='" . generer_objet_url($id, $objet) . "' title='" . _T(objet_info($objet, 'texte_objet')) . " $id'>"
				. generer_objet_info($id, $objet, 'titre')
				. '</a>'
				. '</span>';
		}
	}

	// les ajoutes
	$new = array_diff($new, $intersection);
	foreach ($new as $id) {
		if ($id = intval(trim($id))) {
			$liste[$id] = "<span class='diff-ajoute'>"
				. "<a href='" . generer_objet_url($id, $objet) . "' title='" . _T(objet_info($objet, 'texte_objet')) . " $id'>"
				. generer_objet_info($id, $objet, 'titre')
				. '</a>'
				. '</span>';
		}
	}

	ksort($liste);
	$liste = implode(', ', $liste);

	return $liste;
}
