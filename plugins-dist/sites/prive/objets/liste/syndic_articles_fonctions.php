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

/**
 * Un test sur les articles syndiques
 * pour "depublier les items qui ne figurent plsu dans le flux"
 *
 * @global <type> $my_sites
 * @param <type> $id
 * @return <type>
 */
function filtre_test_syndic_article_miroir_dist($id) {
	if (isset($GLOBALS['my_sites'][$id]['miroir']) and $GLOBALS['my_sites'][$id]['miroir'] == 'oui') {
		return ' ';
	}

	return '';
}
