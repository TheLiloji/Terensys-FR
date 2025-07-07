<?php

/**
 * Traitement du dépot local
 *
 * Le dépot local est la liste de paquets qui sont présents sur l'hébergement
 * dans un des répertoires de plugins, actifs ou non actifs.
 *
 * Dans la base, un dépot local est représenté avec un id_dépot nul.
 * Il n'y a cependant pas de ligne spécifique décrivant le dépot local
 * dans la table SQL spip_depots, mais juste des valeurs id_depot=0 dans
 * la table spip_paquets.
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Depots
 */

/**
 * Met à jour les tables SQL paquets et plugins pour qui concerne
 * les paquets locaux (ceux présents sur le site).
 *
 * On ne met à jour que ce qui a changé, sauf si on force le recalcule
 * de toutes les informations locales avec var_mode=vider_paquets_locaux
 * dans l'URL ou en mettant le paramètre $force à true.
 *
 * @uses  svp_descriptions_paquets_locaux()
 * @uses  svp_base_supprimer_paquets_locaux()
 * @uses  svp_base_inserer_paquets_locaux()
 * @uses  svp_base_modifier_paquets_locaux()
 * @uses  svp_base_actualiser_paquets_actifs()
 *
 * @param bool $force
 *     - false : n'actualise que les paquets modifiés
 *     - true : efface et recrée la liste de tous les paquets locaux
 * @param array $erreurs_xml
 *     Si des erreurs XML sont présentes, elles se retrouvent dans ce tableau
 * @return string
 *     Temps d'exécution
 **/
function svp_actualiser_paquets_locaux($force = false, &$erreurs_xml = []) {

	spip_timer('paquets_locaux');
	$paquets = svp_descriptions_paquets_locaux($erreurs_xml);

	// un mode pour tout recalculer sans désinstaller le plugin... !
	if (
		$force
		or _request('var_mode') == 'vider_paquets_locaux'
		or _request('var_mode') == 'recalcul'
	) {
		svp_base_supprimer_paquets_locaux();
		svp_base_inserer_paquets_locaux($paquets);
	} else {
		svp_base_modifier_paquets_locaux($paquets);
	}
	svp_base_actualiser_paquets_actifs();

	$temps = spip_timer('paquets_locaux');
#spip_log('svp_actualiser_paquets_locaux', 'SVP');
#spip_log($temps, 'SVP');
	return 'Éxécuté en : ' . $temps;
}


/**
 * Calcule la description de chaque paquet local
 *
 * Les paquets peuvent être stockés à 3 endroits :
 * plugins, plugins-dist, plugins-supp définis par les constantes respectives
 * _DIR_PLUGINS, _DIR_PLUGINS_DIST, _DIR_PLUGINS_SUPP
 *
 * @param array $erreurs_xml
 *     Les erreurs XML éventuelles des paquet.xml se retrouvent dedans s'il y en a
 * @return array
 *     Descriptions des paquets (intégrant un hash), stockés par
 *     constante, puis par chemin.
 *     array[_DIR_PLUGIN*][$chemin] = description
 **/
function svp_descriptions_paquets_locaux(&$erreurs_xml = []) {
	include_spip('inc/plugin');
	liste_plugin_files(_DIR_PLUGINS);
	liste_plugin_files(_DIR_PLUGINS_DIST);
	$get_infos = charger_fonction('get_infos', 'plugins');
	$paquets_locaux = [
		'_DIR_PLUGINS' => $get_infos([], false, _DIR_PLUGINS),
		'_DIR_PLUGINS_DIST' => $get_infos([], false, _DIR_PLUGINS_DIST),
	];
	if (defined('_DIR_PLUGINS_SUPPL') and _DIR_PLUGINS_SUPPL) {
		liste_plugin_files(_DIR_PLUGINS_SUPPL);
		$paquets_locaux['_DIR_PLUGINS_SUPPL'] = $get_infos([], false, _DIR_PLUGINS_SUPPL);
	}

	// creer la liste des signatures
	foreach ($paquets_locaux as $const_dir => $paquets) {
		foreach ($paquets as $chemin => $paquet) {
			// on propose le paquet uniquement s'il n'y a pas eu d'erreur de lecture XML bloquante
			if (!isset($paquet['erreur'])) {
				$paquets_locaux[$const_dir][$chemin]['signature'] = md5($const_dir . $chemin . serialize($paquet));
			} else {
				// Erreur XML !
				unset($paquets_locaux[$const_dir][$chemin]);
				spip_log("Impossible de lire la description XML de $chemin . Erreurs :", 'svp.' . _LOG_ERREUR);
				spip_log($paquet['erreur'], 'svp.' . _LOG_ERREUR);
				$erreurs_xml[] = $paquet['erreur'][0];
			}
		}
	}

	return $paquets_locaux;
}


