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
 * Toutes les fonctions image_xx de ce fichier :
 *  - prennent une image en entree
 *  - fournissent une image en sortie
 *  - sont chainables les unes derrieres les autres dans toutes les combinaisons possibles
 *
 * @package SPIP\FiltresImages\ImagesTransforme
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// librairie de base du core
include_spip('inc/filtres_images_mini');

/**
 * Un filtre pour re-orienter automatiquement une image selon son exif - si besoin
 * si pas d'exif ou pas d'orientation, le filtre ne fait rien et renvoie l'image d'origine
 *
 * @param string $img
 * @return string
 */
function image_oriente_selon_exif($img) {
	$fonction = ['image_oriente_selon_exif', func_get_args()];
	$image = _image_valeurs_trans($img, 'oriente_selon_exif', false, $fonction, false, _SVG_SUPPORTED);
	if (!$image) {
		return $img;
	}

	if ($image['creer']) {
		// SVG : on ne fait rien...
		if ($image['format_source'] === 'svg') {
			return $img;
		}

		$exif_data = function_exists('exif_read_data') ? @exif_read_data($image['fichier']) : [];
		// si pas d'exif data ou pas d'orientation on ne fait rien, on retourne l'image d'origine, cas particulier de ce filtre
		if (!$exif_data) {
			return $img;
		}
		$exif_orientation = ($exif_data['Orientation'] ?? 0);
		if (!$exif_orientation || !in_array($exif_orientation, [2, 3, 4, 5, 6, 7, 8])) {
			return $img;
		}

		$im = @$image['fonction_imagecreatefrom']($image['fichier']);
		if (!$im) {
			return '';
		}
		// il faut re-orienter l'image donc
		switch ($exif_orientation) {
			case 3:
			case 4:
				$im = imagerotate($im, 180, 0);
				break;
			case 5:
			case 6:
				$im = imagerotate($im, -90, 0);
				break;
			case 7:
			case 8:
				$im = imagerotate($im, 90, 0);
				break;
		}
		if (in_array($exif_orientation, [2, 5, 7, 4])) {
			imageflip($im, IMG_FLIP_HORIZONTAL);
		}
		// produire le resultat
		_image_gd_output($im, $image);
		imagedestroy($im);
	}
	return _image_ecrire_tag($image, ['src' => $image['fichier_dest']]);
}

// 1/ Aplatir une image semi-transparente (supprimer couche alpha)
// en remplissant la transparence avec couleur choisir $coul.
// 2/ Forcer le format de sauvegarde (jpg, png, gif)
// pour le format jpg, $qualite correspond au niveau de compression (defaut 85)
// pour le format gif, $qualite correspond au nombre de couleurs dans la palette (defaut 128)
// pour le format png, $qualite correspond au nombre de couleur dans la palette ou si 0 a une image truecolor (defaut truecolor)
// attention, seul 128 est supporte en l'etat (production d'images avec palette reduite pas satisfaisante)
// 3/ $transparence a "true" permet de conserver la transparence (utile pour conversion GIF)
function image_aplatir($im, $format = 'jpg', $coul = '000000', $qualite = null, $transparence = false) {
	$transp_y = null;
	if ($qualite === null) {
		if (in_array($format, ['jpg', 'webp'], true)) {
			$qualite = _IMG_GD_QUALITE;
		} elseif ($format == 'png') {
			$qualite = 0;
		} else {
			$qualite = 128;
		}
	}
	$qualite = intval($qualite);

	$fonction = ['image_aplatir', func_get_args()];
	$image = _image_valeurs_trans($im, "aplatir-$format-$coul-$qualite-$transparence", $format, $fonction, '', _SVG_SUPPORTED);
	if (!$image) {
		return '';
	}
	if ($image['format_source'] === 'svg') {
		// ne pas forcer le format
		$image = _image_valeurs_trans($im, "aplatir-$format-$coul-$qualite-$transparence", false, $fonction, '', _SVG_SUPPORTED);
	}

	include_spip('inc/filtres');
	$couleurs = _couleur_hex_to_dec($coul);
	$dr = $couleurs['red'];
	$dv = $couleurs['green'];
	$db = $couleurs['blue'];

	$x_i = $image['largeur'];
	$y_i = $image['hauteur'];

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];
	if ($creer) {
		if ($image['format_source'] === 'svg') {
			if ($transparence) {
				$svg = svg_charger($im);
			} else {
				$svg = svg_ajouter_background($im, $coul);
			}
			_image_gd_output($svg, $image);
		} else {
			$im = @$image['fonction_imagecreatefrom']($im);
			if (!$im) {
				return '';
			}
			imagepalettetotruecolor($im);

			// si c'est un gif source, commencer par en faire une copie
			if ($image['format_source'] == 'gif') {
				$im_ = imagecreatetruecolor($x_i, $y_i);
				// Si un GIF est transparent,
				// fabriquer un PNG transparent
				// Conserver la transparence
				@imagealphablending($im_, false);
				@imagesavealpha($im_, true);
				if (function_exists('imageAntiAlias')) {
					imageAntiAlias($im_, true);
				}
				@ImageCopyResampled($im_, $im, 0, 0, 0, 0, $x_i, $y_i, $x_i, $y_i);
				imagedestroy($im);
				$im = $im_;
			}

			$im_ = imagecreatetruecolor($x_i, $y_i);
			// allouer la couleur de fond
			if ($transparence) {
				@imagesavealpha($im_, true);
				$color_t = imagecolorallocatealpha($im_, $dr, $dv, $db, 127);
			} else {
				$color_t = ImageColorAllocate($im_, $dr, $dv, $db);
			}

			imagefill($im_, 0, 0, $color_t);
			imagelayereffect($im_, IMG_EFFECT_ALPHABLEND);
			imagecopy($im_, $im, 0, 0, 0, 0, $x_i, $y_i);

			// passer en palette si besoin
			if ($format == 'gif' or ($format == 'png' and $qualite !== 0)) {
				// creer l'image finale a palette
				// (on recycle l'image initiale si possible, sinon on en recree une)
				$im = imagecreatetruecolor($x_i, $y_i);

				// utilisons un fond vert en guise de transparence
				$color_t  = ImageColorAllocate($im, 0, 255, 0);
				imagefill($im, 0, 0, $color_t);
				imagelayereffect($im, IMG_EFFECT_ALPHABLEND);
				@imagecolortransparent($im, $color_t);

				// copier l'image true color
				imagecopy($im, $im_, 0, 0, 0, 0, $x_i, $y_i);
				@imagetruecolortopalette($im, true, $qualite);

				// matcher les couleurs au mieux par rapport a l'image initiale
				// si la fonction est disponible
				if (function_exists('imagecolormatch')) {
					// et la transformer en palette
					@imagecolormatch($im_, $im);
				}

				// produire le resultat
				_image_gd_output($im, $image, $qualite);
				imagedestroy($im);
			} else {
				_image_gd_output($im_, $image, $qualite);
			}
			imagedestroy($im_);
		}
	}

	return _image_ecrire_tag($image, ['src' => $dest]);
}

// Enregistrer une image dans un format donne
// (conserve la transparence gif, png, ico)
// utilise [->@image_aplatir]
function image_format($img, $format = 'png') {
	$qualite = null;
	if ($format == 'png8') {
		$format = 'png';
		$qualite = 128;
	}

	return image_aplatir($img, $format, 'cccccc', $qualite, true);
}

