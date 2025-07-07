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
} // securiser

/*
Ce jeu d'URLs est une variante de urls/propres, qui ajoute
le prefixe './?' aux adresses, ce qui permet de l'utiliser en
mode "Query-String", sans .htaccess ;

	<http://mon-site-spip/?-Rubrique->

Attention : le mode 'propres_qs' est moins fonctionnel que le mode 'propres' ou
'propres2'. Si vous pouvez utiliser le .htaccess, ces deux derniers modes sont
preferables au mode 'propres_qs'.
*/

# donner un exemple d'url pour le formulaire de choix
defined('URLS_PROPRES_QS_EXEMPLE') || define('URLS_PROPRES_QS_EXEMPLE', '?Titre-de-l-article');
# specifier le form de config utilise pour ces urls
defined('URLS_PROPRES_QS_CONFIG') || define('URLS_PROPRES_QS_CONFIG', 'propres');

if (!defined('_terminaison_urls_propres')) {
	define('_terminaison_urls_propres', '');
}

defined('_debut_urls_propres') || define('_debut_urls_propres', './?');

/**
 * Generer l'url d'un objet SPIP
 * @param int $id
 * @param string $objet
 * @param string $args
 * @param string $ancre
 * @return string
 */
function urls_propres_qs_generer_url_objet_dist(int $id, string $objet, string $args = '', string $ancre = ''): string {
	$generer = charger_fonction_url('objet', 'propres');
	return $generer($id, $objet, $args, $ancre);
}


/**
 * Decoder une url propres en query string
 * retrouve le fond et les parametres d'une URL abregee
 * le contexte deja existant est fourni dans args sous forme de tableau
 *
 * @param string $url
 * @param string $entite
 * @param array $contexte
 * @return array([contexte],[type],[url_redirect],[fond]) : url decodee
 */
function urls_propres_qs_decoder_url_dist(string $url, string $entite, array $contexte = []): array {
	$decoder = charger_fonction_url('decoder', 'propres');

	return $decoder($url, $entite, $contexte);
}
