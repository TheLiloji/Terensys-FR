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
 * Gestion des cookies
 *
 * @package SPIP\Core\Cookies
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Place un cookie (préfixé) sur le poste client
 *
 * @global string cookie_prefix Préfixe de cookie défini
 * @link http://fr.php.net/setcookie
 *
 * @param string $name
 *     Nom du cookie
 * @param string $value
 *     Valeur à stocker
 * @param array{expires: int, path: string, domain: string, secure: bool, samesite: string} $options
 *     Tableau clé => valeur de l’option
 *     - expires = 0 : Date d'expiration du cookie (timestamp)
 *     - path = 'AUTO' : Chemin sur lequel le cookie sera disponible
 *     - domain = '' : Domaine à partir duquel le cookie est disponible
 *     - secure = false : cookie sécurisé ou non ?
 *     - samesite = 'Lax' : valeur samesite (Lax, Strict ou None)
 * @return bool
 *     true si le cookie a été posé, false sinon.
 *
 * @note La signature changera en SPIP 5.0 pour reprendre la même signature que PHP.
 * @note Anciens paramètres (SPIP < 4.0) (à la place de $options) (pour rétrocompatibilité)
 *   param int $expire
 *     Date d'expiration du cookie (timestamp)
 *   param string $path
 *     Chemin sur lequel le cookie sera disponible
 *   param string $domain
 *     Domaine à partir duquel le cookie est disponible
 *   param bool $secure
 *     cookie sécurisé ou non ?
 **/
function spip_setcookie($name = '', $value = '', $options = []) {
	static $to_secure_list = ['spip_session'];
	/** @deprecated _COOKIE_SECURE_LIST: Will be deprecated in 5.0 (Use option `'httponly' => true`) */
	if (defined('_COOKIE_SECURE_LIST') and is_array(constant('_COOKIE_SECURE_LIST'))) {
		$to_secure_list = array_merge($to_secure_list, constant('_COOKIE_SECURE_LIST'));
	}

	if (!is_array($options)) {
		// anciens paramètres :
		# spip_setcookie($name = '', $value = '', $expire = 0, $path = 'AUTO', $domain = '', $secure = '')
		$opt = func_get_args();
		$opt = array_slice($opt, 2);
		$options = []; # /!\ après le func_get_args (sinon $opt[0] référence la nouvelle valeur de $options !);
		if (isset($opt[0])) {
			$options['expires'] = $opt[0];
		}
		if (isset($opt[1])) {
			$options['path'] = $opt[1];
		}
		if (isset($opt[2])) {
			$options['domain'] = $opt[2];
		}
		if (isset($opt[3])) {
			$options['secure'] = $opt[3];
		}
		if (isset($opt[4])) {
			$options['httponly'] = $opt[4];
		}
	}

	// expires
	$options['expires'] ??= 0;
	if (!isset($options['path']) || $options['path'] === 'AUTO') {
		$options['path'] = defined('_COOKIE_PATH') ? constant('_COOKIE_PATH') : preg_replace(',^\w+://[^/]*,', '', url_de_base());
	}
	if (empty($options['domain']) && defined('_COOKIE_DOMAIN') && constant('_COOKIE_DOMAIN')) {
		$options['domain'] = constant('_COOKIE_DOMAIN');
	}
	$options['secure'] ??= false;
	$options['secure'] = ($options['secure'] ?: ($_SERVER['HTTPS'] ?? false));
	if (defined('_COOKIE_SECURE') && constant('_COOKIE_SECURE')) {
		/** @deprecated Will be deprecated in 5.0 (Use option `'secure' => true`; automatic in HTTPS) */
		$options['secure'] = true;
	}
	$options['httponly'] ??= false;
	$options['samesite'] = ($options['samesite'] ?? 'Lax') ?: 'Lax';

	if (in_array($name, $to_secure_list)) {
		$options['httponly'] = true;
	}

	// in fine renommer le prefixe si besoin
	if (strpos($name, 'spip_') === 0) {
		$name = $GLOBALS['cookie_prefix'] . '_' . substr($name, 5);
	}

	#spip_log("cookie('$name', '$value', " . json_encode($options, true) . ")", "cookies");
	$a = @setcookie($name, $value, $options);

	spip_cookie_envoye(true);

	return $a;
}

/**
 * Teste si un cookie a déjà été envoyé ou pas
 *
 * Permet par exemple à `redirige_par_entete()` de savoir le type de
 * redirection à appliquer (serveur ou navigateur)
 *
 * @see redirige_par_entete()
 *
 * @param bool|string $set
 *     true pour déclarer les cookies comme envoyés
 * @return bool
 **/
function spip_cookie_envoye($set = '') {
	static $envoye = false;
	if ($set) {
		$envoye = true;
	}

	return $envoye;
}

/**
 * Adapte le tableau PHP `$_COOKIE` pour prendre en compte le préfixe
 * des cookies de SPIP
 *
 * Si le préfixe des cookies de SPIP est différent de `spip` alors
 * la fonction modifie les `$_COOKIE` ayant le préfixe spécifique + '_'
 * pour remettre le préfixe `spip_` à la place.
 *
 * Ainsi les appels dans le code n'ont pas besoin de gérer le préfixe,
 * ils appellent simplement `$_COOKIE['spip_xx']` qui sera forcément
 * la bonne donnée.
 *
 * @param string $cookie_prefix
 *     Préfixe des cookies de SPIP
 **/
function recuperer_cookies_spip($cookie_prefix) {
	$cookie_prefix .= '_';
	$prefix_long = strlen($cookie_prefix);

	foreach (array_keys($_COOKIE) as $name) {
		if (str_starts_with($name, 'spip_') && !str_starts_with($name, $cookie_prefix)) {
			unset($_COOKIE[$name]);
		}
	}
	foreach ($_COOKIE as $name => $value) {
		if (str_starts_with($name, $cookie_prefix)) {
			$spipname = 'spip_' . substr($name, $prefix_long);
			$_COOKIE[$spipname] = $value;
		}
	}
}


/**
 * Teste si javascript est supporté par le navigateur et pose un cookie en conséquence
 *
 * Si la valeur d'environnement `js` arrive avec la valeur
 *
 * - `-1` c'est un appel via une balise `<noscript>`.
 * - `1` c'est un appel via javascript
 *
 * Inscrit le résultat dans le cookie `spip_accepte_ajax`
 *
 * @see  html_tests_js()
 * @uses spip_setcookie()
 *
 **/
function exec_test_ajax_dist() {
	switch (_request('js')) {
		// on est appele par <noscript>
		case -1:
			spip_setcookie('spip_accepte_ajax', -1);
			include_spip('inc/headers');
			redirige_par_entete(chemin_image('erreur-xx.svg'));
			break;

		// ou par ajax
		case 1:
		default:
			spip_setcookie('spip_accepte_ajax', 1);
			break;
	}
}
