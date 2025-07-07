<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use function SpipLeague\Component\Kernel\param;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
} // securiser

# donner un exemple d'url pour le formulaire de choix
defined('URLS_PROPRES_EXEMPLE') || define('URLS_PROPRES_EXEMPLE', 'Titre-de-l-article -Rubrique-');
# specifier le form de config utilise pour ces urls
defined('URLS_PROPRES_CONFIG') || define('URLS_PROPRES_CONFIG', 'propres');

// TODO: une interface permettant de verifier qu'on veut effectivment modifier
// une adresse existante
defined('CONFIRMER_MODIFIER_URL') || define('CONFIRMER_MODIFIER_URL', false);

/*

- Comment utiliser ce jeu d'URLs ?

Recopiez le fichier "htaccess.txt" du repertoire de base du site SPIP sous
le sous le nom ".htaccess" (attention a ne pas ecraser d'autres reglages
que vous pourriez avoir mis dans ce fichier) ; si votre site est en
"sous-repertoire", vous devrez aussi editer la ligne "RewriteBase" ce fichier.
Les URLs definies seront alors redirigees vers les fichiers de SPIP.

Dans les pages de configuration, choisissez 'propres' comme type d'url

SPIP calculera alors ses liens sous la forme
	"Mon-titre-d-article".

La variante 'propres2' ajoutera '.html' aux adresses generees :
	"Mon-titre-d-article.html"

Variante 'qs' (experimentale) : ce systeme fonctionne en "Query-String",
c'est-a-dire sans utilisation de .htaccess ; les adresses sont de la forme
	"/?Mon-titre-d-article"
*/

if (!defined('_terminaison_urls_propres')) {
	define('_terminaison_urls_propres', '');
}
if (!defined('_debut_urls_propres')) {
	define('_debut_urls_propres', '');
}

$config_urls_propres = isset($GLOBALS['meta']['urls_propres']) ? unserialize($GLOBALS['meta']['urls_propres']) : [];
// pour choisir le caractere de separation titre-id en cas de doublon
// (ne pas utiliser '/')
if (!defined('_url_propres_sep_id')) {
	define('_url_propres_sep_id', $config_urls_propres['url_propres_sep_id'] ?? '-');
}
// option pour tout passer en minuscules
if (!defined('_url_minuscules')) {
	define('_url_minuscules', $config_urls_propres['url_minuscules'] ?? 0);
}
if (!defined('_URLS_PROPRES_MAX')) {
	define('_URLS_PROPRES_MAX', $config_urls_propres['URLS_PROPRES_MAX'] ?? 80);
}
if (!defined('_URLS_PROPRES_MIN')) {
	define('_URLS_PROPRES_MIN', $config_urls_propres['URLS_PROPRES_MIN'] ?? 3);
}

if (!defined('_url_sep_id')) {
	define('_url_sep_id', \_url_propres_sep_id);
}

// Ces chaines servaient de marqueurs a l'epoque ou les URL propres devaient
// indiquer la table ou les chercher (articles, auteurs etc),
// et elles etaient retirees par les preg_match dans la fonction ci-dessous.
// Elles peuvent a present etre definies a "" pour avoir des URL plus jolies.
// Les preg_match restent necessaires pour gerer les anciens signets.

if (!defined('_MARQUEUR_URL')) {
	define('_MARQUEUR_URL', serialize([
		'rubrique1' => '-',
		'rubrique2' => '-',
		'breve1' => '+',
		'breve2' => '+',
		'site1' => '@',
		'site2' => '@',
		'auteur1' => '_',
		'auteur2' => '_',
		'mot1' => '+-',
		'mot2' => '-+'
	]));
}

// Retire les marqueurs de type dans une URL propre ancienne maniere

function retirer_marqueurs_url_propre($url_propre) {
	if (preg_match(',^[+][-](.*?)[-][+]$,', $url_propre, $regs)) {
		return $regs[1];
	} else {
		if (preg_match(',^([-+_@])(.*?)\1?$,', $url_propre, $regs)) {
			return $regs[2];
		}
	}

	// les articles n'ont pas de marqueur
	return $url_propre;
}


