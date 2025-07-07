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
 * verifier et maj le statut des documents
 *
 * @param bool $affiche
 * @return
 */
function medias_check_statuts($affiche = false) {
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table('documents');
	# securite, si jamais on arrive ici avant un upgrade de base
	if (!isset($desc['field']['statut'])) {
		return;
	}

	// utiliser sql_allfetsel pour clore la requete avant la mise a jour en base sur chaque doc (sqlite)
	// iterer par groupe de 100 pour ne pas exploser sur les grosses bases
	$docs = array_column(
		sql_allfetsel('id_document', 'spip_documents', "statut='0'", '', '', '0,100'),
		'id_document'
	);
	while (count($docs)) {
		include_spip('action/editer_document');
		foreach ($docs as $id_document) {
			// mettre a jour le statut si necessaire
			document_instituer($id_document);
		}
		if ($affiche) {
			echo ' .';
		}
		$docs = array_column(
			sql_allfetsel('id_document', 'spip_documents', "statut='0'", '', '', '0,100'),
			'id_document'
		);
	}
}

/**
 * Mise a jour de la BDD
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function medias_upgrade($nom_meta_base_version, $version_cible) {

	// ne pas installer tant qu'on est pas a jour sur version base SPIP
	// cas typique d'un upgrade qui commence par suppression de connect.php
	// SPIP lance la maj des plugins lors de la connexion, alors que l'upgrade SPIP
	// a pas encore ete joue : ca casse cet upgrade quand on migre depuis un tres vieux SPIP
	if (
		isset($GLOBALS['meta']['version_installee'])
		and ($GLOBALS['spip_version_base'] != (str_replace(',', '.', $GLOBALS['meta']['version_installee'])))
	) {
		return;
	}

	if (!isset($GLOBALS['meta'][$nom_meta_base_version])) {
		$trouver_table = charger_fonction('trouver_table', 'base');
		if (
			$desc = $trouver_table('spip_documents')
			and !isset($desc['field']['statut'])
		) {
			ecrire_meta($nom_meta_base_version, '0.1.0');
		}
	}

	$maj = [];
	$maj['create'] = [
		['maj_tables', ['spip_documents', 'spip_documents_liens', 'spip_types_documents']],
		['creer_base_types_doc']
	];
	$maj['0.2.0'] = [
		['sql_alter', "TABLE spip_documents ADD statut varchar(10) DEFAULT '0' NOT NULL"],
	];
	$maj['0.3.0'] = [
		['sql_alter', "TABLE spip_documents ADD date_publication datetime DEFAULT '0000-00-00 00:00:00' NOT NULL"],
	];
	$maj['0.4.0'] = [
		// recalculer tous les statuts en tenant compte de la date de publi des articles...
		['medias_check_statuts', true],
	];
	$maj['0.5.0'] = [
		['sql_alter', 'TABLE spip_documents ADD brise tinyint DEFAULT 0'],
	];
	$maj['0.6.0'] = [
		['sql_alter', "TABLE spip_types_documents ADD media varchar(10) DEFAULT 'file' NOT NULL"],
		['creer_base_types_doc', '', 'media'],
	];
	$maj['0.7.0'] = [
		['sql_alter', "TABLE spip_documents ADD credits varchar(255) DEFAULT '' NOT NULL"],
	];
	$maj['0.10.0'] = [
		['sql_alter', "TABLE spip_documents CHANGE fichier fichier TEXT NOT NULL DEFAULT ''"],
	];
	$maj['0.11.0'] = [
		['sql_alter', "TABLE spip_documents CHANGE mode mode varchar(10) DEFAULT 'document' NOT NULL"],
	];
	$maj['0.14.0'] = [
		['medias_maj_meta_documents'],
		['creer_base_types_doc', '', 'media'],
	];
	$maj['0.15.0'] = [
		['creer_base_types_doc', '', 'media'],
	];
	$maj['0.15.1'] = [
		['sql_alter', 'TABLE spip_documents CHANGE taille taille bigint'],
	];
	$maj['0.16.0'] = [
		['creer_base_types_doc', '', 'media'],
	];

	$maj['1.0.0'] = [
		// on cree le champ en defaut '?' pour reperer les nouveaux a peupler
		['sql_alter', "TABLE spip_documents ADD media varchar(10) DEFAULT '?' NOT NULL"],
		['medias_peuple_media_document', 'media'],
		// puis on retablit le bon defaut
		['sql_alter', "TABLE spip_documents CHANGE media media varchar(10) DEFAULT 'file' NOT NULL"],
	];
	$maj['1.0.1'] = [
		// puis on retablit le bon defaut
		['sql_alter', "TABLE spip_types_documents CHANGE media media_defaut varchar(10) DEFAULT 'file' NOT NULL"],
	];

	$maj['1.1.0'] = [
		['sql_alter', 'TABLE spip_documents_liens ADD INDEX id_objet (id_objet)'],
		['sql_alter', 'TABLE spip_documents_liens ADD INDEX objet (objet)'],
	];
	$maj['1.1.1'] = [
		['creer_base_types_doc'],
	];
	// reparer les media sur les file suite a upgrade rate depuis SPIP 2.x
	$maj['1.2.0'] = [
		// on remet en ? tous les media=file
		['sql_updateq', 'spip_documents', ['media' => '?'], "media='file'"],
		// et on repeuple
		['medias_peuple_media_document'],
	];
	$maj['1.2.1'] = [
		['creer_base_types_doc'],
	];
	$maj['1.2.3'] = [
		// ajout de mbtiles
		['creer_base_types_doc'],
		// reparer les clauses DEFAULT manquantes de maniere reccurente sur cette table
		['sql_alter', "TABLE spip_documents CHANGE extension extension VARCHAR(10) DEFAULT '' NOT NULL"],
		['sql_alter', "TABLE spip_documents CHANGE credits credits varchar(255) DEFAULT '' NOT NULL"],
		['sql_alter', "TABLE spip_documents CHANGE statut statut varchar(10) DEFAULT '0' NOT NULL"],
	];
	$maj['1.2.4'] = [
		// ajout de tar
		['creer_base_types_doc']
	];
	$maj['1.2.5'] = [
		// ajout de json
		['creer_base_types_doc']
	];
	$maj['1.2.6'] = [
		// ajout de md (markdown)
		['creer_base_types_doc']
	];
	$maj['1.2.7'] = [
		// ajout de ics + vcf
		['creer_base_types_doc']
	];
	$maj['1.3.0'] = [
		// ajout de rang_lien
		['maj_tables', 'spip_documents_liens'],
	];
	$maj['1.3.1'] = [
		// plus de place dans les crédits
		['sql_alter', "TABLE spip_documents CHANGE credits credits text DEFAULT '' NOT NULL"],
	];
	$maj['1.3.2'] = [
		// buggons en 2038 plutôt qu'en 2018'
		['medias_check_statuts', true],
	];
	$maj['1.3.4'] = [
		// 1.3.2 et 1.3.3 n'étaient pas suffisants grml'
		['medias_maj_date_publication_documents'],
		['medias_check_statuts', true]
	];
	$maj['1.3.5'] = [
		// ajout de duree
		['maj_tables', 'spip_documents'],
	];
	$maj['1.3.6'] = [
		// ajout de vtt
		['creer_base_types_doc']
	];

	$maj['1.4.0'] = [
		// update de SVG
		['creer_base_types_doc']
	];

	$maj['1.5.0'] = [
		// ajout de webp
		['creer_base_types_doc'],
	];


	// upgrade des logos
	$maj['1.6.0'] = [
		['sql_alter', "TABLE spip_documents CHANGE mode mode varchar(10) DEFAULT 'document' NOT NULL"]
	];
	$tables_objets_sql = lister_tables_objets_sql();
	foreach (array_keys($tables_objets_sql) as $table) {
		$maj['1.6.0'][] = ['medias_upgrade_logo_objet', objet_type($table)];
	};


	$maj['1.7.0'] = [
		// ajout de alt
		['maj_tables', 'spip_documents'],
	];

	$maj['1.8.0'] = [
		// ajout de GeoJson
		['creer_base_types_doc'],
	];
	include_spip('base/upgrade');
	include_spip('base/medias');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Upgrader les logos objet vers des documents
 * @param $objet
 */
