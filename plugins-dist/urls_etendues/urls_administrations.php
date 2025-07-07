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
 * Installation/maj des tables urls
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function urls_upgrade($nom_meta_base_version, $version_cible) {
	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])) {
		$trouver_table = charger_fonction('trouver_table', 'base');
		if (
			$desc = $trouver_table('spip_urls')
			and isset($desc['exist']) and $desc['exist']
			and !isset($desc['field']['id_parent'])
		) {
			ecrire_meta($nom_meta_base_version, '1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}

	$maj = [];
	$maj['create'] = [
		['maj_tables', ['spip_urls']],
	];
	$maj['1.1.0'] = [
		['sql_alter', "table spip_urls ADD id_parent bigint(21) DEFAULT '0' NOT NULL"],
		['sql_alter', 'table spip_urls DROP PRIMARY KEY'],
		['sql_alter', 'table spip_urls ADD PRIMARY KEY (id_parent, url)'],
	];
	$maj['1.1.1'] = [
		['urls_migre_arbo_prefixes'],
	];

	$maj['1.1.2'] = [
		['sql_alter', "table spip_urls ADD segments SMALLINT(3) DEFAULT '1' NOT NULL"],
		['urls_migre_urls_segments'],
	];
	$maj['1.1.3'] = [
		['sql_alter', "table spip_urls ADD perma TINYINT(1) DEFAULT '0' NOT NULL"],
	];
	$maj['1.1.4'] = [
		['sql_alter', "table spip_urls CHANGE `type` `type` varchar(25) DEFAULT 'article' NOT NULL"],
	];

	$maj['2.0.0'] = [
		['sql_alter', "table spip_urls ADD langue VARCHAR(10) DEFAULT '' NOT NULL"],
		['sql_alter', 'table spip_urls ADD KEY langue (langue)'],
	];

	$maj['2.0.1'] = [
		['sql_alter', 'table spip_urls ADD KEY url (url)'],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function urls_migre_arbo_prefixes() {
	$res = sql_select('*', 'spip_urls', "url REGEXP '\d+:\/\/'");
	while ($row = sql_fetch($res)) {
		$url = explode('://', $row['url']);
		$set = ['id_parent' => intval(reset($url)), 'url' => end($url)];
		if (
			!sql_updateq(
				'spip_urls',
				$set,
				'id_parent=' . intval($row['id_parent']) . ' AND url=' . sql_quote($row['url'])
			)
		) {
			if (
				$set['id_parent'] == 0
				and sql_countsel(
					'spip_urls',
					'id_parent=' . intval($set['id_parent']) . ' AND url=' . sql_quote($set['url']) . ' AND type=' . sql_quote($row['type']) . ' AND id_objet=' . sql_quote($row['id_objet'])
				)
			) {
				spip_log('suppression url doublon ' . var_export($row, 1), 'urls.' . _LOG_INFO_IMPORTANTE);
				sql_delete('spip_urls', 'id_parent=' . intval($row['id_parent']) . ' AND url=' . sql_quote($row['url']));
			} else {
				spip_log('Impossible de convertir url doublon ' . var_export($row, 1), 'urls.' . _LOG_ERREUR);
				echo "Impossible de convertir l'url " . $row['url'] . '. Verifiez manuellement dans spip_urls';
			}
		}
		if (time() >= _TIME_OUT) {
			sql_free($res);

			return;
		}
	}
}

function urls_migre_urls_segments() {
	sql_updateq('spip_urls', ['segments' => 1], "segments<1 OR NOT(url REGEXP '\/')");
	$res = sql_select('DISTINCT url', 'spip_urls', "url REGEXP '\/' AND segments=1");
	while ($row = sql_fetch($res)) {
		$segments = count(explode('/', $row['url']));
		sql_updateq('spip_urls', ['segments' => $segments], 'url=' . sql_quote($row['url']));
		if (time() >= _TIME_OUT) {
			sql_free($res);

			return;
		}
	}
}

/**
 * Desinstallation/suppression des tables urls
 *
 * @param string $nom_meta_base_version
 */
function urls_vider_tables($nom_meta_base_version) {
	// repasser dans les urls par defaut
	ecrire_meta('type_urls', 'page');
	sql_drop_table('spip_urls');
	effacer_meta($nom_meta_base_version);
}
