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

include_spip('inc/filtres_images_lib_mini');

/**
 * Toutes les fonctions couleur_xx de ce fichier :
 *  - prennent une couleur hexa sur 6 caracteres en entree (les couleurs web nommees sont admises aussi)
 *  - fournissent une couleur hexa en sortie
 *  - sont chainables les unes derrieres les autres dans toutes les combinaisons possibles
 */

function couleur_extraire($img, $x = 10, $y = 6) {
	include_spip('filtres/images_lib');

	return _image_couleur_extraire($img, $x, $y);
}


function couleur_web($couleur) {
	include_spip('filtres/images_lib');
	$rvb = _couleur_hex_to_dec($couleur);

	$rvb = array_map('multiple_de_trois', $rvb);

	return _couleur_dec_to_hex($rvb['red'], $rvb['green'], $rvb['blue']);
}

function couleur_4096($couleur) {
	$r = (substr($couleur, 0, 1));
	$v = (substr($couleur, 2, 1));
	$b = (substr($couleur, 4, 1));

	return "$r$r$v$v$b$b";
}

// Lire la luminance relative d'une couleur
// de 0 à 1
// cf. https://fr.wikipedia.org/wiki/Luminance#Luminance_relative
// cf. https://bl.ocks.org/Fil/cf03a054826ee5b3013577ecc0b009e6
function couleur_luminance_relative($couleur) {
	$c = _couleur_hex_to_dec($couleur);
	return (0.2126 * $c['red'] + 0.7152 * $c['green'] + 0.0722 * $c['blue']) / 255;
}

function couleur_extreme($couleur, $limite = 0.5) {
	// force la couleur au noir ou au blanc le plus proche
	// -> donc couleur foncee devient noire
	//    et couleur claire devient blanche
	// -> la limite est une valeur de 0 a 255, permettant de regler le point limite entre le passage noir ou blanc

	if (couleur_luminance_relative($couleur) > $limite) {
		$couleur_texte = 'ffffff';
	} else {
		$couleur_texte = '000000';
	}

	return $couleur_texte;
}

function couleur_inverser($couleur) {
	$couleurs = _couleur_hex_to_dec($couleur);
	$red = 255 - $couleurs['red'];
	$green = 255 - $couleurs['green'];
	$blue = 255 - $couleurs['blue'];

	$couleur = _couleur_dec_to_hex($red, $green, $blue);

	return $couleur;
}

function couleur_foncer_si_claire($couleur, $seuil = 122) {
	// ne foncer que les couleurs claires
	// utile pour ecrire sur fond blanc,
	// mais sans changer quand la couleur est deja foncee
	if (couleur_luminance_relative($couleur) > $seuil / 255) {
		include_spip('inc/filtres_images_mini');
		return couleur_foncer($couleur);
	} else {
		return $couleur;
	}
}

function couleur_eclaircir_si_foncee($couleur, $seuil = 123) {
	if (couleur_luminance_relative($couleur) < $seuil / 255) {
		include_spip('inc/filtres_images_mini');
		return couleur_eclaircir($couleur);
	} else {
		return $couleur;
	}
}

/**
 * Modifie la saturation et parfois la luminosité de la couleur transmise
 *
 * Opère sur une échelle absolue.
 *
 * @link https://www.spip.net/3326
 * @example
 *     - `[(#VAL{fc3924}|couleur_saturation{0})]` retourne blanc (ffffff),
 *     - `[(#VAL{fc3924}|couleur_saturation{1})]` retourne la couleur avec sa saturation au maximum (fb1800)
 *     - `[(#VAL{fc3924}|couleur_saturation{0.2})]` retourne la couleur avec 20% de saturation (fed0cc)
 *
 * @uses _couleur_hex_to_dec()
 * @uses _couleur_dec_to_hex()
 *
 * @param string $couleur
 *      Couleur en écriture hexadécimale, tel que `ff3300`
 * @param float $val
 *      Pourcentage désiré (entre 0 et 1)
 * @param bool|string $strict
 *      Si true, ne change que la saturation, sans toucher à la luminosité
 * @return string
 *      Couleur en écriture hexadécimale.
**/
function couleur_saturation($couleur, $val, $strict = false) {

	include_spip('filtres/images_lib');

	// Soit on ne change que la saturation
	if ($strict) {
		$old_rgb         = _couleur_hex_to_dec($couleur);
		$hsl             = _couleur_rgb_to_hsl($old_rgb['red'], $old_rgb['green'], $old_rgb['blue']);
		$rgb             = _couleur_hsl_to_rgb($hsl['h'], floatval($val), $hsl['l']);
		[$r, $g, $b] = array_values($rgb);

	// Soit on joue sur la saturation et la luminosité
	} else {
		$couleurs = _couleur_hex_to_dec($couleur);
		$r = 255 - $couleurs['red'];
		$g = 255 - $couleurs['green'];
		$b = 255 - $couleurs['blue'];

		$max = max($r, $g, $b, 1);

		$r = 255 - intval($r / $max * 255 * $val);
		$g = 255 - intval($g / $max * 255 * $val);
		$b = 255 - intval($b / $max * 255 * $val);
	}

	$couleur = _couleur_dec_to_hex($r, $g, $b);

	return $couleur;
}

/**
 * Modifie la luminance de la couleur transmise
 *
 * Change la luminance en forçant le résultat sur une échelle absolue.
 *
 * @link https://www.spip.net/3326
 * @example
 *     - `[(#VAL{fc3924}|couleur_luminance{0})]` retourne blanc (ffffff),
 *     - `[(#VAL{fc3924}|couleur_luminance{1})]` retourne noir (000000)
 *     - `[(#VAL{fc3924}|couleur_luminance{0.5})]` retourne une luminance moyenne (fb1b03)
 *     - `[(#VAL{fc3924}|couleur_luminance{0.2})]` retourne la couleur avec 20% de luminance (fda49a)
 *
 * @uses _couleur_hex_to_dec()
 * @uses couleur_saturation()
 * @uses _couleur_rgb2hsl()
 * @uses _couleur_hsl2rgb()
 * @uses _couleur_dec_to_hex()
 *
 * @param string $couleur
 *      Couleur en écriture hexadécimale, tel que `ff3300`
 * @param float $val
 *      Pourcentage désiré (entre 0 et 1)
 * @return string
 *      Couleur en écriture hexadécimale.
**/
function couleur_luminance($couleur, $val) {
	include_spip('filtres/images_lib');

	$couleurs = _couleur_hex_to_dec($couleur);
	$r = $couleurs['red'];
	$g = $couleurs['green'];
	$b = $couleurs['blue'];

	// Cas etonnant: quand gris parfait, la correction de HSL ne fonctionne pas
	// en revanche, couleur_saturation retourne exactement la bonne valeur
	if ($r == $g && $g == $b) {
		return couleur_saturation($couleur, $val);
	}


	$couleur = _couleur_rgb_to_hsl($r, $g, $b);
	$h = $couleur['h'];
	$s = $couleur['s'];
	$l = $couleur['l'];

	$rgb = _couleur_hsl_to_rgb($h, $s, 1 - $val);
	$r = $rgb['r'];
	$g = $rgb['g'];
	$b = $rgb['b'];

	$retour = _couleur_dec_to_hex($r, $g, $b);

	return $retour;
}
