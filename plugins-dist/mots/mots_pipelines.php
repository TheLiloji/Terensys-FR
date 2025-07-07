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
 * Utilisations de pipelines
 *
 * @package SPIP\Mots\Pipelines
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Définir les meta de configuration liées aux mots
 *
 * @pipeline configurer_liste_metas
 * @param array $metas
 *     Couples nom de la méta => valeur par défaut
 * @return array
 *    Couples nom de la méta => valeur par défaut
 */
function mots_configurer_liste_metas($metas) {
	$metas['articles_mots'] = 'non';
	$metas['config_precise_groupes'] = 'non';

	#$metas['mots_cles_forums'] =  'non';
	return $metas;
}

/**
 * Utilisation du pipeline affiche milieu
 *
 * - Ajoute le formulaire de configuration des mots sur la configuration des contenus
 * - Ajoute le formulaire d'édition de mots sur les objets qui le peuvent
 *
 * @pipeline affiche_milieu
 *
 * @param array $flux
 *     Données du pipeline
 * @return array
 *     Données du pipeline
 */
function mots_affiche_milieu($flux) {
	if ($flux['args']['exec'] == 'configurer_contenu') {
		$flux['data'] .= recuperer_fond('prive/squelettes/inclure/configurer', ['configurer' => 'configurer_mots']);
	}

	// si on est sur une page ou il faut inserer les mots cles...
	if (
		$en_cours = trouver_objet_exec($flux['args']['exec'])
		and $en_cours['edition'] !== true // page visu
		and $type = $en_cours['type']
		and $id_table_objet = $en_cours['id_table_objet']
		and isset($flux['args'][$id_table_objet])
		and ($id = intval($flux['args'][$id_table_objet]))
	) {
		$texte = recuperer_fond(
			'prive/objets/editer/liens',
			[
				'table_source' => 'mots',
				'objet' => $type,
				'id_objet' => $id,
			]
		);
		if ($p = strpos($flux['data'], '<!--affiche_milieu-->')) {
			$flux['data'] = substr_replace($flux['data'], $texte, $p, 0);
		} else {
			$flux['data'] .= $texte;
		}
	}

	return $flux;
}

/**
 * Optimise la base de données en supprimant les liens orphelins
 *
 * @pipeline optimiser_base_disparus
 *
 * @param array $flux
 *     Données du pipeline
 * @return array
 *     Données du pipeline
 */
function mots_optimiser_base_disparus($flux) {
	$n = &$flux['data'];
	$mydate = $flux['args']['date'];

	$result = sql_delete('spip_mots', 'length(titre)=0 AND maj < ' . sql_quote($mydate));

	include_spip('action/editer_liens');
	// optimiser les liens morts :
	// entre mots vers des objets effaces
	// depuis des mots effaces
	$n += objet_optimiser_liens(['mot' => '*'], '*');

	return $flux;
}


/**
 * Copier le type des groupes sur la table spip_mots
 * à chaque changement d'un groupe.
 *
 * @pipeline post_edition
 *
 * @param array $flux
 *     Données du pipeline
 * @return array
 *     Données du pipeline
 */
function mots_post_edition($flux) {
	if (
		isset($flux['args']['table'])
		and ($flux['args']['table'] == 'spip_groupes_mots')
		and isset($flux['data']['titre'])
	) {
		sql_updateq('spip_mots', ['type' => $flux['data']['titre']], 'id_groupe=' . $flux['args']['id_objet']);
	}

	return $flux;
}