// Transforme l'image en PNG transparent
// alpha = 0: aucune transparence
// alpha = 127: completement transparent
function image_alpha($im, $alpha = 63) {
	$fonction = ['image_alpha', func_get_args()];
	$image = _image_valeurs_trans($im, "alpha-$alpha", 'png', $fonction, false, _SVG_SUPPORTED);
	if (!$image) {
		return '';
	}
	if ($image['format_source'] === 'svg') {
		// ne pas forcer le format
		$image = _image_valeurs_trans($im, "alpha-$alpha", false, $fonction, '', _SVG_SUPPORTED);
	}

	$x_i = $image['largeur'];
	$y_i = $image['hauteur'];

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	if ($creer) {
		if ($image['format_source'] === 'svg') {
			$atts = [];
			if ($alpha > 0) {
				$atts['opacity'] = round((127 - $alpha) / 127.0, 2);
			}
			$svg = svg_transformer($im, $atts);
			_image_gd_output($svg, $image);
		} else {
			// Creation de l'image en deux temps
			// de facon a conserver les GIF transparents
			$im = $image['fonction_imagecreatefrom']($im);
			imagepalettetotruecolor($im);
			$im2 = imagecreatetruecolor($x_i, $y_i);
			@imagealphablending($im2, false);
			@imagesavealpha($im2, true);
			$color_t = ImageColorAllocateAlpha($im2, 255, 255, 255, 127);
			imagefill($im2, 0, 0, $color_t);
			imagecopy($im2, $im, 0, 0, 0, 0, $x_i, $y_i);

			$im_ = imagecreatetruecolor($x_i, $y_i);
			imagealphablending($im_, false);
			imagesavealpha($im_, true);

			for ($x = 0; $x < $x_i; $x++) {
				for ($y = 0; $y < $y_i; $y++) {
					$rgb = ImageColorAt($im2, $x, $y);

					if (function_exists('imagecolorallocatealpha')) {
						$a = ($rgb >> 24) & 0xFF;
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;

						$a_ = $alpha + $a - round($a * $alpha / 127);
						$rgb = imagecolorallocatealpha($im_, $r, $g, $b, $a_);
					}
					imagesetpixel($im_, $x, $y, $rgb);
				}
			}
			_image_gd_output($im_, $image);
			imagedestroy($im_);
			imagedestroy($im);
			imagedestroy($im2);
		}
	}

	return _image_ecrire_tag($image, ['src' => $dest]);
}

/**
 * Recadre (rogne) une image en indiquant la taille de la découpe souhaitée
 *
 * On peut indiquer une proportion ou une taille spécifique, une position de rognage
 * et une couleur de fond, si le rognage est de taille plus grande que l'image d'origine.
 *
 * @example
 *     - `[(#FICHIER|image_recadre{800, 400})]`
 *     - `[(#FICHIER|image_recadre{800, 400, center})]`
 *     - `[(#FICHIER|image_recadre{800, 400, center, black})]`
 *     - `[(#FICHIER|image_recadre{16:9})]`
 *     - `[(#FICHIER|image_recadre{16:9, -})]` (- est appliqué par défaut, équivalent à image_passe_partout)
 *     - `[(#FICHIER|image_recadre{16:9, +, center, white})]`
 *     - `[(#FICHIER|image_recadre{16:9, -, top left})]`
 *     - `[(#FICHIER|image_recadre{16:9, -, top=40 left=20})]`
 *
 * @filtre
 * @uses _image_valeurs_trans()
 * @uses _image_tag_changer_taille() si image trop grande pour être traitée
 * @uses _image_ecrire_tag()
 * @link https://www.spip.net/5786
 *
 * @param string $im
 *     Chemin de l'image ou balise html `<img src=... />`
 * @param string|int $width
 *     Largeur du recadrage
 *     ou ratio sous la forme "16:9"
 * @param string|int $height
 *     Hauteur du recadrage
 *     ou "+" (agrandir) ou "-" (reduire) si un ratio est fourni pour width
 * @param string $position
 *     Indication de position de la découpe :
 *     - `center`, `left`, `right`, `top`, `bottom`,
 *     - ou combinaisons de plusiers `top left`
 *     - ou indication en pixels depuis une position `top=50` ou composée `top=40 left=50`
 *     - ou nom d'une fonction spéciale qui calculera et retournera la position souhaitée
 * @param string $background_color
 *     Couleur de fond si on agrandit l'image
 * @return string
 *     balise image recadrée
 */
function image_recadre($im, $width, $height = '-', $position = 'center', $background_color = 'white') {
	// reorienter l'image au prealable si besoin
	$im = image_oriente_selon_exif($im);

	$fonction = ['image_recadre', func_get_args()];

	$forcer_format = false;
	$background_color = strtolower(trim($background_color));
	$position = strtolower(trim($position));
	$image = _image_valeurs_trans($im, "recadre-$width-$height-$position-$background_color", $forcer_format, $fonction, false, _SVG_SUPPORTED);

	if (!$image) {
		return '';
	}

	$x_i = $image['largeur'];
	$y_i = $image['hauteur'];

	// on recadre pour respecter un ratio ?
	// width : "16:9"
	// height : "+" pour agrandir l'image et "-" pour la croper
	if (str_contains($width, ':')) {
		[$wr, $hr] = explode(':', $width);
		$hm = $x_i / $wr * $hr;
		$ym = $y_i / $hr * $wr;
		if ($height == '+' ? ($y_i < $hm) : ($y_i > $hm)) {
			$width = $x_i;
			$height = round($hm);
		} else {
			$width = round($ym);
			$height = $y_i;
		}
	}

	if ($image['format_source'] !== 'svg' && _IMG_GD_MAX_PIXELS && $x_i * $y_i > _IMG_GD_MAX_PIXELS) {
		spip_log("image_recadre impossible sur $im : " . $x_i * $y_i . 'pixels');

		// on se rabat sur une reduction CSS
		return _image_tag_changer_taille($im, $width, $height);
	}

	if ($width == 0) {
		$width = $x_i;
	}
	if ($height == 0) {
		$height = $y_i;
	}

	$offset_width = $x_i - $width;
	$offset_height = $y_i - $height;

	if ($background_color === 'transparent') {
		if ($width > $x_i or $height > $y_i) {
			if ($image['format_source'] !== 'svg') {
				$forcer_format = 'png';
			}
			$image = _image_valeurs_trans($im, "recadre-$width-$height-$position-$background_color", $forcer_format, $fonction, false, _SVG_SUPPORTED);
		}
	}

	// chercher une fonction spéciale de calcul des coordonnées de positionnement.
	// exemple 'focus' ou 'focus-center' avec le plugin 'Centre Image'
	if (!in_array($position, ['center', 'top', 'right', 'bottom', 'left'], true)) {
		if (count(explode(' ', $position)) == 1) {
			$positionner = charger_fonction('image_positionner_par_' . str_replace('-', '_', $position), 'inc', true);
			if ($positionner) {
				$position = $positionner($im, $width, $height);
			}
		}
	}

	if (str_contains($position, 'left')) {
		if (preg_match(';left=(\d{1}\d+);', $position, $left)) {
			$offset_width = $left[1];
		} else {
			$offset_width = 0;
		}
	} elseif (str_contains($position, 'right')) {
		$offset_width = $offset_width;
	} else {
		$offset_width = intval(ceil($offset_width / 2));
	}

	if (str_contains($position, 'top')) {
		if (preg_match(';top=(\d{1}\d+);', $position, $top)) {
			$offset_height = $top[1];
		} else {
			$offset_height = 0;
		}
	} elseif (str_contains($position, 'bottom')) {
		$offset_height = $offset_height;
	} else {
		$offset_height = intval(ceil($offset_height / 2));
	}

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	if ($creer) {
		if ($image['format_source'] === 'svg') {
			$svg = svg_recadrer($im, $width, $height, $offset_width, $offset_height, $background_color);
			_image_gd_output($svg, $image);
		} else {
			$im = $image['fonction_imagecreatefrom']($im);
			if (!$im) {
				return '';
			}
			imagepalettetotruecolor($im);
			$im_ = imagecreatetruecolor($width, $height);
			@imagealphablending($im_, false);
			@imagesavealpha($im_, true);

			if ($background_color == 'transparent') {
				$color_t = imagecolorallocatealpha($im_, 255, 255, 255, 127);
			} else {
				$bg = _couleur_hex_to_dec($background_color);
				$color_t = imagecolorallocate($im_, $bg['red'], $bg['green'], $bg['blue']);
			}
			imagefill($im_, 0, 0, $color_t);
			imagecopy(
				$im_,
				$im,
				max(0, -$offset_width),
				max(0, -$offset_height),
				max(0, $offset_width),
				max(0, $offset_height),
				min($width, $x_i),
				min($height, $y_i)
			);

			_image_gd_output($im_, $image);
			imagedestroy($im_);
			imagedestroy($im);
		}
	}

	return _image_ecrire_tag($image, ['src' => $dest, 'width' => $width, 'height' => $height]);
}

