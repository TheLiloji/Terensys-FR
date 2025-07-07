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
 * Fonctions et filtres du compresseur
 *
 * @package SPIP\Compresseur\Fonctions
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
$GLOBALS['spip_matrice']['compresseur_embarquer_images_css'] = 'inc/compresseur_embarquer.php';

/**
 * Minifier un fichier JS ou CSS
 *
 * Si la source est un chemin, on retourne un chemin avec le contenu minifié
 * dans _DIR_VAR/cache_$format/
 * Si c'est un flux on le renvoit compacté
 * Si on ne sait pas compacter, on renvoie ce qu'on a recu
 *
 * @param string $source
 *     Contenu à minifier ou chemin vers un fichier dont on veut minifier le contenu
 * @param string $format
 *     Format de la source (js|css).
 * @return string
 *     - Contenu minifié (si la source est un contenu)
 *     - Chemin vers un fichier ayant le contenu minifié (si source est un fichier)
 */
function minifier($source, $format = null) {
	$contenu = null;
	$maybe_file = false;
	if (
		strpos($source, "\n") === false
		and strpos($source, '{') === false
		and strpos($source, '}') === false
	) {
		$maybe_file = true;
		include_spip('inc/filtres');
		$source = supprimer_timestamp($source);
	}
	if (!$format and preg_match(',\.(js|css)$,', $source, $r)) {
		$format = $r[1];
	}
	include_spip('inc/compresseur_minifier');
	if (!function_exists($minifier = 'minifier_' . $format)) {
		return $source;
	}

	// Si on n'importe pas, est-ce un fichier ?
	if (
		$maybe_file
		and preg_match(',\.' . $format . '$,i', $source)
		and file_exists($source)
	) {
		// si c'est un fichier deja minifie, on ne fait rien !
		if (preg_match(',\.min\.' . $format . '$,i', $source)) {
			return $source;
		}
		// un fichier minifie existe-t-il deja, fourni avec le non-minifie ? (lib tierce-partie)
		$f = preg_replace(',\.(' . $format . ')$,i', ".min.\\1", $source);
		if (file_exists($f)) {
			return $f;
		}

		// si c'est une css, il faut reecrire les url en absolu
		if ($format == 'css') {
			$source = url_absolue_css($source);
		}

		// calculer le nom du fichier
		$f = basename($source, '.' . $format);
		$f = sous_repertoire(_DIR_VAR, 'cache-' . $format)
			. preg_replace(',(.*?)(_rtl|_ltr)?$,', "\\1-minify-"
				. substr(md5("$source-minify"), 0, 4) . "\\2", $f, 1)
			. '.' . $format;

		if (
			(@filemtime($f) > @filemtime($source))
			and (!defined('_VAR_MODE') or _VAR_MODE != 'recalcul')
		) {
			return $f;
		}

		if (!lire_fichier($source, $contenu)) {
			return $source;
		}

		// traiter le contenu
		$contenu = $minifier($contenu);

		// ecrire le fichier destination, en cas d'echec renvoyer la source
		if (ecrire_fichier_calcule_si_modifie($f, $contenu) === false) {
			return $source;
		}
		return $f;
	}

	// Sinon simple minification de contenu
	return $minifier($source);
}

/**
 * Synonyme historique de minifier, pour compatibilite
 *
 * @deprecated 1.6 (SPIP3.1)
 * @see minifier()
 * @uses minifier()
 *
 * @param string $source
 * @param string $format
 * @return string
 */
function compacte($source, $format = null) {
	return minifier($source, $format);
}

/**
 * Compacte les éléments CSS et JS d'un <head> HTML
 *
 * Cette fonction vérifie les réglages du site et traite le compactage
 * des css et/ou js d'un <head>
 *
 * @see compacte_head_files()
 *
 * @param string $flux
 *     Partie de contenu du head HTML
 * @return string
 *     Partie de contenu du head HTML
 */
