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
 * Cette action permet de basculer du mode image au mode document et vice versa
 *
 * @param int $id_document
 * @param string $mode
 * @return void
 */
function action_changer_mode_document_dist($id_document = null, $mode = null) {
	if (is_null($id_document) or is_null($mode)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();

		if (!preg_match(',^(\d+)\W(\w+)$,', $arg, $r)) {
			spip_log("action_changer_mode_document $arg pas compris");
		} else {
			array_shift($r);
			[$id_document, $mode] = $r;
		}
	}

	if (
		$id_document
		and include_spip('inc/autoriser')
		and autoriser('modifier', 'document', $id_document)
	) {
		action_changer_mode_document_post($id_document, $mode);
	}
}

function action_changer_mode_document_post($id_document, $mode) {
	// - id_document le doc a modifier
	// - mode le mode a lui donner
	if (
		$id_document = intval($id_document)
		and in_array($mode, ['vignette', 'image', 'document'])
	) {
		include_spip('action/editer_document');
		document_modifier($id_document, ['mode' => $mode]);
	}
}
