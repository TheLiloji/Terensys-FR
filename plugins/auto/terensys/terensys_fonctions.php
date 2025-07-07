<?php

/**
 * Fonctions utiles au plugin Terensys
 *
 * @plugin     Terensys
 * @copyright  2025
 * @author     Terensys
 * @licence    GNU/GPL
 * @package    SPIP\Terensys\Fonctions
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Construction, a partir des objets selectionnes, des chemins de sources vers les listes correspondantes
 * Ce tableau sera ensuite comparé à la valeur $flux['data']['source'] fourni par le pipeline recuperer_fond()
 *
 * @return array
 *     les chemins sources vers les listes où activer Rang
 **/
function terensys_get_sources() {
	// mettre en cache le tableau calculé
	static $sources;
	if (is_array($sources)) {
		return $sources;
	}

	$sources = [];
	$objets = terensys_liste_objets();

	foreach ($objets as $objet) {
		if (!empty($objet)) {
			$source = 'prive/objets/liste/' . $objet;
			$sources[] = $source;
		}

		// cas objets historiques
		if ($objet == 'mots') {
			$source = 'prive/objets/liste/mots-admin';
			$sources[] = $source;
		}
	}

	$sources[] = 'prive/objets/contenu/rubrique-enfants';

	return $sources;
}

/**
 * Retourne la listes des objets (nom au pluriel) cochés dans la configuration.
 *
 * @return array
 */
function terensys_liste_objets() {
	include_spip('inc/config');
	$objets = [];

	if ($tables = lire_config('rang/objets')) {
		foreach ($tables as $table) {
			$objets[] = table_objet($table);
		}
	}
	$objets[] = 'rubriques';

	return $objets;
}


/**
 * Retourne la liste des pages (exec) sur lesquelles activer Rang.
 *    - Prendre la liste des objets cochés dans la configuration en considérant que le nom de l'objet et de l'exec sont identiques ;
 *    - Ajouter le nom de l'objet parent si il existe ;
 *    - Ajouter les cas particuliers historiques ;
 *    - Enfin le pipeline rang_declarer_contexte permet d'ajouter un exec spécifique (une page de config, etc.).
 *
 * @return array
 *    la liste des contextes
 */
function terensys_get_contextes() {
	static $contextes;
	if (is_array($contextes)) {
		return $contextes;
	}

	$contextes = [];

	$objets = terensys_liste_objets();

	foreach ($objets as $objet) {
		// le nom de l'objet au pluriel
		$contextes[] = $objet;

		// si l’objet a un parent declare, on ajoute le nom de cet objet
		include_spip('base/objets');
		if ($info_parent = objet_type_decrire_infos_parents(objet_type($objet))) {
			foreach ($info_parent as $info) {
				if (isset($info['type']) && $info['type']) {
					$contextes[] = $info['type'];
				}
			}
		}

		// parce que les mots ne font rien comme les autres
		if ($objet == 'mots') {
			$contextes[] = 'groupe_mots';
		}
	}

	// vérifier si des plugins déclarent des contextes spécifiques
	$contextes = pipeline('rang_declarer_contexte', $contextes);

	return $contextes;
}