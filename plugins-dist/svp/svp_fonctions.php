<?php

/**
 * Déclarations de fonctions
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Fonctions
 **/

function svp_importer_charset($texte) {
	if ($GLOBALS['meta']['charset'] == 'utf-8') {
		return $texte;
	}

	return importer_charset($texte, 'utf-8');
}

/**
 * Retourne un texte expliquant l'intervalle de compatibilité avec un plugin ou SPIP
 *
 * Retourne par exemple "2.0 <= SPIP < 3.1"
 *
 * @param string $intervalle
 *     L'intervalle tel que déclaré dans paquet.xml. Par exemple "[2.1;3.0.*]"
 * @param string $logiciel
 *     Nom du plugin pour qui est cette intervalle
 * @return string
 *     Texte expliquant l'intervalle
 **/
function svp_afficher_intervalle($intervalle, $logiciel) {
	if (!strlen($intervalle)) {
		return '';
	}
	if (!defined('_EXTRAIRE_INTERVALLE')) {
		include_spip('inc/plugin');
	}
	if (!preg_match(_EXTRAIRE_INTERVALLE . 'Uis', $intervalle, $regs)) {
		return false;
	}

	$mineure = $regs[1];
	$majeure = preg_replace(',\.999$,', '.*', $regs[2]);
	$mineure_inc = $intervalle[0] == '[';
	$majeure_inc = substr($intervalle, -1) == ']';
	if (strlen($mineure)) {
		if (!strlen($majeure)) {
			$version = _T('svp:info_logiciel_version', [
				'logiciel' => $logiciel,
				'signe'    => ($mineure_inc ? '&ge;' : '&gt;'),
				'version'  =>  $mineure
			]);
		} else {
			$version = _T('svp:info_logiciel_version_intervalle', [
				'logiciel' => $logiciel,
				'signe_min' => ($mineure_inc ? '&ge;' : '&gt;'),
				'version_min' => $mineure,
				'signe_max' => ($majeure_inc ? '&le;' : '&lt;'),
				'version_max' => $majeure,
			]);
		}
	} else {
		if (!strlen($majeure)) {
			$version = $logiciel;
		} else {
			$version = _T('svp:info_logiciel_version', [
				'logiciel' => $logiciel,
				'signe' => ($majeure_inc ? '&le;' : '&lt;'),
				'version' => $majeure,
			]);
		}
	}

	return $version;
}

/**
 * Traduit un type d'état de plugin
 *
 * Si l'état n'existe pas, prendra par défaut 'developpement'
 *
 * @see plugin_etat_en_clair()
 * @param string $etat
 *     Le type d'état (stable, test, ...)
 * @return string
 *     Traduction de l'état dans la langue en cours
 **/
function svp_afficher_etat($etat) {
	include_spip('plugins/afficher_plugin');

	return plugin_etat_en_clair($etat);
}

/**
 * Retourne un texte HTML présentant la liste des dépendances d'un plugin
 *
 * Des liens vers les plugins dépendants sont présents lorsque les plugins
 * en dépendance sont connus dans notre base.
 *
 * @uses svp_afficher_intervalle()
 * @param string $balise_serialisee
 *     Informations des dépendances (tableau sérialisé) tel que stocké
 *     en base dans la table spip_paquets
 * @param string $dependance
 *     Type de dépendances à afficher (necessite ou utilise).
 *     Une autre valeur indique qu'on demande la liste des librairies dépendantes.
 * @param string $sep
 *     Séparateur entre les noms de dépendances
 * @param string $lien
 *     Type de lien affecté au plugin référencé dans la base locale. Prend les valeurs :
 *
 *    - local : le lien pointe vers la page publique du plugin sur le site lui-même. Il faut
 * donc que le site propose des pages publiques pour les plugins sinon une 404 sera affichée;
 *    - pluginspip : le lien pointe vers la page du plugin sur le site de référence Plugins SPIP;
 *    - non : aucun lien n'est affiché.
 * @return string
 *     Texte informant des dépendances
 **/
