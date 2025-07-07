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
 * Afficher le diff d'un id_rubrique
 * on affiche en fait le deplacement si id_rubrique a change
 * le nom de la rubrique sinon
 *
 * @param string $champ
 * @param string $old
 * @param string $new
 * @param string $format
 * @return string
 */
function afficher_diff_id_rubrique_dist($champ, $old, $new, $format = 'diff') {
	// ne pas se compliquer la vie !
	if ($old == $new) {
		$out = _T('info_dans_rubrique')
			. ' <b>&#171;&nbsp;' . generer_objet_info($new, 'rubrique', 'titre') . '&nbsp;&#187;</b>';
	} else {
		$out = _T(
			'revisions:version_deplace_rubrique',
			[
				'from' => generer_objet_info($old, 'rubrique', 'titre'),
				'to' => generer_objet_info($new, 'rubrique', 'titre')
			]
		);
	}

	return $out;
}
