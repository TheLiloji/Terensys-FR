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
 * Puces d'actions rapides sur les mots clés
 *
 * @package SPIP\Mots\PucesStatut
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Afficher la puce statut d'un mot :
 * en fait juste une icone indépendante du statut
 *
 * @param int $id
 *     Identifiant du mot clé
 * @param string $statut
 *     Statut du mot (il n'en a pas)
 * @param int $id_groupe
 *     Identifiant du groupe de mot clé parent
 * @param string $type
 *     Type d'objet
 * @param string $ajax
 *     Indique s'il ne faut renvoyer que le coeur du menu car on est
 *     dans une requete ajax suite à un post de changement rapide
 * @param bool $menu_rapide
 *     Indique si l'on peut changer le statut, ou si on l'affiche simplement
 * @return string
 *     Code HTML de l'image de puce de statut à insérer
 */
function puce_statut_mot_dist($id, $statut, $id_groupe, $type, $ajax = '', $menu_rapide = _ACTIVER_PUCE_RAPIDE) {
	return "<img src='" . chemin_image('mot-16.png') . "' width='16' height='16' alt=''  />";
}
