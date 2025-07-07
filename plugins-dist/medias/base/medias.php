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
 * Déclarations relatives à la base de données
 *
 * @package SPIP\Medias\Pipelines
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Interfaces des tables documents pour le compilateur
 *
 * @param array $interfaces
 * @return array
 */
function medias_declarer_tables_interfaces($interfaces) {
	$interfaces['table_des_tables']['documents'] = 'documents';
	$interfaces['table_des_tables']['types_documents'] = 'types_documents';

	$interfaces['exceptions_des_tables']['documents']['type_document'] = ['types_documents', 'titre'];
	$interfaces['exceptions_des_tables']['documents']['extension_document'] = ['types_documents', 'extension'];
	$interfaces['exceptions_des_tables']['documents']['mime_type_extension'] = ['types_documents', 'mime_type'];
	$interfaces['exceptions_des_tables']['documents']['media_document'] = ['types_documents', 'media_defaut'];

	$interfaces['exceptions_des_jointures']['spip_documents']['id_forum'] = ['spip_documents_liens', 'id_forum'];
	$interfaces['exceptions_des_jointures']['spip_documents']['vu'] = ['spip_documents_liens', 'vu'];
	$interfaces['table_date']['types_documents'] = 'date';

	$interfaces['table_des_traitements']['FICHIER'][] = 'get_spip_doc(%s)';
	$interfaces['table_des_traitements']['CREDITS']['documents'] = _TRAITEMENT_TYPO;
	return $interfaces;
}


/**
 * Table principale spip_documents et spip_types_documents
 *
 * @param array $tables_principales
 * @return array
 */
function medias_declarer_tables_principales($tables_principales) {

	$spip_types_documents = [
		'extension' => "varchar(10) DEFAULT '' NOT NULL",
		'titre' => "text DEFAULT '' NOT NULL",
		'descriptif' => "text DEFAULT '' NOT NULL",
		'mime_type' => "varchar(100) DEFAULT '' NOT NULL",
		'inclus' => "ENUM('non', 'image', 'embed') DEFAULT 'non'  NOT NULL",
		'upload' => "ENUM('oui', 'non') DEFAULT 'oui'  NOT NULL",
		'media_defaut' => "varchar(10) DEFAULT 'file' NOT NULL",
		'maj' => 'TIMESTAMP'
	];

	$spip_types_documents_key = [
		'PRIMARY KEY' => 'extension',
		'KEY inclus' => 'inclus'
	];

	$tables_principales['spip_types_documents'] =
		['field' => &$spip_types_documents, 'key' => &$spip_types_documents_key];

	return $tables_principales;
}

/**
 * Table des liens documents-objets spip_documents_liens
 *
 * @param array $tables_auxiliaires
 * @return array
 */
function medias_declarer_tables_auxiliaires($tables_auxiliaires) {

	$spip_documents_liens = [
		'id_document' => "bigint(21) DEFAULT '0' NOT NULL",
		'id_objet' => "bigint(21) DEFAULT '0' NOT NULL",
		'objet' => "VARCHAR (25) DEFAULT '' NOT NULL",
		'vu' => "ENUM('non', 'oui') DEFAULT 'non' NOT NULL",
		'rang_lien' => "int(4) DEFAULT '0' NOT NULL"
	];

	$spip_documents_liens_key = [
		'PRIMARY KEY' => 'id_document,id_objet,objet',
		'KEY id_document' => 'id_document',
		'KEY id_objet' => 'id_objet',
		'KEY objet' => 'objet',
	];

	$tables_auxiliaires['spip_documents_liens'] = [
		'field' => &$spip_documents_liens,
		'key' => &$spip_documents_liens_key
	];

	return $tables_auxiliaires;
}

/**
 * Declarer le surnom des breves
 *
 * @param array $surnoms
 * @return array
 */
