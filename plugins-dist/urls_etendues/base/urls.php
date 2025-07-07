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
 * Declarer les interfaces
 *
 * @param array $interfaces
 * @return array
 */
function urls_declarer_tables_interfaces($interfaces) {
	$interfaces['table_des_tables']['urls'] = 'urls';

	return $interfaces;
}

/**
 * Tables de jointures
 *
 * @param array $tables_auxiliaires
 * @return array
 */
function urls_declarer_tables_auxiliaires($tables_auxiliaires) {

	$spip_urls = [
		// un id parent eventuel, pour discriminer les doublons arborescents
		'id_parent' => "bigint(21) DEFAULT '0' NOT NULL",
		'url' => 'VARCHAR(255) NOT NULL',
		// la table cible
		'type' => "varchar(25) DEFAULT 'article' NOT NULL",
		// l'id dans la table
		'id_objet' => 'bigint(21) NOT NULL',
		// pour connaitre la plus recente.
		// ATTENTION, pas on update CURRENT_TIMESTAMP implicite
		// et pas le nom maj, surinterprete par inc/import_1_3
		'date' => "DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL",
		// nombre de segments dans url
		'segments' => "SMALLINT(3) DEFAULT '1' NOT NULL",
		// URL permanente, prioritaire
		'perma' => "TINYINT(1) DEFAULT '0' NOT NULL",
		// langue des urls : on le nomme langue et pas lang pour eviter les ambiguites avec le champ lang des objets
		// qui apparait dans les jointures sans prefixe de table, via le champ titre "titre, lang"
		'langue' => "VARCHAR(10) DEFAULT '' NOT NULL",

	];

	$spip_urls_key = [
		'PRIMARY KEY' => 'id_parent, url',
		'KEY type' => 'type, id_objet',
		'KEY langue' => 'langue',
		'KEY url' => 'url',
	];

	$tables_auxiliaires['spip_urls'] = [
		'field' => &$spip_urls,
		'key' => &$spip_urls_key
	];

	return $tables_auxiliaires;
}