/**
 * Recadrer une image dans le rectangle le plus petit possible sans perte
 * de pixels non transparent
 * Le recadrage se fait en conservant le centre de l'image : on recadre symétriquement gauche vs droite et haut vs bas
 * TODO : proposer une option pour vraiment recadrer au plus juste, meme si ca decentre l'image
 *
 * @param string $im
 * @return string
 */
function image_recadre_mini($im) {
	// reorienter l'image au prealable si besoin
	$im = image_oriente_selon_exif($im);

	$fonction = ['image_recadre_mini', func_get_args()];
	$image = _image_valeurs_trans($im, 'recadre_mini', false, $fonction);

	if (!$image) {
		return '';
	}

	$width = $image['largeur'];
	$height = $image['hauteur'];

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];
	if ($creer) {
		$im = $image['fonction_imagecreatefrom']($im);
		imagepalettetotruecolor($im);

		// trouver le rectangle mini qui contient des infos de couleur
		// recherche optimisee qui ne balaye que par zone
		$min_x = $width;
		$min_y = $height;
		$max_y = $max_x = 0;
		$yy = 0;
		while ($yy <= $height / 2 and $max_y <= $min_y) {
			if ($yy < $min_y) {
				for ($xx = 0; $xx < $width; $xx++) {
					$color_index = imagecolorat($im, $xx, $yy);
					$color_tran = imagecolorsforindex($im, $color_index);
					if ($color_tran['alpha'] !== 127) {
						$min_y = min($yy, $min_y);
						$max_y = max($height - 1 - $yy, $max_y);
						break;
					}
				}
			}
			if ($height - 1 - $yy > $max_y) {
				for ($xx = 0; $xx < $width; $xx++) {
					$color_index = imagecolorat($im, $xx, $height - 1 - $yy);
					$color_tran = imagecolorsforindex($im, $color_index);
					if ($color_tran['alpha'] !== 127) {
						$min_y = min($yy, $min_y);
						$max_y = max($height - 1 - $yy, $max_y);
						break;
					}
				}
			}
			$yy++;
		}
		$min_y = min($max_y, $min_y); // tout a 0 aucun pixel trouve

		$xx = 0;
		while ($xx <= $width / 2 and $max_x <= $min_x) {
			if ($xx < $min_x) {
				for ($yy = $min_y; $yy < $max_y; $yy++) {
					$color_index = imagecolorat($im, $xx, $yy);
					$color_tran = imagecolorsforindex($im, $color_index);
					if ($color_tran['alpha'] !== 127) {
						$min_x = min($xx, $min_x);
						$max_x = max($xx, $max_x);
						break; // inutile de continuer sur cette colonne
					}
				}
			}
			if ($width - 1 - $xx > $max_x) {
				for ($yy = $min_y; $yy < $max_y; $yy++) {
					$color_index = imagecolorat($im, $width - 1 - $xx, $yy);
					$color_tran = imagecolorsforindex($im, $color_index);
					if ($color_tran['alpha'] !== 127) {
						$min_x = min($width - 1 - $xx, $min_x);
						$max_x = max($width - 1 - $xx, $max_x);
						break; // inutile de continuer sur cette colonne
					}
				}
			}
			$xx++;
		}
		$min_x = min($max_x, $min_x); // tout a 0 aucun pixel trouve

		$width = $max_x - $min_x + 1;
		$height = $max_y - $min_y + 1;

		$im_ = imagecreatetruecolor($width, $height);
		@imagealphablending($im_, false);
		@imagesavealpha($im_, true);

		$color_t = imagecolorallocatealpha($im_, 255, 255, 255, 127);
		imagefill($im_, 0, 0, $color_t);
		imagecopy($im_, $im, 0, 0, $min_x, $min_y, $width, $height);

		_image_gd_output($im_, $image);
		imagedestroy($im_);
		imagedestroy($im);
	} else {
		[$height, $width] = taille_image($image['fichier_dest']);
	}

	return _image_ecrire_tag($image, ['src' => $dest, 'width' => $width, 'height' => $height]);
}

/**
 * Flip une image selon un axe de symétrie central et vertical
 * (c'est donc un flip horizontal au sens de GD)
 * @param string $im
 * @return string
 */
function image_flip_vertical($im) {
	// reorienter l'image au prealable si besoin, car sinon ça inverse potentiellement le flip
	$im = image_oriente_selon_exif($im);

	$fonction = ['image_flip_vertical', func_get_args()];
	$image = _image_valeurs_trans($im, 'flip_v', false, $fonction, '', _SVG_SUPPORTED);
	if (!$image) {
		return '';
	}

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	if ($creer) {
		if ($image['format_source'] === 'svg') {
			$svg = svg_flip($im, 'v');
			_image_gd_output($svg, $image);
		} else {
			$im = $image['fonction_imagecreatefrom']($im);
			imagepalettetotruecolor($im);
			@imagesavealpha($im, true);
			// historique : la definition de SPIP est l'inverse de celle de GD...
			imageflip($im, IMG_FLIP_HORIZONTAL);
			_image_gd_output($im, $image);
			imagedestroy($im);
		}
	}

	return _image_ecrire_tag($image, ['src' => $dest]);
}

/**
 * Flip une image selon un axe de symétrie central et horizontal
 * (c'est donc un flip vertical au sens de GD)
 * @param string $im
 * @return string
 */
function image_flip_horizontal($im) {
	// reorienter l'image au prealable si besoin, car sinon ça inverse potentiellement le flip
	$im = image_oriente_selon_exif($im);

	$fonction = ['image_flip_horizontal', func_get_args()];
	$image = _image_valeurs_trans($im, 'flip_h', false, $fonction, '', _SVG_SUPPORTED);
	if (!$image) {
		return '';
	}

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	if ($creer) {
		if ($image['format_source'] === 'svg') {
			$svg = svg_flip($im, 'h');
			_image_gd_output($svg, $image);
		} else {
			$im = $image['fonction_imagecreatefrom']($im);
			imagepalettetotruecolor($im);
			@imagesavealpha($im, true);
			// historique : la definition de SPIP est l'inverse de celle de GD...
			imageflip($im, IMG_FLIP_VERTICAL);
			_image_gd_output($im, $image);
			imagedestroy($im);
		}
	}

	return _image_ecrire_tag($image, ['src' => $dest]);
}