function svp_afficher_dependances($balise_serialisee, $dependance = 'necessite', $sep = '<br />', $lien = 'local') {
	$texte = '';

	$t = unserialize($balise_serialisee);
	$dependances = $t[$dependance];
	if (is_array($dependances)) {
		ksort($dependances);

		foreach ($dependances as $_compatibilite => $_dependance) {
			$compatibilite = ($_compatibilite !== 0)
				? _T(
					'svp:info_compatibilite_dependance',
					['compatibilite' => svp_afficher_intervalle($_compatibilite, 'SPIP')]
				)
				: '';
			if ($compatibilite) {
				$texte .= ($texte ? str_repeat($sep, 2) : '') . $compatibilite;
			}
			foreach ($_dependance as $_plugin) {
				if ($texte) {
					$texte .= $sep;
				}
				if (($dependance == 'necessite') or ($dependance == 'utilise')) {
					if ($plugin = sql_fetsel('id_plugin, nom', 'spip_plugins', 'prefixe=' . sql_quote($_plugin['nom']))) {
						$nom = extraire_multi($plugin['nom']);
						if ($lien == 'non') {
							$logiciel = $nom;
						} else {
							$url = ($lien == 'local')
								? generer_objet_url($plugin['id_plugin'], 'plugin')
								: "https://plugins.spip.net/{$_plugin['nom']}.html";
							$bulle = _T('svp:bulle_aller_plugin');
							$logiciel = '<a href="' . $url . '" title="' . $bulle . '">' . $nom . '</a>';
						}
					} else {
						// Cas ou le plugin n'est pas encore dans la base SVP.
						// On affiche son préfixe, cependant ce n'est pas un affichage devant perdurer
						$logiciel = $_plugin['nom'];
					}
					$intervalle = '';
					if (isset($_plugin['compatibilite'])) {
						$intervalle = svp_afficher_intervalle($_plugin['compatibilite'], $logiciel);
					}
					$texte .= $intervalle ?: $logiciel;
				} else // On demande l'affichage des librairies
				{
					$texte .= '<a href="' . $_plugin['lien'] . '" title="' . _T('svp:bulle_telecharger_librairie') . '">' . $_plugin['nom'] . '</a>';
				}
			}
		}
	}

	return $texte;
}


/**
 * Retourne un texte HTML présentant les crédits d'un plugin
 *
 * Des liens vers les crédits sont présents lorsqu'ils sont déclarés
 * dans le paquet.xml.
 *
 * @param string $balise_serialisee
 *     Informations des crédits (tableau sérialisé) tel que stocké
 *     en base dans la table spip_paquets
 * @param string $sep
 *     Séparateur entre les différents crédits
 * @return string
 *     Texte informant des crédits
 **/
function svp_afficher_credits($balise_serialisee, $sep = ', ') {
	$texte = '';

	$credits = unserialize($balise_serialisee);
	if (is_array($credits)) {
		foreach ($credits as $_credit) {
			if ($texte) {
				$texte .= $sep;
			}
			// Si le credit en cours n'est pas un array c'est donc un copyright
			$texte .=
				(!is_array($_credit))
					? PtoBR(propre($_credit)) // propre pour les [lien->url] des auteurs de plugin.xml ...
					: ($_credit['url'] ? '<a href="' . $_credit['url'] . '">' : '') .
					$_credit['nom'] .
					($_credit['url'] ? '</a>' : '');
		}
	}

	return $texte;
}


/**
 * Retourne un texte HTML présentant la liste des langues et traducteurs d'un plugin
 *
 * Des liens vers les traducteurs sont présents lorsqu'ils sont connus.
 *
 * @param array $langues
 *     Tableau code de langue => traducteurs
 * @param string $sep
 *     Séparateur entre les différentes langues
 * @return string
 *     Texte informant des langues et traducteurs
 **/
