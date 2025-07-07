<?php

/**
 * Fichier permettant de calculer les données SQL à insérer
 * à partir d'une arbre de description originaire d'un plugin.xml
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Plugins
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Pour une description de plugin donnée (issue de la dtd de plugin.xml),
 * prépare les données à installer en bdd
 *
 * Les données sont parfois sérialisées, parfois transcodées, parfois compilées
 * pour tenir compte des spécificités de cette DTD et du stockage en bdd.
 *
 * @uses  compiler_branches_spip()
 * @param array $plugin
 *     Description de plugin
 * @return array
 *     Couples clés => valeurs de description du paquet
 **/
function plugins_preparer_sql_plugin($plugin) {
	$dependances = [];
	include_spip('inc/svp_outiller');

	$champs = [];
	if (!$plugin) {
		return $champs;
	}

	// On initialise les champs ne necessitant aucune transformation
	$champs['etat'] = (isset($plugin['etat']) and $plugin['etat']) ? $plugin['etat'] : '';
	$champs['version'] = $plugin['version'] ? normaliser_version($plugin['version']) : '';
	$champs['version_base'] = (isset($plugin['schema']) and $plugin['schema']) ? $plugin['schema'] : '';

	// Renommage de certains champs
	$champs['logo'] = (isset($plugin['logo']) and $plugin['logo']) ? $plugin['logo'] : '';
	$champs['lien_doc'] = (isset($plugin['documentation']) and $plugin['documentation']) ? normaliser_lien($plugin['documentation']) : '';
	// On passe le prefixe en lettres majuscules comme ce qui est fait dans SPIP
	// Ainsi les valeurs dans la table spip_plugins coincideront avec celles de la meta plugin
	$champs['prefixe'] = strtoupper($plugin['prefix']);

	// Indicateurs d'etat numerique (pour simplifier la recherche des maj de STP)
	static $num = ['stable' => 4, 'test' => 3, 'dev' => 2, 'experimental' => 1];
	$champs['etatnum'] = (isset($plugin['etat']) and isset($num[$plugin['etat']])) ? $num[$plugin['etat']] : 0;

	// On passe en utf-8 avec le bon charset les champs pouvant contenir des entites html
	$champs['description'] = entite2charset($plugin['description'], 'utf-8');

	// Traitement des auteurs, credits, licences et copyright
	// -- on extrait les auteurs, licences et copyrights sous forme de tableaux
	// -- depuis le commit 18294 du core la balise auteur est renvoyee sous forme de tableau mais
	//    contient toujours qu'un seul index
	$balise_auteur = entite2charset($plugin['auteur'][0], 'utf-8');
	$auteurs = normaliser_auteur_licence($balise_auteur, 'auteur');
	$balise_licence = isset($plugin['licence'][0]) ? entite2charset($plugin['licence'][0], 'utf-8') : '';
	$licences = normaliser_auteur_licence($balise_licence, 'licence');
	// -- on merge les tableaux recuperes dans auteur et licence
	$champs['auteur'] = $champs['licence'] = $champs['copyright'] = '';
	if ($t = array_merge($auteurs['auteur'], $licences['auteur'])) {
		$champs['auteur'] = serialize($t);
	}
	if ($t = array_merge($auteurs['licence'], $licences['licence'])) {
		$champs['licence'] = serialize($t);
	}
	if ($t = array_merge($auteurs['copyright'], $licences['copyright'])) {
		$champs['copyright'] = serialize($t);
	}

	// Extrait d'un nom et un slogan normalises
	// Slogan : si vide on ne fait plus rien de special, on traitera ça a l'affichage
	$champs['slogan'] = $plugin['slogan'] ? entite2charset($plugin['slogan'], 'utf-8') : '';
	// Nom :	on repere dans le nom du plugin un chiffre en fin de nom
	//			et on l'ampute de ce numero pour le normaliser
	//			et on passe tout en unicode avec le charset du site
	$champs['nom'] = trim(entite2charset($plugin['nom'], 'utf-8'));

	// Extraction de la compatibilite SPIP et construction de la liste des branches spip supportees
	$champs['compatibilite_spip'] = $plugin['compatibilite'] ?: '';
	$champs['branches_spip'] = ($plugin['compatibilite']) ? compiler_branches_spip($plugin['compatibilite']) : '';

	// Construction du tableau des dependances necessite, lib et utilise
	$dependances['necessite'] = $plugin['necessite'];
	$dependances['librairie'] = $plugin['lib'];
	$dependances['utilise'] = $plugin['utilise'];
	$champs['dependances'] = serialize($dependances);

	// Calculer le champ 'procure' (tableau sérialisé prefixe => version)
	$champs['procure'] = '';
	if (!empty($plugin['procure'][0])) {
		$champs['procure'] = [];
		foreach ($plugin['procure'][0] as $procure) {
			$p = strtoupper($procure['nom']);
			if (
				!isset($champs['procure'][$p])
				or spip_version_compare($procure['version'], $champs['procure'][$p], '>')
			) {
				$champs['procure'][$p] = $procure['version'];
			}
		}
		$champs['procure'] = serialize($champs['procure']);
	}

	// Champs non supportes par la DTD plugin et ne pouvant etre deduits d'autres balises
	$champs['lien_demo'] = '';
	$champs['lien_dev'] = '';
	$champs['credit'] = '';

	return $champs;
}

