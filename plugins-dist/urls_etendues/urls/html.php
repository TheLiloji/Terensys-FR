<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/*

- Comment utiliser ce jeu d'URLs ?

Recopiez le fichier "htaccess.txt" du repertoire de base du site SPIP sous
le sous le nom ".htaccess" (attention a ne pas ecraser d'autres reglages
que vous pourriez avoir mis dans ce fichier) ; si votre site est en
"sous-repertoire", vous devrez aussi editer la ligne "RewriteBase" ce fichier.
Les URLs definies seront alors redirigees vers les fichiers de SPIP.

Dans les pages de configuration, choisissez 'html' comme type d'url

SPIP calculera alors ses liens sous la forme "article123.html".

Note : si le fichier htaccess.txt se revele trop "puissant", car trop
generique, et conduit a des problemes (en lien par exemple avec d'autres
applications installees dans votre repertoire, a cote de SPIP), vous
pouvez l'editer pour ne conserver que la partie concernant les URLS 'html'.

*/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
} // securiser

# donner un exemple d'url pour le formulaire de choix
defined('URLS_HTML_EXEMPLE') || define('URLS_HTML_EXEMPLE', 'article12.html');

/**
 * Generer l'url d'un objet SPIP
 * @param int $id
 * @param string $objet
 * @param string $args
 * @param string $ancre
 * @return string
 */
function urls_html_generer_url_objet_dist(int $id, string $objet, string $args = '', string $ancre = ''): string {

	if ($generer_url_externe = charger_fonction_url($objet, 'defaut')) {
		$url = $generer_url_externe($id, $args, $ancre);
		// une url === null indique "je ne traite pas cette url, appliquez le calcul standard"
		// une url vide est une url vide, ne rien faire de plus
		if (!is_null($url)) {
			return $url;
		}
	}

	return _DIR_RACINE . $objet . $id . '.html' . ($args ? "?$args" : '') . ($ancre ? "#$ancre" : '');
}


/**
 * Decoder une url html
 * retrouve le fond et les parametres d'une URL abregee
 * le contexte deja existant est fourni dans args sous forme de tableau
 *
 * @param string $url
 * @param string $entite
 * @param array $contexte
 * @return array
 *   [$contexte_decode, $type, $url_redirect, $fond]
 */
function urls_html_decoder_url_dist(string $url, string $entite, array $contexte = []): array {

	// traiter les injections du type domaine.org/spip.php/cestnimportequoi/ou/encore/plus/rubrique23
	if ($GLOBALS['profondeur_url'] > 0 and $entite == 'sommaire') {
		return [[], '404'];
	}

	// voir s'il faut recuperer le id_* implicite et les &debut_xx;
	include_spip('inc/urls');
	$r = nettoyer_url_page($url, $contexte);
	if ($r) {
		array_pop($r); // nettoyer_url_page renvoie un argument de plus inutile ici
		// il n'est pas necessaire de forcer le fond en 4eme arg car l'url n'est pas query string
		// sauf si pas de fond connu
		if ($entite) {
			array_pop($r);
		}

		return $r;
	}

	/*
	 * Le bloc qui suit sert a faciliter les transitions depuis
	 * le mode 'urls-propres' vers les modes 'urls-standard' et 'url-html'
	 * Il est inutile de le recopier si vous personnalisez vos URLs
	 * et votre .htaccess
	 */
	// Si on est revenu en mode html, mais c'est une ancienne url_propre
	// on ne redirige pas, on assume le nouveau contexte (si possible)
	return urls_transition_retrouver_anciennes_url_propres($url, $entite, $contexte);
	/* Fin du bloc compatibilite url-propres */
}
