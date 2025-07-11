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
 * Gestion de syndication (RSS,...)
 *
 * @package SPIP\Sites\Syndication
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// ATTENTION
// Cette inclusion charge executer_une_syndication pour compatibilite,
// mais cette fonction ne doit plus etre invoquee directement:
// il faut passer par cron() pour avoir un verrou portable
// Voir un exemple dans action/editer/site
include_spip('genie/syndic');


/**
 * Analyse d'une URL au format Atom/RSS
 * @param string $url_syndic
 * @return array|string
 */
function syndic_atomrss_dist($url_syndic) {

	// Aller chercher les donnees du RSS et les analyser
	include_spip('inc/distant');
	$res = recuperer_url($url_syndic, ['transcoder' => true]);
	if (
		!$res
		or !$res['page']
		or intval($res['status'] / 100) != 2
	) {
		$items = _T('sites:avis_echec_syndication_02');
	} else {
		$items = analyser_backend($res['page'], $url_syndic);
	}

	return $items;
}

/**
 * @param string $raw_data
 * @param string $raw_format
 * @return array
 */
function syndic_atomrss_raw_data_to_array_dist($raw_data, $raw_format) {
	$data = [];
	if ($raw_format == 'xml') {
		$simplexml_to_array = charger_fonction('simplexml_to_array', 'inc');
		$data = $simplexml_to_array($raw_data);
	}
	return $data;
}

/**
 * Analyse un texte de backend
 *
 * @param string $rss
 *     Texte du fichier de backend
 * @param string $url_syndic
 *     URL du site d'où à été extrait le texte
 * @return array|string
 *     - array : tableau des items lus,
 *     - string : texte d'erreur
 **/
