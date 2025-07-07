<?php

/**
 * Ce fichier contient l'API de gestion de l'objet plugin.
 *
 * @package SPIP\SVP\PLUGIN\API
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retourne la description complète ou une liste de champs précisés dans l'appel
 * d'un objet plugin identifié par son préfixe.
 *
 * @param int|string   $plugin       La valeur du préfixe  ou de l'id du plugin.
 * @param array|string $informations Identifiant d'un champ ou de plusieurs champs de la description d'un plugin.
 *                                   Si l'argument est vide, la fonction renvoie la description complète.
 *
 * @return mixed La description brute complète ou partielle du plugin :
 *               - sous la forme d'une valeur simple si l'information demandée est unique (chaine)
 *               - sous la forme d'un tabelau associatif indexé par le nom du champ sinon.
 */
function plugin_lire($plugin, $informations = []) {

	// Initialisation des tableaux statiques
	static $descriptions_plugin = [];
	static $prefixes = [];

	// On détermine si on a passé l'id ou le préfixe du plugin :
	// -- si c'est le préfixe on le passe en majuscules pour être cohérent avec le stockage en base.
	// -- on essaye de déterminer l'index de la description qui est toujours le préfixe.
	$id_plugin = intval($plugin);
	if ($id_plugin) {
		$index = $prefixes[$id_plugin] ?? '';
	} else {
		$prefixe = strtoupper($plugin);
		$index = $prefixe;
	}

	if (!$index or !isset($descriptions_plugin[$index])) {
		// Initialisation de la jointure entre plugins et dépôts.
		$from = ['spip_plugins', 'spip_depots_plugins'];
		$group_by = ['spip_plugins.id_plugin'];

		// Préfixe, jointure et conditions sur la table des dépots pour éviter les plugins installés.
		$where = [
			'spip_depots_plugins.id_depot>0',
			'spip_depots_plugins.id_plugin=spip_plugins.id_plugin'
		];
		// Condition sur le préfixe ou l'id.
		$where[] = $id_plugin
			? 'spip_plugins.id_plugin=' . $id_plugin
			: 'spip_plugins.prefixe=' . sql_quote($prefixe);

		// Acquisition de tous les champs du plugin et sauvegarde de celle-ci à l'index du préfixe.
		if ($description = sql_fetsel('*', $from, $where, $group_by)) {
			if (!$index) {
				// Première sauvegarde du plugin connu par son id, il ne faut stocker l'index pour un futur appel.
				$index = $description['prefixe'];
				$prefixes[$id_plugin] = $index;
			}
			$descriptions_plugin[$index] = $description;
		} elseif ($index) {
			$descriptions_plugin[$index] = [];
		}
	}

	// Si il y a eu une erreur lors du fetsel et que l'index est indéterminé (première lecture par id)
	// on renvoie une description vide.
	$description = $index ? $descriptions_plugin[$index] : [];

	// On ne retourne que les champs demandés
	if ($description and $informations) {
		// Extraction des seules informations demandées.
		// -- si on demande une information unique on renvoie la valeur simple, sinon on renvoie un tableau.
		// -- si une information n'est pas un champ valide elle n'est pas renvoyée sans monter d'erreur.
		if (is_array($informations)) {
			if (count($informations) == 1) {
				// Tableau d'une seule information : on revient à une chaine unique.
				$informations = array_shift($informations);
			} else {
				// Tableau des informations valides
				$description = array_intersect_key($description, array_flip($informations));
			}
		}

		if (is_string($informations)) {
			// Valeur unique demandée.
			$description = $description[$informations] ?? '';
		}
	}

	return $description;
}