/**
 * Supprime tous les paquets et plugins locaux.
 **/
function svp_base_supprimer_paquets_locaux() {
	sql_delete('spip_paquets', 'id_depot = ' . 0); //_paquets locaux en 0

	$ids = sql_allfetsel('DISTINCT(id_plugin)', 'spip_paquets');
	$ids = array_column($ids, 'id_plugin');
	sql_delete('spip_plugins', sql_in('id_plugin', $ids, 'NOT'));
}


/**
 * Actualise les informations en base sur les paquets locaux
 * en ne modifiant que ce qui a changé.
 *
 * @uses  svp_supprimer_plugins_orphelins()
 * @uses  svp_base_inserer_paquets_locaux()
 *
 * @param array $paquets_locaux
 *     Descriptions des paquets (intégrant un hash), stockés par
 *     constante, puis par chemin.
 *     array[_DIR_PLUGIN*][$chemin] = description
 **/
function svp_base_modifier_paquets_locaux($paquets_locaux) {
	include_spip('inc/svp_depoter_distant');

	// On ne va modifier QUE les paquets locaux qui ont change
	// Et cela en comparant les md5 des informations fournies.
	$signatures = [];

	// recuperer toutes les signatures
	foreach ($paquets_locaux as $const_dir => $paquets) {
		foreach ($paquets as $chemin => $paquet) {
			$signatures[$paquet['signature']] = [
				'constante' => $const_dir,
				'chemin' => $chemin,
				'paquet' => $paquet,
			];
		}
	}

	// tous les paquets du depot qui ne font pas parti des signatures
	$anciens_paquets = sql_allfetsel(
		'id_paquet',
		'spip_paquets',
		['id_depot=' . sql_quote(0), sql_in('signature', array_keys($signatures), 'NOT')]
	);
	$anciens_paquets = array_column($anciens_paquets, 'id_paquet');

	// tous les plugins correspondants aux anciens paquets
	$anciens_plugins = sql_allfetsel(
		'p.id_plugin',
		['spip_plugins AS p', 'spip_paquets AS pa'],
		['p.id_plugin=pa.id_plugin', sql_in('pa.id_paquet', $anciens_paquets)]
	);
	$anciens_plugins = array_column($anciens_plugins, 'id_plugin');

	// suppression des anciens paquets
	sql_delete('spip_paquets', sql_in('id_paquet', $anciens_paquets));

	// supprimer les plugins orphelins
	svp_supprimer_plugins_orphelins($anciens_plugins);

	// on ne garde que les paquets qui ne sont pas presents dans la base
	$signatures_base = sql_allfetsel('signature', 'spip_paquets', 'id_depot=' . sql_quote(0));
	$signatures_base = array_column($signatures_base, 'signature');
	$signatures = array_diff_key($signatures, array_flip($signatures_base));

	// on recree la liste des paquets locaux a inserer
	$paquets_locaux = [];
	foreach ($signatures as $s => $infos) {
		if (!isset($paquets_locaux[$infos['constante']])) {
			$paquets_locaux[$infos['constante']] = [];
		}
		$paquets_locaux[$infos['constante']][$infos['chemin']] = $infos['paquet'];
	}

	svp_base_inserer_paquets_locaux($paquets_locaux);
}


/**
 * Insère en base tous les paquets locaux transmis
 *
 * De chaque description est extrait la partie plugin (1 seul plugin
 * par préfixe de plugin connu) et la partie paquet (il peut y avoir plusieurs
 * paquets pour un même préfixe de plugin).
 *
 * @note
 *     On essaie au mieux de faire des requêtes d'insertions multiples,
 *     mieux gérées par les moteurs SQL (particulièrement pour SQLite)
 *
 * @uses  plugins_preparer_sql_paquet()
 * @uses  svp_compiler_multis()
 * @uses  eclater_plugin_paquet()
 * @uses  svp_rechercher_maj_version()
 * @uses  svp_corriger_obsolete_paquets()
 *
 * @param array $paquets_locaux
 *     Descriptions des paquets (intégrant un hash), stockés par
 *     constante, puis par chemin.
 *     array[_DIR_PLUGIN*][$chemin] = description
 **/
