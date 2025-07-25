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


//
// Fonctions graphiques
//
// La matrice permet au compilateur de reconnaitre un filtre
// et de faire la bonne inclusion au moment de son appel dans un squelette

// Filtres image -> image
$GLOBALS['spip_matrice']['image_recadre'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_recadre_mini'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_alpha'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_flip_vertical'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_flip_horizontal'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_masque'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_nb'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_flou'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_RotateBicubic'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_rotation'] = 'filtres/images_transforme.php';

$GLOBALS['spip_matrice']['image_gamma'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_sepia'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_aplatir'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_format'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_renforcement'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_imagick'] = 'filtres/images_transforme.php';
$GLOBALS['spip_matrice']['image_fond_transparent'] = 'filtres/images_transforme.php';


// Filtres couleur -> couleur
$GLOBALS['spip_matrice']['couleur_extraire'] = 'filtres/couleurs.php';
$GLOBALS['spip_matrice']['couleur_extreme'] = 'filtres/couleurs.php';
$GLOBALS['spip_matrice']['couleur_inverser'] = 'filtres/couleurs.php';
$GLOBALS['spip_matrice']['couleur_foncer_si_claire'] = 'filtres/couleurs.php';
$GLOBALS['spip_matrice']['couleur_eclaircir_si_foncee'] = 'filtres/couleurs.php';
$GLOBALS['spip_matrice']['couleur_saturation'] = 'filtres/couleurs.php';
$GLOBALS['spip_matrice']['couleur_luminance'] = 'filtres/couleurs.php';
$GLOBALS['spip_matrice']['couleur_web'] = 'filtres/couleurs.php';
$GLOBALS['spip_matrice']['couleur_4096'] = 'filtres/couleurs.php';

// ces filtres la ne devraient jamais etre appeles dans les squelettes en direct
// et n'ont rien a faire dans la matrice
/*
$GLOBALS['spip_matrice']['_image_couleur_extraire'] = 'filtres/images_lib.php';
$GLOBALS['spip_matrice']['_couleur_dec_to_hex'] = 'filtres/images_lib.php';
$GLOBALS['spip_matrice']['_couleur_hex_to_dec'] = 'filtres/images_lib.php';
$GLOBALS['spip_matrice']['_image_distance_pixel'] = 'filtres/images_lib.php';
$GLOBALS['spip_matrice']['_image_decale_composante'] = 'filtres/images_lib.php';
*/


/**
 * Créer une image typo
 * @deprecated 4.2 Utiliser le plugin `images_typo`
 * @note
 *    Cas particulier historique : son nom commence par "image_"
 *    mais s'applique sur un texte…
 * @return string
 */
function image_typo() {
	trigger_deprecation('spip', '4.4', 'Using "|%s" from SPIP is deprecated, install "%s" plugin to get the same functionnality', __FUNCTION__, 'images_typo');
	include_spip('inc/filtres_images_mini.php');
	include_spip('filtres/images_typo');
	$tous = func_get_args();
	return call_user_func_array('produire_image_typo', $tous);
}
