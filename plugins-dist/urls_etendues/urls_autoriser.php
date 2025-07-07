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
 * Définit les autorisations du plugin Urls Etendues
 *
 * @package SPIP\UrlsEtendues\Autorisations
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction du pipeline autoriser. N'a rien à faire
 *
 * @pipeline autoriser
 */
function urls_autoriser() {
}

/**
 * Autorisation de voir la page controler_urls
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_url_administrer_dist($faire, $type = '', $id = 0, $qui = null, $opt = null) {
	return (
		isset($GLOBALS['meta']['urls_activer_controle'])
		and $GLOBALS['meta']['urls_activer_controle'] == 'oui'
		and $qui['statut'] == '0minirezo'
		and !$qui['restreint']);
}

/**
 * Autorisation de voir le menu de gestion des urls
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_controlerurls_menu_dist($faire, $type = '', $id = 0, $qui = null, $opt = null) {
	return autoriser('administrer', 'url');
}

/**
 * Autorisation de ???
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_configurerurls_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('configurer', '_urls', $id, $qui, $opt);
}

/**
 * Autorisation de modifier l'url d'un objet
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_modifierurl_dist($faire, $type = '', $id = 0, $qui = null, $opt = null) {
	if (autoriser('modifier', $type, $id, $qui, $opt)) {
		return true;
	}

	// si pas le droit de 'modifier', regarder d'un peu plus pres pourquoi
	if (!$type or !intval($id)) {
		return false;
	}
	// verifier si l'objet existe encore en base
	$table_sql = table_objet_sql($type);
	$primary = id_table_objet($type);
	if (!sql_countsel($table_sql, "$primary=" . intval($id))) {
		return autoriser('administrer', 'url');
	}

	return false;
}