function svp_base_inserer_paquets_locaux($paquets_locaux) {
	include_spip('inc/svp_depoter_distant');

	// On initialise les informations specifiques au paquet :
	// l'id du depot et les infos de l'archive
	$paquet_base = [
		'id_depot' => 0,
		'nom_archive' => '',
		'nbo_archive' => '',
		'maj_archive' => '0000-00-00 00:00:00',
		'src_archive' => '',
		'date_modif' => '0000-00-00 00:00:00',
		'maj_version' => '',
		'signature' => '',
	];

	$preparer_sql_paquet = charger_fonction('preparer_sql_paquet', 'plugins');

	// pour chaque decouverte, on insere les paquets en base.
	// on evite des requetes individuelles, tres couteuses en sqlite...
	$cle_plugins = []; // prefixe => id
	$insert_plugins = []; // insertion prefixe...
	$insert_plugins_vmax = []; // vmax des nouveaux plugins...
	$insert_paquets = []; // insertion de paquet...

	include_spip('inc/config');
	$recents = lire_config('plugins_interessants');
	$installes = lire_config('plugin_installes');
	$actifs = lire_config('plugin');
	$attentes = lire_config('plugin_attente');

	foreach ($paquets_locaux as $const_dir => $paquets) {
		foreach ($paquets as $chemin => $paquet) {
			// Si on est en presence d'un plugin dont la dtd est "paquet" on compile en multi
			// les nom, slogan et description a partir des fichiers de langue.
			// De cette façon, les informations des plugins locaux et distants seront identiques
			// => On evite l'utilisation de _T() dans les squelettes
			if ($paquet['dtd'] == 'paquet') {
				$multis = svp_compiler_multis($paquet['prefix'], constant($const_dir) . '/' . $chemin);
				if (isset($multis['nom'])) {
					$paquet['nom'] = $multis['nom'];
				}
				$paquet['slogan'] = $multis['slogan'] ?? '';
				$paquet['description'] = $multis['description'] ?? '';
			}

			// On met les neccesite, utilise, procure, dans la clé 0
			// pour être homogène avec le résultat d'une extraction de paquet xml
			// dans une source d'archives. cf svp_phraser_plugin()
			$paquet = svp_adapter_structure_dependances($paquet);


			$le_paquet = $paquet_base;
			#$le_paquet['traductions'] = serialize($paquet['traductions']);

			if ($champs = $preparer_sql_paquet($paquet)) {
				// Eclater les champs recuperes en deux sous tableaux, un par table (plugin, paquet)
				$champs = eclater_plugin_paquet($champs);

				// On complete les informations du paquet et du plugin
				$le_paquet = array_merge($le_paquet, $champs['paquet']);
				$le_plugin = $champs['plugin'];

				// Mettre le préfixe en majuscule si besoin
				$prefixe = strtoupper($le_plugin['prefixe']);

				// creation du plugin...
				// on fait attention lorqu'on cherche ou ajoute un plugin
				// le nom et slogan est TOUJOURS celui de la plus haute version
				// et il faut donc possiblement mettre a jour la base...
				//
				// + on est tolerant avec les versions identiques de plugin deja presentes
				//   on permet le recalculer le titre...
				if (!isset($cle_plugins[$prefixe])) {
					if (!$res = sql_fetsel('id_plugin, vmax', 'spip_plugins', 'prefixe = ' . sql_quote($prefixe))) {
						// on ne stocke pas de vmax pour les plugins locaux dans la bdd... (parait il)
						if (!isset($insert_plugins[$prefixe])) {
							$insert_plugins[$prefixe] = $le_plugin;
							$insert_plugins_vmax[$prefixe] = $le_paquet['version'];
						} elseif (spip_version_compare($le_paquet['version'], $insert_plugins_vmax[$prefixe], '>')) {
							$insert_plugins[$prefixe] = $le_plugin;
							$insert_plugins_vmax[$prefixe] = $le_paquet['version'];
						}
					} else {
						$id_plugin = $res['id_plugin'];
						$cle_plugins[$prefixe] = $id_plugin;
						// comme justement on ne stocke pas de vmax pour les plugins locaux...
						// il est possible que ce test soit faux. pff.
						if (spip_version_compare($le_paquet['version'], $res['vmax'], '>=')) {
							sql_updateq('spip_plugins', $le_plugin, 'id_plugin=' . sql_quote($id_plugin));
						}
					}
				}

				// ajout du prefixe dans le paquet
				$le_paquet['prefixe'] = $prefixe;
				$le_paquet['constante'] = $const_dir;
				$le_paquet['src_archive'] = $chemin;
				$le_paquet['recent'] = $recents[$chemin] ?? 0;
				$le_paquet['installe'] = is_array($chemin) && in_array(
					$chemin,
					$installes
				) ? 'oui' : 'non'; // est desinstallable ?
				$le_paquet['obsolete'] = 'non';
				$le_paquet['signature'] = $paquet['signature'];

				// le plugin est il actuellement actif ?
				$actif = 'non';
				if (
					isset($actifs[$prefixe])
					and ($actifs[$prefixe]['dir_type'] == $const_dir)
					and ($actifs[$prefixe]['dir'] == $chemin)
				) {
					$actif = 'oui';
				}
				$le_paquet['actif'] = $actif;

				// le plugin etait il actif mais temporairement desactive
				// parce qu'une dependence a disparue ?
				$attente = 'non';
				if (
					isset($attentes[$prefixe])
					and ($attentes[$prefixe]['dir_type'] == $const_dir)
					and ($attentes[$prefixe]['dir'] == $chemin)
				) {
					$attente = 'oui';
					$le_paquet['actif'] = 'oui'; // il est presenté dans la liste des actifs (en erreur).
				}
				$le_paquet['attente'] = $attente;

				// on recherche d'eventuelle mises a jour existantes
				if ($maj_version = svp_rechercher_maj_version($prefixe, $le_paquet['version'], $le_paquet['etatnum'], $le_paquet['compatibilite_spip'])) {
					$le_paquet['maj_version'] = $maj_version;
				}

				$insert_paquets[] = $le_paquet;
			}
		}
	}

	if ($insert_plugins) {
		sql_insertq_multi('spip_plugins', $insert_plugins);
		$pls = sql_allfetsel(['id_plugin', 'prefixe'], 'spip_plugins', sql_in('prefixe', array_keys($insert_plugins)));
		foreach ($pls as $p) {
			$cle_plugins[$p['prefixe']] = $p['id_plugin'];
		}
	}

	if ($insert_paquets) {
		// sert pour le calcul d'obsolescence
		$id_plugin_concernes = [];

		foreach ($insert_paquets as $c => $p) {
			$insert_paquets[$c]['id_plugin'] = $cle_plugins[$p['prefixe']];
			$id_plugin_concernes[$insert_paquets[$c]['id_plugin']] = true;
		}

		sql_insertq_multi('spip_paquets', $insert_paquets);

		svp_corriger_obsolete_paquets(array_keys($id_plugin_concernes));
	}
}