/**
 * Normalise un lien issu d'un plugin.xml
 *
 * Éliminer les textes superflus dans les liens (raccourcis [XXX->http...])
 * et normaliser l'esperluete pour éviter l'erreur d'entité indéfinie
 *
 * @param string $url
 *     URL à normaliser
 * @return string
 *     URL normalisée
 */
function normaliser_lien($url) {
	if (!preg_match(',https?://[^]\s]+,', $url, $r)) {
		return '';
	}
	$url = str_replace('&', '&amp;', str_replace('&amp;', '&', $r[0]));

	return $url;
}


/**
 * Normalise un auteur ou une licence issue d'un plugin.xml
 *
 * - Élimination des multi (exclus dans la nouvelle version)
 * - Transformation en attribut des balises A
 * - Interprétation des balises BR et LI et de la virgule et du
 *   espace+tiret comme séparateurs
 *
 * @uses  _RACCOURCI_LIEN
 *
 * @param string $texte
 *     Texte de la balise
 * @param string $balise
 *     Nom de la balise (auteur | licence)
 * @return array
 *     Tableau listant les auteurs, licences et copyright trouvés
 */
function normaliser_auteur_licence($texte, $balise) {
	include_spip('inc/filtres');
	include_spip('inc/lien');
	include_spip('inc/svp_outiller');


	// Regexp des raccourcis, aussi utilisee pour la fusion de sauvegarde Spip
	// Laisser passer des paires de crochets pour la balise multi
	// mais refuser plus d'imbrications ou de mauvaises imbrications
	// sinon les crochets ne peuvent plus servir qu'a ce type de raccourci
	// @deprecated
	defined('_RACCOURCI_LIEN') || define('_RACCOURCI_LIEN', '/\[([^][]*?([[][^]>-]*[]][^][]*)*)->(>?)([^]]*)\]/msS');


	// On extrait le multi si besoin et on selectionne la traduction francaise
	$t = normaliser_multi($texte);

	$res = ['auteur' => [], 'licence' => [], 'copyright' => []];
	foreach (preg_split('@(<br */?>)|<li>|,|\s-|\n_*\s*|&amp;| & | et @', $t[_LANGUE_PAR_DEFAUT]) as $v) {
		// On detecte d'abord si le bloc texte en cours contient un eventuel copyright
		// -- cela generera une balise copyright et non auteur
		$copy = '';
		if (preg_match('/(?:\&#169;|¬©|copyright|\(c\)|&copy;)[\s:]*([\d-]+)/i', $v, $r)) {
			$copy = trim($r[1]);
			$v = str_replace($r[0], '', $v);
			$res['copyright'][] = $copy;
		}

		// On detecte ensuite un lien eventuel d'un auteur
		// -- soit sous la forme d'une href d'une ancre
		// -- soit sous la forme d'un raccourci SPIP
		// Dans les deux cas on garde preferentiellement le contenu de l'ancre ou du raccourci
		// si il existe
		$href = $mail = '';
		if (preg_match('@<a[^>]*href=(\W)(.*?)\1[^>]*>(.*?)</a>@', $v, $r)) {
			$href = $r[2];
			$v = str_replace($r[0], $r[3], $v);
		} elseif (preg_match(_RACCOURCI_LIEN, $v, $r)) {
			if (preg_match('/([^\w\d._-]*)(([\w\d._-]+)@([\w\d.-]+))/', $r[4], $m)) {
				$mail = $r[4];
			} else {
				$href = $r[4];
			}
			$v = $r[1] ?: str_replace($r[0], '', $v);
		} else {
			$href = '';
		}

		// On detecte ensuite un mail eventuel
		if (!$mail and preg_match('/([^\w\d._-]*)(([\w\d._-]+)@([\w\d.-]+))/', $v, $r)) {
			$mail = $r[2];
			$v = str_replace($r[2], '', $v);
			if (!$v) {
				// On considere alors que la premiere partie du mail peut faire office de nom d'auteur
				if (preg_match('/(([\w\d_-]+)[.]([\w\d_-]+))@/', $r[2], $s)) {
					$v = ucfirst($s[2]) . ' ' . ucfirst($s[3]);
				} else {
					$v = ucfirst($r[3]);
				}
			}
		}

		// On detecte aussi si le bloc texte en cours contient une eventuelle licence
		// -- cela generera une balise licence et non auteur
		//    cette heuristique n'est pas deterministe car la phrase de licence n'est pas connue
		$licence = [];
		if (
			preg_match(
				'/\b((gnu|free|creative\s+common|cc)*[\/|\s|-]*(apache|lgpl|agpl|gpl|fdl|mit|bsd|art\s+|attribution|by)(\s+licence|\-sharealike|-nc-nd|-nc-sa|-sa|-nc|-nd)*\s*v*(\d*[\.\d+]*))\b/i',
				$v,
				$r
			)
		) {
			if ($licence = definir_licence($r[2], $r[3], $r[4], $r[5])) {
				$res['licence'][] = $licence;
			}
		}

		// On finalise la balise auteur ou licence si on a pas trouve de licence prioritaire
		if ($href) {
			$href = !preg_match(',https?://,', $href, $matches) ? 'http://' . $href : $href;
		}
		$v = trim(textebrut($v));
		if ((strlen($v) > 2) and !$licence) {
			if ($balise == 'auteur') {
				$res['auteur'][] = ['nom' => $v, 'url' => $href, 'mail' => $mail];
			} else {
				$res['licence'][] = ['nom' => $v, 'url' => $href];
			}
		}
	}

	return $res;
}


/**
 * Expanse les multi en un tableau de textes complets, un par langue
 *
 * @param string $texte
 *     Le texte
 * @return array
 *     Texte expansé par code de langue : couples (code de langue => texte)
 */
function normaliser_multi($texte) {

	include_spip("src/Texte/Collecteur/AbstractCollecteur");
	include_spip("src/Texte/Collecteur/Multis");
	$collecteurMultis = new Spip\Texte\Collecteur\Multis();
	$multis = $collecteurMultis->collecter($texte);

	if (!$multis) {
		return [_LANGUE_PAR_DEFAUT => $texte];
	}

	$trads = [];
	foreach ($multis as $multi) {
		foreach ($multi['trads'] as $k => $v) {
			// Si le code de langue n'est pas précisé dans le multi c'est donc la langue par défaut
			$lang = $k ?: _LANGUE_PAR_DEFAUT;
			$trads[$lang] = str_replace($multi['raw'], $v, $trads[$lang] ?? $texte);
		}
	}

	return $trads;
}
