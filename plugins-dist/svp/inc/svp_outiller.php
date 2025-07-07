<?php

/**
 * Fichier de fonctions
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Fonctions
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


if (!defined('_SVP_VERSION_SPIP_MIN')) {
	/**
	 * Version SPIP minimale quand un plugin ne le precise pas
	 *
	 * Version SPIP correspondant à l'apparition des plugins */
	define('_SVP_VERSION_SPIP_MIN', '1.9.0');
}

if (!defined('_SVP_VERSION_SPIP_MAX')) {
	/**
	 * Version SPIP maximale
	 *
	 * Pour l'instant on ne connait pas la borne sup exacte */
	define('_SVP_VERSION_SPIP_MAX', '4.4.999');
}

/**
 * Liste des branches significatives de SPIP et de leurs bornes (versions min et max)
 *
 * À mettre a jour en fonction des sorties
 *
 * @global array $GLOBALS ['infos_branches_spip']
 */
$GLOBALS['infos_branches_spip'] = [
	'1.9' => [_SVP_VERSION_SPIP_MIN, '1.9.2'],
	'2.0' => ['2.0.0', '2.0.26'],
	'2.1' => ['2.1.0', '2.1.30'],
	'3.0' => ['3.0.0', '3.0.28'],
	'3.1' => ['3.1.0', '3.1.999'],
	'3.2' => ['3.2.0', '3.2.999'],
	'3.3' => ['3.3.0-dev', '3.3.0-dev'],
	'4.0' => ['4.0.0-alpha', '4.0.999'],
	'4.1' => ['4.1.0-alpha', '4.1.999'],
	'4.2' => ['4.2.0-alpha', '4.2.999'],
	'4.3' => ['4.3.0-alpha', '4.3.999'],
	'4.4' => ['4.4.0-beta', _SVP_VERSION_SPIP_MAX],
];

/**
 * Liste des licences de plugin
 *
 * @global array $GLOBALS ['licences_plugin']
 */
$GLOBALS['licences_plugin'] = [
	'apache' => [
		'versions' => ['2.0', '1.1', '1.0'],
		'nom' => 'Apache licence, version @version@',
		'url' => 'http://www.apache.org/licenses/LICENSE-@version@'
	],
	'art' => [
		'versions' => ['1.3'],
		'nom' => 'Art libre @version@',
		'url' => 'http://artlibre.org/licence/lal'
	],
	'mit' => [
		'versions' => [],
		'nom' => 'MIT',
		'url' => 'http://opensource.org/licenses/mit-license.php'
	],
	'bsd' => [
		'versions' => [],
		'nom' => 'BSD',
		'url' => 'http://www.freebsd.org/copyright/license.html'
	],
	'agpl' => [
		'versions' => ['3'],
		'nom' => 'AGPL @version@',
		'url' => 'http://www.gnu.org/licenses/agpl.html'
	],
	'fdl' => [
		'versions' => ['1.3', '1.2', '1.1'],
		'nom' => 'FDL @version@',
		'url' => 'http://www.gnu.org/licenses/fdl-@version@.html'
	],
	'lgpl' => [
		'versions' => ['3.0', '2.1'],
		'nom' => ['3.0' => 'LGPL 3', '2.1' => 'LGPL 2.1'],
		'url' => 'http://www.gnu.org/licenses/lgpl-@version@.html'
	],
	'gpl' => [
		'versions' => ['3', '2', '1'],
		'nom' => 'GPL @version@',
		'url' => 'http://www.gnu.org/licenses/gpl-@version@.0.html'
	],
	'ccby' => [
		'versions' => ['2.0', '2.5', '3.0'],
		'suffixes' => ['-sa', '-nc', '-nd', '-nc-nd', '-nc-sa'],
		'nom' => 'CC BY@suffixe@ @version@',
		'url' => 'http://creativecommons.org/licenses/by@suffixe@/@version@/'
	]
];
# define('_LICENCES_PLUGIN', serialize($licences_plugin));

/**
 * Fusionne 2 intervalles de compatibilité
 *
 * Soit '[1.9;2.1]' et '[2.1;3.0.*]', la fonction retourne '[1.9;3.0.*]'
 *
 * En gros la fonction est utilisé pour calculer l'intervalle de validité
 * d'un plugin ayant plusieurs paquets avec des compatibilités différentes.
 * La compatibilité du plugin est le total de toutes les compatibilités.
 *
 * @uses  extraire_bornes()
 * @uses  construire_intervalle()
 * @param string $intervalle_a
 *     Intervalle de compatibilité
 * @param string $intervalle_b
 *     Intervalle de compatibilité
 * @return string
 *     Intervalle de compatibilité
 **/
