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

include_spip('base/abstract_sql');
include_spip('inc/modeles');
include_spip('inc/liens');

/**
 * Production de la balise a+href à partir des raccourcis `[xxx->url]` etc.
 *
 * @note
 *     Compliqué car c'est ici qu'on applique typo(),
 *     et en plus, on veut pouvoir les passer en pipeline
 *
 * @see typo()
 * @param string $lien
 * @param string $texte
 * @param string $class
 * @param string $title
 * @param string $hlang
 * @param string $rel
 * @param string $connect
 * @param array $env
 * @return string
 */
function inc_lien_dist(
	$lien,
	$texte = '',
	$class = '',
	$title = '',
	$hlang = '',
	$rel = '',
	$connect = '',
	$env = []
) {
	$mime = null;
	static $u = null;
	if (!$u) {
		$u = url_de_base();
	}
	$typo = false;

	// Si une langue est demandee sur un raccourci d'article, chercher
	// la traduction ;
	// - [{en}->art2] => traduction anglaise de l'article 2, sinon art 2
	// - [{}->art2] => traduction en langue courante de l'art 2, sinon art 2
	// s'applique a tout objet traduit
	if (
		$hlang
		and $match = typer_raccourci($lien)
	) {
		[$type, , $id, , $args, , $ancre] = array_pad($match, 7, null);
		$trouver_table = charger_fonction('trouver_table', 'base');
		$desc = $trouver_table(table_objet($type, $connect), $connect);
		if (
			$desc
			and $id_table_objet = $desc['key']['PRIMARY KEY']
		) {
			$table_objet_sql = $desc['table'];
			if (
				$row = sql_fetsel('*', $table_objet_sql, "$id_table_objet=" . intval($id))
				and isset($row['id_trad'])
				and isset($row['lang'])
				and $id_dest = sql_getfetsel(
					$id_table_objet,
					$table_objet_sql,
					'id_trad=' . intval($row['id_trad']) . ' AND lang=' . sql_quote($hlang)
				)
				and objet_test_si_publie($type, $id_dest)
			) {
				$lien = "$type$id_dest";
			} else {
				$hlang = '';
			}
		} else {
			$hlang = '';
		}
	}

	$mode = ($texte and $class) ? 'url' : 'tout';
	$lang = '';
	$lien = calculer_url($lien, $texte, $mode, $connect);
	if ($mode === 'tout') {
		$texte = $lien['titre'];
		if (!$class and isset($lien['class'])) {
			$class = $lien['class'];
		}
		$lang = $lien['lang'] ?? '';
		$mime = isset($lien['mime']) ? " type='" . $lien['mime'] . "'" : '';
		$lien = $lien['url'];
	}

	$lien = trim($lien);
	if (strncmp($lien, '#', 1) == 0) {  # ancres pures (internes a la page)
		$class = 'spip_ancre';
	} elseif (strncasecmp($lien, 'mailto:', 7) == 0) { # pseudo URL de mail
		$class = 'spip_mail';
	} elseif (strncmp($texte, '<html>', 6) == 0) { # cf traiter_lien_explicite
		$class = 'spip_url';
		# spip_out sur les URLs externes
		if (lien_is_url_externe($lien)) {
			$class .= ' spip_out';
		}
	} elseif (!$class) {
		# spip_out sur les URLs externes
		if (lien_is_url_externe($lien)) {
			$class = 'spip_out';
		}
	}
	if ($class) {
		$class = " class=\"$class\"";
	}

	// Si l'objet n'est pas de la langue courante, on ajoute hreflang
	if (!$hlang and isset($lang) and $lang !== $GLOBALS['spip_lang']) {
		$hlang = $lang;
	}

	$lang = ($hlang ? " hreflang=\"$hlang\"" : '');

	if ($title) {
		$title = ' title="' . attribut_html($title) . '"';
	} else {
		$title = ''; // $title peut etre 'false'
	}

	// rel=external pour les liens externes
	if (
		(strncmp($lien, 'http://', 7) == 0 or strncmp($lien, 'https://', 8) == 0)
		and strncmp("$lien/", $u, strlen($u)) != 0
	) {
		$rel = trim("$rel external");
	}
	if ($rel) {
		$rel = " rel=\"$rel\"";
	}

	$lang_objet_prev = '';
	if ($hlang and $hlang !== $GLOBALS['spip_lang']) {
		$lang_objet_prev = $GLOBALS['lang_objet'] ?? null;
		$GLOBALS['lang_objet'] = $hlang;
	}

	// si pas de modele dans le texte du lien, on peut juste passer typo sur le texte, c'est plus rapide
	// les rares cas de lien qui encapsule un modele passe en dessous, c'est plus lent
	include_spip("src/Texte/Collecteur/AbstractCollecteur");
	include_spip("src/Texte/Collecteur/Modeles");
	$collecteurModeles = new Spip\Texte\Collecteur\Modeles();
	if (!$collecteurModeles->detecter($texte)) {
		$texte = typo($texte, true, $connect, $env);
		$lien = '<a href="' . str_replace(
			'"',
			'&quot;',
			$lien
		) . "\"$class$lang$title$rel" . ($mime ?? '') . ">$texte</a>";
		if ($lang_objet_prev !== '') {
			if ($lang_objet_prev) {
				$GLOBALS['lang_objet'] = $lang_objet_prev;
			} else {
				unset($GLOBALS['lang_objet']);
			}
		}

		return $lien;
	}

	# ceci s'execute heureusement avant les tableaux et leur "|".
	# Attention, le texte initial est deja echappe mais pas forcement
	# celui retourne par calculer_url.
	# Penser au cas [<imgXX|right>->URL], qui exige typo('<a>...</a>')
	$lien = '<a href="' . str_replace('"', '&quot;', $lien) . "\"$class$lang$title$rel$mime>$texte</a>";
	#$res = typo($lien, true, $connect, $env);
	$p = $GLOBALS['toujours_paragrapher'];
	$GLOBALS['toujours_paragrapher'] = false;
	$res = propre($lien, $connect, $env);
	$GLOBALS['toujours_paragrapher'] = $p;

	// dans ce cas, echapons le resultat du modele pour que propre etc ne viennent pas pouicher le html
	$res = echappe_html("<html>$res</html>");
	if ($lang_objet_prev !== '') {
		if ($lang_objet_prev) {
			$GLOBALS['lang_objet'] = $lang_objet_prev;
		} else {
			unset($GLOBALS['lang_objet']);
		}
	}

	return $res;
}