/**
 * Adapte la structure des dépendances d'un paquet xml lu par SPIP
 * à une structure attendue par SVP.
 *
 * C'est à dire, met les necessite, utilises, lib, procure dans une sous clé 0
 *
 * @note
 *     Cette clé 0 indique la description principale du paquet.xml
 *     mais d'autres clés semblent pouvoir exister
 *     si la balise `<spip>` est présente dedans
 *
 * @see svp_phraser_plugin() côté SVP
 * @see plugins_fusion_paquet() côté SVP
 *
 * @see plugins_get_infos_dist() côté SPIP (extractions de tous les paquets d'un dossier)
 *
 * @param array $paquet Description d'un paquet
 * @return array Description d'un paquet adaptée
**/
function svp_adapter_structure_dependances($paquet) {
	// mettre les necessite, utilise, librairie dans la cle 0
	foreach (['necessite', 'utilise', 'lib', 'procure'] as $dep) {
		if (!empty($paquet[$dep])) {
			$paquet[$dep] = [$paquet[$dep]];
		}
	}
	return $paquet;
}

/**
 * Fait correspondre l'état des métas des plugins actifs & installés
 * avec ceux en base de données dans spip_paquets pour le dépot local
 **/
function svp_base_actualiser_paquets_actifs() {
	$installes = lire_config('plugin_installes');
	$actifs = lire_config('plugin');
	$attentes = lire_config('plugin_attente');

	$locaux = sql_allfetsel(
		['id_paquet', 'prefixe', 'actif', 'installe', 'attente', 'constante', 'src_archive'],
		'spip_paquets',
		'id_depot=' . sql_quote(0)
	);
	$changements = [];

	foreach ($locaux as $l) {
		$copie = $l;
		$prefixe = strtoupper($l['prefixe']);
		// actif ?
		if (
			isset($actifs[$prefixe])
			and ($actifs[$prefixe]['dir_type'] == $l['constante'])
			and ($actifs[$prefixe]['dir'] == $l['src_archive'])
		) {
			$copie['actif'] = 'oui';
		} else {
			$copie['actif'] = 'non';
		}

		// attente ?
		if (
			isset($attentes[$prefixe])
			and ($attentes[$prefixe]['dir_type'] == $l['constante'])
			and ($attentes[$prefixe]['dir'] == $l['src_archive'])
		) {
			$copie['attente'] = 'oui';
			$copie['actif'] = 'oui'; // il est presente dans la liste des actifs (en erreur).
		} else {
			$copie['attente'] = 'non';
		}

		// installe ?
		if (in_array($l['src_archive'], $installes)) {
			$copie['installe'] = 'oui';
		} else {
			$copie['installe'] = 'non';
		}

		if ($copie != $l) {
			$changements[$l['id_paquet']] = [
				'actif' => $copie['actif'],
				'installe' => $copie['installe'],
				'attente' => $copie['attente']
			];
		}
	}

	if (count($changements)) {
		// On insere, en encapsulant pour sqlite...
		if (sql_preferer_transaction()) {
			sql_demarrer_transaction();
		}

		foreach ($changements as $id_paquet => $data) {
			sql_updateq('spip_paquets', $data, 'id_paquet=' . intval($id_paquet));
		}

		if (sql_preferer_transaction()) {
			sql_terminer_transaction();
		}
	}
}