// Pipeline pour creation d'une adresse : il recoit l'url propose par le
// precedent, un tableau indiquant le titre de l'objet, son type, son id,
// et doit donner en retour une chaine d'url, sans se soucier de la
// duplication eventuelle, qui sera geree apres
function urls_propres_creer_chaine_url($x) {
	// NB: ici url_old ne sert pas, mais un plugin qui ajouterait une date
	// pourrait l'utiliser pour juste ajouter la
	$url_old = $x['data'];
	$objet = $x['objet'];
	include_spip('inc/filtres');

	include_spip('action/editer_url');
	if (
		!$url = url_nettoyer(
			$objet['titre'],
			_URLS_PROPRES_MAX,
			_URLS_PROPRES_MIN,
			'-',
			\_url_minuscules ? 'spip_strtolower' : ''
		)
	) {
		$url = $objet['type'] . $objet['id_objet'];
	}

	$x['data'] = $url;

	return $x;
}

/**
 * Trouver l'URL associee a la n-ieme cle primaire d'une table SQL
 *
 * @param string $type
 * @param int $id_objet
 * @return string|false
 */
function declarer_url_propre($type, $id_objet) {
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table(table_objet($type));
	// Quand $type ne reference pas une table
	if (!$desc) {
		return false;
	}
	$table = $desc['table'];
	$champ_titre = $desc['titre'] ?: 'titre';
	$col_id = $desc['key']['PRIMARY KEY'] ?? null;
	if (!$col_id) {
		return false;
	}


	$id_objet = intval($id_objet);


	// Recuperer une URL propre correspondant a l'objet.
	// mais urls a 1 segment uniquement (pas d'urls /)
	// de preference avec id_parent=0, puis perma, puis langue='' puis par date desc
	$row = sql_fetsel(
		"U.url, U.date, U.id_parent, U.perma, $champ_titre",
		"$table AS O LEFT JOIN spip_urls AS U ON (U.type='$type' AND U.id_objet=O.$col_id)",
		"O.$col_id=$id_objet AND (U.segments IS NULL OR U.segments=1)",
		'',
		'U.id_parent=0 DESC, U.perma DESC, U.langue=\'\' DESC, U.date DESC',
		1
	);

	// en SQLite le left join retourne du vide si il y a une url mais qui ne correspond pas pour la condition sur le segment
	// on verifie donc que l'objet existe bien avant de sortir ou de creer une url pour cet objet
	if (!$row) {
		$row = sql_fetsel(
			"'' as url, '' as date, 0 as id_parent, 0 as perma, $champ_titre",
			"$table AS O",
			"O.$col_id=$id_objet"
		);
	}

	if (!$row) {
		return '';
	} # Quand $id_objet n'est pas un numero connu

	$url_propre = $row['url'];

	// si url_propre connue mais avec id_parent non nul, essayer de reinserer tel quel avec id_parent=0
	if ($url_propre and $row['id_parent']) {
		include_spip('action/editer_url');
		$set = ['url' => $url_propre, 'type' => $type, 'id_objet' => $id_objet, 'perma' => $row['perma']];
		// si on arrive pas a reinserer tel quel, on annule url_propre pour forcer un recalcul d'url
		if (!url_insert($set, false, \_url_propres_sep_id)) {
			$url_propre = '';
		} else {
			$url_propre = $row['url'] = $set['url'];
		}
	}

	// Se contenter de cette URL si elle existe ;
	// sauf si on invoque par "voir en ligne" avec droit de modifier l'url

	// l'autorisation est verifiee apres avoir calcule la nouvelle url propre
	// car si elle ne change pas, cela ne sert a rien de verifier les autorisations
	// qui requetent en base
	$modifier_url = (defined('_VAR_URLS') and _VAR_URLS and !$row['perma']);
	if ($url_propre and !$modifier_url) {
		return $url_propre;
	}

	// Sinon, creer une URL
	$url = pipeline(
		'propres_creer_chaine_url',
		[
			'data' => $url_propre,  // le vieux url_propre
			'objet' => array_merge(
				$row,
				['type' => $type, 'id_objet' => $id_objet]
			)
		]
	);

	// Eviter de tamponner les URLs a l'ancienne (cas d'un article
	// intitule "auteur2")
	include_spip('inc/urls');
	$objets = urls_liste_objets();
	if (
		preg_match(',^(' . $objets . ')[0-9]+$,', $url, $r)
		and $r[1] != $type
	) {
		$url = $url . \_url_propres_sep_id . $id_objet;
	}

	// Pas de changement d'url
	if ($url == $url_propre) {
		return $url_propre;
	}

	// verifier l'autorisation, maintenant qu'on est sur qu'on va agir
	if ($modifier_url) {
		include_spip('inc/autoriser');
		$modifier_url = autoriser('modifierurl', $type, $id_objet);
	}

	// Verifier si l'utilisateur veut effectivement changer l'URL
	if (
		$modifier_url
		and CONFIRMER_MODIFIER_URL
		and $url_propre
		and $url != preg_replace('/' . preg_quote(\_url_propres_sep_id, '/') . '.*/', '', $url_propre)
	) {
		$confirmer = true;
	} else {
		$confirmer = false;
	}

	if ($confirmer and !_request('ok')) {
		die("vous changez d'url ? $url_propre -&gt; $url");
	}

	$set = ['url' => $url, 'type' => $type, 'id_objet' => $id_objet];
	include_spip('action/editer_url');
	if (!url_insert($set, $confirmer, \_url_propres_sep_id)) {
		return $url_propre;
	} //serveur out ? retourner au mieux

	return $set['url'];
}

