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
 * Déclarations d'autorisations
 *
 * @package SPIP\Breves\Autorisations
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction du pipeline autoriser. N'a rien à faire
 *
 * @pipeline autoriser
 */
function revisions_autoriser() {
}

/**
 * Autorisation de voir les revisions ?
 *
 * Il faut :
 * - des revisions définies pour cet objet
 * - que l'objet existe
 * - que l'on soit autorisé à voir l'objet
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_voirrevisions_dist($faire, $type, $id, $qui, $opt) {
	$table = table_objet_sql($type);
	$id_table_objet = id_table_objet($type);

	include_spip('inc/revisions');
	if (!liste_champs_versionnes($table)) {
		return false;
	}

	if (!$row = sql_fetsel('*', $table, "$id_table_objet=" . intval($id))) {
		return false;
	}

	return
		autoriser('voir', $type, $id, $qui, $opt);
}

/**
 * Autorisation de voir le menu révisions
 *
 * Il faut des révisions activées et présentes.
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_revisions_menu_dist($faire, $type = '', $id = 0, $qui = null, $opt = null) {
	// SI pas de revisions sur un objet quelconque.
	// ET pas de version... pas de bouton, c'est inutile...
	include_spip('inc/config');
	if (!lire_config('objets_versions/') and !sql_countsel('spip_versions')) {
		return false;
	}

	return true;
}

/**
 * Autorisation de voir le menu configurer_revisions
 *
 * Il faut avoir accès à la page configurer_revisions
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 **/
function autoriser_configurerrevisions_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('configurer', '_revisions', $id, $qui, $opt);
}
