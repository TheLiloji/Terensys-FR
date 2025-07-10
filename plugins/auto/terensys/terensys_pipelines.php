<?php

include_spip('terensys_fonctions');

/**
 * Utilisations de pipelines par Terensys
 *
 * @plugin     Terensys
 * @copyright  2025
 * @author     Terensys
 * @licence    GNU/GPL
 * @package    SPIP\Terensys\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}



/**
 * Ajout de contenu sur certaines pages,
 * notamment des formulaires de liaisons entre objets
 *
 * @pipeline affiche_milieu
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function terensys_affiche_milieu($flux) {
	$texte = '';
	$e = trouver_objet_exec($flux['args']['exec']);


	if (
		$e
		and !$e['edition']
		and in_array($e['type'], ['articles_editorial'])
	) {
		$id_article = $flux['args']['id_articles_editorial'];
		$table = $e['table_objet_sql'];
		$id_table = $e['id_table_objet'];
		$type = sql_getfetsel('type', $table, $id_table.'='.$id_article);

		$types = ['texte_image','texte','client'];


		switch($type) {
			case 'partenaire':
				$texte .= afficher_champs($flux, $e, 'partenaire');
				break;
			case 'map':
				$texte .= afficher_champs($flux, $e, 'client');
				break;
			case 'carrousel':
				$texte .= afficher_champs($flux, $e, 'client');
				break;
			default:
				break;
		}		
	}
	
	if ($texte) {
		if ($p = strpos($flux['data'], '<!--affiche_milieu-->')) {
			$flux['data'] = substr_replace($flux['data'], $texte, $p, 0);
		} else {
			$flux['data'] .= $texte;
		}
	}

	return $flux;
}


function afficher_champs($flux, $e, $champ) {
	return recuperer_fond('prive/objets/editer/liens', [
		'table_source' => $champ,
		'objet' => $e['type'],
		'id_objet' => $flux['args'][$e['id_table_objet']]
	]);
}

/**
 * Optimiser la base de données
 *
 * Supprime les liens orphelins de l'objet vers quelqu'un et de quelqu'un vers l'objet.
 * Supprime les objets à la poubelle.
 *
 * @pipeline optimiser_base_disparus
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function terensys_optimiser_base_disparus($flux) {

	include_spip('action/editer_liens');
	$flux['data'] += objet_optimiser_liens(['client' => '*', 'partenaire' => '*'], '*');

	sql_delete('spip_articles_editoriaux', "statut='poubelle' AND maj < " . sql_quote($flux['args']['date']));

	return $flux;
}


/**
 * Calculer et Inserer le JS qui gére le tri par Drag&Drop dans le bon contexte (la page ?exec=xxxxx)
 *
 * @param array $flux Données du pipeline
 * @return    array        Données du pipeline
 */
function terensys_recuperer_fond($flux) {
	include_spip('inc/rang');
	$contexte_exec = terensys_get_contextes();
	$tables_objets_selectionnes = terensys_liste_objets();

	if (in_array(_request('exec'), terensys_get_contextes())
		and !empty($tables_objets_selectionnes)
	) {

		// Gestion du contexte : dans quelle page insérer le JS ?
		if (in_array($flux['args']['fond'], terensys_get_sources())
			&& strpos($flux['data']['texte'], 'data-objet=')
		) {
			// recuperer le nom de l'objet
			preg_match('/data-objet=["\'](\w+)["\']/', $flux['data']['texte'], $result);
			$objet_nom = $result[1];
			$objet_type = objet_type($objet_nom);

			// insérer le script de tri si on a bien un objet à ranger
			if ($objet_type) {
				// suffixe de la pagination : particularité des objets historiques
				switch ($objet_type) {
					case 'article':
						$suffixe_pagination = 'art';
						break;
					case 'site':
						$suffixe_pagination = 'sites';
						break;
					case 'breve':
						$suffixe_pagination = 'bre';
						break;
					default:
						$suffixe_pagination = $objet_type;
						break;
				}

				// Calcul du JS à insérer avec les paramètres
				$ajout_script = recuperer_fond('prive/squelettes/inclure/rang', [
					'suffixe_pagination' => $suffixe_pagination,
				]);

				// et hop, on insère le JS calculé
				$flux['data']['texte'] = str_replace('</table>', '</table>' . $ajout_script, $flux['data']['texte']);
			}
		}
	}

	return $flux;
}