/**
 * Detecter qu'une URL est externe pour poser une class en fonction
 */
function lien_is_url_externe(?string $url_lien): bool {
	if ($url_lien === null or $url_lien === '') {
		return false;
	}
	if (
		preg_match(',^\w+://,iS', $url_lien)
		and strncasecmp($url_lien, url_de_base(), strlen(url_de_base()))
	) {
		return true;
	}

	return false;
}

/**
 * Générer le HTML d'un lien quelconque
 *
 * Cette fonction génère une balise `<a>` suivant de multiples arguments.
 *
 * @param array $args
 *   Tableau des arguments disponibles pour générer le lien :
 *   - texte : texte du lien, seul argument qui n'est pas un attribut
 *   - href
 *   - name
 *   - etc, tout autre attribut supplémentaire…
 * @return string
 *   Retourne une balise HTML de lien ou une chaîne vide.
 */
function balise_a($args = []) {
	$balise_a = '';

	// Il faut soit au minimum un href OU un name pour réussir à générer quelque chose
	if (is_array($args) and (isset($args['href']) or isset($args['name']))) {
		include_spip('inc/filtres');
		$texte = '';

		// S'il y a un texte, on le récupère et on l'enlève des attributs
		if (isset($args['texte']) and is_scalar($args['texte'])) {
			$texte = $args['texte'];
			unset($args['texte']);
		} // Si on a un href sans texte, on en construit un avec l'URL
		elseif (isset($args['href']) and is_scalar($args['href'])) {
			static $lien_court;
			if (!$lien_court) {
				$lien_court = charger_fonction('lien_court', 'inc');
			}
			$texte = quote_amp($lien_court($args['href']));
		}

		// Il ne reste normalement plus que des attributs, on les ajoute à la balise
		$balise_a = '<a';
		foreach ($args as $attribut => $valeur) {
			if (is_scalar($valeur) and !empty($valeur)) {
				$balise_a .= ' ' . $attribut . '="' . attribut_html($valeur) . '"';
			}
		}
		// Puis on ajoute le texte
		$balise_a .= '>' . $texte . '</a>';
	}

	return $balise_a;
}