function image_masque($im, $masque, $pos = '') {
	// reorienter l'image au prealable si besoin, car sinon l'orientation relative du masque et de l'image ne seront pas bonnes
	$im = image_oriente_selon_exif($im);

	$defini = [];
	$rgb3 = [];
	// Passer, en plus de l'image d'origine,
	// une image de "masque": un fichier PNG24 transparent.
	// Le decoupage se fera selon la transparence du "masque",
	// et les couleurs seront eclaircies/foncees selon de couleur du masque.
	// Pour ne pas modifier la couleur, le masque doit etre en gris 50%.
	//
	// Si l'image source est plus grande que le masque, alors cette image est reduite a la taille du masque.
	// Sinon, c'est la taille de l'image source qui est utilisee.
	//
	// $pos est une variable libre, qui permet de passer left=..., right=..., bottom=..., top=...
	// dans ce cas, le masque est place a ces positions sur l'image d'origine,
	// et evidemment cette image d'origine n'est pas redimensionnee
	//
	// Positionnement horizontal: text-align=left, right, center
	// Positionnement vertical : vertical-align=top, bottom, middle
	// (les positionnements left, right, top, left sont relativement inutiles, mais coherence avec CSS)
	//
	// Choix du mode de fusion: mode=masque, normal, eclaircir, obscurcir, produit, difference, ecran, superposer, lumiere_dure, teinte, saturation, valeur
	// https://en.wikipedia.org/wiki/Blend_modes
	// masque: mode par defaut
	// normal: place la nouvelle image par dessus l'ancienne
	// eclaircir: place uniquement les points plus clairs
	// obscurcir: place uniquement les points plus fonc'es
	// produit: multiplie par le masque (points noirs rendent l'image noire, points blancs ne changent rien)
	// difference: remplit avec l'ecart entre les couleurs d'origine et du masque
	// ecran: effet inverse de 'produit' -> l'image resultante est plus claire
	// superposer: combine les modes 'produit' et 'ecran' -> les parties claires sont eclaircies, les parties sombres assombries.
	// lumiere_dure: equivalent a 'superposer', sauf que l'image du bas et du haut sont inversees.
	// teinte: utilise la teinte du masque
	// saturation: utilise la saturation du masque
	// valeur: utilise la valeur du masque

	$mode = 'masque';

	$numargs = func_num_args();
	$arg_list = func_get_args();
	$variable = [];

	$texte = $arg_list[0];
	for ($i = 1; $i < $numargs; $i++) {
		if (($p = strpos($arg_list[$i], '=')) !== false) {
			$nom_variable = substr($arg_list[$i], 0, $p);
			$val_variable = substr($arg_list[$i], $p + 1);
			$variable["$nom_variable"] = $val_variable;
			$defini["$nom_variable"] = 1;
		}
	}
	if (isset($defini['mode']) and $defini['mode']) {
		$mode = $variable['mode'];
	}

	// utiliser _image_valeurs_trans pour accepter comme masque :
	// - une balise <img src='...' />
	// - une image avec un timestamp ?01234
	$mask = _image_valeurs_trans($masque, 'source-image_masque', 'png', null, true);
	if (!$mask) {
		return '';
	}
	$masque = $mask['fichier'];

	$pos = md5(serialize($variable) . $mask['date_src']);
	$fonction = ['image_masque', func_get_args()];
	$image = _image_valeurs_trans($im, "masque-$masque-$pos", 'png', $fonction);
	if (!$image) {
		return '';
	}

	$x_i = $image['largeur'];
	$y_i = $image['hauteur'];

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	// doit-on positionner l'image ?
	$placer = false;
	foreach (['right', 'left', 'bottom', 'top', 'text-align', 'vertical-align'] as $pl) {
		if (isset($defini[$pl]) and $defini[$pl]) {
			$placer = true;
			break;
		}
	}

	if ($creer) {
		$im_m = $mask['fichier'];
		$x_m = $mask['largeur'];
		$y_m = $mask['hauteur'];

		$im2 = $mask['fonction_imagecreatefrom']($masque);
		if ($mask['format_source'] == 'gif' and function_exists('ImageCopyResampled')) {
			$im2_ = imagecreatetruecolor($x_m, $y_m);
			// Si un GIF est transparent,
			// fabriquer un PNG transparent
			// Conserver la transparence
			if (function_exists('imageAntiAlias')) {
				imageAntiAlias($im2_, true);
			}
			@imagealphablending($im2_, false);
			@imagesavealpha($im2_, true);
			@ImageCopyResampled($im2_, $im2, 0, 0, 0, 0, $x_m, $y_m, $x_m, $y_m);
			imagedestroy($im2);
			$im2 = $im2_;
		}

		if ($placer) {
			// On fabriquer une version "agrandie" du masque,
			// aux dimensions de l'image source
			// et on "installe" le masque dans cette image
			// ainsi: aucun redimensionnement

			$dx = 0;
			$dy = 0;

			if (isset($defini['right']) and $defini['right']) {
				$right = $variable['right'];
				$dx = ($x_i - $x_m) - $right;
			}
			if (isset($defini['bottom']) and $defini['bottom']) {
				$bottom = $variable['bottom'];
				$dy = ($y_i - $y_m) - $bottom;
			}
			if (isset($defini['top']) and $defini['top']) {
				$top = $variable['top'];
				$dy = $top;
			}
			if (isset($defini['left']) and $defini['left']) {
				$left = $variable['left'];
				$dx = $left;
			}
			if (isset($defini['text-align']) and $defini['text-align']) {
				$align = $variable['text-align'];
				if ($align == 'right') {
					$right = 0;
					$dx = ($x_i - $x_m);
				} else {
					if ($align == 'left') {
						$left = 0;
						$dx = 0;
					} else {
						if ($align = 'center') {
							$dx = round(($x_i - $x_m) / 2);
						}
					}
				}
			}
			if (isset($defini['vertical-align']) and $defini['vertical-align']) {
				$valign = $variable['vertical-align'];
				if ($valign == 'bottom') {
					$bottom = 0;
					$dy = ($y_i - $y_m);
				} else {
					if ($valign == 'top') {
						$top = 0;
						$dy = 0;
					} else {
						if ($valign = 'middle') {
							$dy = round(($y_i - $y_m) / 2);
						}
					}
				}
			}

			$im3 = imagecreatetruecolor($x_i, $y_i);
			@imagealphablending($im3, false);
			@imagesavealpha($im3, true);
			if ($mode == 'masque') {
				$color_t = ImageColorAllocateAlpha($im3, 128, 128, 128, 0);
			} else {
				$color_t = ImageColorAllocateAlpha($im3, 128, 128, 128, 127);
			}
			imagefill($im3, 0, 0, $color_t);

			imagecopy($im3, $im2, $dx, $dy, 0, 0, $x_m, $y_m);

			imagedestroy($im2);
			$im2 = imagecreatetruecolor($x_i, $y_i);
			@imagealphablending($im2, false);
			@imagesavealpha($im2, true);

			imagecopy($im2, $im3, 0, 0, 0, 0, $x_i, $y_i);
			imagedestroy($im3);
			$x_m = $x_i;
			$y_m = $y_i;
		}

		$rapport = $x_i / $x_m;
		if (($y_i / $y_m) < $rapport) {
			$rapport = $y_i / $y_m;
		}

		$x_d = ceil($x_i / $rapport);
		$y_d = ceil($y_i / $rapport);

		if ($x_i < $x_m or $y_i < $y_m) {
			$x_dest = $x_i;
			$y_dest = $y_i;
			$x_dec = 0;
			$y_dec = 0;
		} else {
			$x_dest = $x_m;
			$y_dest = $y_m;
			$x_dec = round(($x_d - $x_m) / 2);
			$y_dec = round(($y_d - $y_m) / 2);
		}

		$nouveau = _image_valeurs_trans(image_reduire($im, $x_d, $y_d), '');
		if (!is_array($nouveau)) {
			return '';
		}
		$im_n = $nouveau['fichier'];

		$im = $nouveau['fonction_imagecreatefrom']($im_n);
		imagepalettetotruecolor($im);
		if ($nouveau['format_source'] == 'gif' and function_exists('ImageCopyResampled')) {
			$im_ = imagecreatetruecolor($x_dest, $y_dest);
			// Si un GIF est transparent,
			// fabriquer un PNG transparent
			// Conserver la transparence
			if (function_exists('imageAntiAlias')) {
				imageAntiAlias($im_, true);
			}
			@imagealphablending($im_, false);
			@imagesavealpha($im_, true);
			@ImageCopyResampled($im_, $im, 0, 0, 0, 0, $x_dest, $y_dest, $x_dest, $y_dest);
			imagedestroy($im);
			$im = $im_;
		}
		$im_ = imagecreatetruecolor($x_dest, $y_dest);
		@imagealphablending($im_, false);
		@imagesavealpha($im_, true);
		$color_t = ImageColorAllocateAlpha($im_, 255, 255, 255, 127);
		imagefill($im_, 0, 0, $color_t);

		// calcul couleurs de chaque pixel selon les modes de fusion
		for ($x = 0; $x < $x_dest; $x++) {
			for ($y = 0; $y < $y_dest; $y++) {
				$rgb = ImageColorAt($im2, $x, $y); // image au dessus
				$a = ($rgb >> 24) & 0xFF;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				$rgb2 = ImageColorAt($im, $x + $x_dec, $y + $y_dec); // image en dessous
				$a2 = ($rgb2 >> 24) & 0xFF;
				$r2 = ($rgb2 >> 16) & 0xFF;
				$g2 = ($rgb2 >> 8) & 0xFF;
				$b2 = $rgb2 & 0xFF;

				if ($mode == 'normal') {
					$v = (127 - $a) / 127;
					if ($v == 1) {
						$r_ = $r;
						$g_ = $g;
						$b_ = $b;
					} else {
						$v2 = (127 - $a2) / 127;
						if ($v + $v2 == 0) {
							$r_ = $r2;
							$g_ = $g2;
							$b_ = $b2;
						} else {
							if ($v2 == 0) {
								$r_ = $r;
								$g_ = $g;
								$b_ = $b;
							} else {
								if ($v == 0) {
									$r_ = $r2;
									$g_ = $g2;
									$b_ = $b2;
								} else {
									$r_ = $r + (($r2 - $r) * $v2 * (1 - $v));
									$g_ = $g + (($g2 - $g) * $v2 * (1 - $v));
									$b_ = $b + (($b2 - $b) * $v2 * (1 - $v));
								}
							}
						}
					}
					$a_ = min($a, $a2);
				} elseif (in_array($mode, ['produit', 'difference', 'superposer', 'lumiere_dure', 'ecran'], true)) {
					if ($mode == 'produit') {
						$r = ($r / 255) * $r2;
						$g = ($g / 255) * $g2;
						$b = ($b / 255) * $b2;
					} elseif ($mode == 'difference') {
						$r = abs($r - $r2);
						$g = abs($g - $g2);
						$b = abs($b - $b2);
					} elseif ($mode == 'superposer') {
						$r = ($r2 < 128) ? 2 * $r * $r2 / 255 : 255 - (2 * (255 - $r) * (255 - $r2) / 255);
						$g = ($g2 < 128) ? 2 * $g * $g2 / 255 : 255 - (2 * (255 - $g) * (255 - $g2) / 255);
						$b = ($b2 < 128) ? 2 * $b * $b2 / 255 : 255 - (2 * (255 - $b) * (255 - $b2) / 255);
					} elseif ($mode == 'lumiere_dure') {
						$r = ($r < 128) ? 2 * $r * $r2 / 255 : 255 - (2 * (255 - $r2) * (255 - $r) / 255);
						$g = ($g < 128) ? 2 * $g * $g2 / 255 : 255 - (2 * (255 - $g2) * (255 - $g) / 255);
						$b = ($b < 128) ? 2 * $b * $b2 / 255 : 255 - (2 * (255 - $b2) * (255 - $b) / 255);
					} elseif ($mode == 'ecran') {
						$r = 255 - (((255 - $r) * (255 - $r2)) / 255);
						$g = 255 - (((255 - $g) * (255 - $g2)) / 255);
						$b = 255 - (((255 - $b) * (255 - $b2)) / 255);
					}
					$r = max(0, min($r, 255));
					$g = max(0, min($g, 255));
					$b = max(0, min($b, 255));

					// melange en fonction de la transparence du masque
					$v = (127 - $a) / 127;
					if ($v == 1) { // melange complet
						$r_ = $r;
						$g_ = $g;
						$b_ = $b;
					} else {
						$v2 = (127 - $a2) / 127;
						if ($v + $v2 == 0) { // ??
							$r_ = $r2;
							$g_ = $g2;
							$b_ = $b2;
						} else { // pas de melange (transparence du masque)
							$r_ = $r + (($r2 - $r) * $v2 * (1 - $v));
							$g_ = $g + (($g2 - $g) * $v2 * (1 - $v));
							$b_ = $b + (($b2 - $b) * $v2 * (1 - $v));
						}
					}
					$a_ = $a2;
				} elseif ($mode == 'eclaircir' or $mode == 'obscurcir') {
					$v = (127 - $a) / 127;
					if ($v == 1) {
						$r_ = $r;
						$g_ = $g;
						$b_ = $b;
					} else {
						$v2 = (127 - $a2) / 127;
						if ($v + $v2 == 0) {
							$r_ = $r2;
							$g_ = $g2;
							$b_ = $b2;
						} else {
							$r_ = $r + (($r2 - $r) * $v2 * (1 - $v));
							$g_ = $g + (($g2 - $g) * $v2 * (1 - $v));
							$b_ = $b + (($b2 - $b) * $v2 * (1 - $v));
						}
					}
					if ($mode == 'eclaircir') {
						$r_ = max($r_, $r2);
						$g_ = max($g_, $g2);
						$b_ = max($b_, $b2);
					} else {
						$r_ = min($r_, $r2);
						$g_ = min($g_, $g2);
						$b_ = min($b_, $b2);
					}
					$a_ = min($a, $a2);
				} elseif (in_array($mode, ['teinte', 'saturation', 'valeur'], true)) {
					include_spip('filtres/images_lib');
					$hsv = _couleur_rgb2hsv($r, $g, $b); // image au dessus
					$h = $hsv['h'];
					$s = $hsv['s'];
					$v = $hsv['v'];
					$hsv2 = _couleur_rgb2hsv($r2, $g2, $b2); // image en dessous
					$h2 = $hsv2['h'];
					$s2 = $hsv2['s'];
					$v2 = $hsv2['v'];
					switch ($mode) {
						case 'teinte':
							$rgb3 = _couleur_hsv2rgb($h, $s2, $v2);
							break;
						case 'saturation':
							$rgb3 = _couleur_hsv2rgb($h2, $s, $v2);
							break;
						case 'valeur':
							$rgb3 = _couleur_hsv2rgb($h2, $s2, $v);
							break;
					}
					$r = $rgb3['r'];
					$g = $rgb3['g'];
					$b = $rgb3['b'];

					// melange en fonction de la transparence du masque
					$v = (127 - $a) / 127;
					if ($v == 1) { // melange complet
						$r_ = $r;
						$g_ = $g;
						$b_ = $b;
					} else {
						$v2 = (127 - $a2) / 127;
						if ($v + $v2 == 0) { // ??
							$r_ = $r2;
							$g_ = $g2;
							$b_ = $b2;
						} else { // pas de melange (transparence du masque)
							$r_ = $r + (($r2 - $r) * $v2 * (1 - $v));
							$g_ = $g + (($g2 - $g) * $v2 * (1 - $v));
							$b_ = $b + (($b2 - $b) * $v2 * (1 - $v));
						}
					}
					$a_ = $a2;
				} else {
					$r_ = $r2 + 1 * ($r - 127);
					$r_ = max(0, min($r_, 255));
					$g_ = $g2 + 1 * ($g - 127);
					$g_ = max(0, min($g_, 255));
					$b_ = $b2 + 1 * ($b - 127);
					$b_ = max(0, min($b_, 255));
					$a_ = $a + $a2 - round($a * $a2 / 127);
				}

				$color = ImageColorAllocateAlpha($im_, intval($r_), intval($g_), intval($b_), $a_);
				imagesetpixel($im_, $x, $y, $color);
			}
		}

		_image_gd_output($im_, $image);
		imagedestroy($im_);
		imagedestroy($im);
		imagedestroy($im2);
	}
	$x_dest = largeur($dest);
	$y_dest = hauteur($dest);

	return _image_ecrire_tag($image, ['src' => $dest, 'width' => $x_dest, 'height' => $y_dest]);
}