function svp_afficher_langues($langues, $sep = ', ') {
	$texte = '';

	if ($langues) {
		foreach ($langues as $_code => $_traducteurs) {
			if ($texte) {
				$texte .= $sep;
			}
			$traducteurs_langue = [];
			foreach ($_traducteurs as $_traducteur) {
				if (is_array($_traducteur)) {
					$traducteurs_langue[] =
						($_traducteur['lien'] ? '<a href="' . $_traducteur['lien'] . '">' : '') .
						$_traducteur['nom'] .
						($_traducteur['lien'] ? '</a>' : '');
				}
			}
			$texte .= $_code . (count($traducteurs_langue) > 0 ? ' (' . implode(', ', $traducteurs_langue) . ')' : '');
		}
	}

	return $texte;
}

/**
 * Traduit un type de dépot de plugin
 *
 * @param string $type
 *     Type de dépot (svn, git, manuel)
 * @return string
 *     Titre complet et traduit du type de dépot
 **/
function svp_traduire_type_depot($type) {

	$traduction = '';
	if ($type) {
		$traduction = _T('svp:info_type_depot_' . $type);
	}

	return $traduction;
}

/**
 * Calcule l'url exacte d'un lien de démo en fonction de son écriture
 *
 * @param string $url_demo
 *     URL de démonstration telle que saisie dans le paquet.xml
 * @param boolean $url_absolue
 *     Indique que seules les url absolues doivent être retournées par la fonction.
 *     Tous les autres types d'url renvoient une chaine vide
 * @return string
 *     URL calculée en fonction de l'URL d'entrée
 **/
function svp_calculer_url_demo($url_demo, $url_absolue = false) {

	$url_calculee = '';
	$url_demo = trim($url_demo);
	if (strlen($url_demo) > 0) {
		$url_elements = @parse_url($url_demo);
		if (isset($url_elements['scheme']) and $url_elements['scheme']) {
			// Cas 1 : http://xxxx. C'est donc une url absolue que l'on conserve telle qu'elle.
			$url_calculee = $url_demo;
		} else {
			if (!$url_absolue) {
				if (isset($url_elements['query']) and $url_elements['query']) {
					// Cas 2 : ?exec=xxx ou ?page=yyy. C'est donc une url relative que l'on transforme
					// en url absolue privée ou publique en fonction de la query.
					$egal = strpos($url_elements['query'], '=');
					$page = substr($url_elements['query'], $egal + 1, strlen($url_elements['query']) - $egal - 1);
					if (strpos($url_elements['query'], 'exec=') !== false) {
						$url_calculee = generer_url_ecrire($page);
					} else {
						$url_calculee = generer_url_public($page);
					}
				} elseif (isset($url_elements['path']) and $url_elements['path']) {
					// Cas 3 : xxx/yyy. C'est donc une url relative que l'on transforme
					$url_calculee = generer_url_public($url_demo);
				}
			}
		}
	}

	return $url_calculee;
}

/**
 * Critère de compatibilité avec une version précise ou une branche de SPIP ou une liste de branches séparées par
 * une virgule.
 *
 * Fonctionne sur les tables spip_paquets et spip_plugins.
 * Si aucune valeur n'est explicité dans le critère on interroge le contexte pour trouver une variable
 * compatible_spip et sinon tous les objets sont retournés.
 *
 * Le ! (NOT) ne fonctionne que sur une branche ou une liste de branches SPIP.
 *
 * @critere
 * @example
 *   {compatible_spip}
 *   {compatible_spip 2.0.8} ou {compatible_spip 1.9}
 *   {compatible_spip #ENV{vers}} ou {compatible_spip #ENV{vers, 1.9.2}}
 *   {compatible_spip #GET{vers}} ou {compatible_spip #GET{vers, 2.1}}
 *   {compatible_spip '2.0,2.1'}
 *   {!compatible_spip 2.0}
 *   {!compatible_spip '2.0,2.1'}
 *   {!compatible_spip #ENV{vers}} ou {!compatible_spip #GET{vers}}
 *
 * @param string $idb Identifiant de la boucle
 * @param array $boucles AST du squelette
 * @param Critere $crit Paramètres du critère dans cette boucle
 *
 * @return void
 */