function expanser_liens($t, $connect = '', $env = []) {
	$t = pipeline('pre_liens', $t);

	include_spip("src/Texte/Collecteur/AbstractCollecteur");
	include_spip("src/Texte/Collecteur/Liens");

	$collecteurLiens = new Spip\Texte\Collecteur\Liens();
	$hasLiens = $collecteurLiens->detecter($t);
	if ($hasLiens) {
		$t = $collecteurLiens->echapper($t);
	}

	// on passe a traiter_modeles le collecteur de liens reperes pour lui permettre
	// de remettre le texte d'origine dans les parametres du modele
	$t = traiter_modeles($t, false, false, $connect ?? '', $hasLiens ? $collecteurLiens : null, $env);

	$t = corriger_typo($t);

	// traiter les liens si il y en avait
	if ($hasLiens) {
		$t = $collecteurLiens->retablir($t);
		$liens = $collecteurLiens->collecter($t);

		if (!empty($liens)) {
			$lien = charger_fonction('lien', 'inc');

			$offset_pos = 0;
			foreach ($liens as $l) {
				[$titre, $bulle, $hlang] = traiter_raccourci_lien_atts($l['texte']);

				// corrigeons pour eviter d'avoir un <a...> dans un href...
				$href = $l['href'];
				$balise_lien = $lien($href, $titre, '', $bulle, $hlang, '', $connect, $env);

				$t = substr_replace($t, $balise_lien, $l['pos'] + $offset_pos, $l['length']);
				$offset_pos += strlen($balise_lien) - $l['length'];
			}
		}

	}

	return $t;
}


/**
 * Nettoie un texte en enlevant les raccourcis typo, sans les traiter
 *
 * On ne laisse que les titres des liens, en les explicitant si ce n’est pas fait.
 *
 * @param string $texte
 * @param string $connect
 * @return string
 */
function nettoyer_raccourcis_typo($texte, $connect = '') {
	$texte = pipeline('nettoyer_raccourcis_typo', $texte);

	// on utilise les \r pour passer entre les gouttes
	$texte = str_replace("\r\n", "\n", $texte);
	$texte = str_replace("\r", "\n", $texte);

	// sauts de ligne et paragraphes
	$texte = preg_replace("/\n\n+/", "\r", $texte);

	// supprimer les traits, lignes etc
	$texte = preg_replace("/(^|\r|\n)(-[-#\*]*\s?|_ )/", "\n", $texte);

	// travailler en accents charset
	$texte = unicode2charset(html2unicode($texte, true /* secure */));

	include_spip("src/Texte/Collecteur/AbstractCollecteur");
	include_spip("src/Texte/Collecteur/Liens");
	$collecteurLiens = new Spip\Texte\Collecteur\Liens();
	$liens = $collecteurLiens->collecter($texte);
	if (!empty($liens)) {
		$offset_pos = 0;
		foreach ($liens as $l) {
			[$titre, , ] = traiter_raccourci_lien_atts($l['texte']);
			if (!$titre) {
				$match = typer_raccourci($l['href']);
				if (!isset($match[0])) {
					$match[0] = '';
				}
				[$type, , $id, , , , ] = array_pad($match, 7, null);

				if ($type) {
					$url = generer_objet_url($id, $type, '', '', true);
					if (is_array($url)) {
						[$type, $id] = $url;
					}
					$titre = traiter_raccourci_titre($id, $type, $connect);
				}
				$titre = $titre ? $titre['titre'] : $match[0];
			}
			$titre = corriger_typo(supprimer_tags($titre));

			$texte = substr_replace($texte, $titre, $l['pos'] + $offset_pos, $l['length']);
			$offset_pos += strlen($titre) - $l['length'];
		}
	}

	// supprimer les ancres
	$texte = preg_replace(_RACCOURCI_ANCRE, '', $texte);

	// supprimer les notes
	$texte = preg_replace(',\[\[.*\]\],UimsS', '', $texte);

	// supprimer les codes typos
	$texte = str_replace(['}', '{'], '', $texte);

	// supprimer les tableaux
	$texte = preg_replace(",(?:^|\r|\n)\|.*\|(?:\r|\n|$),s", "\r", $texte);

	// indiquer les sauts de paragraphes
	$texte = str_replace("\r", "\n\n", $texte);
	$texte = str_replace("\n\n+", "\n\n", $texte);

	$texte = trim($texte);

	return $texte;
}


