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
 * Présentation des pages d'installation et d'erreurs
 *
 * @package SPIP\Core\Minipres
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retourne le début d'une page HTML minimale (de type installation)
 *
 * @deprecated 4.2 Utiliser `Spip\Afficher\Minipage\Admin`
 * @uses \Spip\Afficher\Minipage\Admin
 *
 * @param string $titre
 *    Titre. `AUTO`, indique que l'on est dans le processus d'installation de SPIP
 * @param string $onload
 *    Attributs pour la balise `<body>`
 * @param bool $all_inline
 *    Inliner les css et js dans la page (limiter le nombre de hits)
 * @return string
 *    Code HTML
 */
function install_debut_html($titre = 'AUTO', $onload = '', $all_inline = false) {
	trigger_deprecation('spip', '4.2', 'Using "%s" is deprecated, use "%s" instead', __FUNCTION__, 'Spip\Afficher\Minipage\Admin::installDebutPage');

	if ($onload) {
		include_spip('inc/filtres');
		$onLoad = extraire_attribut("<body $onload>", 'onload');
	}

	$options = [
		'all_inline' => $all_inline,
		'onload' => $onload,
		'titre' => $titre,
	];

	$minipage = new Spip\Afficher\Minipage\Admin();
	return $minipage->installDebutPage($options);
}

/**
 * Retourne la fin d'une page HTML minimale (de type installation ou erreur)
 *
 * @deprecated 4.2 Utiliser `Spip\Afficher\Minipage\Admin`
 * @uses \Spip\Afficher\Minipage\Admin
 *
 * @return string Code HTML
 */
function install_fin_html() {
	trigger_deprecation('spip', '4.2', 'Using "%s" is deprecated, use "%s" instead', __FUNCTION__, 'Spip\Afficher\Minipage\Admin::installFinPage');

	$minipage = new Spip\Afficher\Minipage\Admin();
	return $minipage->installFinPage();
}


/**
 * Retourne une page HTML contenant, dans une présentation minimale,
 * le contenu transmis dans `$titre` et `$corps`.
 *
 * Appelée pour afficher un message d’erreur (l’utilisateur n’a pas
 * accès à cette page par exemple).
 *
 * Lorsqu’aucun argument n’est transmis, un header 403 est renvoyé,
 * ainsi qu’un message indiquant une interdiction d’accès.
 *
 * @deprecated 4.2 Utiliser `Spip\Afficher\Minipage\Admin`
 * @uses \Spip\Afficher\Minipage\Admin
 *
 * @example
 *  ```php
 *	use Spip\Afficher\Minipage\Admin as MinipageAdmin;
 *
 *  if (!autoriser('configurer')) {
 *		$minipage = new MinipageAdmin();
 *		echo $minipage->page();
 *      exit;
 *  }
 *  ```
 *
 * @param string $titre
 *   Titre de la page
 * @param string $corps
 *   Corps de la page
 * @param array $options
 *   string onload : Attribut onload de `<body>`
 *   bool all_inline : Inliner les css et js dans la page (limiter le nombre de hits)
 *   int status : status de la page
 * @return string
 *   HTML de la page
 */
function minipres($titre = '', $corps = '', $options = []) {
	trigger_deprecation('spip', '4.4', 'Using "%s" is deprecated, use "%s" instead', __FUNCTION__, 'Spip\Afficher\Minipage\Admin::page');
	$args = func_get_args();
	if (isset($args[2]) and is_string($args[2])) {
		$options = ['onload' => $args[2]];
	}
	if (isset($args[3])) {
		$options['all_inline'] = $args[3];
	}

	$options = array_merge([
		'onload' => '',
		'all_inline' => false,
	], $options);

	$options['titre'] = $titre;

	$minipage = new Spip\Afficher\Minipage\Admin();
	return $minipage->page($corps, $options);
}
