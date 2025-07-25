<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

// Script pour appeler un squelette apres s'etre authentifie

require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/inc_version.php';

include_spip('inc/cookie');

$auth = charger_fonction('auth', 'inc');
$var_auth = $auth();

if ($var_auth !== '') {
	if (!is_int($var_auth)) {
		// si l'authentifie' n'a pas acces a l'espace de redac
		// c'est qu'on voulait forcer sa reconnaissance en tant que visiteur.
		// On reexecute pour deboucher sur le include public.
		// autrement on insiste
		if (is_array($var_auth)) {
			$var_auth = '../?' . $_SERVER['QUERY_STRING'];
			spip_setcookie('spip_session', $_COOKIE['spip_session'], [
				'expires' => time() + 3600 * 24 * 14,
				'httponly' => true,
			]);
		}
		include_spip('inc/headers');
		redirige_formulaire($var_auth);
	}
}

// En somme, est prive' ce qui est publiquement nomme'...
include 'public.php';