function analyser_backend($rss, $url_syndic = '') {
	$articles = [];
	include_spip('inc/texte'); # pour couper()

	$rss = pipeline('pre_syndication', $rss);

	if (!defined('_SYNDICATION_DEREFERENCER_URL')) {
		/** si true, les URLs de type feedburner sont déréférencées */
		define('_SYNDICATION_DEREFERENCER_URL', false);
	}

	// Echapper les CDATA
	cdata_echappe($rss, $echappe_cdata);

	// supprimer les commentaires
	$rss = preg_replace(',<!--.*-->,Ums', '', $rss);

	// simplifier le backend, en supprimant les espaces de nommage type "dc:"
	$rss = preg_replace(',<(/?)(dc):,i', '<\1', $rss);

	// chercher auteur/lang dans le fil au cas ou les items n'en auraient pas
	[$header] = preg_split(',<(item|entry)\b,', $rss, 2);
	if (
		preg_match_all(
			',<(author|creator)\b(.*)</\1>,Uims',
			$header,
			$regs,
			PREG_SET_ORDER
		)
	) {
		$les_auteurs_du_site = [];
		foreach ($regs as $reg) {
			$nom = $reg[2];
			if (preg_match(',<name>(.*)</name>,Uims', $nom, $reg)) {
				$nom = $reg[1];
			}
			$les_auteurs_du_site[] = trim(textebrut(filtrer_entites($nom)));
		}
		$les_auteurs_du_site = join(', ', array_unique($les_auteurs_du_site));
	} else {
		$les_auteurs_du_site = '';
	}

	$langue_du_site = '';

	if (
		preg_match(',<([^>]*xml:)?lang(uage)?' . '>([^<>]+)<,i', $header, $match) && ($l = $match[3])
		|| ($l = extraire_attribut(extraire_balise($header, 'feed'), 'xml:lang'))
	) {
		$langue_du_site = $l;
	} elseif (preg_match(',<feed\s[^>]*xml:lang=[\'"]([^<>\'"]+)[\'"],i', $header, $match)) {
		// atom
		$langue_du_site = $match[1];
	}

	// Recuperer les blocs item et entry
	$items = array_merge(extraire_balises($rss, 'item'), extraire_balises($rss, 'entry'));


	//
	// Analyser chaque <item>...</item> du backend et le transformer en tableau
	//

	if (!count($items)) {
		return _T('sites:avis_echec_syndication_01');
	}

	if (!defined('_SYNDICATION_MAX_ITEMS')) { define('_SYNDICATION_MAX_ITEMS', 1000);
	}
	$nb_items = 0;
	foreach ($items as $item) {
		$data = [];
		if ($nb_items++ > _SYNDICATION_MAX_ITEMS) {
			break;
		}

		// URL (semi-obligatoire, sert de cle)

		// guid n'est un URL que si marque de <guid ispermalink="true"> ;
		// attention la valeur par defaut est 'true' ce qui oblige a quelque
		// gymnastique
		if (
			preg_match(
				',<guid.*>[[:space:]]*(https?:[^<]*)</guid>,Uims',
				$item,
				$regs
			)
			and preg_match(
				',^(true|1)?$,i',
				extraire_attribut($regs[0], 'ispermalink') ?? ''
			)
		) {
			$data['url'] = $regs[1];
		} // contourner les redirections feedburner
		else {
			if (
				_SYNDICATION_DEREFERENCER_URL
				and preg_match(
					',<feedburner:origLink>(.*)<,Uims',
					$item,
					$regs
				)
			) {
				$data['url'] = $regs[1];
			} // <link>, plus classique
			else {
				if (
					preg_match(
						',<link[^>]*[[:space:]]rel=["\']?alternate[^>]*>(.*)</link>,Uims',
						$item,
						$regs
					)
				) {
					$data['url'] = trim($regs[1]);
					// c'est pas impossible d'avoir l'URL dans un href quand meme :)
					if (empty($data['url'])) {
						$data['url'] = extraire_attribut($regs[0], 'href');
					}
				} else {
					if (
						preg_match(
							',<link[^>]*[[:space:]]rel=.alternate[^>]*>,Uims',
							$item,
							$regs
						)
					) {
						$data['url'] = extraire_attribut($regs[0], 'href');
					} else {
						if (preg_match(',<link[^>]*>\s*([^\s]+)\s*</link>,Uims', $item, $regs)) {
							$data['url'] = $regs[1];
						} else {
							if (preg_match(',<link[^>]*>,Uims', $item, $regs)) {
								$data['url'] = extraire_attribut($regs[0], 'href');
							} // Aucun link ni guid, mais une enclosure
							else {
								if (
									preg_match(',<enclosure[^>]*>,ims', $item, $regs)
									and $url = extraire_attribut($regs[0], 'url')
								) {
									$data['url'] = $url;
								} // pas d'url, c'est genre un compteur...
								else {
									$data['url'] = '';
								}
							}
						}
					}
				}
			}
		}

		// Titre (semi-obligatoire)
		$data['titre'] = '';
		if (preg_match(',<title[^>]*>(.*?)</title>,ims', $item, $match)) {
			$data['titre'] = trim($match[1]);
		} elseif (preg_match(',<link[[:space:]][^>]*>,Uims', $item, $mat)) {
			$title = extraire_attribut($mat[0], 'title');
			if ($title) {
				$data['titre'] = trim($title);
			}
		}

		if ($data['titre'] === '') {
			$data['titre'] = _T('ecrire:info_sans_titre');
		}

		// Date
		$la_date = '';
		if (
			preg_match(
				',<(published|modified|issued)>([^<]*)<,Uims',
				$item,
				$match
			)
		) {
			cdata_echappe_retour($match[2], $echappe_cdata);
			$la_date = my_strtotime($match[2], $langue_du_site);
		}
		if (
			!$la_date and
			preg_match(',<(pubdate)>([^<]*)<,Uims', $item, $match)
		) {
			cdata_echappe_retour($match[2], $echappe_cdata);
			$la_date = my_strtotime($match[2], $langue_du_site);
		}
		if (
			!$la_date and
			preg_match(',<([a-z]+:date)>([^<]*)<,Uims', $item, $match)
		) {
			cdata_echappe_retour($match[2], $echappe_cdata);
			$la_date = my_strtotime($match[2], $langue_du_site);
		}
		if (
			!$la_date and
			preg_match(',<date>([^<]*)<,Uims', $item, $match)
		) {
			cdata_echappe_retour($match[1], $echappe_cdata);
			$la_date = my_strtotime($match[1], $langue_du_site);
		}

		// controle de validite de la date
		// pour eviter qu'un backend errone passe toujours devant
		// (note: ca pourrait etre defini site par site, mais ca risque d'etre
		// plus lourd que vraiment utile)
		if ($GLOBALS['controler_dates_rss']) {
			if (
				!$la_date
				or $la_date > time() + 48 * 3600
			) {
				$la_date = time();
			}
		}

		if ($la_date) {
			$data['date'] = $la_date;
		}

		// Honorer le <lastbuilddate> en forcant la date
		if (
			preg_match(
				',<(lastbuilddate|updated|modified)>([^<>]+)</\1>,i',
				$item,
				$regs
			)
			and $lastbuilddate = my_strtotime(trim($regs[2]), $langue_du_site)
			// pas dans le futur
			and $lastbuilddate < time()
		) {
			$data['lastbuilddate'] = $lastbuilddate;
		}

		// Auteur(s)
		if (
			preg_match_all(
				',<(author|creator)\b[^>]*>(.*)</\1>,Uims',
				$item,
				$regs,
				PREG_SET_ORDER
			)
		) {
			$auteurs = [];
			foreach ($regs as $reg) {
				$nom = $reg[2];
				if (preg_match(',<name\b[^>]*>(.*)</name>,Uims', $nom, $reg)) {
					$nom = $reg[1];
				}
				// Cas particulier d'un auteur Flickr
				if (preg_match(',nobody@flickr.com \((.*)\),Uims', $nom, $reg)) {
					$nom = $reg[1];
				}
				$auteurs[] = trim(textebrut(filtrer_entites($nom)));
			}
			$data['lesauteurs'] = join(', ', array_unique($auteurs));
		} else {
			$data['lesauteurs'] = $les_auteurs_du_site;
		}

		// Description
		if (
			preg_match(',<(description|summary)\b.*'
			. '>(.*)</\1\b,Uims', $item, $match)
		) {
			$data['descriptif'] = trim($match[2]);
		}
		if (
			preg_match(',<(content)\b.*'
			. '>(.*)</\1\b,Uims', $item, $match)
		) {
			$data['content'] = trim($match[2]);
		}

		// lang
		if (
			preg_match(
				',<([^>]*xml:)?lang(uage)?' . '>([^<>]+)<,i',
				$item,
				$match
			)
		) {
			$data['lang'] = trim($match[3]);
		} else {
			if ($lang = trim(extraire_attribut($item, 'xml:lang') ?? '')) {
				$data['lang'] = $lang;
			} else {
				$data['lang'] = trim($langue_du_site);
			}
		}

		// source et url_source  (pas trouve d'exemple en ligne !!)
		# <source url="http://www.truc.net/music/uatsap.mp3" length="19917" />
		# <source url="http://www.truc.net/rss">Site source</source>
		if (
			preg_match(
				',(<source[^>]*>)(([^<>]+)</source>)?,i',
				$item,
				$match
			)
		) {
			$data['source'] = trim($match[3]);
			$data['url_source'] = str_replace(
				'&amp;',
				'&',
				trim(extraire_attribut($match[1], 'url'))
			);
		}

		// Récupération de l'id quelqu'il soit
		if (preg_match(',<id>(.+)</id>,Uims', $item, $regs)) {
			$data['id'] = $regs[1];

			// GitHub et Gitlab : Identification du commit ou du ticket
			if (preg_match(',(?:commit|issues)/(\w+)$,Uims', $data['id'], $regs)) {
				$data['id_commit'] = $regs[1];
			}
		}

		// tags
		# a partir de "<dc:subject>", (del.icio.us)
		# ou <media:category> (flickr)
		# ou <itunes:category> (apple)
		# on cree nos tags microformat <a rel="directory" href="url">titre</a>
		# http://microformats.org/wiki/rel-directory-fr
		$tags = [];
		if (
			preg_match_all(
				',<(([a-z]+:)?(subject|category|directory|keywords?|tags?|type))[^>]*>'
				. '(.*?)</\1>,ims',
				$item,
				$matches,
				PREG_SET_ORDER
			)
		) {
			$tags = ajouter_tags($matches, $item);
		} # array()
		elseif (
			preg_match_all(
				',<(([a-z]+:)?(subject|category|directory|keywords?|tags?|type))[^>]*/>'
				. ',ims',
				$item,
				$matches,
				PREG_SET_ORDER
			)
		) {
			$tags = ajouter_tags($matches, $item);
		} # array()
		// Pieces jointes :
		// chercher <enclosure> au format RSS et les passer en microformat
		// ou des microformats relEnclosure,
		// ou encore les media:content
		if (!afficher_enclosures(join(', ', $tags))) {
			// on prend toutes les pièces jointes possibles, et on essaie de les rendre uniques.
			$enclosures = [];
			# rss 2
			if (
				preg_match_all(
					',<enclosure[[:space:]][^<>]+>,i',
					$item,
					$matches,
					PREG_PATTERN_ORDER
				)
			) {
				$enclosures += array_map('enclosure2microformat', $matches[0]);
			}
			# atom
			if (
				preg_match_all(
					',<link\b[^<>]+rel=["\']?enclosure["\']?[^<>]+>,i',
					$item,
					$matches,
					PREG_PATTERN_ORDER
				)
			) {
				$enclosures += array_map('enclosure2microformat', $matches[0]);
			}
			# media rss
			if (
				preg_match_all(
					',<media:content\b[^<>]+>,i',
					$item,
					$matches,
					PREG_PATTERN_ORDER
				)
			) {
				$enclosures += array_map('enclosure2microformat', $matches[0]);
			}
			$data['enclosures'] = join(', ', array_unique($enclosures));
			unset($enclosures);
		}
		$data['item'] = $item;

		// Nettoyer les donnees et remettre les CDATA en place
		cdata_echappe_retour($data, $echappe_cdata);
		cdata_echappe_retour($tags, $echappe_cdata);

		$data['raw_data'] = $item;
		cdata_echappe_retour($data['raw_data'], $echappe_cdata, false);
		$data['raw_format'] = 'xml';


		// passer l'url en absolue
		$data['url'] = url_absolue(filtrer_entites($data['url']), $url_syndic);

		// si on demande un dereferencement de l'URL, il faut verifier que ce n'est pas une redirection
		if (_SYNDICATION_DEREFERENCER_URL) {
			$target = $data['url'];
			include_spip('inc/distant');
			// on fait un GET et pas un HEAD car les vieux SPIP ne repondent pas la redirection avec un HEAD (honte) sur un article virtuel
			// mais on limite la taille de ce qu'on telecharge
			$res = recuperer_url($target, ['taille_max' => 4096]);

			// et récuperer l'URL finale de la page apres redirections eventuelles
			if (!empty($res['url'])) {
				$data['url'] = $res['url'];
			}
		}

		// Trouver les microformats (ecrase les <category> et <dc:subject>)
		if (
			preg_match_all(
				',<a[[:space:]]([^>]+[[:space:]])?rel=[^>]+>.*</a>,Uims',
				$data['item'],
				$regs,
				PREG_PATTERN_ORDER
			)
		) {
			$tags = $regs[0];
		}

		$data['tags'] = $tags;
		// enlever le html des titre pour etre homogene avec les autres objets spip
		$data['titre'] = textebrut($data['titre']);

		$articles[] = $data;
	}

	return $articles;
}


