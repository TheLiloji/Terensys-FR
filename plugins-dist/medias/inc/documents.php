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

// inclure les fonctions bases du core
include_once _DIR_RESTREINT . 'inc/documents.php';

include_spip('inc/actions'); // *action_auteur et determine_upload

// Constante indiquant le charset probable des documents non utf-8 joints

if (!defined('CHARSET_JOINT')) {
	define('CHARSET_JOINT', 'iso-8859-1');
}

// Filtre pour #FICHIER permettant d'incruster le contenu d'un document
// Si 2e arg fourni, conversion dans le charset du site si possible

function contenu_document($arg, $charset = '') {
	include_spip('inc/distant');
	if (is_numeric($arg)) {
		$r = sql_fetsel('fichier,distant', 'spip_documents', 'id_document=' . intval($arg));
		if (!$r) {
			return '';
		}
		$f = $r['fichier'];
		$f = ($r['distant'] == 'oui') ? _DIR_RACINE . copie_locale($f) : get_spip_doc($f);
	} else {
		if (!@file_exists($f = $arg)) {
			if (!$f = copie_locale($f)) {
				return '';
			}
			$f = _DIR_RACINE . $f;
		}
	}

	$r = spip_file_get_contents($f);

	if ($charset) {
		include_spip('inc/charsets');
		if ($charset !== 'auto') {
			$r = importer_charset($r, $charset);
		} elseif ($GLOBALS['meta']['charset'] == 'utf-8' and !is_utf8($r)) {
			$r = importer_charset($r, CHARSET_JOINT);
		}
	}

	return $r;
}

function generer_url_document_dist($id_document, $args = '', $ancre = '') {

	include_spip('inc/autoriser');
	// si on a pas le droit de voir le document, meme via le htaccess
	if (!autoriser('voir', 'document', $id_document, null, ['htaccess' => true])) {
		return '';
	}

	$r = sql_fetsel('fichier,distant', 'spip_documents', 'id_document=' . intval($id_document));

	if (!$r) {
		return '';
	}

	$f = $r['fichier'];

	if ($r['distant'] == 'oui') {
		return $f;
	}

	// Si droit de voir tous les docs, sans htaccess, pas seulement celui-ci
	// il est inutilement couteux de rajouter une protection
	if (autoriser('voir', 'document')) {
		return get_spip_doc($f);
	}

	include_spip('inc/securiser_action');

	// cette action doit etre publique !
	return generer_url_action(
		'acceder_document',
		$args . ($args ? '&' : '')
			. 'arg=' . $id_document
			. ($ancre ? "&ancre=$ancre" : '')
			. '&cle=' . calculer_cle_action($id_document . ',' . $f)
			. '&file=' . rawurlencode($f),
		true,
		true
	);
}

//
// Affiche le document avec sa vignette par defaut
//
// Attention : en mode 'doc', si c'est un fichier graphique on prefere
// afficher une vue reduite, quand c'est possible (presque toujours, donc)
// En mode 'image', l'image conserve sa taille
//
// A noter : dans le portfolio prive on pousse le vice jusqu'a reduire la taille
// de la vignette -> c'est a ca que sert la variable $portfolio
function vignette_automatique($img, $doc, $lien, $x = 0, $y = 0, $align = '', $class = null, $connect = null) {
	include_spip('inc/distant');
	include_spip('inc/texte');
	include_spip('inc/filtres_images_mini');
	if (is_null($class)) {
		$class = 'spip_logo spip_logos';
	}
	$e = $doc['extension'] ?? null;
	if (!$img) {
		if ($img = image_du_document($doc, $connect)) {
			if (!$x and !$y) {
				// eviter une double reduction
				$img = image_reduire($img);
			}
		} else {
			$f = charger_fonction('vignette', 'inc');
			$img = $f($e, $doc['media'] ?? '');
			$size = @spip_getimagesize($img);
			$img = "<img src='$img' " . $size[3] . ' />';
			$class .= ' spip_document_icone';
		}
	} else {
		$size = @spip_getimagesize($img);
		$img = "<img src='$img' " . $size[3] . ' />';
	}
	// on appelle image_reduire independamment de la presence ou non
	// des librairies graphiques
	// la fonction sait se debrouiller et faire de son mieux dans tous les cas
	if ($x or $y) {
		$img = image_reduire($img, $x, $y);
	}
	$img = inserer_attribut($img, 'alt', '');
	if ($align) {
		$class .= " spip_logo_$align";
	}
	$img = inserer_attribut($img, 'class', trim($class));

	if (!$lien) {
		return $img;
	}

	$titre = supprimer_tags(typo($doc['titre']));
	$titre = ' - ' . taille_en_octets($doc['taille'])
		. ($titre ? " - $titre" : '');

	$type = sql_fetsel('titre, mime_type', 'spip_types_documents', 'extension = ' . sql_quote($e));

	$mime = $type['mime_type'];
	$titre = attribut_html(couper($type['titre'] . $titre, 80));

	return "<a href='$lien' type='$mime' title='$titre'>$img</a>";
}

/**
 * Trouve une image caractéristique d'un document.
 *
 * Si celui-ci est une image et que les outils graphiques sont dispos,
 * retourner le document (en exploitant sa copie locale s'il est distant).
 *
 * Si on a un connecteur externe, on utilise l’URL externe.
 *
 * Autrement retourner la vignette fournie par SPIP pour ce type MIME
 *
 * @param array $document
 * @param null|string $connect
 * @return string Chemin de l’image
 */
function image_du_document($document, $connect = null) {
	if (
		$e = $document['extension'] ?? null
		and in_array($e, formats_image_acceptables())
		and (!test_espace_prive() or $GLOBALS['meta']['creer_preview'] == 'oui')
		and $document['fichier']
	) {
		include_spip('inc/quete');
		if ($document['distant'] == 'oui') {
			$image = _DIR_RACINE . copie_locale($document['fichier']);
		} elseif ($image = document_spip_externe($document['fichier'], $connect)) {
			return $image;
		} else {
			$image = get_spip_doc($document['fichier']);
		}
		if (@file_exists($image)) {
			return $image;
		}
	}

	return '';
}

/**
 * Affiche le code d'un raccourcis de document, tel que <doc123|left>
 *
 * Affiche un code de raccourcis de document, et l'insère
 * dans le textarea principal de l'objet (champ 'texte') sur un double-clic
 *
 * @param string $doc
 *    Type de raccourcis : doc,img,emb...
 * @param int $id
 *    Identifiant du document
 * @param string $align
 *    Alignement du document : left,center,right
 * @param bool $short
 *    Réduire le texte affiché à la valeur de 'align'
 *
 * @return string
 *    Texte du raccourcis
 **/
function affiche_raccourci_doc($doc, $id, $align = '', $short = false) {

	$pipe = '';
	if ($align) {
		$pipe = "|$align";
	}

	$model = "&lt;$doc$id$pipe&gt;";
	$text = $model;
	if ($short) {
		$text = $align ?: $model;
	}

	$classes = 'btn btn_link btn_mini';
	$classes = " class=\"$classes\"";

	$styles = '';
	if ($align && !$short) {
		// a priori ne sert plus de toutes façons…
		$styles = 'text-align: ' . ($align ?: 'center') . ';';
		$styles = " style=\"$styles\"";
	}

	$js = "barre_inserer('$model'); return false;";
	$js = " onmousedown=\"$js\"";

	$title = attribut_html(_T('medias:inserer_raccourci'));
	$title = " title=\"$title\"";

	return "\n<button{$classes}{$styles}{$js}{$title}>$text</button>\n";
}