// Repere dans la partie texte d'un raccourci [texte->...]
// la langue et la bulle eventuelles : [texte|title{lang}->...]
// accepte un niveau de paire de crochets dans le texte :
// [texte[]|title{lang}->...]
// mais refuse
// [texte[|title{lang}->...]
// pour ne pas confondre avec un autre raccourci
define('_RACCOURCI_ATTRIBUTS', '/^((?:[^[]*?(?:\[[^]]*\])?)*?)([|]([^<>]*?))?([{]([a-z_]*)[}])?$/');

function traiter_raccourci_lien_atts($texte) {
	$bulle = $hlang = false;

	// title et hreflang donnes par le raccourci ?
	if (
		strpbrk($texte, '|{') !== false
		and preg_match(_RACCOURCI_ATTRIBUTS, $texte, $m)
	) {
		$n = count($m);

		// |infobulle ?
		if ($n > 2) {
			$bulle = $m[3];

			// {hreflang} ?
			if ($n > 4) {
				// si c'est un code de langue connu, on met un hreflang
				if (traduire_nom_langue($m[5]) <> $m[5]) {
					$hlang = $m[5];
				} elseif (!$m[5]) {
					$hlang = test_espace_prive() ?
						$GLOBALS['lang_objet'] : $GLOBALS['spip_lang'];
					// sinon c'est un italique ou un gras dans le title ou dans le texte du lien
				} else {
					if ($bulle) {
						$bulle .= $m[4];
					} else {
						$m[1] .= $m[2] . $m[4];
					}
				}
			}
			// S'il n'y a pas de hreflang sous la forme {}, ce qui suit le |
			// est peut-etre une langue
			else {
				if (preg_match('/^[a-z_]+$/', $m[3])) {
					// si c'est un code de langue connu, on met un hreflang
					// mais on laisse le title (c'est arbitraire tout ca...)
					if (traduire_nom_langue($m[3]) <> $m[3]) {
						$hlang = $m[3];
					}
				}
			}
		}
		$texte = $m[1];
	}

	if ($bulle) {
		$bulle = nettoyer_raccourcis_typo($bulle);
		$bulle = corriger_typo($bulle);
	}

	return [trim($texte), $bulle, $hlang];
}

define('_EXTRAIRE_DOMAINE', '/^(?:(?:[^\W_]((?:[^\W_]|-){0,61}[^\W_,])?\.)+[a-z0-9]{2,6}|localhost)\b/Si');
define('_RACCOURCI_CHAPO', '/^(\W*)(\W*)(\w*\d+([?#].*)?)$/');

