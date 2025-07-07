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
 * Vignette pour une extension de document
 *
 * Recherche les fichiers d'icones au format SVG pour l'extension demandée.
 * On cherche prive/vignettes/ext.svg dans le path.
 *
 * @param string $ext
 *     Extension du fichier. Exemple : png
 * @param string $media
 *     Permet de retourner une variante de la vignette adaptee au media
 *     cas des mp4 par exemple que l'on decline quand c'est un audio
 * @param bool $loop
 *     Autoriser la fonction à s'appeler sur elle-même
 *     (paramètre interne).
 * @return ?string
 *     null si l'image n'est pas trouvée
 *     Chaîne (chemin vers l'image) si on ne demande pas de taille
 */
function inc_vignette_dist($ext, $media = '', $loop = true) {

	// deprecated signature avec second argument true pour récuperer un tableau avec size
	if ($media === true) {
		trigger_deprecation(
			'medias',
			'2.0',
			'Using "%s" is deprecated',
			'vignette($ext, true)'
		);
		$v = inc_vignette_dist($ext, '', $loop);
		$largeur = $hauteur = 0;
		if ($v && ($size = @spip_getimagesize($v))) {
			$largeur = $size[0];
			$hauteur = $size[1];
		}

		return [$v, $largeur, $hauteur];
	}

	if (!$ext) {
		$ext = 'txt';
	}

	// Chercher la vignette correspondant a ce type de document
	// dans les vignettes persos, ou dans les vignettes standard
	# installation dans un dossier /vignettes personnel, par exemple /squelettes/vignettes
	if (!$media || !($v = find_in_path('prive/vignettes/' . $ext . '-' . $media . '.svg'))) {
		$v = find_in_path('prive/vignettes/' . $ext . '.svg');
	}
	if (!$v) {
		if ($loop) {
			$f = charger_fonction('vignette', 'inc');
			$v = $f('defaut', $media, false);
		} else {
			$v = false;
		}
	} # pas trouve l'icone de base

	return $v ?: null;
}