function medias_declarer_tables_objets_surnoms($surnoms) {
	$surnoms['type_document'] = 'types_documents'; # hum
	#$surnoms['extension'] = "types_documents"; # hum
	#$surnoms['type'] = "types_documents"; # a ajouter pour id_table_objet('type')=='extension' ?
	return $surnoms;
}

function medias_declarer_tables_objets_sql($tables) {
	$tables['spip_articles']['champs_versionnes'][] = 'jointure_documents';
	$tables['spip_documents'] = [
		'table_objet_surnoms' => ['doc', 'img', 'emb'],
		'type_surnoms' => [],
		'url_voir' => 'document_edit',
		'url_edit' => 'document_edit',
		'page' => '',
		'texte_retour' => 'icone_retour',
		'texte_objets' => 'medias:objet_documents',
		'texte_objet' => 'medias:objet_document',
		'texte_modifier' => 'medias:info_modifier_document',
		'info_aucun_objet' => 'medias:aucun_document',
		'info_1_objet' => 'medias:un_document',
		'info_nb_objets' => 'medias:des_documents',
		'titre' => "CASE WHEN length(titre)>0 THEN titre ELSE fichier END as titre, '' AS lang",
		'date' => 'date',
		'principale' => 'oui',
		'field' => [
			'id_document' => 'bigint(21) NOT NULL',
			'id_vignette' => "bigint(21) DEFAULT '0' NOT NULL",
			'extension' => "VARCHAR(10) DEFAULT '' NOT NULL",
			'titre' => "text DEFAULT '' NOT NULL",
			'date' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'descriptif' => "text DEFAULT '' NOT NULL",
			'fichier' => "text NOT NULL DEFAULT ''",
			'taille' => 'bigint',
			'largeur' => 'integer',
			'hauteur' => 'integer',
			'duree' => 'integer',
			'media' => "varchar(10) DEFAULT 'file' NOT NULL",
			'mode' => "varchar(10) DEFAULT 'document' NOT NULL",
			'distant' => "VARCHAR(3) DEFAULT 'non'",
			'statut' => "varchar(10) DEFAULT '0' NOT NULL",
			'credits' => "text DEFAULT '' NOT NULL",
			'alt' => "text DEFAULT '' NOT NULL",
			'date_publication' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'brise' => 'tinyint DEFAULT 0',
			'maj' => 'TIMESTAMP'
		],
		'key' => [
			'PRIMARY KEY' => 'id_document',
			'KEY id_vignette' => 'id_vignette',
			'KEY mode' => 'mode',
			'KEY extension' => 'extension'
		],
		'join' => [
			'id_document' => 'id_document',
			'extension' => 'extension'
		],
		'statut' => [
			[
				'champ' => 'statut',
				'publie' => 'publie',
				'previsu' => 'publie,prop,prepa',
				'post_date' => 'date_publication',
				'exception' => ['statut', 'tout']
			]
		],
		'tables_jointures' => ['types_documents'],
		'rechercher_champs' => [
			'titre' => 3,
			'descriptif' => 1,
			'fichier' => 1,
			'credits' => 1,
		],
		'champs_editables' => [
			'titre',
			'descriptif',
			'date',
			'taille',
			'largeur',
			'hauteur',
			'duree',
			'mode',
			'credits',
			'alt',
			'fichier',
			'distant',
			'extension',
			'id_vignette',
			'media'
		],
		'champs_versionnes' => [
			'id_vignette',
			'titre',
			'descriptif',
			'taille',
			'largeur',
			'hauteur',
			'duree',
			'mode',
			'credits',
			'fichier',
			'distant'
		],
		'modeles' => ['document', 'doc', 'img', 'emb', 'image', 'video', 'audio', 'file'],
		'modeles_styliser' => 'medias_modeles_styliser',
	];

	// jointures sur les forum pour tous les objets
	$tables[]['tables_jointures'][] = 'documents_liens';

	// recherche jointe sur les documents pour les articles et rubriques
	$tables['spip_articles']['rechercher_jointures']['document'] = ['titre' => 2, 'descriptif' => 1];
	$tables['spip_rubriques']['rechercher_jointures']['document'] = ['titre' => 2, 'descriptif' => 1];

	return $tables;
}