/**
 * Retourne la valeur d'un champ de redirection (articles virtuels)
 *
 * L'entrée accepte plusiers types d'écritures :
 * - une URL compète,
 * - un lien SPIP tel que `[Lien->article23]`,
 * - ou un raccourcis SPIP comme `rub2` ou `rubrique2`
 *
 * @param string $virtuel
 *     Texte qui définit la redirection, à analyser.
 *     Plusieurs types peuvent être acceptés :
 *     - un raccourci Spip habituel, tel que `[texte->TYPEnnn]`
 *     - un ultra raccourci Spip, tel que `TYPEnnn`
 *     - une URL standard
 * @param bool $url
 *     false : retourne uniquement le nom du lien (TYPEnnn)
 *     true : retourne l'URL calculée pour le lien
 * @return string
 *     Nom du lien ou URL
 */
function virtuel_redirige($virtuel, $url = false) {
	if (!strlen($virtuel)) {
		return '';
	}

	if (preg_match(_RACCOURCI_CHAPO, $virtuel, $m)) {
		return !$url ? $m[3] : traiter_lien_implicite($m[3]);
	}

	include_spip("src/Texte/Collecteur/AbstractCollecteur");
	include_spip("src/Texte/Collecteur/Liens");
	$collecteurLiens = new Spip\Texte\Collecteur\Liens();
	if ($liens = $collecteurLiens->collecter($virtuel)) {
		$lien = reset($liens);
		return !$url ? $lien['href'] : traiter_lien_implicite($lien['href']);
	}

	return $virtuel;

}


// Cherche un lien du type [->raccourci 123]
// associe a une fonction generer_url_raccourci() definie explicitement
// ou implicitement par le jeu de type_urls courant.
//
// Valeur retournee selon le parametre $pour:
// 'tout' : tableau d'index url,class,titre,lang (vise <a href="U" class='C' hreflang='L'>T</a>)
// 'titre': seulement T ci-dessus (i.e. le TITRE ci-dessus ou dans table SQL)
// 'url':   seulement U  (i.e. generer_url_RACCOURCI)

function calculer_url(?string $ref, ?string $texte = '', string $pour = 'url', string $connect = '', bool $echappe_typo = true) {
	$ref ??= '';
	$texte ??= '';
	$r = traiter_lien_implicite($ref, $texte, $pour, $connect);
	$r = ($r ?: traiter_lien_explicite($ref, $texte, $pour, $connect, $echappe_typo));

	return $r;
}

define('_EXTRAIRE_LIEN', ',^\s*(?:' . _PROTOCOLES_STD . '):?/?/?\s*$,iS');

function traiter_lien_explicite(?string $ref, ?string $texte = '', string $pour = 'url', string $connect = '', bool $echappe_typo = true) {
	$ref ??= '';
	$texte ??= '';
	if (preg_match(_EXTRAIRE_LIEN, $ref)) {
		return ($pour != 'tout') ? '' : ['titre'=>'', 'url'=>''];
	}

	$lien = entites_html(trim($ref));

	// Liens explicites
	if (!$texte) {
		$texte = str_replace('"', '', $lien);
		static $lien_court;
		// evite l'affichage de trop longues urls.
		if (!$lien_court) {
			$lien_court = charger_fonction('lien_court', 'inc');
		}
		$texte = $lien_court($texte);
		if ($echappe_typo) {
			$texte = '<html>' . quote_amp($texte) . '</html>';
		}
	}

	// petites corrections d'URL
	if (preg_match('/^www\.[^@]+$/S', $lien)) {
		$lien = 'https://' . $lien;
	} else {
		if (strpos($lien, '@') && email_valide($lien)) {
			if (!$texte) {
				$texte = $lien;
			}
			$lien = 'mailto:' . $lien;
		}
	}

	if ($pour == 'url') {
		return $lien;
	}

	if ($pour == 'titre') {
		return $texte;
	}

	return ['url' => $lien, 'titre' => $texte];
}

function liens_implicite_glose_dist($texte, $id, $type, $args, $ancre, $connect = '') {
	if (function_exists($f = 'glossaire_' . $ancre)) {
		$url = $f($texte, $id);
	} else {
		$url = glossaire_std($texte);
	}

	return $url;
}