/**
 * Construit le contenu multilangue (tag <multi>) des balises nom, slogan
 * et description à partir des items de langue contenus dans le fichier
 * paquet-prefixe_langue.php
 *
 * @param string $prefixe Préfixe du plugin
 * @param string $dir_source Chemin d'accès du plugin
 * @return array
 *     Tableau clé => texte multilangue entre <multi> et </multi>
 *     Les clés peuvent être 'nom', 'slogan' et 'description', mais
 *     seules les clés ayant une explication dans la chaine de langue
 *     sont retournées.
 */
function svp_compiler_multis($prefixe, $dir_source) {

	$multis = [];
	// ici on cherche le fichier et les cles avec un prefixe en minuscule systematiquement...
	$prefixe = strtolower($prefixe);
	$module = "paquet-$prefixe";
	$item_nom = $prefixe . '_nom';
	$item_slogan = $prefixe . '_slogan';
	$item_description = $prefixe . '_description';

	// On cherche tous les fichiers de langue destines a la traduction du paquet.xml
	if ($fichiers_langue = glob($dir_source . "/lang/{$module}_*.php")) {
		include_spip('inc/lang_liste');
		include_spip('inc/traduire');
		$nom = $slogan = $description = '';
		foreach ($fichiers_langue as $_fichier_langue) {
			$nom_fichier = basename($_fichier_langue, '.php');
			$langue = substr($nom_fichier, strlen($module) + 1 - strlen($nom_fichier));
			// Si la langue est reconnue, on traite la liste des items de langue
			if (isset($GLOBALS['codes_langues'][$langue])) {
				$trads = lire_fichier_langue($_fichier_langue);
				foreach ($trads as $_item => $_traduction) {
					if ($_traduction = trim($_traduction)) {
						if ($_item == $item_nom) {
							$nom .= "[$langue]$_traduction";
						}
						if ($_item == $item_slogan) {
							$slogan .= "[$langue]$_traduction";
						}
						if ($_item == $item_description) {
							$description .= "[$langue]$_traduction";
						}
					}
				}
			}
		}

		// Finaliser la construction des balises multi
		if ($nom) {
			$multis['nom'] = "<multi>$nom</multi>";
		}
		if ($slogan) {
			$multis['slogan'] = "<multi>$slogan</multi>";
		}
		if ($description) {
			$multis['description'] = "<multi>$description</multi>";
		}
	}

	return $multis;
}


/**
 * Met à jour les informations d'obsolescence des paquets locaux.
 *
 * L'obsolescence indique qu'un paquet est plus ancien (de version ou état
 * moins avancé) qu'un autre également présent localement.
 *
 * @param array $ids_plugin
 *     Liste d'identifiants de plugins
 *     En cas d'absence, passera sur tous les paquets locaux
 **/