function fusionner_intervalles($intervalle_a, $intervalle_b) {

	$bornes_fusionnees = [];
	// On recupere les bornes de chaque intervalle
	$borne_a = extraire_bornes($intervalle_a);
	$borne_b = extraire_bornes($intervalle_b);

	// On initialise la borne min de chaque intervalle a 1.9.0 inclus si vide
	if (!$borne_a['min']['valeur']) {
		$borne_a['min']['valeur'] = _SVP_VERSION_SPIP_MIN;
		$borne_a['min']['incluse'] = true;
	}
	if (!$borne_b['min']['valeur']) {
		$borne_b['min']['valeur'] = _SVP_VERSION_SPIP_MIN;
		$borne_b['min']['incluse'] = true;
	}

	// On initialise la borne max de chaque intervalle a la version SPIP max incluse si vide
	if (!$borne_a['max']['valeur']) {
		$borne_a['max']['valeur'] = _SVP_VERSION_SPIP_MAX;
		$borne_a['max']['incluse'] = true;
	}
	if (!$borne_b['max']['valeur']) {
		$borne_b['max']['valeur'] = _SVP_VERSION_SPIP_MAX;
		$borne_b['max']['incluse'] = true;
	}

	// On calcul maintenant :
	// -- la borne min de l'intervalle fusionne = min(min_a, min_b)
	if (spip_version_compare($borne_a['min']['valeur'], $borne_b['min']['valeur'], '<=')) {
		$bornes_fusionnees['min'] = $borne_a['min'];
	} else {
		$bornes_fusionnees['min'] = $borne_b['min'];
	}
	// -- la borne max de l'intervalle fusionne = max(max_a, max_b)
	if (spip_version_compare($borne_a['max']['valeur'], $borne_b['max']['valeur'], '<=')) {
		$bornes_fusionnees['max'] = $borne_b['max'];
	} else {
		$bornes_fusionnees['max'] = $borne_a['max'];
	}

	return construire_intervalle($bornes_fusionnees);
}

/**
 * Extrait les valeurs d'un intervalle de compatibilité.
 *
 * Calcule les valeurs min, max et si ces valeurs sont intégrées ou non
 * à l'intervalle.
 * Les bornes vides sont initialisées avec la borne min ou max de SPIP.
 * Les caractères * sont renvoyés tels quels.
 *
 * @param string $intervalle
 *     Intervalle de compatibilité, tel que '[2.1;3.0]'
 * @param bool $initialiser
 *     - True pour mettre les valeurs connues mini et maxi de SPIP lorsque
 *     les bornes ne sont pas renseignées dans l'intervalle.
 *     - False pour ne rien mettre sinon.
 * @return array
 *     Tableau avec les index :
 *     - min : la borne inférieure, qui contient les index 'valeur' et 'incluse'
 *     - max : la borne  supérieure, qui contient les index 'valeur' et 'incluse'
 *     Le sous index 'incluse' vaut true si cette borne est incluse dans l'intervalle.
 **/
function extraire_bornes($intervalle, $initialiser = false) {
	static $borne_vide = ['valeur' => '', 'incluse' => false];
	static $borne_inf_init = ['valeur' => _SVP_VERSION_SPIP_MIN, 'incluse' => true];
	static $borne_sup_init = ['valeur' => _SVP_VERSION_SPIP_MAX, 'incluse' => true];

	if ($initialiser) {
		$bornes = ['min' => $borne_inf_init, 'max' => $borne_sup_init];
	} else {
		$bornes = ['min' => $borne_vide, 'max' => $borne_vide];
	}

	if ($intervalle) {
		if (preg_match(',^[\[\(\]]([0-9.a-zRC\s\-]*)[;]([0-9.a-zRC\s\-\*]*)[\]\)\[]$,Uis', $intervalle, $matches)) {
			if ($matches[1]) {
				$bornes['min']['valeur'] = trim($matches[1]);
			}
			if ($matches[2]) {
				$bornes['max']['valeur'] = trim($matches[2]);
			}
		}
		$bornes['min']['incluse'] = (substr($intervalle, 0, 1) === '[');
		$bornes['max']['incluse'] = (substr($intervalle, -1) === ']');
	}

	return $bornes;
}