/**
 * Passage de l'image en noir et blanc
 * un noir & blanc "photo" n'est pas "neutre": les composantes de couleur sont
 * ponderees pour obtenir le niveau de gris
 * on peut ici regler cette ponderation en "pour mille"
 *
 * Par défaut, si on ne passe pas de pondération, on utilise le filtre IMG_FILTER_GRAYSCALE de GD beaucoup plus rapide
 *
 * @param string $im
 * @param ?int $val_r
 * @param ?int $val_g
 * @param ?int $val_b
 * @return string
 */
function image_nb($im, $val_r = null, $val_g = null, $val_b = null) {

	$fonction = ['image_nb', func_get_args()];
	$effet = 'nb-' . ($val_r ?? 'default') . '-' . ($val_g ?? 'default') . '-' . ($val_b ?? 'default');
	$image = _image_valeurs_trans($im, $effet, false, $fonction, false, _SVG_SUPPORTED);
	if (!$image) {
		return '';
	}


	$f = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	// Methode precise
	// resultat plus beau, mais tres lourd
	// Et: indispensable pour preserver transparence!

	if ($creer) {
		if ($image['format_source'] === 'svg') {
			#$svg = svg_transformer($f, ['style' => "filter:grayscale(100%);"]); // ne semble pas fonctionner dans Safari+Chrome
			$svg = svg_filter_grayscale($f, 1.0);
			_image_gd_output($svg, $image);
		} else {
			// Creation de l'image en deux temps
			// de facon a conserver les GIF transparents
			$im = $image['fonction_imagecreatefrom']($f);
			imagepalettetotruecolor($im);
			if (is_null($val_r) && is_null($val_g) && is_null($val_b)) {
				spip_log("image_nb: $f via imagefilter", 'images' . _LOG_DEBUG);
				@imagesavealpha($im, true);
				imagefilter($im, IMG_FILTER_GRAYSCALE);
				_image_gd_output($im, $image);
				imagedestroy($im);
			}
			else {
				spip_log("image_nb: $f via calcul manuel", 'images' . _LOG_DEBUG);
				// calcul à la main, beaucoup plus lent
				$default = ['r' => 299, 'g' => 587, 'b' => 114];
				$val_r = $val_r ?? $default['r'];
				$val_g = $val_g ?? $default['g'];
				$val_b = $val_b ?? $default['b'];

				$x_i = $image['largeur'];
				$y_i = $image['hauteur'];

				$im_ = imagecreatetruecolor($x_i, $y_i);
				@imagealphablending($im_, false);
				@imagesavealpha($im_, true);
				$color_t = ImageColorAllocateAlpha($im_, 255, 255, 255, 127);
				imagefill($im_, 0, 0, $color_t);
				imagecopy($im_, $im, 0, 0, 0, 0, $x_i, $y_i);

				for ($x = 0; $x < $x_i; $x++) {
					for ($y = 0; $y < $y_i; $y++) {
						$rgb = ImageColorAt($im_, $x, $y);
						$a = ($rgb >> 24) & 0xFF;
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;

						$c = round(($val_r * $r / 1000) + ($val_g * $g / 1000) + ($val_b * $b / 1000));
						if ($c < 0) {
							$c = 0;
						}
						if ($c > 254) {
							$c = 254;
						}

						$color = ImageColorAllocateAlpha($im_, $c, $c, $c, $a);
						imagesetpixel($im_, $x, $y, $color);
					}
				}
				_image_gd_output($im_, $image);
				imagedestroy($im_);
				imagedestroy($im);
			}
		}
	}

	return _image_ecrire_tag($image, ['src' => $dest]);
}

