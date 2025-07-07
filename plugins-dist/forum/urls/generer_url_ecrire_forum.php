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

function urls_generer_url_ecrire_forum_dist($id, $args = '', $ancre = '', $public = null, $connect = '') {
	$a = 'id_forum=' . intval($id);
	if (is_null($public) and !$connect) {
		$public = objet_test_si_publie('forum', $id, $connect);
	}
	$h = ($public or $connect)
		? generer_objet_url_absolue($id, 'forum', $args, $ancre, $connect)
		: (generer_url_ecrire('controler_forum', "debut_forum=@$id" . ($args ? "&$args" : ''))
			. ($ancre ? "#$ancre" : ''));

	return $h;
}
