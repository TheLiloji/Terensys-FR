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

function metadata_image_dist($fichier) {
	$meta = [];

	if ($size_image = @spip_getimagesize($fichier)) {
		$meta['largeur'] = intval($size_image[0]);
		$meta['hauteur'] = intval($size_image[1]);
		$meta['type_image'] = decoder_type_image($size_image[2]);
	}

	return $meta;
}

/**
 * Convertit le type numerique retourne par getimagesize() en extension fichier
 *
 * @param int $type
 * @param bool $strict
 * @return string
 */
function decoder_type_image($type, $strict = false) {
	switch ($type) {
		case IMAGETYPE_GIF:
			return 'gif';
		case IMAGETYPE_JPEG:
			return 'jpg';
		case IMAGETYPE_PNG:
			return 'png';
		case IMAGETYPE_SWF:
			return $strict ? '' : 'swf';
		case IMAGETYPE_PSD:
			return 'psd';
		case IMAGETYPE_BMP:
			return 'bmp';
		case IMAGETYPE_TIFF_II:
		case IMAGETYPE_TIFF_MM:
			return 'tif';
		case IMAGETYPE_WEBP:
			return 'webp';
		case IMAGETYPE_SVG:
			return $strict ? '' : 'svg';
		default:
			return '';
	}
}