/**
 * Calcul d'un flou sur une image.
 * On utilise une matrice de convolution pour le niveau 1 et le niveau 2, qu'on repasse autant de fois que nécessaire
 * pour arriver au niveau de flou souhaité qui est appriximativement le rayon du flou
 * Le filtre préserve la transparence
 *
 * @param $im
 * @param int $niveau
 * @return string
 */
function image_flou($im, $niveau = 3) {
	$fonction = ['image_flou', func_get_args()];
	$image = _image_valeurs_trans($im, "flou-$niveau", false, $fonction, '', _SVG_SUPPORTED);
	if (!$image) {
		return '';
	}

	$x_i = $image['largeur'];
	$y_i = $image['hauteur'];

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	$new_width = $x_i + $niveau;
	$new_height = $y_i + $niveau;
	if ($creer) {
		spip_timer('image_flou');
		if ($image['format_source'] === 'svg') {
			spip_log($log = "image_flou: $im via SVG", 'images' . _LOG_DEBUG);
			$svg = svg_recadrer($im, $new_width, $new_height, round(($x_i - $new_width) / 2), round(($y_i - $new_height) / 2));
			#$svg = svg_transformer($svg, ['style' => "filter:blur({$niveau}px);"]); // ne semble pas supporte par safari+chrome
			$svg = svg_filter_blur($svg, $niveau);
			_image_gd_output($svg, $image);
		} else {
			spip_log($log = "image_flou: $im via gd legacy", 'images' . _LOG_DEBUG);
			// Creation de l'image en deux temps
			// de facon a conserver les GIF transparents
			$im = $image['fonction_imagecreatefrom']($im);
			imagepalettetotruecolor($im);
			if ($niveau > 0) {
				// preparer l'image agrandie
				$temp = imagecreatetruecolor($new_width, $new_height);
				imageAntiAlias($temp, true);
				@imagesavealpha($temp, true);
				// les pixels en transparence 127 sont pris pour du noir par le imageconvolution de GD
				$color_t = ImageColorAllocateAlpha($temp, 255, 255, 255, 126);
				imagefill($temp, 0, 0, $color_t);
				@imagealphablending($temp, true);
				@imagecopy($temp, $im, ($new_width - $x_i) / 2, ($new_height - $y_i) / 2, 0, 0, $x_i, $y_i);
				imagedestroy($im);
				$gaussians = [
					1 => [[1.0, 1.0, 0.0], [2.0, 2.0, 0.0], [0.0, 0.0, 0.0]],
					2 => [[1.0, 2.0, 1.0], [2.0, 4.0, 2.0], [1.0, 2.0, 1.0]],
				];
				$divisors = [];
				foreach ($gaussians as $k => $v) {
					$divisors[$k] = array_sum(array_map('array_sum', $gaussians[$k]));
				}
				while ($niveau > 0) {
					$step = min($niveau, 2);
					imageconvolution($temp, $gaussians[$step], $divisors[$step], 0);
					$niveau -= $step;
				}
				_image_gd_output($temp, $image);
				imagedestroy($temp);
			} else {
				_image_gd_output($im, $image);
				imagedestroy($im);
			}
		}
		spip_log($log . " en " . spip_timer('image_flou'), 'images' . _LOG_DEBUG);
	}

	return _image_ecrire_tag($image, ['src' => $dest, 'width' => $new_width, 'height' => $new_height]);
}

/**
 * Determiner les nouvelles dimensions de l'image apres rotation
 * @param int|float $angle
 *   en degres
 * @param int $width
 * @param int $height
 * @param int $center_x
 * @param int $center_y
 * @return array
 *  [int, int]
 */
function dimensions_rotation_image($angle, $width, $height, $center_x = null, $center_y = null) {
	// calculer dimensions en simplifiant angles droits, ce qui evite "floutage"
	// des rotations a angle droit
	if (round($angle / 90) * 90 == $angle) {
		$droit = true;
		if (round($angle / 180) * 180 == $angle) {
			$rotate_height = $height;
			$rotate_width = $width;
		} else {
			$rotate_height = $width;
			$rotate_width = $height;
			$rot = 90;
		}
	} else {
		$droit = false;

		// convert degrees to radians
		$angle = $angle + 180;
		$angle = deg2rad($angle);

		if ($center_x === null) {
			$center_x = floor(($width - 1) / 2);
		}
		if ($center_y === null) {
			$center_y = floor(($height - 1) / 2);
		}

		$cosangle = cos($angle);
		$sinangle = sin($angle);

		$corners = [[0, 0], [$width, 0], [$width, $height], [0, $height]];

		$corners = array_map(function ($v) use ($center_x, $center_y, $cosangle, $sinangle) {
			//Translate coords to center for rotation
			$v[0] -= $center_x;
			$v[1] -= $center_y;
			return [
				$v[0] * $cosangle + $v[1] * $sinangle,
				$v[1] * $cosangle - $v[0] * $sinangle,
			];
		}, $corners);

		$min_x = min(array_column($corners, 0));
		$max_x = max(array_column($corners, 0));
		$min_y = min(array_column($corners, 1));
		$max_y = max(array_column($corners, 1));

		$rotate_width = ceil($max_x - $min_x);
		$rotate_height = ceil($max_y - $min_y);
	}

	return [$rotate_width, $rotate_height, $droit];
}

/**
 * permet de faire tourner une image d'un angle quelconque
 * le flag "crop" permet de recadrer l'image pour conserver sa taille d'origine
 *
 * @param string $im
 * @param float $angle
 * @param bool $crop
 * @return string
 */