/**
 * Strtotime même avec le format W3C !
 *
 * Car hélàs, strtotime ne le reconnait pas tout seul !
 *
 * @link http://www.w3.org/TR/NOTE-datetime Format datetime du W3C
 *
 * @param string $la_date
 *     Date à parser
 * @return int
 *     Timestamp
 **/
function my_strtotime($la_date, $lang = null) {
	// format complet
	if (
		preg_match(
			',^(\d+-\d+-\d+[T ]\d+:\d+(:\d+)?)(\.\d+)?'
			. '(Z|([-+]\d{2}):\d+)?$,',
			$la_date,
			$match
		)
	) {
		$match = array_pad($match, 6, null);
		$la_date = str_replace('T', ' ', $match[1]) . ' GMT';

		return strtotime($la_date) - intval($match[5]) * 3600;
	}

	// YYYY
	if (preg_match(',^\d{4}$,', $la_date, $match)) {
		return strtotime($match[0] . '-01-01');
	}

	// YYYY-MM
	if (preg_match(',^\d{4}-\d{2}$,', $la_date, $match)) {
		return strtotime($match[0] . '-01');
	}

	// YYYY-MM-DD hh:mm:ss
	if (preg_match(',^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\b,', $la_date, $match)) {
		return strtotime($match[0]);
	}

	// utiliser strtotime en dernier ressort
	// en nettoyant le jour qui prefixe parfois la date, suivi d'une virgule
	// et les UT qui sont en fait des UTC
	$la_date_c = preg_replace('/^\w+,\s*/ms', '', $la_date);
	$la_date_c = preg_replace('/UT\s*$/ms', 'UTC', $la_date_c);
	if (
		$s = strtotime($la_date)
		or $s = strtotime($la_date_c)
	) {
		return $s;
	}

	// essayons de voir si le nom du mois est dans la langue du flux et remplacons le
	// par la version anglaise avant de faire strtotime
	if ($lang) {
		// "fr-fr"
		[$lang] = explode('-', $lang);
		static $months = null;
		if (!isset($months[$lang])) {
			$prev_lang = $GLOBALS['spip_lang'];
			changer_langue($lang);
			foreach (range(1, 12) as $m) {
				$s = _T("date_mois_$m");
				$months[$lang][$s] = date('M', strtotime("2013-$m-01"));
				$s = _T('date_mois_' . $m . '_abbr');
				$months[$lang][$s] = date('M', strtotime("2013-$m-01"));
				$months[$lang][trim($s, '.')] = date('M', strtotime("2013-$m-01"));
			}
			changer_langue($prev_lang);
		}
		spip_log($la_date_c, 'dbgs');
		foreach ($months[$lang] as $loc => $en) {
			if (stripos($la_date_c, (string) $loc) !== false) {
				$s = str_ireplace($loc, $en, $la_date_c);
				if ($s = strtotime($s)) {
					return $s;
				}
			}
		}
	}

	// erreur
	spip_log("Impossible de lire le format de date '$la_date'");

	return false;
}