/**
 * Transformer un lien raccourci art23 en son URL
 * Par defaut la fonction produit une url prive si on est dans le prive
 * ou publique si on est dans le public.
 * La globale lien_implicite_cible_public permet de forcer un cas ou l'autre :
 * $GLOBALS['lien_implicite_cible_public'] = true;
 *  => tous les liens raccourcis pointent vers le public
 * $GLOBALS['lien_implicite_cible_public'] = false;
 *  => tous les liens raccourcis pointent vers le prive
 * unset($GLOBALS['lien_implicite_cible_public']);
 *  => retablit le comportement automatique
 *
 * @param string $ref
 * @param string $texte
 * @param string $pour
 * @param string $connect
 * @return array|bool|string
 */
function traiter_lien_implicite(?string $ref, ?string $texte = '', string $pour = 'url', $connect = '') {
	$ref ??= '';
	$texte ??= '';
	$cible = $GLOBALS['lien_implicite_cible_public'] ?? null;
	if (!($match = typer_raccourci($ref))) {
		return false;
	}

	[$type, , $id, , $args, , $ancre] = array_pad($match, 7, null);

	# attention dans le cas des sites le lien doit pointer non pas sur
	# la page locale du site, mais directement sur le site lui-meme
	$url = '';
	if ($f = charger_fonction("implicite_$type", 'liens', true)) {
		$url = $f($texte, $id, $type, $args, $ancre, $connect);
	}

	if (!$url) {
		$url = generer_objet_url($id, $type, $args ?? '', $ancre ?? '', $cible, '', $connect ?? '');
	}

	if (!$url) {
		return false;
	}

	if (is_array($url)) {
		[$type, $id] = array_pad($url, 2, null);
		$url = generer_objet_url($id, $type, $args ?? '', $ancre ?? '', $cible, '', $connect ?? '');
	}

	if ($pour === 'url') {
		return $url;
	}

	$r = traiter_raccourci_titre($id, $type, $connect);
	if ($r) {
		$r['class'] = ($type == 'site') ? 'spip_out' : 'spip_in';
	}

	if ($texte = trim($texte)) {
		$r['titre'] = $texte;
	}

	if (empty($r['titre'])) {
		$r['titre'] = _T($type) . " $id";
	}

	if ($pour == 'titre') {
		return $r['titre'];
	}

	$r['url'] = $url;

	// dans le cas d'un lien vers un doc, ajouter le type='mime/type'
	if (
		$type == 'document'
		and $mime = sql_getfetsel(
			'mime_type',
			'spip_types_documents',
			'extension IN (' . sql_get_select('extension', 'spip_documents', 'id_document=' . sql_quote($id)) . ')',
			'',
			'',
			'',
			'',
			$connect
		)
	) {
		$r['mime'] = $mime;
	}

	return $r;
}

// analyse des raccourcis issus de [TITRE->RACCOURCInnn] et connexes

define('_RACCOURCI_URL', '/^\s*(\w*?)\s*(\d+)(\?(.*?))?(#([^\s]*))?\s*$/S');

function typer_raccourci(?string $lien): array {
	if ($lien === null) {
		return [];
	}
	if (!preg_match(_RACCOURCI_URL, $lien, $match)) {
		return [];
	}

	$f = $match[1];
	// valeur par defaut et alias historiques
	if (!$f) {
		$f = 'article';
	} else {
		if ($f == 'art') {
			$f = 'article';
		} else {
			if ($f == 'br') {
				$f = 'breve';
			} else {
				if ($f == 'rub') {
					$f = 'rubrique';
				} else {
					if ($f == 'aut') {
						$f = 'auteur';
					} else {
						if ($f == 'doc' or $f == 'im' or $f == 'img' or $f == 'image' or $f == 'emb') {
							$f = 'document';
						} else {
							if (preg_match('/^br..?ve$/S', $f)) {
								$f = 'breve'; # accents :(
							}
						}
					}
				}
			}
		}
	}

	$match[0] = $f;

	return $match;
}

/**
 * Retourne le titre et la langue d'un objet éditorial
 *
 * @param int $id Identifiant de l'objet
 * @param string $type Type d'objet
 * @param string $connect Connecteur SQL utilisé
 * @return array {
 * @var string $titre Titre si présent, sinon ''
 * @var string $lang Langue si présente, sinon ''
 * }
 **/