/**
 * Contruit un intervalle de compatibilité
 *
 * @param array $bornes
 *     L'intervalle décrit sous forme de tableau avec pour index :
 *     - min : la borne inférieure, qui contient les index 'valeur' et 'incluse'
 *     - max : la borne  supérieure, qui contient les index 'valeur' et 'incluse'
 *     Le sous index 'incluse' vaut true si cette borne est incluse dans l'intervalle.
 * @param string $dtd
 *     DTD de destination (paquet ou plugin) qui influera sur l'écriture à faire
 *     en utilisant des parenthèses ou des crochets pour définir l'exclusion d'une intervalle
 *     tel que ']2.1.2,3.0.1[' (paquet) ou '(2.1.2,3.0.1)' (plugin)
 * @return string
 *     Intervalle de compatibilité tel que '[2.1;3.0]'
 **/
function construire_intervalle($bornes, $dtd = 'paquet') {
	return ($bornes['min']['incluse'] ? '[' : ($dtd == 'paquet' ? ']' : '('))
	. $bornes['min']['valeur'] . ';' . $bornes['max']['valeur']
	. ($bornes['max']['incluse'] ? ']' : ($dtd == 'paquet' ? '[' : ')'));
}


/**
 * Retourne la liste des branches de SPIP comprises dans un intervalle
 * de compatibilité donné.
 *
 * La borne inférieure ne peut pas posséder le caractère `*` et peut être libellée x, x.y ou x.y.z (écriture recommandée).
 * La borne supérieure peut posséder des étoiles en y ou z.
 *
 * @uses  extraire_bornes()
 * @param string $intervalle
 *     Intervalle de compatibilité, tel que [2.0.0;3.0.0]
 * @return string
 *     Branches de SPIP séparées par des virgules, tel que 2.0,2.1,3.0
 **/