function compacte_head($flux) {
	include_spip('inc/compresseur');
	if (!defined('_INTERDIRE_COMPACTE_HEAD')) {
		// dans l'espace prive on compacte toujours, c'est concu pour
		if (
			(!test_espace_prive()
				and !empty($GLOBALS['meta']['auto_compress_css'])
				and $GLOBALS['meta']['auto_compress_css'] == 'oui') or (test_espace_prive() and !defined('_INTERDIRE_COMPACTE_HEAD_ECRIRE'))
		) {
			$flux = compacte_head_files($flux, 'css');
		}
		if (
			(!test_espace_prive()
				and !empty($GLOBALS['meta']['auto_compress_js'])
				and $GLOBALS['meta']['auto_compress_js'] == 'oui') or (test_espace_prive() and !defined('_INTERDIRE_COMPACTE_HEAD_ECRIRE'))
		) {
			$flux = compacte_head_files($flux, 'js');
		}
	}

	return $flux;
}

/**
 * Embarquer sous forme URI Scheme un fichier
 *
 * Une URI Scheme est de la forme data:xxx/yyy;base64,....
 *
 * Experimental
 *
 * @filtre
 *
 * @staticvar array $mime
 *     Couples (extension de fichier => type myme)
 * @param string $src
 *     Chemin du fichier
 * @param string $base
 *     Le chemin de base à partir duquel chercher $src
 * @param int $maxsize
 *     Taille maximale des fichiers à traiter
 * @return string
 *     URI Scheme du fichier si la compression est faite,
 *     URL du fichier sinon (la source)
 */
function filtre_embarque_fichier($src, $base = '', $maxsize = 4096) {
	$contenu = null;
	static $mime = [];

	if (empty($src) or !is_string($src)) {
		return $src;
	}

	if (str_contains($src, '?')) {
		include_spip('inc/filtres');
		$src = supprimer_timestamp($src);
	}

	$extension = substr(strrchr($src, '.'), 1);
	$filename = $base . $src;

	if (
		!file_exists($filename)
		or filesize($filename) > $maxsize
		or !lire_fichier($filename, $contenu)
	) {
		return $src;
	}

	if (!isset($mime[$extension])) {
		if (isset($GLOBALS['tables_mime']) and isset($GLOBALS['tables_mime'][$extension])) {
			$mime[$extension] = $GLOBALS['tables_mime'][$extension];
		}
	}
	if (!isset($mime[$extension])) {
		if (!function_exists('sql_getfetsel')) {
			include_spip('base/abstract_sql');
		}
		$mime[$extension] = sql_getfetsel('mime_type', 'spip_types_documents', 'extension=' . sql_quote($extension));
	}

	// cas particulier du SVG : un urlencode est suffisant, plus performant et mieux supporté
	if ($extension === 'svg') {
		include_spip('inc/svg');
		$contenu = svg_nettoyer($contenu);
		// plus un peu de mise en forme pour faciliter l'embed
		$contenu = trim($contenu);
		$contenu = preg_replace('/\\s+/', ' ', $contenu);
		$contenu = str_replace('"', "'", $contenu);
		$contenu = rawurlencode($contenu);

		// rétablir les échappements tolérés, pour faciliter la lecture du svg
		$contenu = str_replace(['%20', '%27', '%2C', '%3D', '%3A', '%2F'], [' ', "'", ',', '=', ':', '/'], $contenu);

		$encoded = 'utf8,' . $contenu;
	} else {
		$base64 = base64_encode($contenu);
		$encoded = 'base64,' . $base64;
	}

	$encoded = 'data:' . $mime[$extension] . ';' . $encoded;

	return $encoded;
}


/**
 * Embarquer le 'src' d'une balise html en URI Scheme
 *
 * Experimental
 *
 * @param string $img
 *     Code HTML d'une image
 * @param int $maxsize
 *     Taille maximale des fichiers à traiter
 * @return string
 *     Code HTML de l'image, avec la source en URI Scheme si cela a été possible.
 */
function filtre_embarque_src($img, $maxsize = 4096) {
	$src = extraire_attribut($img, 'src');
	if ($src2 = filtre_embarque_fichier($src, '', $maxsize) and $src2 != $src) {
		$img = inserer_attribut($img, 'src', $src2);
	}

	return $img;
}
