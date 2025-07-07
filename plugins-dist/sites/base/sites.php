<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Interfaces des tables syndic et syndic article
 *
 * @param array $interfaces
 * @return array
 */
function sites_declarer_tables_interfaces($interfaces) {

	$interfaces['table_des_tables']['sites'] = 'syndic'; // compat pour les boucles (SITES)
	$interfaces['table_des_tables']['syndication'] = 'syndic';
	$interfaces['table_des_tables']['syndic'] = 'syndic';
	$interfaces['table_des_tables']['syndic_articles'] = 'syndic_articles';

	# ne sert plus ? verifier balise_URL_ARTICLE
	$interfaces['exceptions_des_tables']['syndic_articles']['url_article'] = 'url';
	# ne sert plus ? verifier balise_LESAUTEURS
	$interfaces['exceptions_des_tables']['syndic_articles']['lesauteurs'] = 'lesauteurs';
	$interfaces['exceptions_des_tables']['syndic_articles']['url_site'] = ['syndic', 'url_site'];
	$interfaces['exceptions_des_tables']['syndic_articles']['nom_site'] = ['syndic', 'nom_site'];

	$interfaces['table_date']['syndication'] = 'date';

	$interfaces['tables_jointures']['spip_syndic_articles'][] = 'syndic';

	$interfaces['table_des_traitements']['NOM_SITE'][] = _TRAITEMENT_TYPO;

	// Articles syndiques : passage des donnees telles quelles, sans traitement typo
	// la securite et conformite XHTML de ces champs est assuree par safehtml()
	foreach (['DESCRIPTIF', 'SOURCE', 'URL', 'URL_SOURCE', 'LESAUTEURS', 'TAGS'] as $balise) {
		if (!isset($interfaces['table_des_traitements'][$balise]['syndic_articles'])) {
			$interfaces['table_des_traitements'][$balise]['syndic_articles'] = 'safehtml(%s)';
		} else {
			if (strpos($interfaces['table_des_traitements'][$balise]['syndic_articles'], 'safehtml') == false) {
				$interfaces['table_des_traitements'][$balise]['syndic_articles'] = 'safehtml(' . $interfaces['table_des_traitements'][$balise]['syndic_articles'] . ')';
			}
		}
	}

	return $interfaces;
}


