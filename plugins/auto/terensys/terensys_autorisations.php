<?php

/**
 * Définit les autorisations du plugin Terensys
 *
 * @plugin     Terensys
 * @copyright  2025
 * @author     Terensys
 * @licence    GNU/GPL
 * @package    SPIP\Terensys\Autorisations
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Fonction d'appel pour le pipeline
 * @pipeline autoriser */
function terensys_autoriser() {
}


// -----------------
// Objet articles_editoriaux


/**
 * Autorisation de voir un élément de menu (articleseditoriaux)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_articleseditoriaux_menu_dist($faire, $type, $id, $qui, $opt) {
	return true;
}


/**
* Autorisation de voir (articleseditoriaux)
*
* @param  string $faire Action demandée
* @param  string $type  Type d'objet sur lequel appliquer l'action
* @param  int    $id    Identifiant de l'objet
* @param  array  $qui   Description de l'auteur demandant l'autorisation
* @param  array  $opt   Options de cette autorisation
* @return bool          true s'il a le droit, false sinon
**/
function autoriser_articleseditoriaux_voir_dist($faire, $type, $id, $qui, $opt) {
	return true;
}

/**
* Autorisation de voir (articleseditorial)
*
* @param  string $faire Action demandée
* @param  string $type  Type d'objet sur lequel appliquer l'action
* @param  int    $id    Identifiant de l'objet
* @param  array  $qui   Description de l'auteur demandant l'autorisation
* @param  array  $opt   Options de cette autorisation
* @return bool          true s'il a le droit, false sinon
**/
function autoriser_articleseditorial_voir_dist($faire, $type, $id, $qui, $opt) {
	return true;
}

/**
 * Autorisation de créer (articleseditorial)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_articleseditorial_creer_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], ['0minirezo', '1comite']);
}

/**
 * Autorisation de modifier (articleseditorial)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_articleseditorial_modifier_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], ['0minirezo', '1comite']);
}

/**
 * Autorisation de supprimer (articleseditorial)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_articleseditorial_supprimer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}


// -----------------
// Objet clients


/**
 * Autorisation de voir un élément de menu (clients)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_clients_menu_dist($faire, $type, $id, $qui, $opt) {
	return true;
}


/**
 * Autorisation de voir le bouton d'accès rapide de création (client)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_clientcreer_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('creer', 'client', '', $qui, $opt);
}

/**
* Autorisation de voir (clients)
*
* @param  string $faire Action demandée
* @param  string $type  Type d'objet sur lequel appliquer l'action
* @param  int    $id    Identifiant de l'objet
* @param  array  $qui   Description de l'auteur demandant l'autorisation
* @param  array  $opt   Options de cette autorisation
* @return bool          true s'il a le droit, false sinon
**/
function autoriser_clients_voir_dist($faire, $type, $id, $qui, $opt) {
	return true;
}

/**
* Autorisation de voir (client)
*
* @param  string $faire Action demandée
* @param  string $type  Type d'objet sur lequel appliquer l'action
* @param  int    $id    Identifiant de l'objet
* @param  array  $qui   Description de l'auteur demandant l'autorisation
* @param  array  $opt   Options de cette autorisation
* @return bool          true s'il a le droit, false sinon
**/
function autoriser_client_voir_dist($faire, $type, $id, $qui, $opt) {
	return true;
}

/**
 * Autorisation de créer (client)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_client_creer_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], ['0minirezo', '1comite']);
}

/**
 * Autorisation de modifier (client)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_client_modifier_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], ['0minirezo', '1comite']);
}

/**
 * Autorisation de supprimer (client)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_client_supprimer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}



/**
 * Autorisation de lier/délier l'élément (clients)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_associerclients_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}// -----------------
// Objet personnes


/**
 * Autorisation de voir un élément de menu (personnes)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_personnes_menu_dist($faire, $type, $id, $qui, $opt) {
	return true;
}


/**
 * Autorisation de voir le bouton d'accès rapide de création (personne)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_personnecreer_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('creer', 'personne', '', $qui, $opt);
}

/**
* Autorisation de voir (personnes)
*
* @param  string $faire Action demandée
* @param  string $type  Type d'objet sur lequel appliquer l'action
* @param  int    $id    Identifiant de l'objet
* @param  array  $qui   Description de l'auteur demandant l'autorisation
* @param  array  $opt   Options de cette autorisation
* @return bool          true s'il a le droit, false sinon
**/
function autoriser_personnes_voir_dist($faire, $type, $id, $qui, $opt) {
	return true;
}

/**
* Autorisation de voir (personne)
*
* @param  string $faire Action demandée
* @param  string $type  Type d'objet sur lequel appliquer l'action
* @param  int    $id    Identifiant de l'objet
* @param  array  $qui   Description de l'auteur demandant l'autorisation
* @param  array  $opt   Options de cette autorisation
* @return bool          true s'il a le droit, false sinon
**/
function autoriser_personne_voir_dist($faire, $type, $id, $qui, $opt) {
	return true;
}

/**
 * Autorisation de créer (personne)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_personne_creer_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], ['0minirezo', '1comite']);
}

/**
 * Autorisation de modifier (personne)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_personne_modifier_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], ['0minirezo', '1comite']);
}

/**
 * Autorisation de supprimer (personne)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_personne_supprimer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}


// -----------------
// Objet partenaires


/**
 * Autorisation de voir un élément de menu (partenaires)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_partenaires_menu_dist($faire, $type, $id, $qui, $opt) {
	return true;
}


/**
 * Autorisation de voir le bouton d'accès rapide de création (partenaire)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_partenairecreer_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('creer', 'partenaire', '', $qui, $opt);
}

/**
* Autorisation de voir (partenaires)
*
* @param  string $faire Action demandée
* @param  string $type  Type d'objet sur lequel appliquer l'action
* @param  int    $id    Identifiant de l'objet
* @param  array  $qui   Description de l'auteur demandant l'autorisation
* @param  array  $opt   Options de cette autorisation
* @return bool          true s'il a le droit, false sinon
**/
function autoriser_partenaires_voir_dist($faire, $type, $id, $qui, $opt) {
	return true;
}

/**
* Autorisation de voir (partenaire)
*
* @param  string $faire Action demandée
* @param  string $type  Type d'objet sur lequel appliquer l'action
* @param  int    $id    Identifiant de l'objet
* @param  array  $qui   Description de l'auteur demandant l'autorisation
* @param  array  $opt   Options de cette autorisation
* @return bool          true s'il a le droit, false sinon
**/
function autoriser_partenaire_voir_dist($faire, $type, $id, $qui, $opt) {
	return true;
}

/**
 * Autorisation de créer (partenaire)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_partenaire_creer_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], ['0minirezo', '1comite']);
}

/**
 * Autorisation de modifier (partenaire)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_partenaire_modifier_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], ['0minirezo', '1comite']);
}

/**
 * Autorisation de supprimer (partenaire)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_partenaire_supprimer_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}



/**
 * Autorisation de lier/délier l'élément (partenaires)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_associerpartenaires_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}

function autoriser_terensysdoc_menu_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo' and !$qui['restreint'];
}