function traiter_raccourci_titre($id, $type, $connect = '') {
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table(table_objet($type));

	if (!($desc and $s = $desc['titre'])) {
		return [];
	}

	$_id = $desc['key']['PRIMARY KEY'];
	$r = sql_fetsel($s, $desc['table'], "$_id=$id", '', '', '', '', $connect);

	if (!$r) {
		return [];
	}

	$r['titre'] = supprimer_numero($r['titre']);

	if (!$r['titre'] and !empty($r['surnom'])) {
		$r['titre'] = $r['surnom'];
	}

	if (!isset($r['lang'])) {
		$r['lang'] = '';
	}

	return $r;
}

//
// Raccourcis ancre [#ancre<-]
//

define('_RACCOURCI_ANCRE', '/\[#?([^][]*)<-\]/S');

function traiter_raccourci_ancre(?string $texte): string {
	if ($texte === null) {
		return '';
	}
	if (preg_match_all(_RACCOURCI_ANCRE, $texte, $m, PREG_SET_ORDER)) {
		foreach ($m as $regs) {
			$texte = str_replace(
				$regs[0],
				'<a ' . (html5_permis() ? 'id' : 'name') . '="' . entites_html($regs[1]) . '"></a>',
				$texte
			);
		}
	}

	return $texte;
}

//
// Raccourcis automatiques [?SPIP] vers un glossaire
// Wikipedia par defaut, avec ses contraintes techniques
// cf. http://fr.wikipedia.org/wiki/Wikip%C3%A9dia:Conventions_sur_les_titres

define('_RACCOURCI_GLOSSAIRE', '/\[\?+\s*([^][<>]+)\]/S');
define('_RACCOURCI_GLOSES', '/^([^|#{]*\w[^|#{]*)([^#]*)(#([^|{}]*))?(.*)$/S');

function traiter_raccourci_glossaire(?string $texte): string {
	if ($texte === null) {
		return '';
	}
	if (!preg_match_all(_RACCOURCI_GLOSSAIRE, $texte, $matches, PREG_SET_ORDER)) {
		return $texte;
	}

	include_spip('inc/charsets');
	$lien = charger_fonction('lien', 'inc');

	// Eviter les cas particulier genre "[?!?]"
	// et isoler le lexeme a gloser de ses accessoires
	// (#:url du glossaire, | bulle d'aide, {} hreflang)
	// Transformation en pseudo-raccourci pour passer dans inc_lien
	foreach ($matches as $regs) {
		if (preg_match(_RACCOURCI_GLOSES, $regs[1], $r)) {
			preg_match('/^(.*?)(\d*)$/', $r[4], $m);
			$_n = intval($m[2]);
			$gloss = $m[1] ? ('#' . $m[1]) : '';
			$t = $r[1] . $r[2] . $r[5];
			[$t, $bulle, $hlang] = traiter_raccourci_lien_atts($t);

			if ($bulle === false) {
				$bulle = $m[1];
			}

			$t = unicode2charset(charset2unicode($t), 'utf-8');
			$ref = $lien("glose$_n$gloss", $t, 'spip_glossaire', $bulle, $hlang);
			$texte = str_replace($regs[0], $ref, $texte);
		}
	}

	return $texte;
}

function glossaire_std($terme) {
	global $url_glossaire_externe;
	static $pcre = null;

	if ($pcre === null) {
		$pcre = $GLOBALS['meta']['pcre_u'] ?? '';

		if (strpos($url_glossaire_externe, '%s') === false) {
			$url_glossaire_externe .= '%s';
		}
	}

	$glosateur = str_replace(
		'@lang@',
		$GLOBALS['spip_lang'],
		$GLOBALS['url_glossaire_externe']
	);

	$terme = rawurlencode(preg_replace(',\s+,' . $pcre, '_', $terme));

	return str_replace('%s', $terme, $glosateur);
}