// A partir d'un <dc:subject> ou autre essayer de recuperer
// le mot et son url ; on cree <a href="url" rel="tag">mot</a>
function creer_tag($mot, $type, $url) {
	if (!strlen($mot = trim($mot))) {
		return '';
	}
	$mot = "<a rel=\"tag\">$mot</a>";
	if ($url) {
		$mot = inserer_attribut($mot, 'href', $url);
	}
	if ($type) {
		$mot = inserer_attribut($mot, 'rel', $type);
	}

	return $mot;
}


function ajouter_tags($matches, $item) {
	include_spip('inc/filtres');
	$tags = [];
	foreach ($matches as $match) {
		$type = ($match[3] == 'category' or $match[3] == 'directory')
			? 'directory' : 'tag';
		$mot = supprimer_tags($match[0]);
		if (
			!strlen($mot)
			and !strlen($mot = extraire_attribut($match[0], 'label'))
		) {
			break;
		}
		// rechercher un url
		if ($url = extraire_attribut($match[0], 'domain')) {
			// category@domain est la racine d'une url qui se prolonge
			// avec le contenu text du tag <category> ; mais dans SPIP < 2.0
			// on donnait category@domain = #URL_RUBRIQUE, et
			// text = #TITRE_RUBRIQUE ; d'ou l'heuristique suivante sur le slash
			if (substr($url, -1) == '/') {
				$url .= rawurlencode($mot);
			}
		} else {
			if (
				$url = extraire_attribut($match[0], 'resource')
				or $url = extraire_attribut($match[0], 'url')
			) {
			} ## cas particuliers
			else {
				if (extraire_attribut($match[0], 'scheme') == 'urn:flickr:tags') {
					foreach (explode(' ', $mot) as $petit) {
						if (
							$t = creer_tag(
								$petit,
								$type,
								'http://www.flickr.com/photos/tags/' . rawurlencode($petit) . '/'
							)
						) {
							$tags[] = $t;
						}
					}
					$mot = '';
				} else {
					if (
						// cas atom1, a faire apres flickr
						$term = extraire_attribut($match[0], 'term')
					) {
						if ($scheme = extraire_attribut($match[0], 'scheme')) {
							$url = suivre_lien($scheme, $term);
						} else {
							$url = $term;
						}
					} else {
						# type delicious.com
						foreach (explode(' ', $mot) as $petit) {
							if (
								preg_match(
									',<rdf\b[^>]*\bresource=["\']([^>]*/'
									. preg_quote(rawurlencode($petit), ',') . ')["\'],i',
									$item,
									$m
								)
							) {
								$mot = '';
								if ($t = creer_tag($petit, $type, $m[1])) {
									$tags[] = $t;
								}
							}
						}
					}
				}
			}
		}

		if ($t = creer_tag($mot, $type, $url)) {
			$tags[] = $t;
		}
	}

	return $tags;
}