function svp_corriger_obsolete_paquets($ids_plugin = []) {
	// on minimise au maximum le nombre de requetes.
	// 1 pour lister les paquets
	// 1 pour mettre à jour les obsoletes à oui
	// 1 pour mettre à jour les obsoletes à non

	$where = ['pa.id_plugin = pl.id_plugin', 'id_depot=' . sql_quote(0)];
	if ($ids_plugin) {
		$where[] = sql_in('pl.id_plugin', $ids_plugin);
	}

	// comme l'on a de nouveaux paquets locaux...
	// certains sont peut etre devenus obsoletes
	// parmis tous les plugins locaux presents
	// concernes par les memes prefixes que les plugins ajoutes.
	$obsoletes = [];
	$changements = [];

	$paquets = sql_allfetsel(
		['pa.id_paquet', 'pl.prefixe', 'pa.version', 'pa.etatnum', 'pa.obsolete', 'pa.compatibilite_spip'],
		['spip_paquets AS pa', 'spip_plugins AS pl'],
		$where
	);

	// L'obsolescence doit tenir compte de la compatibilité avec notre version de SPIP en cours
	foreach ($paquets as $c => $p) {
		$paquets[$c]['compatible'] = plugin_version_compatible(
			$p['compatibilite_spip'],
			$GLOBALS['spip_version_branche'],
			'spip'
		);
	}

	foreach ($paquets as $c => $p) {
		$obsoletes[$p['prefixe']][] = $c;

		// si 2 paquet locaux ont le meme prefixe,
		// mais pas la meme version,
		// sont compatibles avec notre SPIP,
		// l'un est obsolete : la version la plus ancienne
		// Si version et etat sont egaux, on ne decide pas d'obsolescence.
		if (count($obsoletes[$p['prefixe']]) > 1) {
			foreach ($obsoletes[$p['prefixe']] as $cle) {
				if ($cle == $c) {
					continue;
				}
				if (!$paquets[$c]['compatible']) {
					continue;
				}

				// je suis plus petit qu'un autre
				if (spip_version_compare($paquets[$c]['version'], $paquets[$cle]['version'], '<')) {
					if ($paquets[$c]['etatnum'] <= $paquets[$cle]['etatnum']) {
						if ($paquets[$c]['obsolete'] != 'oui') {
							$paquets[$c]['obsolete'] = 'oui';
							$changements[$c] = true;
						}
					}
				} // je suis plus grand ou egal a un autre...
				else {
					// je suis strictement plus grand qu'un autre...
					if (spip_version_compare($paquets[$c]['version'], $paquets[$cle]['version'], '>')) {
						// si mon etat est meilleur, rendre obsolete les autres
						if ($paquets[$c]['etatnum'] >= $paquets[$cle]['etatnum']) {
							if ($paquets[$cle]['obsolete'] != 'oui') {
								$paquets[$cle]['obsolete'] = 'oui';
								$changements[$cle] = true;
							}
						}
					}

					// je suis egal a un autre
					// si mon etat est strictement meilleur, rendre obsolete les autres
					elseif ($paquets[$c]['etatnum'] > $paquets[$cle]['etatnum']) {
						if ($paquets[$cle]['obsolete'] != 'oui') {
							$paquets[$cle]['obsolete'] = 'oui';
							$changements[$cle] = true;
						}
					}
				}
			}
		} else {
			if ($paquets[$c]['obsolete'] != 'non') {
				$paquets[$c]['obsolete'] = 'non';
				$changements[$c] = true;
			}
		}
	}

	if (count($changements)) {
		$oui = $non = [];
		foreach ($changements as $c => $null) {
			if ($paquets[$c]['obsolete'] == 'oui') {
				$oui[] = $paquets[$c]['id_paquet'];
			} else {
				$non[] = $paquets[$c]['id_paquet'];
			}
		}

		if ($oui) {
			sql_updateq('spip_paquets', ['obsolete' => 'oui'], sql_in('id_paquet', $oui));
		}
		if ($non) {
			sql_updateq('spip_paquets', ['obsolete' => 'non'], sql_in('id_paquet', $non));
		}
	}
}


/**
 * Supprime les plugins devenus orphelins dans cette liste.
 *
 * @param array $ids_plugin
 *     Liste d'identifiants de plugins
 * @return array
 *     Liste de plugins non orphelins
 **/