/**
 * Creer la table des types de document
 *
 * @param string $serveur
 * @param string $champ_media
 * @return void
 */
function creer_base_types_doc($serveur = '', $champ_media = 'media_defaut') {
	global $tables_images, $tables_sequences, $tables_documents, $tables_mime;
	include_spip('base/typedoc');
	include_spip('base/abstract_sql');

	// charger en memoire tous les types deja definis pour limiter les requettes
	$rows = sql_allfetsel('mime_type,titre,inclus,extension,' . $champ_media . ',upload,descriptif', 'spip_types_documents', '', '', '', '', '', $serveur);
	$deja = [];
	foreach ($rows as $k => $row) {
		$deja[$row['extension']] = &$rows[$k];
	}

	$insertions = [];
	$updates = [];

	foreach ($tables_mime as $extension => $type_mime) {
		if (isset($tables_images[$extension])) {
			$titre = $tables_images[$extension];
			$inclus = 'image';
		} else {
			if (isset($tables_sequences[$extension])) {
				$titre = $tables_sequences[$extension];
				$inclus = 'embed';
			} else {
				$inclus = 'non';
				if (isset($tables_documents[$extension])) {
					$titre = $tables_documents[$extension];
				} else {
					$titre = '';
				}
			}
		}

		// type de media (par defaut)
		$media = 'file';
		if (preg_match(',^image/,', $type_mime) or in_array($type_mime, ['application/illustrator'])) {
			$media = 'image';
		} elseif (preg_match(',^audio/,', $type_mime)) {
			$media = 'audio';
		} elseif (
			preg_match(',^video/,', $type_mime) or in_array(
				$type_mime,
				['application/ogg', 'application/x-shockwave-flash', 'application/mp4']
			)
		) {
			$media = 'video';
		}

		$set = [
			'mime_type' => $type_mime,
			'titre' => $titre,
			'inclus' => $inclus,
			'extension' => $extension,
			$champ_media => $media,
			'upload' => 'oui',
			'descriptif' => '',
		];
		if (!isset($deja[$extension])) {
			$insertions[] = $set;
		} elseif (array_diff($deja[$extension], $set)) {
			$updates[$extension] = $set;
		}
	}

	if (count($updates)) {
		foreach ($updates as $extension => $set) {
			sql_updateq('spip_types_documents', $set, 'extension=' . sql_quote($extension));
		}
	}

	if ($insertions) {
		sql_insertq_multi('spip_types_documents', $insertions, [], $serveur);
	}
}


/**
 * Optimiser la base de données en supprimant les liens orphelins
 *
 * @param array $flux
 * @return array
 */
function medias_optimiser_base_disparus($flux) {

	include_spip('action/editer_liens');
	// optimiser les liens morts :
	// entre documents vers des objets effaces
	// depuis des documents effaces
	$flux['data'] += objet_optimiser_liens(['document' => '*'], '*');

	// on ne nettoie volontairement pas automatiquement les documents orphelins
	// mais il faut nettoyer les logos qui ne sont plus liés à rien
	$res = sql_select(
		'D.id_document',
		'spip_documents AS D
						LEFT JOIN spip_documents_liens AS L
							ON (L.id_document=D.id_document)',
		sql_in('D.mode', ['logoon', 'logooff']) . ' AND L.id_document IS NULL'
	);

	$supprimer_document = charger_fonction('supprimer_document', 'action');
	while ($row = sql_fetch($res)) {
		autoriser_exception('supprimer', 'document', $row['id_document']);
		$supprimer_document($row['id_document']);
		$flux['data']++;
	}

	return $flux;
}
