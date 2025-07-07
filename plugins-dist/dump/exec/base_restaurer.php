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
 * Gestion d'affichage de la page en cas de restauration interrompue
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Finir une restauration interrompue par logout
 */
function exec_base_restaurer_dist() {
	include_spip('base/dump');
	$status_file = base_dump_meta_name(0) . '_restauration';
	$restaurer = charger_fonction('restaurer', 'action');
	$restaurer($status_file);
}