function image_rotation($im, $angle, $crop = false) {
	// reorienter l'image au prealable si besoin, car sinon on a pas la bonne base de rotation
	$im = image_oriente_selon_exif($im);

	$fonction = ['image_rotation', func_get_args()];
	$image = _image_valeurs_trans($im, "rot-$angle", 'png', $fonction, false, _SVG_SUPPORTED);
	if (!$image) {
		return '';
	}
	if ($image['format_source'] === 'svg') {
		// ne pas forcer le format
		$image = _image_valeurs_trans($im, "rot-$angle", false, $fonction, false, _SVG_SUPPORTED);
	}

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	if ($creer) {
		spip_timer('image_rotation');
		if ($image['format_source'] === 'svg') {
			$center_x = floor(($image['largeur'] - 1) / 2);
			$center_y = floor(($image['hauteur'] - 1) / 2);
			[$rotate_width, $rotate_height, $droit] = dimensions_rotation_image(
				$angle,
				$image['largeur'],
				$image['hauteur'],
				$center_x,
				$center_y
			);
			$svg = svg_recadrer(
				$im,
				$rotate_width,
				$rotate_height,
				-round(($rotate_width - $image['largeur']) / 2),
				-round(($rotate_height - $image['hauteur']) / 2)
			);
			spip_log($log = "image_rotation: $im avec SVG", 'images' . _LOG_DEBUG);
			$svg = svg_rotate($svg, $angle, 0.5, 0.5);
			_image_gd_output($svg, $image);
		} else {
			$effectuer_gd = true;

			if ($GLOBALS['meta']['image_process'] == 'convert') {
				if (!defined('_CONVERT_COMMAND')) {
					@define('_CONVERT_COMMAND', 'convert');
					@define('_ROTATE_COMMAND', _CONVERT_COMMAND . ' -background none -rotate %t %src %dest');
				} else {
					@define('_ROTATE_COMMAND', '');
				}
				if (_ROTATE_COMMAND !== '') {
					spip_log($log = "image_rotation: $im avec convert", 'images' . _LOG_DEBUG);
					$commande = str_replace(
						['%t', '%src', '%dest'],
						[$angle, escapeshellcmd($im), escapeshellcmd($dest)],
						_ROTATE_COMMAND
					);
					spip_log($commande, 'images' . _LOG_DEBUG);
					exec($commande);
					if (file_exists($dest)) { // precaution
						$effectuer_gd = false;
					}
				}
			}
			if ($effectuer_gd) {
				spip_log($log = "image_rotation: $im avec gd", 'images' . _LOG_DEBUG);
				// Creation de l'image en deux temps
				// de facon a conserver les GIF transparents
				$im = $image['fonction_imagecreatefrom']($im);
				imagepalettetotruecolor($im);
				$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
				$im = imagerotate($im, 360 - $angle, $transparent);
				_image_gd_output($im, $image);
				imagedestroy($im);
			}
		}
		$t = spip_timer('image_rotation');
		spip_log("$log en $t", 'images' . _LOG_DEBUG);
	}
	[$src_y, $src_x] = taille_image($dest);

	$image_tournee = _image_ecrire_tag($image, ['src' => $dest, 'width' => $src_x, 'height' => $src_y]);
	if ($crop) {
		$image_tournee = image_recadre($image_tournee, $image['largeur'], $image['hauteur'], 'center', 'transparent');
	}
	return $image_tournee;
}

/**
 * Permet d'appliquer un filtre basé sur une méthode Imagick:: a une image
 * par exemple: [(#LOGO_ARTICLE|image_imagick{waveImage,20,60})]
 * Les arguments suivant le nom de la méthode sont ceux de la méthode
 *
 * Liste des méthodes: https://www.php.net/manual/en/book.imagick.php
 *
 * @return string
 * @throws ImagickException
 *
 * @uses _image_imagick_write()
 */
function image_imagick() {
	$arr = [];
	$tous = func_get_args();
	$img = $tous[0];
	$fonc = $tous[1];
	$tous[0] = '';
	$tous_var = implode('-', $tous);

	$fonction = ['image_imagick', func_get_args()];
	$image = _image_valeurs_trans($img, "$tous_var", 'png', $fonction);
	if (!$image) {
		return '';
	}

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	if ($creer) {
		if (method_exists(\Imagick::class, $fonc)) {
			$imagick = new Imagick();
			$imagick->readImage($im);
			$args = $tous;
			array_shift($args);
			array_shift($args);
			call_user_func_array([$imagick, $fonc], $args);
			_image_gd_output($imagick, $image, _IMG_GD_QUALITE, '_image_imagick_write');
		} else {
			erreur_squelette(_L("Methode Imagick::{$fonc} inconnue"));
			return '';
		}
	}
	[$src_y, $src_x] = taille_image($dest);

	return _image_ecrire_tag($image, ['src' => $dest, 'width' => $src_x, 'height' => $src_y]);
}

/**
 * Ecriture sur le disque d'une umage générée par Imagick
 *
 * @see image_imagick()
 *
 * @param string $fichier
 *     Le path vers l'image (ex : local/cache-vignettes/L180xH51/image.png).
 * @return bool
 *
 *     - false si l'image créée a une largeur nulle ou n'existe pas ;
 *     - true si une image est bien retournée.
 */
function _image_imagick_write(\Imagick $imagick, $fichier, $qualite = _IMG_GD_QUALITE) {
	$tmp = $fichier . '.tmp';
	$ret = $imagick->writeImage($tmp);

	if (file_exists($tmp)) {
		$taille_test = getimagesize($tmp);
		if ($taille_test[0] < 1) {
			return false;
		}

		spip_unlink($fichier); // le fichier peut deja exister
		@rename($tmp, $fichier);

		return $ret;
	}

	return false;
}

// Permet de rendre une image
// plus claire (gamma > 0)
// ou plus foncee (gamma < 0)
function image_gamma($im, $gamma = 0) {
	include_spip('filtres/images_lib');
	$fonction = ['image_gamma', func_get_args()];
	$image = _image_valeurs_trans($im, "gamma-$gamma", false, $fonction, false, _SVG_SUPPORTED);
	if (!$image) {
		return '';
	}

	$x_i = $image['largeur'];
	$y_i = $image['hauteur'];

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	if ($creer) {
		if ($image['format_source'] === 'svg') {
			$pc = round($gamma / 255, 2);
			if ($pc > 0) {
				$svg = svg_ajouter_voile($im, '#ffffff', $pc);
			} else {
				$svg = svg_ajouter_voile($im, '#000000', -$pc);
			}
			_image_gd_output($svg, $image);
		} else {
			// Creation de l'image en deux temps
			// de facon a conserver les GIF transparents
			$im = $image['fonction_imagecreatefrom']($im);
			imagepalettetotruecolor($im);
			@imagesavealpha($im, true);
			imagefilter($im, IMG_FILTER_BRIGHTNESS, $gamma);
			_image_gd_output($im, $image);
			imagedestroy($im);
		}
	}

	return _image_ecrire_tag($image, ['src' => $dest]);
}

/**
 * Passe l'image en "sepia"
 * On peut fixer les valeurs RGB
 * de la couleur "complementaire" pour forcer une dominante
 *
 * Par defaut, on utilise les fonciton de gd pour un résultat plus rapide
 * mais avec un peu moins de piquant que la méthode historique de SPIP,
 * dont on peut retrouver le résultat en passant $legacy = true en argument
 *
 * @param string $im
 * @param string $rgb
 * @param bool $legacy
 * @return mixed|string
 */
