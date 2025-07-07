<?php

/***************************************************************************
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) avec tendresse depuis 2001                               *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 ***************************************************************************/

/**
 * Déclarations d'autorisations
 *
 * @package SPIP\Dump\Autorisations
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction du pipeline autoriser. N'a rien à faire
 *
 * @pipeline autoriser
 */
function dump_autoriser() {
}

/**
 * Autorisation de voir le menu restaurer
 *
 * Il faut avoir le droit de sauvegarder
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 **/
function autoriser_restaurer_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('sauvegarder', $type, $id, $qui, $opt);
}
