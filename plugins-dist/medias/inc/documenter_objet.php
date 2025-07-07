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
 * Calcul du portfolio / liste de documents de l'espace privé
 *
 * @package SPIP\Medias\Fonctions
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retourne le code HTML du portfolio, liste des documents et formulaire d'ajout de documents
 *
 * @note
 *     Marque les documents vus dans les textes de l'objet avant le calcul !
 *
 * @uses inc_marquer_doublons_doc_dist()
 *
 * @param int $id Identifiant de l'objet
 * @param string $type Type d'objet
 * @return string code HTML
 **/
function inc_documenter_objet_dist($id, $type) {
	$serveur = '';

	// avant de documenter un objet, on verifie que ses documents vus sont bien lies !
	$spip_table_objet = table_objet_sql($type);
	$table_objet = table_objet($type);
	$id_table_objet = id_table_objet($type, $serveur);
	$champs = sql_fetsel('*', $spip_table_objet, addslashes($id_table_objet) . '=' . intval($id));

	$marquer_doublons_doc = charger_fonction('marquer_doublons_doc', 'inc');
	$marquer_doublons_doc($champs, $id, $type, $id_table_objet, $table_objet, $spip_table_objet, '', $serveur);

	$contexte = ['objet' => $type, 'id_objet' => $id, 'espace_prive' => test_espace_prive()];

	return recuperer_fond('prive/objets/contenu/portfolio_document', array_merge($_GET, $contexte));
}
