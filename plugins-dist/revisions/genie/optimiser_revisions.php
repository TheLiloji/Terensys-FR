<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * @plugin Revisions pour SPIP
 *
 * @deprecated 4.0 Utiliser genie/revisions_optimiser_revisions.php
 *
 * @license GPL
 * @package SPIP\Revisions\Genie
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('genie/revisions_optimiser_revisions');

/**
 * Tâche Cron d'optimisation des révisions
 *
 * @deprecated 4.0
 * @uses genie_revisions_optimiser_revisions_dist()
 *
 * @param int $last
 *     Timestamp de la dernière exécution de cette tâche
 * @return int
 *     Positif : la tâche a été effectuée
 */
function genie_optimiser_revisions_dist($last) {
	$revisions_optimiser_revisions = charger_fonction('revisions_optimiser_revisions', 'genie');
	return $revisions_optimiser_revisions($last);
}