function compiler_branches_spip($intervalle) {
	include_spip('plugins/installer');

	global $infos_branches_spip;
	$liste_branches_spip = array_keys($GLOBALS['infos_branches_spip']);

	// On force l'initialisation des bornes et on les nettoie des suffixes d'etat
	$bornes = extraire_bornes($intervalle, true);
	// -- Si les bornes sont en dehors de l'intervalle [_SVP_VERSION_SPIP_MIN;_SVP_VERSION_SPIP_MAX] on le reduit
	//    en incluant forcément les bornes
	if (spip_version_compare($bornes['min']['valeur'], _SVP_VERSION_SPIP_MIN, '<')) {
		$bornes['min']['valeur'] = _SVP_VERSION_SPIP_MIN;
		$bornes['min']['incluse'] = true;
	}
	if (spip_version_compare(_SVP_VERSION_SPIP_MAX, $bornes['max']['valeur'], '<')) {
		$bornes['max']['valeur'] = _SVP_VERSION_SPIP_MAX;
		$bornes['max']['incluse'] = true;
	}
	// -- On extrait les bornes en gardant les suffixes éventuels :
	$borne_inf = $bornes['min']['valeur'];
	$borne_sup = $bornes['max']['valeur'];
	// -- la borne inféreieure ne peut jamais contenir "*"
	if (strpos($borne_inf, '*') !== false) {
		return '';
	}

	// Traitement de la borne inférieure
	// -- on initialise la branche inf de l'intervalle que l'on va preciser ensuite
	//    $borne_inf ne peut jamais être vide à ce stade car extraire_bornes() explicite toujours les bornes
	$t = explode('.', $borne_inf);

	// -- x existe toujours.
	//    Si le y est vide ou absent on complète la borne inf avec la première branche commençant par x
	//    car elle sont classées de la plus ancienne à la plus récente.
	if (isset($t[1])) {
		$branche_inf = $t[0] . '.' . $t[1];
	} else {
		$branche_inf = '';
		foreach ($liste_branches_spip as $branche_spip) {
			if (
				($b = explode('.', $branche_spip))
				and ($b[0] === $t[0])
			) {
				$branche_inf = $branche_spip;
				break;
			}
		}
	}

	// -- on extrait la branche et on vérifie si elle est bien référencée dans la liste SPIP.
	//    sinon, on renvoie vide car l'intervalle est en erreur
	$index_inf = array_search($branche_inf, $liste_branches_spip);
	if ($index_inf === false) {
		return '';
	}

	// -- si le z est vide ou absent on complète la borne inf de l'intervalle avec le min de la branche SPIP.
	if (!isset($t[2])) {
		$borne_inf = $infos_branches_spip[$branche_inf][0];
	}

	// -- Si la borne inf n'est pas incluse calcul de la vraie branche inf.
	if (!$bornes['min']['incluse']) {
		if ($borne_inf === $infos_branches_spip[$branche_inf][1]) {
			// alors on passe sur la branche suivante
			if (isset($liste_branches_spip[$index_inf + 1])) {
				$index_inf = $index_inf + 1;
				$branche_inf = $liste_branches_spip[$index_inf];
				$borne_inf = $infos_branches_spip[$branche_inf][0];
			} else {
				return '';
			}
		}
	}

	// Traitement de la borne supérieure
	// -- on initialise la branche sup de l'intervalle que l'on va preciser ensuite
	// $borne_inf ne peut jamais être vide à ce stade car extraire_bornes() explicite toujours les bornes
	$t = explode('.', $borne_sup);

	// -- Le x existe toujours
	//    si le x est une "*" alors on prend la branche correspondant à la version max de SPIP
	if ($t[0] === '*') {
		$branche_sup = $liste_branches_spip[array_key_last($liste_branches_spip)];
	} elseif (
		isset($t[1])
		and ($t[1] !== '*')
	) {
		$branche_sup = $t[0] . '.' . $t[1];
	} else {
		// -- Si le y est vide ou est une "*" on complète la borne sup avec la dernière branche commençant par x
		//	  car elle sont classées de la plus ancienne à la plus récente.
		$branche_sup = '';
		$nb_branches = count($liste_branches_spip);
		for ($i = $nb_branches - 1; $i >= 0 ;$i--) {
			if (
				($b = explode('.', $liste_branches_spip[$i]))
				and ($b[0] === $t[0])
			) {
				$branche_sup = $liste_branches_spip[$i];
				break;
			}
		}
	}

	// -- on extrait la branche et on vérifie si la branche est bien référencée dans la liste SPIP.
	//    sinon, on renvoie vide car l'intervalle est en erreur
	$index_sup = array_search($branche_sup, $liste_branches_spip);
	if ($index_sup === false) {
		return '';
	}

	// -- si le z est vide ou absent ou est une étoile, on complète la borne sup de l'intervalle avec le max de la branche SPIP.
	if (
		!isset($t[2])
		or ($t[2] === '*')
	) {
		$borne_sup = $infos_branches_spip[$branche_sup][1];
	}

	// -- Si la borne sup n'est pas incluse  calcul de la vraie branche sup.
	if (!$bornes['max']['incluse']) {
		if ($borne_sup === $infos_branches_spip[$branche_sup][0]) {
			// alors on passe sur la branche précente
			if (isset($liste_branches_spip[$index_sup - 1])) {
				$index_sup = $index_sup - 1;
				$branche_sup = $liste_branches_spip[$index_sup];
				$borne_sup = $infos_branches_spip[$branche_sup][1];
			} else {
				return '';
			}
		}
	}

	// -- on verifie que les bornes sont bien dans l'ordre :
	//    -> sinon on retourne la branche sup uniquement
	if (spip_version_compare($borne_inf, $borne_sup, '>=')) {
		return $branche_sup;
	}

	// A ce stade, on a un intervalle ferme en bornes ou en branches
	// Il suffit de trouver les branches qui y sont incluses, sachant que les branches inf et sup
	// le sont a coup sur maintenant
	$liste = [];
	for ($i = $index_inf; $i <= $index_sup; $i++) {
		$liste[] = $liste_branches_spip[$i];
	}

	return implode(',', $liste);
}


/**
 * Transforme un texte écrit en entités HTML, dans le charset du site
 *
 * @param string $texte
 *     Texte avec des entités HTML
 * @param string $charset
 * @return string $texte
 *     Texte dans le charset du site
 **/
function entite2charset($texte, $charset = null) {
	if (!strlen($texte)) {
		return '';
	}
	if (!$charset) {
		$charset = $GLOBALS['meta']['charset'];
	}
	include_spip('inc/charsets');

	return unicode2charset(html_entity_decode(preg_replace('/&([lg]t;)/S', '&amp;\1', $texte), ENT_NOQUOTES, $charset));
}

/**
 * Teste si 2 balises XML sont identiques
 *
 * @param array|string $balise1
 *     Balise à comparer
 * @param array|string $balise2
 *     Balise à comparer
 * @return bool
 *     True si elles sont identiques, false sinon.
 **/
function balise_identique($balise1, $balise2) {
	if (is_array($balise1)) {
		foreach ($balise1 as $_attribut1 => $_valeur1) {
			if (!array_key_exists($_attribut1, $balise2)) {
				return false;
			} else {
				if ($_valeur1 != $balise2[$_attribut1]) {
					return false;
				}
			}
		}

		return true;
	} else {
		return ($balise1 == $balise2);
	}
}


