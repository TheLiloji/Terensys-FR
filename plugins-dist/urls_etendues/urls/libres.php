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
Ce jeu d'URLs est une variation de urls/propres mais les urls
de differents types ne sont PAS distinguees par des marqueurs (_,-,+, etc.) ;
*/

# donner un exemple d'url pour le formulaire de choix
defined('URLS_LIBRES_EXEMPLE') || define('URLS_LIBRES_EXEMPLE', 'Titre-de-l-article Rubrique');
# specifier le form de config utilise pour ces urls
defined('URLS_LIBRES_CONFIG') || define('URLS_LIBRES_CONFIG', 'propres');

if (!defined('_MARQUEUR_URL')) {
	define('_MARQUEUR_URL', false);
}


/**
 * Generer l'url d'un objet SPIP
 * @param int $id
 * @param string $objet
 * @param string $args
 * @param string $ancre
 * @return string
 */
function urls_libres_generer_url_objet_dist(int $id, string $objet, string $args = '', string $ancre = ''): string {
	$generer = charger_fonction_url('objet', 'propres');
	return $generer($id, $objet, $args, $ancre);
}


/**
 * Decoder une url libres
 * retrouve le fond et les parametres d'une URL abregee
 * le contexte deja existant est fourni dans args sous forme de tableau
 *
 * @param string $url
 * @param string $entite
 * @param array $contexte
 * @return array([contexte],[type],[url_redirect],[fond]) : url decodee
 */
function urls_libres_decoder_url_dist(string $url, string $entite, array $contexte = []): array {
	$decoder = charger_fonction_url('decoder', 'propres');

	return $decoder($url, $entite, $contexte);
}
