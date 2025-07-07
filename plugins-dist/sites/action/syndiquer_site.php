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

function action_syndiquer_site_dist($id_syndic = null) {

	if (is_null($id_syndic)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_syndic = $securiser_action();
	}


	$id_job = job_queue_add('syndic_a_jour', 'syndic_a_jour', [$id_syndic], 'genie/syndic', true);
	// l'executer immediatement si possible
	if ($id_job) {
		include_spip('inc/queue');
		queue_schedule([$id_job]);
	} else {
		spip_log("Erreur insertion syndic_a_jour($id_syndic) dans la file des travaux", _LOG_ERREUR);
	}
}
