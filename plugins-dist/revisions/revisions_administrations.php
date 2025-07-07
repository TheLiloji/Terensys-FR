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
 * Installation du plugin révisions
 *
 * @package SPIP\Revisions\Installation
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Installation/maj des tables révisions
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function revisions_upgrade($nom_meta_base_version, $version_cible) {
	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])) {
		$trouver_table = charger_fonction('trouver_table', 'base');
		if (
			$desc = $trouver_table('spip_versions')
			and isset($desc['exist']) and $desc['exist']
			and isset($desc['field']['id_article'])
		) {
			ecrire_meta($nom_meta_base_version, '1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}

	$maj = [];
	$maj['create'] = [
		['maj_tables', ['spip_versions', 'spip_versions_fragments']],
		['revisions_update_meta'],
	];

	$maj['1.1.0'] = [
		// Ajout du champs objet et modification du champs id_article en id_objet
		// sur les 2 tables spip_versions et spip_versions_fragments
		['sql_alter', 'TABLE spip_versions CHANGE id_article id_objet bigint(21) DEFAULT 0 NOT NULL'],
		['sql_alter', "TABLE spip_versions ADD objet VARCHAR (25) DEFAULT '' NOT NULL AFTER id_objet"],
		// Les id_objet restent les id_articles puisque les révisions n'étaient possibles que sur les articles
		['sql_updateq', 'spip_versions', ['objet' => 'article'], "objet=''"],
		// Changement des clefs primaires également
		['sql_alter', 'TABLE spip_versions DROP PRIMARY KEY'],
		['sql_alter', 'TABLE spip_versions ADD PRIMARY KEY (id_version, id_objet, objet)'],

		['sql_alter', 'TABLE spip_versions_fragments CHANGE id_article id_objet bigint(21) DEFAULT 0 NOT NULL'],
		['sql_alter', "TABLE spip_versions_fragments ADD objet VARCHAR (25) DEFAULT '' NOT NULL AFTER id_objet"],
		// Les id_objet restent les id_articles puisque les révisions n'étaient possibles que sur les articles
		['sql_updateq', 'spip_versions_fragments', ['objet' => 'article'], "objet=''"],
		// Changement des clefs primaires également
		['sql_alter', 'TABLE spip_versions_fragments DROP PRIMARY KEY'],
		['sql_alter', 'TABLE spip_versions_fragments ADD PRIMARY KEY (id_objet, objet, id_fragment, version_min)'],
		['revisions_update_meta']
	];
	$maj['1.1.2'] = [
		['revisions_update_meta'],
		['sql_updateq', 'spip_versions', ['objet' => 'article'], "objet=''"],
		['sql_updateq', 'spip_versions_fragments', ['objet' => 'article'], "objet=''"],
	];
	$maj['1.1.3'] = [
		['sql_alter', 'TABLE spip_versions DROP KEY id_objet'],
		['sql_alter', 'TABLE spip_versions ADD INDEX id_version (id_version)'],
		['sql_alter', 'TABLE spip_versions ADD INDEX id_objet (id_objet)'],
		['sql_alter', 'TABLE spip_versions ADD INDEX objet (objet)']
	];
	$maj['1.1.4'] = [
		['sql_alter', "TABLE spip_versions CHANGE permanent permanent char(3) DEFAULT '' NOT NULL"],
		['sql_alter', "TABLE spip_versions CHANGE champs champs text DEFAULT '' NOT NULL"],
	];
	$maj['1.2.0'] = [
		['revisions_uncompress_fragments'],
		['revisions_repair_unserialized_fragments'],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function revisions_uncompress_fragments() {
	$count = sql_countsel('spip_versions_fragments', 'compress=' . intval(1));

	if ($count > 20000) {
		$limit = '0,20000';
	} else {
		$limit = "0,$count";
	}

	$res = sql_allfetsel(
		'*',
		'spip_versions_fragments',
		'compress=' . intval(1),
		'',
		'',
		$limit
	);

	foreach ($res as $row) {
		$fragment = @gzuncompress($row['fragment']);

		// si la decompression echoue, on met en base le flag 'corrompu-gz'
		// et au dump le framgment compresse dans un fichier
		if (strlen($row['fragment']) and $fragment === false) {
			$dir_tmp = sous_repertoire(_DIR_TMP, 'versions_fragments_corrompus');
			$f = $row['id_fragment'] . '-' . $row['objet'] . '-' . $row['id_objet'];
			$f = $f . '-gz.txt';
			ecrire_fichier($dir_tmp . $f, $row['fragment']);
			$fragment = 'corrompu-gz';
		}

		$set = [
			'compress' => 0,
			'fragment' => $fragment,
		];

		sql_updateq(
			'spip_versions_fragments',
			$set,
			'id_fragment=' . intval($row['id_fragment']) . '
				AND id_objet=' . intval($row['id_objet']) . '
				AND objet=' . sql_quote($row['objet']) . '
				AND version_min=' . intval($row['version_min'])
		);
		if (time() > _TIME_OUT) {
			return;
		}
	}
	if (sql_countsel('spip_versions_fragments', 'compress=' . intval(1)) > 0) {
		revisions_uncompress_fragments();
	}
	sql_updateq('spip_versions_fragments', ['compress' => -1]);
}

function revisions_repair_unserialized_fragments() {
	$n = sql_countsel('spip_versions_fragments', 'compress=' . intval(-1));
	spip_log("$n fragments a verifier", 'maj.' . _LOG_ERREUR);
	if ($n > 20000) {
		$limit = '0,20000';
	} else {
		$limit = "0,$n";
	}

	$res = sql_allfetsel(
		'*',
		'spip_versions_fragments',
		'compress=' . intval(-1),
		'',
		'',
		$limit
	);

	foreach ($res as $row) {
		$fragment = $row['fragment'];
		$set = [
			'compress' => 0,
		];

		// verifier que le fragment est bien serializable
		if (unserialize($fragment) === false and strncmp($fragment, 'corrompu', 8) !== 0) {
			$dir_tmp = sous_repertoire(_DIR_TMP, 'versions_fragments_corrompus');
			$set['fragment'] = revisions_repair_serialise($fragment);
			if (strncmp($set['fragment'], 'corrompu', 8) == 0) {
				$f = $row['id_fragment'] . '-' . $row['objet'] . '-' . $row['id_objet'];
				spip_log("Fragment serialize corrompu $f", 'maj');
				$f = $f . '-serialize.txt';
				ecrire_fichier($dir_tmp . $f, $fragment);
			}
		}
		sql_updateq(
			'spip_versions_fragments',
			$set,
			'id_fragment=' . intval($row['id_fragment']) . '
				AND id_objet=' . intval($row['id_objet']) . '
				AND objet=' . sql_quote($row['objet']) . '
				AND version_min=' . intval($row['version_min'])
		);

		if (time() > _TIME_OUT) {
			return;
		}
	}
	if (sql_countsel('spip_versions_fragments', 'compress=' . intval(-1)) > 0) {
		revisions_repair_unserialized_fragments();
	}
}

function revisions_repair_serialise($serialize) {
	if (unserialize($serialize)) {
		return $serialize;
	}

	// verifier les strings
	preg_match_all(',s:(\d+):\"(.*)\";(?=}|\w:\d+),Uims', $serialize, $matches, PREG_SET_ORDER);
	$serialize_repair = $serialize;
	foreach ($matches as $match) {
		$s = $match[2];
		$l = $match[1];
		if (strlen($s) !== $l) {
			if (strlen($s) < $l) {
				$s = str_replace("\r\n", "\n", $s);
				$s = str_replace("\r", "\n", $s);
				$s = str_replace("\n", "\r\n", $s);
			}
			if (strlen($s) > $l) {
				$s = str_replace("\r\n", "\n", $s);
				$s = str_replace("\r", "\n", $s);
			}
			if (strlen($s) < $l) {
				$s .= str_pad('', $l - strlen($s), ' ');
			}
			if (strlen($s) == $l) {
				$s = str_replace($match[2], $s, $match[0]);
				$serialize_repair = str_replace($match[0], $s, $serialize_repair);
			}
		}
	}
	if (unserialize($serialize_repair)) {
		return $serialize_repair;
	}

	// on essaye brutalement
	$serialize_repair = $serialize;
	$serialize_repair = str_replace("\r\n", "\n", $serialize_repair);
	$serialize_repair = str_replace("\r", "\n", $serialize_repair);
	if (unserialize($serialize_repair)) {
		return $serialize_repair;
	}
	$serialize_repair = str_replace("\n", "\r\n", $serialize_repair);
	if (unserialize($serialize_repair)) {
		return $serialize_repair;
	}

	#echo "Impossible de reparer la chaine :";
	#var_dump($serialize);
	#var_dump($matches);
	#die("corrompu-serialize");
	return 'corrompu-serialize';
}


/**
 * Desinstallation/suppression des tables revisions
 *
 * @param string $nom_meta_base_version
 */
function revisions_vider_tables($nom_meta_base_version) {
	sql_drop_table('spip_versions');
	sql_drop_table('spip_versions_fragments');

	effacer_meta($nom_meta_base_version);
}

/**
 * Mettre a jour la meta des versions
 *
 * @return void
 */
function revisions_update_meta() {
	// Si dans une installation antérieure ou un upgrade, les articles étaient versionnés
	// On crée la meta correspondante
	// mettre les metas par defaut
	$config = charger_fonction('config', 'inc');
	$config();
	if (isset($GLOBALS['meta']['articles_versions']) and $GLOBALS['meta']['articles_versions'] == 'oui') {
		ecrire_meta('objets_versions', serialize(['articles']));
	}
	effacer_meta('articles_versions');
	if (!$versions = unserialize($GLOBALS['meta']['objets_versions'])) {
		$versions = [];
	}
	$versions = array_map('table_objet_sql', $versions);
	ecrire_meta('objets_versions', serialize($versions));
}
