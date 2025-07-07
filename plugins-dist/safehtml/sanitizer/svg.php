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
 * Informations meta d'un SVG
 *
 * @package SPIP\SafeHtml\Sanitizer
 **/

use enshrined\svgSanitize\Sanitizer;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('inc/autoriser');

/**
 * Nettoyer et normaliser un svg, et enlever ses scripts si nécessaire
 *
 * cf http://www.slideshare.net/x00mario/the-image-that-called-me
 * http://heideri.ch/svgpurifier/SVGPurifier/index.php
 *
 * @param string $file_or_svg
 * @return bool
 */
function sanitizer_svg_dist($file_or_svg) {

	include_spip('inc/svg');
	if ($svg = svg_charger($file_or_svg)) {
		if ($svg === $file_or_svg) {
			$file = false;
		}
		else {
			$file = $file_or_svg;
		}
		unset($file_or_svg);

		// forcer une viewBox et width+height en px
		$svg = svg_force_viewBox_px($svg, true);

		// Securite si pas autorise : virer les scripts et les references externes
		// sauf si on est en mode javascript 'ok' (1), cf. inc_version
		if (
			$GLOBALS['filtrer_javascript'] < 1
			// qu'on soit admin ou non, on sanitize les SVGs car rien ne dit qu'un admin sait que ca contient du JS
			// and !autoriser('televerser', 'script')
		) {
			$svg = sanitizer_svg_string($svg, $file);
		}

		ecrire_fichier($file, $svg);
		clearstatcache();
		return true;
	}

	// pas de svg valide
	return false;
}

/**
 * @param string $svg
 * @param string $fileName
 * @return false|string
 */
function sanitizer_svg_string($svg, $fileName = '') {

	$tracesvg = ($fileName ?: substr($svg, 0,32)."...(".strlen($svg). "c ". md5($svg) . ")");
	spip_log("sanitization SVG $tracesvg", 'svg' . _LOG_DEBUG);

	if (!class_exists('enshrined\svgSanitize\Sanitizer')) {
		spl_autoload_register(function ($class) {
			$prefix = 'enshrined\\svgSanitize\\';
			$base_dir = _DIR_PLUGIN_SAFEHTML . 'lib/svg-sanitizer/src/';
			$len = strlen($prefix);
			if (strncmp($prefix, $class, $len) !== 0) {
				return;
			}
			$relative_class = substr($class, $len);
			$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
			if (file_exists($file)) {
				require $file;
			}
		});
	}

	// sanitization can need multiples call
	$maxiter = 10;
	do {
		$size = strlen($svg);
		$sanitizer = new Sanitizer();
		$sanitizer->setXMLOptions(0); // garder les balises vide en ecriture raccourcie
		$sanitizer->removeXMLTag(true);

		// Pass it to the sanitizer and get it back clean
		$svg = $sanitizer->sanitize($svg);

		// loger les sanitization
		$trace = '';
		foreach ($sanitizer->getXmlIssues() as $issue) {
			$trace .= $issue['message'] . ' L' . $issue['line'] . "\n";
		}
		if ($trace) {
			spip_log($trace, 'svg' . _LOG_DEBUG);
		}
	} while (strlen($svg) !== $size and $maxiter-- > 0);

	return $svg;
}