function sites_declarer_tables_objets_sql($tables) {
	$tables['spip_syndic'] = [
		'table_objet_surnoms' => ['site'],
		'type' => 'site',
		'type_surnoms' => ['syndic'],
		'texte_retour' => 'icone_retour',
		'texte_objets' => 'icone_sites_references',
		'texte_objet' => 'sites:icone_site_reference',
		'texte_modifier' => 'sites:icone_modifier_site',
		'texte_creer' => 'sites:icone_referencer_nouveau_site',
		'info_aucun_objet' => 'sites:info_aucun_site',
		'info_1_objet' => 'sites:info_1_site',
		'info_nb_objets' => 'sites:info_nb_sites',
		'titre' => "nom_site AS titre, '' AS lang",
		'date' => 'date',
		'principale' => 'oui',
		'field' => [
			'id_syndic' => 'bigint(21) NOT NULL',
			'id_rubrique' => "bigint(21) DEFAULT '0' NOT NULL",
			'id_secteur' => "bigint(21) DEFAULT '0' NOT NULL",
			'nom_site' => "text DEFAULT '' NOT NULL",
			'url_site' => "text DEFAULT '' NOT NULL",
			'url_syndic' => "text DEFAULT '' NOT NULL",
			'descriptif' => "text DEFAULT '' NOT NULL",
			'maj' => 'TIMESTAMP',
			'syndication' => "VARCHAR(3) DEFAULT '' NOT NULL",
			'statut' => "varchar(10) DEFAULT '0' NOT NULL",
			'date' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'date_syndic' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'date_index' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'moderation' => "VARCHAR(3) DEFAULT 'non'",
			'miroir' => "VARCHAR(3) DEFAULT 'non'",
			'oubli' => "VARCHAR(3) DEFAULT 'non'",
			'resume' => "VARCHAR(3) DEFAULT 'oui'"
		],
		'key' => [
			'PRIMARY KEY' => 'id_syndic',
			'KEY id_rubrique' => 'id_rubrique',
			'KEY id_secteur' => 'id_secteur',
			'KEY statut' => 'statut, date_syndic',
		],
		'parent' => ['type' => 'rubrique', 'champ' => 'id_rubrique'],
		'join' => [
			'id_syndic' => 'id_syndic',
			'id_rubrique' => 'id_rubrique'
		],
		'statut' => [
			['champ' => 'statut', 'publie' => 'publie', 'previsu' => 'publie,prop', 'exception' => 'statut']
		],
		'texte_changer_statut' => 'sites:info_statut_site_1',
		'statut_textes_instituer' => [
			'prop' => 'texte_statut_propose_evaluation',
			'publie' => 'texte_statut_publie',
			'refuse' => 'texte_statut_poubelle',
		],

		'rechercher_champs' => [
			'nom_site' => 5,
			'url_site' => 1,
			'descriptif' => 3
		],
		'champs_editables' => [
			'nom_site',
			'url_site',
			'descriptif',
			'url_syndic',
			'syndication',
			'moderation',
			'miroir',
			'oubli',
			'resume'
		],
		'champs_versionnes' => [
			'id_rubrique',
			'id_secteur',
			'nom_site',
			'url_site',
			'url_syndic',
			'descriptif'
		],
	];

	$tables['spip_syndic_articles'] = [
		'table_objet_surnoms' => ['syndic_article'],

		'texte_retour' => 'icone_retour',
		'texte_objets' => 'sites:icone_articles_syndic',
		'texte_objet' => 'sites:icone_article_syndic',
		'texte_modifier' => 'icone_modifier_article', # inutile en vrai
		'info_aucun_objet' => 'sites:info_aucun_article_syndique',
		'info_1_objet' => 'sites:info_1_article_syndique',
		'info_nb_objets' => 'sites:info_nb_articles_syndiques',
		'icone_objet' => 'site',

		// pas de page propre ni dans ecrire ni dans le site public
		'url_voir' => '',
		'url_edit' => '',
		'page' => '',

		'date' => 'date',
		'editable' => 'non',
		'principale' => 'oui',
		'field' => [
			'id_syndic_article' => 'bigint(21) NOT NULL',
			'id_syndic' => "bigint(21) DEFAULT '0' NOT NULL",
			'titre' => "text DEFAULT '' NOT NULL",
			'url' => "text DEFAULT '' NOT NULL",
			'date' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'lesauteurs' => "text DEFAULT '' NOT NULL",
			'maj' => 'TIMESTAMP',
			'statut' => "varchar(10) DEFAULT '0' NOT NULL",
			'descriptif' => "text DEFAULT '' NOT NULL",
			'lang' => "VARCHAR(10) DEFAULT '' NOT NULL",
			'url_source' => "TINYTEXT DEFAULT '' NOT NULL",
			'source' => "TINYTEXT DEFAULT '' NOT NULL",
			'tags' => "TEXT DEFAULT '' NOT NULL",
			'raw_data' => "TEXT DEFAULT '' NOT NULL",
			'raw_format' => "TINYTEXT DEFAULT '' NOT NULL",
			'raw_methode' => "TINYTEXT DEFAULT '' NOT NULL",
		],
		'key' => [
			'PRIMARY KEY' => 'id_syndic_article',
			'KEY id_syndic' => 'id_syndic',
			'KEY statut' => 'statut',
			'KEY url' => 'url(255)'
		],
		'join' => [
			'id_syndic_article' => 'id_syndic_article',
			'id_syndic' => 'id_syndic'
		],
		'statut' => [
			['champ' => 'statut', 'publie' => 'publie', 'previsu' => 'publie,prop', 'exception' => 'statut'],
			[
				'champ' => [['spip_syndic', 'id_syndic'], 'statut'],
				'publie' => 'publie',
				'previsu' => 'publie,prop',
				'exception' => 'statut'
			],
		],
		'statut_images' => [
			'puce-rouge-anim-xx.svg',
			'publie' => 'puce-publier-8.png',
			'refuse' => 'puce-supprimer-8.png',
			'dispo' => 'puce-proposer-8.png',
			'off' => 'puce-refuser-8.png',
		],
		'rechercher_champs' => [
			'titre' => 5,
			'descriptif' => 1
		]
	];

	return $tables;
}
