<?php

/**
 * Fichier gérant l'installation et désinstallation du plugin Terensys
 *
 * @plugin     Terensys
 * @copyright  2025
 * @author     Terensys
 * @licence    GNU/GPL
 * @package    SPIP\Terensys\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Fonction d'installation et de mise à jour du plugin Terensys.
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
**/
function terensys_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];

	$maj['create'] = [['maj_tables', ['spip_articles_editoriaux', 'spip_clients', 'spip_clients_liens', 'spip_personnes', 'spip_partenaires', 'spip_partenaires_liens']]];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Fonction de désinstallation du plugin Terensys.
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @return void
**/
function terensys_vider_tables($nom_meta_base_version) {

	sql_drop_table('spip_articles_editoriaux');
	sql_drop_table('spip_clients');
	sql_drop_table('spip_clients_liens');
	sql_drop_table('spip_personnes');
	sql_drop_table('spip_partenaires');
	sql_drop_table('spip_partenaires_liens');

	# Nettoyer les liens courants (le génie optimiser_base_disparus se chargera de nettoyer toutes les tables de liens)
	sql_delete('spip_documents_liens', sql_in('objet', ['articles_editorial', 'client', 'personne', 'partenaire']));
	sql_delete('spip_mots_liens', sql_in('objet', ['articles_editorial', 'client', 'personne', 'partenaire']));
	sql_delete('spip_auteurs_liens', sql_in('objet', ['articles_editorial', 'client', 'personne', 'partenaire']));
	# Nettoyer les versionnages et forums
	sql_delete('spip_versions', sql_in('objet', ['articles_editorial', 'client', 'personne', 'partenaire']));
	sql_delete('spip_versions_fragments', sql_in('objet', ['articles_editorial', 'client', 'personne', 'partenaire']));
	sql_delete('spip_forum', sql_in('objet', ['articles_editorial', 'client', 'personne', 'partenaire']));

	effacer_meta($nom_meta_base_version);
}