/**
 * Déterminer la licence exacte avec un nom et un lien de doc standardisé
 *
 * @param string $prefixe
 *     Préfixe de la licence tel que gnu, free, cc, creative common
 * @param string $nom
 *     Nom de la licence tel que gpl, lgpl, agpl, fdl, mit, bsd...
 * @param string $suffixe
 *     Suffixe de la licence tel que licence, -sharealike, -nc-nd ...
 * @param string $version
 *     Version de la licence tel que 3.0
 * @return array
 *     Si la licence est connu, retourne 2 index :
 *     - nom : le nom le la licence
 *     - url : lien vers la licence
 */
function definir_licence($prefixe, $nom, $suffixe, $version) {
	global $licences_plugin;
	$licence = [];

	$prefixe = strtolower($prefixe);
	$nom = strtolower($nom);
	$suffixe = strtolower($suffixe);

	if (
		((trim($prefixe) == 'creative common') and ($nom == 'attribution'))
		or (($prefixe == 'cc') and ($nom == 'by'))
	) {
		$nom = 'ccby';
	}

	if (array_key_exists($nom, $licences_plugin)) {
		if (!$licences_plugin[$nom]['versions']) {
			// La licence n'est pas versionnee : on affecte donc directement le nom et l'url
			$licence['nom'] = $licences_plugin[$nom]['nom'];
			$licence['url'] = $licences_plugin[$nom]['url'];
		} else {
			// Si la version est pas bonne on prend la plus recente
			if (!$version or !in_array($version, $licences_plugin[$nom]['versions'], true)) {
				$version = $licences_plugin[$nom]['versions'][0];
			}
			if (is_array($licences_plugin[$nom]['nom'])) {
				$licence['nom'] = $licences_plugin[$nom]['nom'][$version];
			} else {
				$licence['nom'] = str_replace('@version@', $version, $licences_plugin[$nom]['nom']);
			}
			$licence['url'] = str_replace('@version@', $version, $licences_plugin[$nom]['url']);

			if ($nom == 'ccby') {
				if ($suffixe == '-sharealike') {
					$suffixe = '-sa';
				}
				if (!$suffixe or !in_array($suffixe, $licences_plugin[$nom]['suffixes'], true)) {
					$suffixe = '';
				}
				$licence['nom'] = str_replace('@suffixe@', strtoupper($suffixe), $licence['nom']);
				$licence['url'] = str_replace('@suffixe@', $suffixe, $licence['url']);
			}
		}
	}

	return $licence;
}

/**
 * Liste les librairies présentes
 *
 * Cherche des librairie dans tous les dossiers 'lib' présents dans chaque
 * chemin déclaré (plugins, squelettes, SPIP). Un répertoire dans un dossier
 * 'lib' est considéré comme une librairie, et le nom de ce répertoire est
 * utilisé comme nom de la librairie.
 *
 * @return array
 *     Tableau de couples (nom de la librairie => répertoire de la librairie)
 **/
function svp_lister_librairies() {
	$libs = [];
	foreach (array_reverse(creer_chemin()) as $d) {
		if (is_dir($dir = $d . 'lib/') and $t = opendir($dir)) {
			while (($f = readdir($t)) !== false) {
				if ($f[0] != '.' and is_dir("$dir/$f")) {
					$libs[$f] = $dir;
				}
			}
		}
	}

	return $libs;
}


/**
 * Retourne '00x.00y.00z' à partir de 'x.y.z'
 *
 * Retourne la chaine de la version x.y.z sous une forme normalisée
 * permettant le tri naturel. On complète à gauche d'un nombre de zéro
 * manquant pour aller à 3 caractères entre chaque point.
 *
 * @see denormaliser_version()
 * @param string $version
 *     Numéro de version dénormalisée
 * @return string
 *     Numéro de version normalisée
 **/
function normaliser_version($version = '') {

	$version_normalisee = '';

	if (preg_match(',([0-9.]+)[\s.-]?(dev|alpha|a|beta|b|rc|pl|p)?,i', $version, $matches)) {
		if (isset($matches[1]) and $matches[1]) {
			$v = explode('.', $matches[1]);
			$vn = [];
			foreach ($v as $_nombre) {
				$vn[] = str_pad($_nombre, 3, '0', STR_PAD_LEFT);
			}
			$version_normalisee = implode('.', $vn);
			if (isset($matches[2]) and $matches[2]) {
				$version_normalisee = $version_normalisee . '-' . $matches[2];
			}
		}
	}

	return $version_normalisee;
}