/**
 * Generer l'url d'un objet SPIP
 * @param int $id
 * @param string $objet
 * @param string $args
 * @param string $ancre
 * @return string
 */
function urls_propres_generer_url_objet_dist(int $id, string $objet, string $args = '', string $ancre = ''): string {

	if ($generer_url_externe = charger_fonction_url($objet, 'defaut')) {
		$url = $generer_url_externe($id, $args, $ancre);
		// une url === null indique "je ne traite pas cette url, appliquez le calcul standard"
		// une url vide est une url vide, ne rien faire de plus
		if (!is_null($url)) {
			return $url;
		}
	}

	// Mode compatibilite pour conserver la distinction -Rubrique-
	if (_MARQUEUR_URL) {
		$marqueur = unserialize(_MARQUEUR_URL);
		$marqueur1 = $marqueur[$objet . '1'] ?? ''; // debut '+-'
		$marqueur2 = $marqueur[$objet . '2'] ?? ''; // fin '-+'
	} else {
		$marqueur1 = $marqueur2 = '';
	}
	// fin

	// Mode propre
	$propre = declarer_url_propre($objet, $id);

	if ($propre === false) {
		// objet inconnu. raccourci ?
		return '';
	}

	if ($propre) {
		$url = \_debut_urls_propres
			. $marqueur1
			. $propre
			. $marqueur2
			. \_terminaison_urls_propres;

		// les urls de type /1234 sont interpretees comme urls courte vers article 1234
		// on les encadre d'un - : /-1234-
		if (is_numeric($url)) {
			$url = '-' . $url . '-';
		}

		if (!defined('_SET_HTML_BASE') or !_SET_HTML_BASE) {
			// Repositionne l'URL par rapport a la racine du site (#GLOBALS)
			$url = str_repeat('../', $GLOBALS['profondeur_url']) . $url;
		} else {
			$url = _DIR_RACINE . $url;
		}
	} else {
		// objet connu mais sans possibilite d'URL lisible, revenir au defaut
		include_spip('base/connect_sql');
		$id_objet = id_table_objet($objet);
		$url = _DIR_RACINE . get_spip_script('./') . '?' . _SPIP_PAGE . "=$objet&$id_objet=$id";
	}

	// Ajouter les args
	if ($args) {
		$url .= ((strpos($url, '?') === false) ? '?' : '&') . $args;
	}

	// Ajouter l'ancre
	if ($ancre) {
		$url .= "#$ancre";
	}

	return $url;
}

/**
 * Decoder une url propres
 * retrouve le fond et les parametres d'une URL abregee
 * le contexte deja existant est fourni dans args sous forme de tableau
 *
 * @param string $url
 * @param string $entite
 * @param array $contexte
 * @return array([contexte],[type],[url_redirect],[fond]) : url decodee
 */
