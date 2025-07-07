<?php

/**
 * Gestion des recherches de plugins par version ou branche
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Recherche
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Construit le WHERE d'une requête SQL de selection des plugins ou paquets
 * compatibles avec une version, une branche de spip ou une liste de branches.
 *
 * Cette fonction est appelée par le critère {compatible_spip}
 *
 * @used-by critere_compatible_spip_dist()
 *
 * @param string $version
 *     Numéro de version de SPIP, tel que 2.0.8 ou de branche tel que 3.1 ou une liste de branche '3.1,3.2'
 * @param string $table
 *     Table d'application ou son alias SQL
 * @param string $op
 *     Opérateur de comparaison, tel que '>' ou '='
 * @return string
 *     Expression where de la requête SQL
 */
function inc_where_compatible_spip($version, $table, $op) {

	// En cas d'erreur sur le critère on annule l'effet du critère.
	$where = '1=1';

	// On verifie en premier si on a affaire à une liste de branches ou à une version/branche unique.
	if (strpos($version, ',') === false) {
		// Le critère concerne une version ou une branche unique.
		if (count(explode('.', $version)) == 3) {
			// le critere s'applique a une version x.y.z (1.9.2, 2.0.8, ...)
			$min = 'SUBSTRING_INDEX(' . $table . '.compatibilite_spip, \';\', 1)';
			$max = 'SUBSTRING_INDEX(' . $table . '.compatibilite_spip, \';\', -1)';

			$where = 'CASE WHEN ' . $min . ' = \'\'
				OR ' . $min . ' = \'[\'
				THEN \'1.9.0\' <= \'' . $version . '\'
				ELSE TRIM(LEADING \'[\' FROM ' . $min . ') <= \'' . $version . '\'
				END
				AND
				CASE WHEN ' . $max . ' = \'\'
				OR ' . $max . ' = \']\'
				THEN \'999.999.999\' >= \'' . $version . '\'
				WHEN ' . $max . ' = \')\'
				OR ' . $max . ' = \'[\'
				THEN \'999.999.999\' > \'' . $version . '\'
				WHEN RIGHT(' . $max . ', 1) = \')\'
				OR RIGHT(' . $max . ', 1) = \'[\'
				THEN LEFT(' . $max . ', LENGTH(' . $max . ') - 1) > \'' . $version . '\'
				ELSE LEFT(' . $max . ', LENGTH(' . $max . ') - 1) >= \'' . $version . '\'
				END';
		} elseif (count(explode('.', $version)) == 2) {
			// le critere s'applique a une branche x.y unique (par exemple 1.9)
			$where = 'LOCATE(\'' . $version . '\', ' . $table . '.branches_spip) ' . $op . ' 0';
		}
	} else {
		// Le critère concerne forcément une liste de branches (la liste n'est pas disponible pour une version).
		$branches = explode(',', $version);
		$where_liste = '';
		foreach ($branches as $_branche) {
			// On vérifie qu'on est bien en présence d'une branche.
			if (count(explode('.', $_branche)) == 2) {
				$where_liste .= ($where_liste ? ' AND ' : '') . 'LOCATE(\'' . $_branche . '\', ' . $table . '.branches_spip) ' . $op . ' 0';
			}
		}

		// Si non vide, on renvoie le where calculé.
		if ($where_liste) {
			$where = $where_liste;
		}
	}

	return $where;
}
