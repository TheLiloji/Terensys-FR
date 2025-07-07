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
 * @plugin Revisions pour SPIP
 * @license GPL
 * @package SPIP\Revisions\Genie
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/abstract_sql');

/**
 * Tâche Cron d'optimisation des révisions
 *
 * @param int $last
 *     Timestamp de la dernière exécution de cette tâche
 * @return int
 *     Positif : la tâche a été effectuée
 */
function genie_revisions_optimiser_revisions_dist($last) {

	optimiser_base_revisions();
	optimiser_tables_revision();
	anonymiser_base_revisions();

	return 1;
}

/**
 * Supprimer les révisions des objets disparus
 */
function optimiser_base_revisions() {
	/**
	 * On commence par récupérer la liste des types d'objet ayant au moins une révision
	 */
	$objets_revises = sql_allfetsel('objet', 'spip_versions', 'id_version=1', 'objet');
	$objets_revises = array_filter(array_column($objets_revises, 'objet'));

	/**
	 * Pour chaque objet, on va contruire un tableau des identifiants disparus
	 * On supprimera ensuite les occurences dans spip_versions et spip_versions_fragments
	 */
	foreach ($objets_revises as $objet) {
		$table = table_objet_sql($objet);
		if (sql_table_exists($table)) {
			$in = [];
			$id_table_objet = id_table_objet($objet);
			$res = sql_select(
				'A.id_objet AS id_objet, A.objet AS objet',
				"spip_versions AS A LEFT JOIN $table AS R
					ON R.$id_table_objet=A.id_objet AND A.objet=" . sql_quote($objet),
				"R.$id_table_objet IS NULL AND A.objet=" . sql_quote($objet) . ' AND A.id_objet > 0',
				'A.id_objet',
				'A.id_objet'
			);

			while ($row = sql_fetch($res)) {
				$in[$row['id_objet']] = true;
			}
			sql_free($res);

			/**
			 * Si on a un array
			 * On supprime toute occurence des objets disparus dans :
			 * -* spip_versions
			 * -* spip_versions_fragments
			 */
			if ($in) {
				foreach (['spip_versions', 'spip_versions_fragments'] as $table) {
					sql_delete($table, sql_in('id_objet', array_keys($in)) . ' AND objet=' . sql_quote($objet));
				}
			}
		}
	}
}

/**
 * Optimisation des tables spip_versions et spip_versions_fragments
 */
function optimiser_tables_revision() {
	foreach (['spip_versions', 'spip_versions_fragments'] as $table) {
		spip_log("debut d'optimisation de la table $table");
		if (sql_optimize($table)) {
			spip_log("fin d'optimisation de la table $table");
		} else {
			spip_log("Pas d'optimiseur necessaire pour $table");
		}
	}
}


/**
 * Dans l'historique des révisions, hash à intervalle régulier les ip
**/
function anonymiser_base_revisions() {
	if (defined('_CNIL_PERIODE') and _CNIL_PERIODE) {
		$critere_cnil = 'date<"' . date('Y-m-d', time() - _CNIL_PERIODE) . '"'
			. ' AND (id_auteur LIKE "%.%" OR id_auteur LIKE "%:%")'; # ipv4 ou ipv6

		$c = sql_countsel('spip_versions', $critere_cnil);
		if ($c > 0) {
			spip_log("CNIL: masquer IP de $c versions anciennes", 'revisions');
			sql_update('spip_versions', ['id_auteur' => 'MD5(id_auteur)'], $critere_cnil);
			return $c;
		}
	}
}
