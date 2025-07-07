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


/*
function terensys_declarer_champs_extras($champs = array()) {

	// Table : spip_articles
	if (!isset($champs['spip_articles']) || !is_array($champs['spip_articles'])) {
		$champs['spip_articles'] = array();
	}

	$champs['spip_articles']['type'] = array(
			'saisie' => 'selection',
			'options' => array(
				'nom' => 'type',
				'label' => 'Type',
				'explication' => 'Definis le type d\'article',
				'explication_apres' => 'Type \'normal\' pour un article uniquement composé de texte. Type \'Avec lien\' pour un article incluant une redirection',
				'datas' => array(
					'normal' => 'Normal',
					'link' => 'Avec lien',
				),
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_articles']['label'] = array(
			'saisie' => 'input',
			'options' => array(
				'nom' => 'label',
				'label' => 'Label',
				'placeholder' => 'label',
				'explication' => 'Le label du bouton de redirection',
				'type' => 'text',
				'autocomplete' => 'defaut',
				'afficher_si' => '@type@ == "link"',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_articles']['url'] = array(
			'saisie' => 'input',
			'options' => array(
				'nom' => 'url',
				'label' => 'URL',
				'placeholder' => 'url',
				'type' => 'text',
				'autocomplete' => 'defaut',
				'afficher_si' => '@type@ == "link"',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);


	// Table : spip_rubriques
	if (!isset($champs['spip_rubriques']) || !is_array($champs['spip_rubriques'])) {
		$champs['spip_rubriques'] = array();
	}

	$champs['spip_rubriques']['type'] = array(
			'saisie' => 'selection',
			'options' => array(
				'nom' => 'type',
				'label' => 'Type de rubrique',
				'explication' => 'Type de la rubrique (laisser vide pour une rubrique de base)',
				'datas' => array(
					'references' => 'Références',
					'equipe' => 'Équipe',
					'services' => 'Services',
					'recrutement' => 'Recrutement',
				),
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);


	// Table : spip_articles_editoriaux
	if (!isset($champs['spip_articles_editoriaux']) || !is_array($champs['spip_articles_editoriaux'])) {
		$champs['spip_articles_editoriaux'] = array();
	}

	$champs['spip_articles_editoriaux']['type'] = array(
			'saisie' => 'selection',
			'options' => array(
				'nom' => 'type',
				'label' => 'Type',
				'explication' => 'Choisir le type de l\'article editorial',
				'datas' => array(
					'texte_image' => 'Texte avec image',
					'texte_image_action' => 'Texte avec image et une action',
					'texte' => 'Texte',
					'map' => 'Map',
					'carrousel' => 'Carrousel',
					'4_blocs' => '4 Blocs',
					'partenaire' => 'Partenaire',
					'image_cartes' => 'Image avec cartes',
				),
				'defaut' => 'texte',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_articles_editoriaux']['sous_titre'] = array(
			'saisie' => 'input',
			'options' => array(
				'nom' => 'sous_titre',
				'label' => 'Sous Titre',
				'explication' => 'Titre de l\'encadré',
				'type' => 'text',
				'autocomplete' => 'defaut',
				'afficher_si' => '@type@ == "image_cartes"',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_articles_editoriaux']['texte'] = array(
			'saisie' => 'textarea',
			'options' => array(
				'nom' => 'texte',
				'label' => 'Texte',
				'rows' => '5',
				'afficher_si' => '@type@ == "texte" || @type@ == "texte_image" || @type@ == "texte_image_action" || @type@ == "image_cartes"',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_articles_editoriaux']['document'] = array(
			'saisie' => 'selecteur_document',
			'options' => array(
				'nom' => 'document',
				'label' => 'Document',
				'afficher_si' => '@type@ == "texte_image" || @type@ == "texte_image_action" || @type@ == "image_cartes"',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_articles_editoriaux']['redirection'] = array(
			'saisie' => 'input',
			'options' => array(
				'nom' => 'redirection',
				'label' => 'Redirection',
				'placeholder' => 'URL',
				'explication' => 'Ajoute une redirection sur un lien internet via un bouton',
				'type' => 'text',
				'autocomplete' => 'defaut',
				'afficher_si' => '@type@ == "texte_image_action" || @type@ == "image_cartes"',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_articles_editoriaux']['label'] = array(
			'saisie' => 'input',
			'options' => array(
				'nom' => 'label',
				'label' => 'Label',
				'placeholder' => 'Label',
				'explication' => 'Label attribué au bouton de redirection',
				'type' => 'text',
				'autocomplete' => 'defaut',
				'afficher_si' => '@type@ == "texte_image_action" || @type@ == "image_cartes"',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_articles_editoriaux']['articles'] = array(
			'saisie' => 'selecteur_article',
			'options' => array(
				'nom' => 'articles',
				'label' => 'Articles',
				'explication' => 'Sélectionnez exactement 4 articles',
				'multiple' => 'on',
				'afficher_si' => '@type@ == "4_blocs"',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);


	// Table : spip_clients
	if (!isset($champs['spip_clients']) || !is_array($champs['spip_clients'])) {
		$champs['spip_clients'] = array();
	}

	$champs['spip_clients']['logo'] = array(
			'saisie' => 'selecteur_document',
			'options' => array(
				'nom' => 'logo',
				'label' => 'Logo',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_clients']['url'] = array(
			'saisie' => 'input',
			'options' => array(
				'nom' => 'url',
				'label' => 'URL',
				'type' => 'text',
				'autocomplete' => 'defaut',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_clients']['article_associe'] = array(
			'saisie' => 'selecteur_article',
			'options' => array(
				'nom' => 'article_associe',
				'label' => 'Article associé',
				'explication' => 'Sélectionner l\'article associé à ce partenaire',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);


	// Table : spip_personnes
	if (!isset($champs['spip_personnes']) || !is_array($champs['spip_personnes'])) {
		$champs['spip_personnes'] = array();
	}

	$champs['spip_personnes']['image'] = array(
			'saisie' => 'selecteur_document',
			'options' => array(
				'nom' => 'image',
				'label' => 'Image',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);


	// Table : spip_partenaires
	if (!isset($champs['spip_partenaires']) || !is_array($champs['spip_partenaires'])) {
		$champs['spip_partenaires'] = array();
	}

	$champs['spip_partenaires']['logo'] = array(
			'saisie' => 'selecteur_document',
			'options' => array(
				'nom' => 'logo',
				'label' => 'Logo',
				'sql' => 'text DEFAULT \'\' NOT NULL',
			),
			'verifier' => array(
			),
		);

	$champs['spip_partenaires']['article_associe'] = array(
			'saisie' => 'selecteur_article',
			'options' => array(
				'nom' => 'article_associe',
				'label' => 'Article associé',
				'explication' => 'Sélectionner l\'article associé à ce partenaire',
				'sql' => 'text DEFAULT \'\' NOT NULL',
				'traitements' => '_TRAITEMENT_RACCOURCIS',
			),
			'verifier' => array(
			),
		);

	return $champs;
}
*/