function image_sepia($im, $rgb = '896f5e', $legacy = false) {
	include_spip('filtres/images_lib');

	if (!function_exists('imagecreatetruecolor')) {
		return $im;
	}

	$couleurs = _couleur_hex_to_dec($rgb);
	$dr = $couleurs['red'];
	$dv = $couleurs['green'];
	$db = $couleurs['blue'];

	$fonction = ['image_sepia', func_get_args()];
	$image = _image_valeurs_trans($im, "sepia-$dr-$dv-$db-" . ($legacy ? "legacy" : "gd"), false, $fonction, false, _SVG_SUPPORTED);
	if (!$image) {
		return '';
	}

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	if ($creer) {
		spip_timer('sepia');
		$log = "image_sepia: caldul de $im par methode ";
		if ($image['format_source'] === 'svg') {
			$log .= "svg";
			#$svg = svg_transformer($im, ['style' => "filter:sepia(1);"]); // ne fonctionne pas dans Chrome+Safari
			$svg = svg_filter_sepia($im, 1);
			_image_gd_output($svg, $image);
		} elseif(!$legacy) {
			$log .= "gd";
			$im = $image['fonction_imagecreatefrom']($im);
			imagepalettetotruecolor($im);
			@imagealphablending($im, false);
			@imagesavealpha($im, true);
			$x_i = $image['largeur'];
			$y_i = $image['hauteur'];
			$im_ = imagecreatetruecolor($x_i, $y_i);
			@imagealphablending($im_, true);
			@imagesavealpha($im_, true);
			@imagecopy($im_, $im, 0,0,0,0,$x_i, $y_i);
			imagefilter($im_,IMG_FILTER_GRAYSCALE);

			$k = 0.2;
			imagefilter($im,IMG_FILTER_COLORIZE, round($k*($dr-127)), round($k*($dv-127)), round($k*($db-127)));
			imagefilter($im,IMG_FILTER_GRAYSCALE);


			$kr = 0.75;$ddr = -40;
			$kv = 0.75;$ddv = -40;
			$kb = 0.85;$ddb = -40;
			imagefilter($im,IMG_FILTER_COLORIZE, round($kr*($dr-127)) + $ddr, round($kv*($dv-127)) + $ddv, round($kb*($db-127)) + $ddb);
			imagelayereffect($im, IMG_EFFECT_OVERLAY);
			@imagecopy($im, $im_, 0,0,0,0,$x_i, $y_i);
			imagelayereffect($im, IMG_EFFECT_NORMAL);
			imagefilter($im, IMG_FILTER_CONTRAST, 3);
			imagefilter($im,IMG_FILTER_BRIGHTNESS, 7);

			_image_gd_output($im, $image);
			imagedestroy($im);
		} else {
			$log .= "legacy";
			$x_i = $image['largeur'];
			$y_i = $image['hauteur'];

			// Creation de l'image en deux temps
			// de facon a conserver les GIF transparents
			$im = $image['fonction_imagecreatefrom']($im);
			imagepalettetotruecolor($im);
			$im_ = imagecreatetruecolor($x_i, $y_i);
			@imagealphablending($im_, false);
			@imagesavealpha($im_, true);
			$color_t = ImageColorAllocateAlpha($im_, 255, 255, 255, 127);
			imagefill($im_, 0, 0, $color_t);
			imagecopy($im_, $im, 0, 0, 0, 0, $x_i, $y_i);

			for ($x = 0; $x < $x_i; $x++) {
				for ($y = 0; $y < $y_i; $y++) {
					$rgb = ImageColorAt($im_, $x, $y);
					$a = ($rgb >> 24) & 0xFF;
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;

					$gray = (.299 * $r + .587 * $g + .114 * $b);
					$k1 = ($gray / 127.5 - 1.0);
					if ($gray <= 127.5) {
						$r = round($dr * $k1) + $dr;
						$g = round($dv * $k1) + $dv;
						$b = round($db * $k1) + $db;
					} else {
						$k2 = 255 * $k1;
						$r = round($k2 - $dr * $k1) + $dr;
						$g = round($k2 - $dv * $k1) + $dv;
						$b = round($k2 - $db * $k1) + $db;
					}
					$color = ImageColorAllocateAlpha($im_, $r, $g, $b, $a);
					imagesetpixel($im_, $x, $y, $color);
				}
			}
			_image_gd_output($im_, $image);
			imagedestroy($im_);
			imagedestroy($im);
		}
		spip_log("$log, calcul en " . spip_timer('sepia'), 'images' . _LOG_DEBUG);
	}

	return _image_ecrire_tag($image, ['src' => $dest]);
}

/**
 * Renforcer la netteté d'une image
 *
 * @param string $im
 *     Code HTML de l'image
 * @param float $k
 *     Niveau de renforcement (entre 0 et 1)
 * @return string Code HTML de l'image
 */
function image_renforcement($im, $k = 0.5) {
	$fonction = ['image_renforcement', func_get_args()];
	$image = _image_valeurs_trans($im, "renforcement-$k", false, $fonction);
	if (!$image) {
		return '';
	}

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];
	$creer = $image['creer'];

	if ($creer) {
		spip_timer('image_renforcement');
		spip_log($log = "image_renforcement: $im via gd legacy", 'images' . _LOG_DEBUG);

		$im = $image['fonction_imagecreatefrom']($im);
		imagepalettetotruecolor($im);
		@imagealphablending($im, true);
		@imagesavealpha($im, true);

		$sharpen = [
			[0.0, -$k, 0.0],
			[-$k, 1 + 4 * $k, -$k],
			[0.0, -$k, 0.0]
		];
		imageconvolution($im, $sharpen, 1, 0);
		_image_gd_output($im, $image);

		spip_log($log . " en " . spip_timer('image_renforcement'), 'images' . _LOG_DEBUG);
	}

	return _image_ecrire_tag($image, ['src' => $dest]);
}

/**
 * Transforme la couleur de fond de l'image en transparence
 * Le filtre ne gere pas la notion de contiguite aux bords, et affectera tous les pixels de l'image dans la couleur visee
 * $background_color : couleur cible
 * $tolerance : distance L1 dans l'espace RGB des couleur autour de la couleur $background_color pour lequel la transparence sera appliquee
 * $alpha : transparence a appliquer pour les pixels de la couleur cibles avec la tolerance ci-dessus
 * $coeff_lissage : coeff applique a la tolerance pour determiner la decroissance de la transparence fonction de la distance L1 entre la couleur du pixel et la couleur cible
 *
 * @param string $im
 * @param string $background_color
 * @param int $tolerance
 * @param int $alpha
 *   alpha = 0: aucune transparence
 *   alpha = 127: completement transparent
 * @param int $coeff_lissage
 * @return mixed|null|string
 */
function image_fond_transparent($im, $background_color, $tolerance = 12, $alpha = 127, $coeff_lissage = 7) {
	$fonction = ['image_fond_transparent', func_get_args()];
	$image = _image_valeurs_trans($im, "fond_transparent-$background_color-$tolerance-$coeff_lissage-$alpha", 'png', $fonction, false, _SVG_SUPPORTED);
	if (!$image) {
		return '';
	}
	if ($image['format_source'] === 'svg') {
		// Ne rien faire si SVG
		$image = _image_valeurs_trans($im, "fond_transparent-$background_color-$tolerance-$coeff_lissage-$alpha", false, $fonction);
	}

	$x_i = $image['largeur'];
	$y_i = $image['hauteur'];

	$im = $image['fichier'];
	$dest = $image['fichier_dest'];

	$creer = $image['creer'];

	if ($creer) {
		$bg = _couleur_hex_to_dec($background_color);
		$bg_r = $bg['red'];
		$bg_g = $bg['green'];
		$bg_b = $bg['blue'];

		// Creation de l'image en deux temps
		// de facon a conserver les GIF transparents
		$im = $image['fonction_imagecreatefrom']($im);
		imagepalettetotruecolor($im);
		$im2 = imagecreatetruecolor($x_i, $y_i);
		@imagealphablending($im2, false);
		@imagesavealpha($im2, true);
		$color_t = ImageColorAllocateAlpha($im2, 255, 255, 255, 127);
		imagefill($im2, 0, 0, $color_t);
		imagecopy($im2, $im, 0, 0, 0, 0, $x_i, $y_i);

		$im_ = imagecreatetruecolor($x_i, $y_i);
		imagealphablending($im_, false);
		imagesavealpha($im_, true);
		$color_f = ImageColorAllocateAlpha($im_, 255, 255, 255, $alpha);

		for ($x = 0; $x < $x_i; $x++) {
			for ($y = 0; $y < $y_i; $y++) {
				$rgb = ImageColorAt($im2, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if ((($d = abs($r - $bg_r) + abs($g - $bg_g) + abs($b - $bg_b)) <= $tolerance)) {
					imagesetpixel($im_, $x, $y, $color_f);
				} elseif ($tolerance and $d <= ($coeff_lissage + 1) * $tolerance) {
					$transp = round($alpha * (1 - ($d - $tolerance) / ($coeff_lissage * $tolerance)));
					$color_p = ImageColorAllocateAlpha($im_, $r, $g, $b, $transp);
					imagesetpixel($im_, $x, $y, $color_p);
				} else {
					imagesetpixel($im_, $x, $y, $rgb);
				}
			}
		}
		_image_gd_output($im_, $image);
		imagedestroy($im_);
		imagedestroy($im);
		imagedestroy($im2);
	}

	return _image_ecrire_tag($image, ['src' => $dest]);
}