function medias_upgrade_logo_objet($objet) {
	$GLOBALS['logo_migrer_en_base'] = true;
	include_spip('ecrire/action/editer_logo');
	logo_migrer_en_base($objet, _TIME_OUT);
	unset($GLOBALS['logo_migrer_en_base']);
}

/**
 * Maj des meta documents
 */
function medias_maj_meta_documents() {
	$config = [];
	if (isset($GLOBALS['meta']['documents_article']) and $GLOBALS['meta']['documents_article'] !== 'non') {
		$config[] = 'spip_articles';
	}
	if (isset($GLOBALS['meta']['documents_rubrique']) and $GLOBALS['meta']['documents_rubrique'] !== 'non') {
		$config[] = 'spip_rubriques';
	}
	ecrire_meta('documents_objets', implode(',', $config));
}

function medias_peuple_media_document($champ_media = 'media_defaut') {
	$res = sql_select('DISTINCT extension', 'spip_documents', 'media=' . sql_quote('?'));
	while ($row = sql_fetch($res)) {
		// attention ici c'est encore le champ media, car on le renomme juste apres
		$media = sql_getfetsel($champ_media, 'spip_types_documents', 'extension=' . sql_quote($row['extension']));
		sql_updateq('spip_documents', ['media' => $media], 'media=' . sql_quote('?') . ' AND extension=' . sql_quote($row['extension']));
		if (time() >= _TIME_OUT) {
			return;
		}
	}
}

/**
 * Maj des date de publication des documents cf ticket #3329, z104221
 */
function medias_maj_date_publication_documents() {
	sql_update('spip_documents', ['statut' => '0'], 'date_publication > ' . sql_quote('2017-01-01 00:00:00'));
	sql_update('spip_documents', ['statut' => '0'], 'date_publication = ' . sql_quote('1970-01-01 01:33:58'));
}

/*
function medias_install($action, $prefix, $version_cible){
	$version_base = $GLOBALS[$prefix."_base_version"];
	switch ($action){
		case 'test':
			# plus necessaire si pas de bug :p
			# medias_check_statuts();
			return (isset($GLOBALS['meta'][$prefix."_base_version"])
				AND version_compare($GLOBALS['meta'][$prefix."_base_version"],$version_cible,">="));
			break;
		case 'install':
			medias_upgrade('medias_base_version',$version_cible);
			break;
		case 'uninstall':
			# pas de deinstallation sur les documents pour le moment, trop dangereux
			# medias_vider_tables();
			break;
	}
}
*/