// Lit contenu des blocs [[CDATA]] dans un flux
function cdata_echappe(&$rss, &$echappe_cdata) {
	$echappe_cdata = [];
	if (
		preg_match_all(
			',<!\[CDATA\[(.*)]]>,Uims',
			$rss,
			$regs,
			PREG_SET_ORDER
		)
	) {
		foreach ($regs as $n => $reg) {
			if (
				strpos($reg[1], '<') !== false
				or strpos($reg[1], '>') !== false
			) {
				// verifier que la chaine est encore dans le flux, car on peut avoir X fois la meme
				// inutile de (sur)peupler le tableau avec des substitutions identiques
				if (strpos($rss, (string) $reg[0]) !== false) {
					$echappe_cdata["@@@SPIP_CDATA$n@@@"] = $reg[1];
					$rss = str_replace($reg[0], "@@@SPIP_CDATA$n@@@", $rss);
				}
			} else {
				$rss = str_replace($reg[0], $reg[1], $rss);
			}
		}
	}
}

// Retablit le contenu des blocs [[CDATA]] dans une chaine ou un tableau
function cdata_echappe_retour(&$x, &$echappe_cdata, $filtrer_entites = true) {
	if (is_string($x)) {
		if ($filtrer_entites and strpos($x, '&lt;') !== false) {
			$x = filtrer_entites($x);
		}
		if (strpos($x, '@@@SPIP_CDATA') !== false) {
			$x = str_replace(array_keys($echappe_cdata), array_values($echappe_cdata), $x);
		}
	} else {
		if (is_array($x)) {
			foreach ($x as $k => &$v) {
				cdata_echappe_retour($v, $echappe_cdata, $filtrer_entites);
			}
		}
	}
}
