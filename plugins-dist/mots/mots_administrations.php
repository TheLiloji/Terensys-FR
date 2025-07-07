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
 * Fichier gérant l'installation et désinstallation du plugin
 *
 * @package SPIP\Mots\Installation
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Installation/maj des tables mots et groupes de mots...
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
 */
function mots_upgrade($nom_meta_base_version, $version_cible) {

	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])) {
		$trouver_table = charger_fonction('trouver_table', 'base');
		if (
			$desc = $trouver_table('spip_mots')
			and isset($desc['exist']) and $desc['exist']
			and $desc = $trouver_table('spip_mots_articles')
			and isset($desc['exist']) and $desc['exist']
		) {
			ecrire_meta($nom_meta_base_version, '1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}

	$maj = [];
	$maj['create'] = [
		['maj_tables', ['spip_groupes_mots', 'spip_mots', 'spip_mots_liens']],
	];
	$maj['1.0.0'] = [
		['maj_tables', ['spip_groupes_mots', 'spip_mots', 'spip_mots_liens']],
	];
	include_spip('maj/legacy/svn10000');
	$maj['2.0.0'] = [
		['maj_liens', 'mot'], // creer la table liens
		['maj_liens', 'mot', 'breve'],
		['sql_drop_table', 'spip_mots_breves'],
		['maj_liens', 'mot', 'rubrique'],
		['sql_drop_table', 'spip_mots_rubriques'],
		['maj_liens', 'mot', 'syndic'],
		['sql_drop_table', 'spip_mots_syndic'],
		['maj_liens', 'mot', 'forum'],
		['sql_drop_table', 'spip_mots_forum'],
		['maj_liens', 'mot', 'auteur'],
		['sql_drop_table', 'spip_mots_auteurs'],
		['maj_liens', 'mot', 'document'],
		['sql_drop_table', 'spip_mots_documents'],
		['maj_liens', 'mot', 'article'],
		['sql_drop_table', 'spip_mots_articles']
	];
	$maj['2.0.1'] = [
		['sql_updateq', 'spip_mots_liens', ['objet' => 'site'], "objet='syndic'"],
	];
	$maj['2.1.0'] = [
		['sql_alter', 'TABLE spip_mots_liens ADD INDEX id_objet (id_objet)'],
		['sql_alter', 'TABLE spip_mots_liens ADD INDEX objet (objet)']
	];
	$maj['2.1.1'] = [
		['sql_alter', 'TABLE spip_mots ADD INDEX id_groupe (id_groupe)']
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Désinstallation/suppression des tables mots et groupes de mots
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @return void
 */
function mots_vider_tables($nom_meta_base_version) {
	sql_drop_table('spip_mots');
	sql_drop_table('spip_groupes_mots');
	sql_drop_table('spip_mots_liens');

	effacer_meta('articles_mots');
	effacer_meta('config_precise_groupes');

	effacer_meta($nom_meta_base_version);
}