function svp_supprimer_plugins_orphelins($ids_plugin) {
	// tous les plugins encore lies a des depots...
	if ($ids_plugin) {
		$p = sql_allfetsel(
			'DISTINCT(p.id_plugin)',
			['spip_plugins AS p', 'spip_paquets AS pa'],
			[sql_in('p.id_plugin', $ids_plugin), 'p.id_plugin=pa.id_plugin']
		);
		$p = array_column($p, 'id_plugin');
		$diff = array_diff($ids_plugin, $p);
		// pour chaque plugin non encore utilise, on les vire !
		sql_delete('spip_plugins', sql_in('id_plugin', $diff));

		return $p; // les plugins encore en vie !
	}

	return [];
}


/**
 * Cherche dans les dépots distants un plugin qui serait plus à jour
 * que le prefixe, version et état que l'on transmet
 *
 * @param string $prefixe
 *    Préfixe du plugin
 * @param string $version
 *    Version du paquet à comparer
 * @param int $etatnum
 *    État du paquet numérique
 * @return string
 *    Version plus à jour, sinon rien
 **/
function svp_rechercher_maj_version($prefixe, $version, $etatnum, $compatibilite_spip = null) {

	$maj_version = '';
	$maj_etatnum = $etatnum;

	// si le plugin dont on recherche la maj n'est pas compatible, ne pas se limiter a la recherche d'un plugin aussi stable
	// on cherche avant tout quelque chose de compatible avec la version de SPIP qu'on utilise, le plus stable possible
	include_spip('inc/svp_rechercher');
	if (
		!is_null($compatibilite_spip)
		and !svp_verifier_compatibilite_spip($compatibilite_spip)
	) {
		$maj_etatnum = 0;
	}

	if (
		$res = sql_allfetsel(
			['pl.id_plugin', 'pa.version', 'pa.etatnum'],
			['spip_plugins AS pl', 'spip_paquets AS pa'],
			[
			'pl.id_plugin = pa.id_plugin',
			'pa.id_depot>' . sql_quote(0),
			'pl.prefixe=' . sql_quote($prefixe),
			'pa.etatnum>=' . sql_quote($maj_etatnum)
			],
			'',
			'pa.etatnum DESC'
		)
	) {
		foreach ($res as $paquet_distant) {
			// si version superieure et etat identique ou meilleur,
			// c'est que c'est une mise a jour possible !
			if (spip_version_compare($paquet_distant['version'], $version, '>')) {
				if (
					!strlen($maj_version)
					or (
						$paquet_distant['etatnum'] >= $maj_etatnum
						and spip_version_compare($paquet_distant['version'], $maj_version, '>')
					)
				) {
					$maj_version = $paquet_distant['version'];
					$maj_etatnum = $paquet_distant['etatnum'];
				}
				# a voir si on utilisera...
				# "superieur"		=> "varchar(3) DEFAULT 'non' NOT NULL",
				# // superieur : version plus recente disponible (distant) d'un plugin (actif?) existant
			}
		}
	}

	return $maj_version;
}


/**
 * Actualise l'information 'maj_version' pour tous les paquets locaux
 *
 * @uses  svp_rechercher_maj_version()
 **/
function svp_actualiser_maj_version() {
	$update = [];
	// tous les paquets locaux
	if (
		$locaux = sql_allfetsel(
			['id_paquet', 'prefixe', 'version', 'maj_version', 'etatnum', 'compatibilite_spip'],
			['spip_paquets'],
			['id_depot=' . sql_quote(0)]
		)
	) {
		foreach ($locaux as $paquet) {
			$new_maj_version = svp_rechercher_maj_version($paquet['prefixe'], $paquet['version'], $paquet['etatnum'], $paquet['compatibilite_spip']);
			if ($new_maj_version != $paquet['maj_version']) {
				$update[$paquet['id_paquet']] = ['maj_version' => $new_maj_version];
			}
		}
	}
	if ($update) {
		// On insere, en encapsulant pour sqlite...
		if (sql_preferer_transaction()) {
			sql_demarrer_transaction();
		}

		foreach ($update as $id_paquet => $data) {
			sql_updateq('spip_paquets', $data, 'id_paquet=' . intval($id_paquet));
		}

		if (sql_preferer_transaction()) {
			sql_terminer_transaction();
		}
	}
}
