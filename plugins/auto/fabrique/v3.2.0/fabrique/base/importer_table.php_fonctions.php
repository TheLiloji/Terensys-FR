<?php

/**
 * Options globales chargées à chaque hit
 *
 * @package SPIP\Fabrique\Templates
**/


/**
 * Extrait toutes les données d'une table
 * et essaie de leur faire prendre moins de place
 * en n'ecrivant qu'une seule fois leurs noms de colonne.
 *
 * @param string $table
 *     Nom de la table SQL a extraire
 * @param string $connect
 *     Connecteur de la base de données
 * @return array
 *     Tableau de deux elements : la liste des cles,
 *     et la liste dans un tableau de toutes les donnees.
**/
function fabrique_extraire_les_donnees_table($table, $connect) {
	$data = sql_allfetsel('*', $table, '', '', '', '', '', $connect);
	if (!$data) {
		return [
			'cles' => [],
			'valeurs' => []
		];
	}

	// extraire les cles
	$un = current($data);
	$cles = array_keys($un);

	// recalculer les valeurs
	$data = array_map('array_values', $data);

	return [
		'cles' => $cles,
		'valeurs' => $data
	];
}
