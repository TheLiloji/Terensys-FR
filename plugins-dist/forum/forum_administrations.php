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
 * @package SPIP\Forum\Installation
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Installation/maj des tables forum
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function forum_upgrade($nom_meta_base_version, $version_cible) {

	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])) {
		$trouver_table = charger_fonction('trouver_table', 'base');
		$trouver_table(''); // vider le cache des descriptions !
		if (
			$desc = $trouver_table('spip_forum')
			and isset($desc['field']['id_article'])
		) {
			ecrire_meta($nom_meta_base_version, '1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}

	$maj = [];
	$maj['create'] = [
		['maj_tables', ['spip_forum']],
	];
	$maj['1.1.0'] = [
		['sql_alter', 'TABLE spip_forum ADD id_objet bigint(21) DEFAULT 0 NOT NULL AFTER id_forum'],
		['sql_alter', "TABLE spip_forum ADD objet VARCHAR (25) DEFAULT '' NOT NULL AFTER id_objet"],
		#array('sql_alter',"TABLE spip_forum DROP INDEX optimal"),
		#array('sql_alter',"TABLE spip_forum ADD INDEX optimal (statut,id_parent,id_objet,objet,date_heure)"),
	];
	$maj['1.1.1'] = [
		['sql_update', 'spip_forum', ['objet' => "'breve'", 'id_objet' => 'id_breve'], 'id_breve> 0'],
		#array('sql_alter',"TABLE spip_forum DROP id_breve"),
		['sql_update', 'spip_forum', ['objet' => "'article'", 'id_objet' => 'id_article'], 'id_article>0'],
		#array('sql_alter',"TABLE spip_forum DROP id_article"),
		['sql_update', 'spip_forum', ['objet' => "'site'", 'id_objet' => 'id_syndic'], 'id_syndic>0'],
		#array('sql_alter',"TABLE spip_forum DROP id_syndic"),
		['sql_update', 'spip_forum', ['objet' => "'message'", 'id_objet' => 'id_message'], 'id_message>0'],
		#array('sql_alter',"TABLE spip_forum DROP id_message"),
		['sql_update', 'spip_forum', ['objet' => "'rubrique'", 'id_objet' => 'id_rubrique'], 'id_rubrique>0'],
		#array('sql_alter',"TABLE spip_forum DROP id_rubrique"),
	];

	# champ ip sur 40 car (compat IPv6)
	$maj['1.2.0'] = [
		['sql_alter', "TABLE spip_forum CHANGE ip ip VARCHAR(40) DEFAULT '' NOT NULL"],
	];
	# rejouer la suppression/creation de l'index optimal
	# et la suppression des vieux champs, car la premiere sequence avait echoue
	# en raison d'un DROP KEY au lieu de DROP INDEX
	$maj['1.2.1'] = [
		['sql_alter', 'TABLE spip_forum DROP INDEX optimal'],
		['sql_alter', 'TABLE spip_forum ADD INDEX optimal (statut,id_parent,id_objet,objet,date_heure)'],
		['sql_alter', 'TABLE spip_forum DROP id_breve'],
		['sql_alter', 'TABLE spip_forum DROP id_article'],
		['sql_alter', 'TABLE spip_forum DROP id_syndic'],
		['sql_alter', 'TABLE spip_forum DROP id_message'],
		['sql_alter', 'TABLE spip_forum DROP id_rubrique'],
	];
	$maj['1.2.2'] = [
		[
			'ecrire_meta',
			'forum_prive_objets',
			($GLOBALS['meta']['forum_prive_objets'] == 'non') ? '' : 'spip_articles,spip_breves,spip_syndic'
		],
	];


	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Désinstallation/suppression des tables forum
 *
 * @param string $nom_meta_base_version
 */
function forum_vider_tables($nom_meta_base_version) {
	sql_drop_table('spip_forum');

	effacer_meta('mots_cles_forums');
	effacer_meta('forums_titre');
	effacer_meta('forums_texte');
	effacer_meta('forums_urlref');
	effacer_meta('forums_afficher_barre');
	effacer_meta('forums_forcer_previsu');
	effacer_meta('formats_documents_forum');
	effacer_meta('forums_publics');
	effacer_meta('forum_prive');
	effacer_meta('forum_prive_objets');
	effacer_meta('forum_prive_admin');

	effacer_meta($nom_meta_base_version);
}
