<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Rechercher les fichiers css de skin mediabox disponibles dans un sous repertoire du chemin()
 *
 * @param $dir
 * @param $fichier_cherche
 * @return array
 */
function mediabox_lister_skin_files($dir, $fichier_cherche) {
	$liste_fichiers = [];
	$dir = trim($dir, '/');

	$chemin = creer_chemin();
	foreach ($chemin as $c) {
		$cd = $c . $dir . '/';
		if (@is_dir($cd)) {
			$subdirs = glob($cd . '*', GLOB_ONLYDIR);
			foreach ($subdirs as $sd) {
				if (is_file($f = $sd . '/' . $fichier_cherche)) {
					$nom = basename($sd);
					// ne prendre que les fichiers pas deja trouves
					// car find_in_path prend le premier qu'il trouve,
					// les autres sont donc masques
					if (!isset($liste_fichiers[$nom])) {
						$liste_fichiers[$nom] = $f;
					}
				}
			}
		}
	}

	return $liste_fichiers;
}


/**
 * lister les skins d'une box donnee
 *
 * @param string $box_name
 * @param string $sous_repertoire
 * @return array[]
 */
function mediabox_lister_skins($box_name, $sous_repertoire = 'skins') {
	$skins = ['none' => ['nom' => _T('mediabox:label_aucun_style')]];


	$dir = $box_name . '/';
	if ($sous_repertoire) {
		$dir .= trim($sous_repertoire, '/') . '/';
	}
	$liste_fichiers = mediabox_lister_skin_files($dir, $box_name . '.css');

	foreach ($liste_fichiers as $short => $fullpath) {
		$nom = _T($s = 'mediabox:info_box_' . str_replace('-', '_', $box_name . '_nom_skin_' . $short), [], ['force' => false]);
		if (!$nom) {
			$nom = $short;
		}

		$skins[$short] = [
			'nom' => $nom
		];

		if (file_exists($f = dirname($fullpath) . '/vignette.jpg')) {
			$skins[$short]['img'] = $f;
		}
	}

	return $skins;
}

/**
 * Presenter le choix de skin d'une box parmi uns liste genereee par la fonction mediabox_lister_skins()
 * @param $skins
 * @param $selected
 * @param string $name
 * @return string
 */
function mediabox_presenter_selection_skins($skins, $selected, $name = 'skin') {
	$out = '';
	if (!is_array($skins) or !count($skins)) {
		return $out;
	}

	$rel = 'habillage_' . substr(md5($name), 0, 4);

	foreach ($skins as $k => $skin) {
		$id = preg_replace(',[^a-z0-9_],i', '_', "{$name}_{$k}");
		$sel = ($selected == "$k" ? " checked='checked'" : '');
		$balise_img = chercher_filtre('balise_img');
		$label = $skin['nom'];
		if (!empty($skin['img'])) {
			$label =
				'<a href="' . $skin['img'] . '" class="mediabox" rel="' . $rel . '" title="' . attribut_html($label) . '">'
				. $balise_img($skin['img'], $label)
				. '</a>';
		}

		$out .= "<div class='choix choix-skin'>";
		$out .= "<input type='radio' name='$name' id='$id' value='$k'$sel />";
		$out .= "<label for='$id'>$label</label>";
		$out .= "</div>\n";
	}

	$out = "<div class='choix clearfix'>$out</div>";

	return $out;
}
