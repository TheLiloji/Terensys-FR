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

function metadata_mp4_dist($file) {

	// est-ce une video ?
	$metadata = charger_fonction('video', 'metadata');
	$meta = $metadata($file);
	if (!empty($meta['media'])) {
		return $meta;
	}

	// sinon est-ce un audio ?
	$metadata = charger_fonction('audio', 'metadata');
	$meta = $metadata($file);
	if (!empty($meta['media'])) {
		return $meta;
	}

	// sinon c'est un media 'file' qui aura donc un mime-type application/mp4
	return [
		'media' => 'file'
	];
}
