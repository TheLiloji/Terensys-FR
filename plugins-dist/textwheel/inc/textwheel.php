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

include_spip('engine/textwheel');
// si une regle change et rend son cache non valide
// incrementer ce define au numero de commit concerne
// (inconsistence entre la wheel et l'inclusion php)
if (!defined('_WHEELS_VERSION')) {
	define('_WHEELS_VERSION', 68672);
}

//
// Definition des principales wheels de SPIP
//
if (!isset($GLOBALS['spip_wheels'])) {
	$GLOBALS['spip_wheels'] = [];
}

// Si le tableau des raccourcis existe déjà
if (!isset($GLOBALS['spip_wheels']['raccourcis']) or !is_array($GLOBALS['spip_wheels']['raccourcis'])) {
	$GLOBALS['spip_wheels']['raccourcis'] = [
		'spip/spip',
		'spip/spip-paragrapher'
	];
} else {
	$GLOBALS['spip_wheels']['raccourcis'] = array_merge(
		[
			'spip/spip',
			'spip/spip-paragrapher'
		],
		$GLOBALS['spip_wheels']['raccourcis']
	);
}

if (test_espace_prive()) {
	$GLOBALS['spip_wheels']['raccourcis'][] = 'spip/ecrire';
}

// Si le tableau des pre_echappe_html_propre existe déjà
if (!isset($GLOBALS['spip_wheels']['pre_echappe_html_propre']) or !is_array($GLOBALS['spip_wheels']['pre_echappe_html_propre'])) {
	$GLOBALS['spip_wheels']['pre_echappe_html_propre'] = [
		'spip/backticks',
	];
} else {
	$GLOBALS['spip_wheels']['pre_echappe_html_propre'] = array_merge(
		[
			'spip/backticks',
		],
		$GLOBALS['spip_wheels']['pre_echappe_html_propre']
	);
}

$GLOBALS['spip_wheels']['interdire_scripts'] = [
	'spip/interdire-scripts'
];

$GLOBALS['spip_wheels']['echappe_js'] = [
	'spip/echappe-js'
];

$GLOBALS['spip_wheels']['paragrapher'] = [
	'spip/spip-paragrapher'
];

$GLOBALS['spip_wheels']['listes'] = [
	'spip/spip-listes'
];

//
// Methode de chargement d'une wheel SPIP
//

class SPIPTextWheelRuleset extends TextWheelRuleSet {
	protected function findFile(&$file, $path = '') {
		static $default_path;

		// absolute file path?
		if (file_exists($file)) {
			return $file;
		}

		// file include with texwheels, relative to calling ruleset
		if ($path and file_exists($f = $path . $file)) {
			return $f;
		}

		return find_in_path($file, 'wheels/');
	}

	/**
	 * @return SPIPTextWheelRuleset
	 */
	public static function &loader($ruleset, $callback = '', $class = 'SPIPTextWheelRuleset') {

		# memoization
		# attention : le ruleset peut contenir apres loading des chemins relatifs
		# il faut donc que le cache depende du chemin courant vers la racine de SPIP
		$key = '';
		if ($callback) {
			$key = $callback($key);
		}
		$key = 'tw-' . md5(_WHEELS_VERSION . '-' . serialize($ruleset) . $key . $class . _DIR_RACINE);

		# lecture du cache
		if (
			(!defined('_VAR_MODE') or _VAR_MODE != 'recalcul')
			and $cacheruleset = tw_cache_get($key)
		) {
			return $cacheruleset;
		}

		# calcul de la wheel
		$ruleset = parent::loader($ruleset, $callback, $class);

		# ecriture du cache
		tw_cache_set($key, $ruleset);

		return $ruleset;
	}
}


function tw_trig_purger($quoi) {
	if ($quoi == 'cache') {
		purger_repertoire(_DIR_CACHE . 'wheels');
	}

	return $quoi;
}


/**
 * Lire une valeur en cache
 * memoization minimale
 * (utilise le plugin memoization si disponible)
 *
 * @param string $key
 * @return mixed
 */
function tw_cache_get($key) {
	if (function_exists('cache_get')) {
		return cache_get($key);
	}

	return @unserialize(file_get_contents(_DIR_CACHE . 'wheels/' . $key . '.txt'));
}

/**
 * Ecrire une valeur en cache
 * memoization minimale
 * (utilise le plugin memoization si disponible)
 *
 * @param string $key
 * @param mixed $value
 * @param int|null $ttl
 * @return bool
 */
function tw_cache_set($key, $value, $ttl = null) {
	if (function_exists('cache_set')) {
		return cache_set($key, $value, $ttl);
	}
	$dir = sous_repertoire(_DIR_CACHE, 'wheels/');

	return ecrire_fichier($dir . $key . '.txt', serialize($value));
}
