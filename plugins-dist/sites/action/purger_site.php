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

function action_purger_site_dist($id_syndic = null) {

	if (is_null($id_syndic)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_syndic = $securiser_action();
	}

	if (
		$id_syndic = intval($id_syndic)
		and autoriser('purger', 'site', $id_syndic)
	) {
		include_spip('base/abstract_sql');
		sql_delete('spip_syndic_articles', 'id_syndic=' . intval($id_syndic));
	}
}
