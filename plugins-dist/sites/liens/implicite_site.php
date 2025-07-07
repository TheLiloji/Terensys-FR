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

function liens_implicite_site_dist($texte, $id, $type, $args, $ancre, $connect = '') {
	if (!$id = intval($id)) {
		return false;
	}
	$url = sql_getfetsel('url_site', 'spip_syndic', 'id_syndic=' . intval($id), '', '', '', '', $connect);

	return $url;
}