function critere_compatible_spip_dist($idb, &$boucles, $crit) {

	// Initialisation de la table (spip_plugins ou spip_paquets)
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;

	// Initialisation du numéro de critère pour utiliser plusieurs fois le critère dans la même boucle
	static $i = 1;

	// La fonction LOCATE retourne soit 0 (pas trouvée) soit une valeur strictement supérieure à 0 (trouvée).
	// Donc avec le modificateur not l'opérateur est un "=", sinon un ">".
	// Le NOT s'utilise uniquement avec une branche SPIP (ex 2.0).
	$op = ($crit->not == '!') ? '=' : '>';

	// On calcule le code des critères.
	// -- Initialisation avec le chargement de la fonction de calcul du critère.
	$boucle->hash .= '
	// COMPATIBILITE SPIP
	$creer_where = charger_fonction(\'where_compatible_spip\', \'inc\');';

	// On traite le critère suivant que la version ou la branche est explicitement fournie ou pas.
	if (!empty($crit->param)) {
		// La ou les versions/branches sont explicites dans l'appel du critere.
		// - on boucle sur les paramètres sachant qu'il est possible de fournir une liste séparée par une virgule
		//   (ex 3.2,3.1)
		foreach ($crit->param as $_param) {
			if (isset($_param[0])) {
				$version = calculer_liste([$_param[0]], [], $boucles, $boucle->id_parent);
				$boucle->hash .= '
				$where' . $i . ' = $creer_where(' . $version . ', \'' . $table . '\', \'' . $op . '\');
				';
				$boucle->where[] = '$where' . $i;
				$i++;
			}
		}
	} else {
		// Pas de version ou de branche explicite dans l'appel du critere.
		// - on regarde si elle est dans le contexte
		$boucle->hash .= '
		$version = isset($Pile[0][\'compatible_spip\']) ? $Pile[0][\'compatible_spip\'] : \'\';
		$where' . $i . '  = $creer_where($version, \'' . $table . '\', \'' . $op . '\');
		';
		$boucle->where[] = '$where' . $i;
		$i++;
	}
}

/**
 * Retourne la liste des plugins trouvés par une recherche
 *
 * @filtre
 * @param string $phrase
 *     Texte de la recherche
 * @param string $etat
 *     État de plugin (stable, test...)
 * @param string|int $depot
 *     Identifiant de dépot
 * @param bool|string $afficher_exclusions
 *     Afficher aussi les paquets déjà installés (true ou 'oui')
 *     ou ceux qui ne le sont pas (false) ?
 * @param bool|string $afficher_doublons
 *     Afficher toutes les versions de paquet (true ou 'oui')
 *     ou seulement la plus récente (false) ?
 * @return array
 *     Tableau classé par pertinence de résultat
 *     - 'prefixe' => tableau de description du paquet (si pas de doublons demandé)
 *     - n => tableau de descriptions du paquet (si doublons autorisés)
 **/
function filtre_construire_recherche_plugins(
	$phrase = '',
	$etat = '',
	$depot = '',
	$afficher_exclusions = true,
	$afficher_doublons = false
) {

	// On traite les paramètres d'affichage
	$afficher_exclusions = ($afficher_exclusions == 'oui') ? true : false;
	$afficher_doublons = ($afficher_doublons == 'oui') ? true : false;

	$tri = ($phrase) ? 'score' : 'nom';
	$version_spip = $GLOBALS['spip_version_branche'] . '.' . $GLOBALS['spip_version_code'];

	// On recupere la liste des paquets:
	// - sans doublons, ie on ne garde que la version la plus recente
	// - correspondant a ces criteres
	// - compatible avec la version SPIP installee sur le site
	// - et n'etant pas deja installes (ces paquets peuvent toutefois etre affiches)
	// tries par nom ou score
	include_spip('inc/svp_rechercher');
	$plugins = svp_rechercher_plugins_spip(
		$phrase,
		$etat,
		$depot,
		$version_spip,
		svp_lister_plugins_installes(),
		$afficher_exclusions,
		$afficher_doublons,
		$tri
	);

	return $plugins;
}

/**
 * Retourne le nombre d'heures entre chaque actualisation
 * si le cron est activé.
 *
 * @return int
 *     Nombre d'heures (sinon 0)
 **/
function filtre_svp_periode_actualisation_depots() {
	include_spip('genie/svp_taches_generales_cron');

	return _SVP_CRON_ACTUALISATION_DEPOTS ? _SVP_PERIODE_ACTUALISATION_DEPOTS : 0;
}


/**
 * Retourne 'x.y.z' à partir de '00x.00y.00z'
 *
 * Retourne la chaine de la version x.y.z sous sa forme initiale,
 * sans remplissage à gauche avec des 0.
 *
 * @see normaliser_version()
 * @param string $version_normalisee
 *     Numéro de version normalisée
 * @return string
 *     Numéro de version dénormalisée
 **/
function denormaliser_version($version_normalisee = '') {

	$version = '';
	if ($version_normalisee) {
		$v = explode('.', $version_normalisee);
		foreach ($v as $_nombre) {
			$n = ltrim($_nombre, '0');
			// On traite les cas du type 001.002.000-dev qui doivent etre transformes en 1.2.0-dev.
			// Etant donne que la denormalisation est toujours effectuee sur une version normalisee on sait
			// que le suffixe commence toujours pas '-'
			$vn[] = ((strlen($n) > 0) and substr($n, 0, 1) != '-') ? $n : "0$n";
		}
		$version = implode('.', $vn);
	}

	return $version;
}

/**
 * Teste l'utilisation du répertoire auto des plugins.
 *
 * Ce répertoire permet de télécharger dedans des plugins
 * lorsqu'il est présent.
 *
 * @return bool
 *     Le répertoire de chargement des plugins auto est-il présent
 *     et utilisable ?
 */
function test_plugins_auto() {
	static $test = null;
	if (is_null($test)) {
		include_spip('inc/plugin'); // pour _DIR_PLUGINS_AUTO
		$test = (defined('_DIR_PLUGINS_AUTO') and _DIR_PLUGINS_AUTO and is_writable(_DIR_PLUGINS_AUTO));
	}

	return $test;
}

/**
 * Compare 2 numéros de versions et indique le composant principal qui change : X, Y ou Z.
 *
 * @note
 * Ne regarde pas si une version est plus récente qu'une autre, donne juste la différence.
 *
 * @note
 * - X = version majeure
 * - Y = version mineure
 * - Z = patch
 *
 * @see https://semver.org/
 *
 * @param string $version1
 *     Numéro de version initial
 * @param string $version2
 *     Numéro de version à comparer
 * @return string
 *     x | y | z
 *     ou une chaîne vide si pas de différence ou erreur.
 */
function filtre_svp_diff_xyz($version1, $version2) {
	$diff = '';
	$versions = [$version1, $version2];

	// Compléter les numéros si nécessaire : 1.0 → 1.0.0
	foreach ($versions as $k => $version) {
		if ($nb = count(explode('.', $version)) < 3) {
			$versions[$k] = $version . str_repeat('.0', 3 - $nb);
		}
	}

	[$x1, $y1, $z1] = explode('.', $versions[0]);
	[$x2, $y2, $z2] = explode('.', $versions[1]);

	if ($x1 != $x2) {
		$diff = 'x';
	} elseif ($y1 != $y2) {
		$diff = 'y';
	} elseif ($z1 != $z2) {
		$diff = 'z';
	}

	return $diff;
}

/**
 * Appel a un pipeline pour filtrer eventuellement l'affichage de certains plugins
 * (ie si on a zen-garden actif, les themes ne sont plus affiches dans la gestion des plugins, mais dans le zen garden)
 *
 * @param int $id_paquet
 * @return bool
 */
function filtre_svp_affichage_filtrer_paquets_dist(int $id_paquet): bool {
	$affiche = pipeline('svp_afficher_paquet', [
		'args' => ['id_paquet' => $id_paquet],
		'data' => true
	]);

	return ($affiche ? true : false);
}
