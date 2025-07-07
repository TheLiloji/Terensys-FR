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
 * Gestion de l'action supprimer_mot
 *
 * @package SPIP\Mots\Actions
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Action supprimant un mot clé dans la base de données dont l'identifiant
 * est en argument de l'action sécurisée
 */
function action_supprimer_mot_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$id_mot = $securiser_action();

	include_spip('action/editer_mot');
	mot_supprimer($id_mot);
}