function urls_propres_decoder_url_dist(string $url, string $entite, array $contexte = []): array {

	$id_objet = $type = 0;
	$url_redirect = null;

	// Migration depuis anciennes URLs ?
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		$res = urls_transition_retrouver_anciennes_url_html($url, $entite, $contexte);
		if ($res) {
			return $res;
		}
	}
	/* Fin compatibilite anciennes urls */

	// Chercher les valeurs d'environnement qui indiquent l'url-propre
	$url_propre = preg_replace(',[?].*,', '', $url);

	// Mode Query-String ?
	$is_qs = false;
	if (
		!$url_propre
		and preg_match(',[?]([^=/?&]+)(&.*)?$,', $url, $r)
	) {
		$url_propre = $r[1];
		$is_qs = true;
	}

	if (
		!$url_propre
		or $url_propre == param('spip.routes.back_office')
		or $url_propre == _SPIP_SCRIPT
	) {
		// qu'est-ce qu'il veut ???
		return [];
	}


	// gerer le cas de retour depuis des urls arbos
	// mais si url arbo ne trouve pas, on veut une 404 par securite
	if ($GLOBALS['profondeur_url'] > 0 and !defined('_FORCE_URLS_PROPRES')) {
		$urls_anciennes = charger_fonction_url('decoder', 'arbo');

		return $urls_anciennes($url_propre, $entite, $contexte);
	}

	include_spip('base/abstract_sql'); // chercher dans la table des URLS

	// Compatibilite avec propres2
	$url_propre = preg_replace(',\.html$,i', '', $url_propre);

	// Revenir en utf-8 si encodage type %D8%A7 (farsi)
	$url_propre = rawurldecode($url_propre);

	// Compatibilite avec les anciens marqueurs d'URL propres
	// Tester l'entree telle quelle (avec 'url_libre' des sites ont pu avoir des entrees avec marqueurs dans la table spip_urls)
	if (!$row = sql_fetsel('id_objet, type, date, url', 'spip_urls', 'url=' . sql_quote($url_propre, '', 'TEXT'))) {
		// Sinon enlever les marqueurs eventuels
		$url_propre2 = retirer_marqueurs_url_propre($url_propre);

		$row = sql_fetsel('id_objet, type, date, url', 'spip_urls', 'url=' . sql_quote($url_propre2, '', 'TEXT'));
	}

	if ($row) {
		$type = $row['type'];
		$col_id = id_table_objet($type);
		$contexte[$col_id] = $row['id_objet'];
		$entite = $row['type'];

		// Si l'url est vieux, donner le nouveau
		if (
			$recent = declarer_url_propre($row['type'], $row['id_objet'])
			and $recent !== $row['url']
		) {
			// Mode compatibilite pour conserver la distinction -Rubrique-
			if (_MARQUEUR_URL) {
				$marqueur = unserialize(_MARQUEUR_URL);
				$marqueur1 = $marqueur[$type . '1']; // debut '+-'
				$marqueur2 = $marqueur[$type . '2']; // fin '-+'
			} else {
				$marqueur1 = $marqueur2 = '';
			}
			$url_redirect = $marqueur1 . $recent . $marqueur2;
		}
	}

	if ($entite == '' or $entite == 'type_urls' /* compat .htaccess 2.0 */) {
		if ($type) {
			$entite = objet_type($type);
		} else {
			// Si ca ressemble a une URL d'objet, ce n'est pas la home
			// et on provoque un 404
			if (preg_match(',^[^\.]+(\.html)?$,', $url)) {
				$entite = '404';
				$contexte['erreur'] = '';

				// l'url n'existe pas...
				// on ne sait plus dire de quel type d'objet il s'agit
				// sauf si on a le marqueur. et la c'est un peu sale...
				if (_MARQUEUR_URL) {
					$fmarqueur = @array_flip(unserialize(_MARQUEUR_URL));
					preg_match(',^([+][-]|[-+@_]),', $url_propre, $regs);
					$objet = ($regs ? substr($fmarqueur[$regs[1]], 0, -1) : 'article');
					$contexte['erreur'] = _T(
						($objet == 'rubrique' or $objet == 'breve')
							? 'public:aucune_' . $objet
							: 'public:aucun_' . $objet
					);
				}
			}
		}
	}

	return [$contexte, $entite, $url_redirect, $is_qs ? $entite : null];
}
