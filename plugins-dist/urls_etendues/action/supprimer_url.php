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

function action_supprimer_url_dist($arg = null) {

	if (is_null($arg)) {
		// Rien a faire ici pour le moment
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}
	if (strncmp($arg, '-1-', 3) == 0) {
		$id_parent = -1;
		$url = substr($arg, 3);
	} else {
		$arg = explode('-', $arg);
		$id_parent = array_shift($arg);
		$url = implode('-', $arg);
	}

	$where = 'id_parent=' . intval($id_parent) . ' AND url=' . sql_quote($url);
	if ($row = sql_fetsel('*', 'spip_urls', $where)) {
		if (autoriser('modifierurl', $row['type'], $row['id_objet'])) {
			include_spip('action/editer_url');
			url_delete($row['type'], $row['id_objet'], $url, $id_parent);
			spip_log('on supprime l\'url ' . $url . ' pour ' . $row['type'] . '/' . $row['id_objet'] . "/$id_parent", 'urls');
		} else {
			spip_log('supprimer sans autorisation l\'URL ' . $id_parent . '://' . $url, 'urls.' . _LOG_ERREUR);
		}
	} else {
		spip_log('Impossible de supprimer une URL inconnue ' . $id_parent . '://' . $url, 'urls.' . _LOG_INFO_IMPORTANTE);
	}
}
