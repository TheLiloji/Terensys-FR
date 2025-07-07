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
 * Gestion des vignettes de types de fichier
 *
 * @package SPIP\Medias\Vignette
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Verifier si le fichier respecte les contraintes de tailles
 *
 * @param  array $infos
 * @param  bool $is_logo
 * @return bool|mixed|string
 */
function inc_verifier_taille_document_acceptable_dist(&$infos, $is_logo = false) {

	// si ce n'est pas une image
	if (!$infos['type_image']) {
		$max_size = (defined('_DOC_MAX_SIZE') and _DOC_MAX_SIZE) ? _DOC_MAX_SIZE : null;
		$res = medias_verifier_poids_fichier($infos, $max_size, false);
		if ($res !== true) {
			return $res;
		}
	} // si c'est une image
	else {
		// Ne pas contraindre les dimensions des images vectorielles, ça n'a pas de sens
		if ($infos['type_image'] !== 'svg') {
			if ($is_logo) {
				$max_width = (defined('_LOGO_MAX_WIDTH') and _LOGO_MAX_WIDTH) ? _LOGO_MAX_WIDTH : null;
				$max_height = (defined('_LOGO_MAX_HEIGHT') and _LOGO_MAX_HEIGHT) ? _LOGO_MAX_HEIGHT : null;
				$min_width = (defined('_LOGO_MIN_WIDTH') and _LOGO_MIN_WIDTH) ? _LOGO_MIN_WIDTH : null;
				$min_height = (defined('_LOGO_MIN_HEIGHT') and _LOGO_MIN_HEIGHT) ? _LOGO_MIN_HEIGHT : null;
			}
			else {
				$max_width = (defined('_IMG_MAX_WIDTH') and _IMG_MAX_WIDTH) ? _IMG_MAX_WIDTH : null;
				$max_height = (defined('_IMG_MAX_HEIGHT') and _IMG_MAX_HEIGHT) ? _IMG_MAX_HEIGHT : null;
				$min_width = (defined('_IMG_MIN_WIDTH') and _IMG_MIN_WIDTH) ? _IMG_MIN_WIDTH : null;
				$min_height = (defined('_IMG_MIN_HEIGHT') and _IMG_MIN_HEIGHT) ? _IMG_MIN_HEIGHT : null;
			}

			$res = medias_verifier_largeur_hauteur_image($infos, $max_width, $max_height, $min_width, $min_height);
			if ($res !== true) {
				return $res;
			}
		}

		if ($is_logo) {
			$max_size = (defined('_LOGO_MAX_SIZE') and _LOGO_MAX_SIZE) ? _LOGO_MAX_SIZE : null;
		}
		else {
			$max_size = (defined('_IMG_MAX_SIZE') and _IMG_MAX_SIZE) ? _IMG_MAX_SIZE : null;
		}

		$res = medias_verifier_poids_fichier($infos, $max_size, true);
		if ($res !== true) {
			return $res;
		}
	}

	return true;
}

/**
 * Verifier largeur maxi et hauteur maxi d'une image
 * + largeur mini et hauteur mini
 * @param array $infos
 * @param null|int $max_width
 * @param null|int $max_height
 * @param null|int $min_width
 * @param null|int $min_height
 * @return bool|string
 */
function medias_verifier_largeur_hauteur_image(&$infos, $max_width = null, $max_height = null, $min_width = null, $min_height = null) {

	if (
		($max_width and $infos['largeur'] > $max_width)
		or ($max_height and $infos['hauteur'] > $max_height)
	) {
		// pas la peine d'embeter le redacteur avec ca si on a active le calcul des miniatures
		// on met directement a la taille maxi a la volee
		if (isset($GLOBALS['meta']['creer_preview']) and $GLOBALS['meta']['creer_preview'] == 'oui') {
			include_spip('inc/filtres');
			$img = filtrer('image_reduire', $infos['fichier'], $max_width ?: '*', $max_height ?: '*');
			$img = extraire_attribut($img, 'src');
			$img = supprimer_timestamp($img);
			if (@file_exists($img) and $img !== $infos['fichier']) {
				spip_unlink($infos['fichier']);
				@rename($img, $infos['fichier']);
				[$h, $w] = taille_image($infos['fichier'], true);
				$infos['largeur'] = $w;
				$infos['hauteur'] = $h;
				$infos['taille'] = @filesize($infos['fichier']);
			}
		}

		if (
			($max_width and $infos['largeur'] > $max_width)
			or ($max_height and $infos['hauteur'] > $max_height)
		) {
			return _T(
				'medias:info_image_max_taille',
				[
					'maxi' =>
						_T(
							'info_largeur_vignette',
							[
								'largeur_vignette' => $max_width ?? '∞',
								'hauteur_vignette' => $max_height ?? '∞'
							]
						),
					'actuel' =>
						_T(
							'info_largeur_vignette',
							[
								'largeur_vignette' => $infos['largeur'],
								'hauteur_vignette' => $infos['hauteur']
							]
						)
				]
			);
		}
	}

	if (
		($min_width and $infos['largeur'] < $min_width)
		or ($min_height and $infos['hauteur'] < $min_height)
	) {
		if ($min_width and $max_width and $min_width > $max_width) {
			spip_log('Constantes invalides détectées, modifiez votre fichier de configuration (_IMG_MIN_WIDTH > _IMG_MAX_WIDTH)', 'medias' . _LOG_INFO_IMPORTANTE);
		}
		if ($min_height and $max_height and $min_height > $max_height) {
			spip_log('Constantes invalides détectées, modifiez votre fichier de configuration (_IMG_MIN_HEIGHT > _IMG_MAX_HEIGHT)', 'medias' . _LOG_INFO_IMPORTANTE);
		}

		return _T(
			'medias:info_image_min_taille',
			[
				'mini' =>
					_T(
						'info_largeur_vignette',
						[
							'largeur_vignette' => $min_width ?? '0',
							'hauteur_vignette' => $min_height ?? '0'
						]
					),
				'actuel' =>
					_T(
						'info_largeur_vignette',
						[
							'largeur_vignette' => $infos['largeur'],
							'hauteur_vignette' => $infos['hauteur']
						]
					)
			]
		);
	}


	return true;
}

/**
 * verifier le poids maxi d'une image
 * @param array $infos
 * @param null|int $max_size
 * @return bool|string
 */
function medias_verifier_poids_fichier($infos, $max_size = null, $is_image = false) {
	if ($max_size and $infos['taille'] > $max_size * 1024) {
		return _T(
			$is_image ? 'medias:info_image_max_poids' : 'medias:info_doc_max_poids',
			[
				'maxi' => taille_en_octets($max_size * 1024),
				'actuel' => taille_en_octets($infos['taille'])
			]
		);
	}

	return true;
}
