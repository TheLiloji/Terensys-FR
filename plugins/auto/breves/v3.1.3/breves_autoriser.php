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
function breves_autoriser() {
}

/**
 * Autorisation de voir la page breves
 *
 * Toujours OK, si les brèves sont activées
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 **/
function autoriser_breves_voir_dist($faire, $type, $id, $qui, $opt) {
	return ($GLOBALS['meta']['activer_breves'] != 'non');
}

/**
 * Autoriser les brèves dans le menu de navigation
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_breves_menu_dist($faire, $type = '', $id = 0, $qui = null, $opt = null) {
	return autoriser('voir', '_breves');
}

/**
 * Autoriser la création de brèves dans le menu de navigation
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_brevecreer_menu_dist($faire, $type, $id, $qui, $opt) {
	return ($GLOBALS['meta']['activer_breves'] != 'non')
	and verifier_table_non_vide();
}

/**
 * Autorisation de créer une brève
 *
 * Il faut que les brèves soient activées qu'une rubrique existe
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 **/
function autoriser_breve_creer_dist($faire, $type, $id, $qui, $opt) {
	return
		($GLOBALS['meta']['activer_breves'] != 'non')
		and (sql_countsel('spip_rubriques') > 0);
}

/**
 * Autoriser à créer une brève dans la rubrique $id
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_rubrique_creerbrevedans_dist($faire, $type, $id, $qui, $opt) {
	$r = sql_fetsel('id_parent', 'spip_rubriques', 'id_rubrique=' . intval($id));

	return
		$id
		and ($r['id_parent'] == 0)
		and ($GLOBALS['meta']['activer_breves'] != 'non')
		and autoriser('voir', 'rubrique', $id);
}


/**
 * Autoriser à modifier la brève $id
 *
 * - admins & redac si la brève n'est pas publiée
 * - admins de rubrique parente si publiée
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_breve_modifier_dist($faire, $type, $id, $qui, $opt) {
	$r = sql_fetsel('id_rubrique,statut', 'spip_breves', 'id_breve=' . intval($id));

	return
		$r and (
		($r['statut'] == 'publie' or (isset($opt['statut']) and $opt['statut'] == 'publie'))
			? autoriser('publierdans', 'rubrique', $r['id_rubrique'], $qui, $opt)
			: in_array($qui['statut'], ['0minirezo', '1comite'])
		);
}
