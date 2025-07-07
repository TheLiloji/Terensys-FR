<?php

/**
 * Déclarations relatives à la base de données
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
 * Déclaration des alias de tables et filtres automatiques de champs
 *
 * @pipeline declarer_tables_interfaces
 * @param array $interfaces
 *     Déclarations d'interface pour le compilateur
 * @return array
 *     Déclarations d'interface pour le compilateur
 */
function terensys_declarer_tables_interfaces($interfaces) {

	$interfaces['table_des_tables']['articles_editoriaux'] = 'articles_editoriaux';
	$interfaces['table_des_tables']['clients'] = 'clients';
	$interfaces['table_des_tables']['personnes'] = 'personnes';
	$interfaces['table_des_tables']['partenaires'] = 'partenaires';

	return $interfaces;
}


/**
 * Déclaration des objets éditoriaux
 *
 * @pipeline declarer_tables_objets_sql
 * @param array $tables
 *     Description des tables
 * @return array
 *     Description complétée des tables
 */
function terensys_declarer_tables_objets_sql($tables) {

	$tables['spip_articles_editoriaux'] = [
		'type' => 'articles_editorial',
		'principale' => 'oui',
		'table_objet_surnoms' => ['articleseditoriaux', 'articles_editorial'], // table_objet('articles_editorial') => 'articles_editoriaux' 
		'field' => [
			'id_articles_editorial' => 'bigint(21) NOT NULL',
			'titre'              => 'text NOT NULL DEFAULT ""',
			'date'               => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"',
			'statut'             => 'varchar(20)  DEFAULT "0" NOT NULL',
			'maj'                => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
		],
		'key' => [
			'PRIMARY KEY'        => 'id_articles_editorial',
			'KEY statut'         => 'statut',
		],
		'titre' => 'titre AS titre, "" AS lang',
		'date' => 'date',
		'champs_editables'  => ['titre'],
		'champs_versionnes' => ['titre'],
		'rechercher_champs' => [],
		'tables_jointures'  => [],
		'statut_textes_instituer' => [
			'prepa'    => 'texte_statut_en_cours_redaction',
			'prop'     => 'texte_statut_propose_evaluation',
			'publie'   => 'texte_statut_publie',
			'refuse'   => 'texte_statut_refuse',
			'poubelle' => 'texte_statut_poubelle',
		],
		'statut' => [
			[
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prop,prepa',
				'post_date' => 'date',
				'exception' => ['statut','tout']
			]
		],
		'texte_changer_statut' => 'articles_editorial:texte_changer_statut_articles_editorial',
	];

	$tables['spip_clients'] = [
		'type' => 'client',
		'principale' => 'oui',
		'field' => [
			'id_client'          => 'bigint(21) NOT NULL',
			'nom'                => 'text NOT NULL DEFAULT ""',
			'adresse'            => 'longtext NOT NULL DEFAULT ""',
			'maj'                => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
		],
		'key' => [
			'PRIMARY KEY'        => 'id_client',
		],
		'titre' => 'nom AS titre, "" AS lang',
		 #'date' => '',
		'champs_editables'  => ['nom', 'adresse'],
		'champs_versionnes' => ['nom', 'adresse'],
		'rechercher_champs' => [],
		'tables_jointures'  => ['spip_clients_liens'],
	];

	$tables['spip_personnes'] = [
		'type' => 'personne',
		'principale' => 'oui',
		'field' => [
			'id_personne'        => 'bigint(21) NOT NULL',
			'nom'                => 'text NOT NULL DEFAULT ""',
			'fonction'           => 'text NOT NULL DEFAULT ""',
			'texte'              => 'longtext NOT NULL DEFAULT ""',
			'maj'                => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
		],
		'key' => [
			'PRIMARY KEY'        => 'id_personne',
		],
		'titre' => 'nom AS titre, "" AS lang',
		 #'date' => '',
		'champs_editables'  => ['nom', 'fonction', 'texte'],
		'champs_versionnes' => ['nom', 'fonction', 'texte'],
		'rechercher_champs' => [],
		'tables_jointures'  => [],
	];

	$tables['spip_partenaires'] = [
		'type' => 'partenaire',
		'principale' => 'oui',
		'field' => [
			'id_partenaire'      => 'bigint(21) NOT NULL',
			'nom'                => 'text NOT NULL DEFAULT ""',
			'description'        => 'text NOT NULL DEFAULT ""',
			'maj'                => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
		],
		'key' => [
			'PRIMARY KEY'        => 'id_partenaire',
		],
		'titre' => 'nom AS titre, "" AS lang',
		 #'date' => '',
		'champs_editables'  => ['nom', 'description'],
		'champs_versionnes' => ['nom', 'description'],
		'rechercher_champs' => [],
		'tables_jointures'  => ['spip_partenaires_liens'],
	];

	return $tables;
}


/**
 * Déclaration des tables secondaires (liaisons)
 *
 * @pipeline declarer_tables_auxiliaires
 * @param array $tables
 *     Description des tables
 * @return array
 *     Description complétée des tables
 */
function terensys_declarer_tables_auxiliaires($tables) {

	$tables['spip_clients_liens'] = [
		'field' => [
			'id_client'          => 'bigint(21) DEFAULT "0" NOT NULL',
			'id_objet'           => 'bigint(21) DEFAULT "0" NOT NULL',
			'objet'              => 'varchar(25) DEFAULT "" NOT NULL',
			'vu'                 => 'varchar(6) DEFAULT "non" NOT NULL',
		],
		'key' => [
			'PRIMARY KEY'        => 'id_client,id_objet,objet',
			'KEY id_client'      => 'id_client',
		]
	];
	$tables['spip_partenaires_liens'] = [
		'field' => [
			'id_partenaire'      => 'bigint(21) DEFAULT "0" NOT NULL',
			'id_objet'           => 'bigint(21) DEFAULT "0" NOT NULL',
			'objet'              => 'varchar(25) DEFAULT "" NOT NULL',
			'vu'                 => 'varchar(6) DEFAULT "non" NOT NULL',
		],
		'key' => [
			'PRIMARY KEY'        => 'id_partenaire,id_objet,objet',
			'KEY id_partenaire'  => 'id_partenaire',
		]
	];

	return $tables;
}
