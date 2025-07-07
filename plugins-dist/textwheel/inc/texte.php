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
 * Gestion des textes et raccourcis SPIP
 *
 * Surcharge de ecrire/inc/texte
 *
 * @package SPIP\Textwheel\Texte
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/texte_mini');
include_spip('inc/lien');

include_spip('inc/textwheel');


defined('_AUTOBR') || define('_AUTOBR', "<br class='autobr' />");
define('_AUTOBR_IGNORER', _AUTOBR ? '<!-- ig br -->' : '');

// Avec cette surcharge, cette globale n'est plus définie, et du coup ça plante dans les plugins qui font un foreach dessus comme ZPIP
$GLOBALS['spip_raccourcis_typo'] = [];
if (!isset($GLOBALS['toujours_paragrapher'])) {
	$GLOBALS['toujours_paragrapher'] = true;
}

// class_spip : savoir si on veut class="spip" sur p i strong & li
// class_spip_plus : class="spip" sur les ul ol h3 hr quote table...
// la difference c'est que des css specifiques existent pour les seconds
//
if (!isset($GLOBALS['class_spip'])) {
	$GLOBALS['class_spip'] = '';
}
if (!isset($GLOBALS['class_spip_plus'])) {
	$GLOBALS['class_spip_plus'] = ' class="spip"';
}


/**
 * Échapper et affichier joliement les `<script` ...
 *
 * @param string $t
 * @return string
 */
function echappe_js($t) {
	static $wheel = null;

	if (!isset($wheel)) {
		$wheel = new TextWheel(
			SPIPTextWheelRuleset::loader($GLOBALS['spip_wheels']['echappe_js'])
		);
	}

	try {
		$t = $wheel->text($t);
	} catch (Exception $e) {
		erreur_squelette($e->getMessage());
		// sanitizer le contenu methode brute, puisqu'on a pas fait mieux
		$t = textebrut($t);
	}

	return $t;
}


/**
 * Paragrapher seulement
 *
 * Fermer les paragraphes ; Essaie de préserver des paragraphes indiqués
 * à la main dans le texte (par ex: on ne modifie pas un `<p align='center'>`)
 *
 * @param string $t
 *     Le texte
 * @param null $toujours_paragrapher
 *     true pour forcer les `<p>` même pour un seul paragraphe
 * @return string
 *     Texte paragraphé
 */
function paragrapher($t, $toujours_paragrapher = null) {
	static $wheel = [];
	if (is_null($toujours_paragrapher)) {
		$toujours_paragrapher = $GLOBALS['toujours_paragrapher'];
	}

	if (!isset($wheel[$toujours_paragrapher])) {
		$ruleset = SPIPTextWheelRuleset::loader($GLOBALS['spip_wheels']['paragrapher']);
		if (
			!$toujours_paragrapher
			and $rule = $ruleset->getRule('toujours-paragrapher')
		) {
			$rule->disabled = true;
			$ruleset->addRules(['toujours-paragrapher' => $rule]);
		}
		$wheel[$toujours_paragrapher] = new TextWheel($ruleset);
	}

	try {
		$t = $wheel[$toujours_paragrapher]->text($t);
	} catch (Exception $e) {
		erreur_squelette($e->getMessage());
	}

	return $t;
}

/**
 * Empêcher l'exécution de code PHP et JS
 *
 * Sécurité : empêcher l'exécution de code PHP, en le transformant en joli code
 * dans l'espace privé. Cette fonction est aussi appelée par propre et typo.
 *
 * De la même manière, la fonction empêche l'exécution de JS mais selon le mode
 * de protection passe en argument
 *
 * Il ne faut pas désactiver globalement la fonction dans l'espace privé car elle protège
 * aussi les balises des squelettes qui ne passent pas forcement par propre ou typo après
 * si elles sont appelées en direct
 *
 * @param string $arg
 *     Code à protéger
 * @param int $mode_filtre
 *     Mode de protection
 *       -1 : protection dans l'espace privé et public
 *       0  : protection dans l'espace public
 *       1  : aucune protection
 *     utilise la valeur de la globale filtrer_javascript si non fourni
 * @return string
 *     Code protégé
 **/
function interdire_scripts($arg, $mode_filtre = null) {
	// on memorise le resultat sur les arguments non triviaux
	static $dejavu = [];
	static $wheel = [];

	if (is_null($mode_filtre) or !in_array($mode_filtre, [-1, 0, 1])) {
		$mode_filtre = $GLOBALS['filtrer_javascript'];
	}

	// Attention, si ce n'est pas une chaine, laisser intact
	if (!$arg or !is_string($arg) or !strstr($arg, '<')) {
		return $arg;
	}
	if (isset($dejavu[$mode_filtre][$arg])) {
		return $dejavu[$mode_filtre][$arg];
	}

	if (!isset($wheel[$mode_filtre])) {
		$ruleset = SPIPTextWheelRuleset::loader(
			$GLOBALS['spip_wheels']['interdire_scripts']
		);
		// Pour le js, trois modes : parano (-1), prive (0), ok (1)
		// desactiver la regle echappe-js si besoin
		if (
			$mode_filtre == 1
			or ($mode_filtre == 0 and !test_espace_prive())
		) {
			$ruleset->addRules(['securite-js' => ['disabled' => true]]);
		}
		$wheel[$mode_filtre] = new TextWheel($ruleset);
	}

	try {
		$t = $wheel[$mode_filtre]->text($arg);
	} catch (Exception $e) {
		erreur_squelette($e->getMessage());
		// sanitizer le contenu methode brute, puisqu'on a pas fait mieux
		$t = textebrut($arg);
	}

	return $dejavu[$mode_filtre][$arg] = $t;
}


/**
 * Applique la typographie générale
 *
 * Effectue un traitement pour que les textes affichés suivent les règles
 * de typographie. Fait une protection préalable des balises HTML et SPIP.
 * Transforme les balises `<multi>`
 *
 * @filtre
 * @uses traiter_modeles()
 * @uses corriger_typo()
 * @uses echapper_faux_tags()
 * @see  propre()
 *
 * @param string $letexte
 *     Texte d'origine
 * @param bool $echapper
 *     Échapper ?
 * @param string|null $connect
 *     Nom du connecteur à la bdd
 * @param array $env
 *     Environnement (pour les calculs de modèles)
 * @return string $t
 *     Texte transformé
 **/
function typo($letexte, $echapper = true, $connect = null, $env = []) {

	// plus vite
	if (!$letexte and (!is_scalar($letexte) or !strlen((string)$letexte))) {
		return '';
	}

	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrit sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	$interdire_script = false;
	if (is_null($connect)) {
		$connect = '';
		$interdire_script = true;
		$env['espace_prive'] = test_espace_prive();
	}

	// Dans l'espace prive on se mefie de tout contenu dangereux
	// https://core.spip.net/issues/3371
	// et aussi dans l'espace public si la globale filtrer_javascript = -1
	// https://core.spip.net/issues/4166
	$secure_prefix = '';
	if (
		$interdire_script
		or $GLOBALS['filtrer_javascript'] == -1
		or (isset($env['espace_prive']) and $env['espace_prive'] and $GLOBALS['filtrer_javascript'] <= 0)
		or (isset($env['wysiwyg']) and $env['wysiwyg'] and $GLOBALS['filtrer_javascript'] <= 0)
	) {
		$secure_prefix = ((!empty($env['espace_prive']) or !empty($env['wysiwyg'])) ? 'safe_ecrire_' :  'safe_public_');
	}

	$echapper = ($echapper ? 'TYPO' : false);
	// Echapper les codes <html> etc, en les securisant si besoin
	if ($echapper) {
		$callback_options = [
		  'secure_prefix' => $secure_prefix,
		  'connect' => $connect,
		  'env' => $env,
		];
		$letexte = echappe_html($letexte, $echapper, false, null, '', $callback_options);
	}

	//
	// Installer les modeles, notamment images et documents ;
	//
	// NOTE : propre() ne passe pas par ici mais directement par corriger_typo
	// cf. inc/lien

	include_spip("src/Texte/Collecteur/AbstractCollecteur");
	include_spip("src/Texte/Collecteur/Modeles");
	$collecteurModeles = new Spip\Texte\Collecteur\Modeles();
	if ($collecteurModeles->detecter($letexte)) {
		include_spip("src/Texte/Collecteur/Liens");
		$collecteurLiens = new Spip\Texte\Collecteur\Liens();

		// avant de traiter les modeles, echapper les raccourcis liens qui peuvent apparaitre en argument du modele
		$letexte = $collecteurLiens->echapper($letexte);

		$letexte = traiter_modeles($letexte, false, $echapper ?: '', $connect, $collecteurLiens, $env);

		if (!$echapper) {
			$echapper = '';
		}

		$letexte = $collecteurLiens->retablir($letexte);
	}

	$letexte = corriger_typo($letexte);
	$letexte = echapper_faux_tags($letexte);

	// reintegrer les echappements
	if ($echapper !== false) {
		$letexte = echappe_retour($letexte, $echapper);
	}

	// Dans les appels directs hors squelette, securiser ici aussi
	if ($interdire_script) {
		$letexte = interdire_scripts($letexte);
	}

	// dans un traitement par typo on ne peut pas avoir de html complexe issu de modeles
	// pas de risque donc a echapper a la fin tout ce qui est suspect
	// on regarde si il y a du suspect avant le retour des modeles qui sont encore echappes ici
	if ($secure_prefix) {
		$letexte = echapper_html_suspect($letexte, ['strict' => true], $connect, $env);
	}

	return $letexte;
}

// Correcteur typographique

define('_TYPO_PROTEGER', "!':;?~%-");
define('_TYPO_PROTECTEUR', "\x1\x2\x3\x4\x5\x6\x7\x8");

define('_TYPO_BALISE', ',</?[a-z!][^<>]*[' . preg_quote(_TYPO_PROTEGER) . '][^<>]*>,imsS');

/**
 * Corrige la typographie
 *
 * Applique les corrections typographiques adaptées à la langue indiquée.
 *
 * @pipeline_appel pre_typo
 * @pipeline_appel post_typo
 * @uses corriger_caracteres()
 * @uses corriger_caracteres()
 *
 * @param string $t Texte
 * @param string $lang Langue
 * @return string Texte
 */
function corriger_typo($t, $lang = '') {
	static $typographie = [];

	// plus vite
	if (!$t and (!is_scalar($t) or !strlen((string)$t))) {
		return '';
	}

	$t = pipeline('pre_typo', $t);

	// Caracteres de controle "illegaux"
	$t = corriger_caracteres($t);

	// Proteger les caracteres typographiques a l'interieur des tags html
	if (preg_match_all(_TYPO_BALISE, $t, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$insert = $reg[0];
			// hack: on transforme les caracteres a proteger en les remplacant
			// par des caracteres "illegaux". (cf corriger_caracteres())
			$insert = strtr($insert, _TYPO_PROTEGER, _TYPO_PROTECTEUR);
			$t = str_replace($reg[0], $insert, $t);
		}
	}

	// trouver les blocs idiomes et les traiter à part
	$t = extraire_idiome($ei = $t, $lang, true);
	$ei = ($ei !== $t);

	// trouver les blocs multi et les traiter à part
	$t = extraire_multi($em = $t, $lang, true);
	$em = ($em !== $t);

	// Charger & appliquer les fonctions de typographie
	$idxl = "$lang:" . ($GLOBALS['lang_objet'] ?? $GLOBALS['spip_lang']);
	if (!isset($typographie[$idxl])) {
		$typographie[$idxl] = charger_fonction(lang_typo($lang), 'typographie');
	}
	$t = $typographie[$idxl]($t);

	// Les citations en une autre langue, s'il y a lieu
	if ($ei) {
		$t = echappe_retour($t, 'idiome');
	}
	if ($em) {
		$t = echappe_retour($t, 'multi');
	}

	// Retablir les caracteres proteges
	$t = strtr($t, _TYPO_PROTECTEUR, _TYPO_PROTEGER);

	// pipeline
	$t = pipeline('post_typo', $t);

	# un message pour abs_url - on est passe en mode texte
	$GLOBALS['mode_abs_url'] = 'texte';

	return $t;
}


//
// Tableaux
//

define('_RACCOURCI_TH_SPAN', '\s*(:?{{[^{}]+}}\s*)?|<');

/**
 * Traitement des raccourcis de tableaux
 *
 * @param string $bloc
 * @return string
 */
function traiter_tableau($bloc) {
	// id "unique" pour les id du tableau
	$tabid = substr(md5($bloc), 0, 4);

	// Decouper le tableau en lignes
	preg_match_all(',([|].*)[|]\n,UmsS', $bloc, $regs, PREG_PATTERN_ORDER);
	$lignes = [];
	$debut_table = $summary = '';
	$l = 0;

	// Traiter chaque ligne
	$reg_line1 = ',^(\|(' . _RACCOURCI_TH_SPAN . '))+$,sS';
	$reg_line_all = ',^(' . _RACCOURCI_TH_SPAN . ')$,sS';
	$hc = $hl = [];
	$thead_ok = false;
	foreach ($regs[1] as $ligne) {
		$l++;

		// Gestion de la premiere ligne :
		if (!$thead_ok and $l == 1) {
			// - <caption> et summary dans la premiere ligne (seulement si on n'a pas dépassé le premier thead) :
			//   || caption | summary || (|summary est optionnel)
			if (preg_match(',^\|\|([^|]*)(\|(.*))?$,sS', rtrim($ligne, '|'), $cap)) {
				$cap = array_pad($cap, 4, '');
				$l = 0;
				$summary = '';
				if ($describedby = trim($cap[3])) {
					if (!html5_permis()) {
						$summary = ' summary="' . entites_html(trim($cap[3])) . '"';
						$describedby = '';
					}
					else {
						$iddescribedby = 'dby' . $tabid;
						$summary = ' aria-describedby="' . $iddescribedby . '"';
					}
				}
				if ($caption = trim($cap[1])) {
					if ($describedby) {
						$caption .= '<br /> <small id="' . $iddescribedby . '" class="summary offscreen">' . $describedby . '</small>';
					}
					$debut_table .= '<caption>' . $caption . "</caption>\n";
				}
				elseif ($describedby) {
					$debut_table .= '<caption id="' . $iddescribedby . '" class="summary offscreen"><small>' . $describedby . "</small></caption>\n";
				}
			}
			// - <thead> sous la forme |{{titre}}|{{titre}}|
			//   Attention thead oblige a avoir tbody
			else {
				if (preg_match($reg_line1, $ligne, $thead)) {
					preg_match_all('/\|([^|]*)/S', $ligne, $cols);
					$ligne = '';
					$cols = $cols[1];
					$colspan = 1;
					for ($c = count($cols) - 1; $c >= 0; $c--) {
						$attr = '';
						if ($cols[$c] == '<') {
							$colspan++;
						} else {
							if ($colspan > 1) {
								$attr = " colspan='$colspan'";
								$colspan = 1;
							}
							// inutile de garder le strong qui n'a servi que de marqueur
							$cols[$c] = str_replace(['{', '}'], '', $cols[$c]);
							$ligne = "<th id='id{$tabid}_c$c'$attr>$cols[$c]</th>$ligne";
							$hc[$c] = "id{$tabid}_c$c"; // pour mettre dans les headers des td
						}
					}

					$debut_table .= "<thead><tr class='row_first'>" .
						$ligne . "</tr></thead>\n";
					$l = 0;
					$thead_ok = true;
				}
			}
		}

		// Sinon ligne normale
		if ($l) {
			// Gerer les listes a puce dans les cellules
			// on declenche simplement sur \n- car il y a les
			// -* -# -? -! (qui produisent des -&nbsp;!)
			if (strpos($ligne, "\n-") !== false) {
				$ligne = traiter_listes($ligne);
			}

			// tout mettre dans un tableau 2d
			preg_match_all('/\|([^|]*)/S', $ligne, $cols);

			// Pas de paragraphes dans les cellules
			foreach ($cols[1] as &$col) {
				if (strlen($col = trim($col))) {
					$col = preg_replace("/\n{2,}/S", '<br /> <br />', $col);
					if (_AUTOBR) {
						$col = str_replace("\n", _AUTOBR . "\n", $col);
					}
				}
			}

			// assembler le tableau
			$lignes[] = $cols[1];
		}
	}

	// maintenant qu'on a toutes les cellules
	// on prepare une liste de rowspan par defaut, a partir
	// du nombre de colonnes dans la premiere ligne.
	// Reperer egalement les colonnes numeriques pour les cadrer a droite
	$rowspans = $numeric = [];
	$nb_cols = $lignes ? max(array_map('count',$lignes)) : 0;
	$nb_lignes = count($lignes);
	// distinguer les colonnes numeriques a point ou a virgule,
	// pour les alignements eventuels sur "," ou "."
	$numeric_class = [
		'.' => 'point',
		',' => 'virgule',
		true => ''
	];
	for ($i = 0; $i < $nb_cols; $i++) {
		$align = true;
		for ($j = 0; $j < $nb_lignes; $j++) {
			$rowspans[$j][$i] = 1;
			if ($align and preg_match('/^[{+-]*(?:\s|\d)*([.,]?)\d*[}]*$/', trim($lignes[$j][$i] ?? ''), $r)) {
				if ($r[1]) {
					$align = $r[1];
				}
			} else {
				$align = '';
			}
		}
		$numeric[$i] = $align ? (" class='numeric " . $numeric_class[$align] . "'") : '';
	}
	for ($j = 0; $j < $nb_lignes; $j++) {
		if (preg_match($reg_line_all, $lignes[$j][0])) {
			$hl[$j] = "id{$tabid}_l$j"; // pour mettre dans les headers des td
		} else {
			unset($hl[0]);
		}
	}
	if (!isset($hl[0])) {
		$hl = [];
	} // toute la colonne ou rien

	// et on parcourt le tableau a l'envers pour ramasser les
	// colspan et rowspan en passant
	$html = '';

	for ($l = count($lignes) - 1; $l >= 0; $l--) {
		$cols = $lignes[$l];
		$colspan = 1;
		$ligne = '';

		for ($c = count($cols) - 1; $c >= 0; $c--) {
			$attr = $numeric[$c] ?? '';
			$cell = trim($cols[$c]);
			if ($cell == '<') {
				$colspan++;
			} elseif ($cell == '^' && $l >= 1) {
				$rowspans[$l - 1][$c] += $rowspans[$l][$c];
			} else {
				if ($colspan > 1) {
					$attr .= " colspan='$colspan'";
					$colspan = 1;
				}
				if (($x = $rowspans[$l][$c] ?? null) > 1) {
					$attr .= " rowspan='$x'";
				}
				$b = ($c == 0 and isset($hl[$l])) ? 'th' : 'td';
				$h = ($hc[$c] ?? '') . ' ' . (($b == 'td' and isset($hl[$l])) ? $hl[$l] : '');
				if ($h = trim($h)) {
					$attr .= " headers='$h'";
				}
				// inutile de garder le strong qui n'a servi que de marqueur
				if ($b == 'th') {
					$attr .= " id='" . $hl[$l] . "'";
					$cols[$c] = str_replace(['{', '}'], '', $cols[$c]);
				}
				$ligne = "\n<$b" . $attr . '>' . $cols[$c] . "</$b>" . $ligne;
			}
		}

		// ligne complete
		$class = alterner($l + 1, 'odd', 'even');
		$html = "<tr class='row_$class $class'>$ligne</tr>\n$html";
	}

	$class = $GLOBALS['class_spip_plus'];
	if (!$class or strpos($GLOBALS['class_spip_plus'], 'class=') === false) {
		$class = ' ' . trim('class="table" ' . $class);
	}
	else {
		$class = str_replace('class="', 'class="table ', $class);
		$class = str_replace("class='", "class='table ", $class);
	}
	return "\n\n<table" . $class . $summary . ">\n"
	. $debut_table
	. "<tbody>\n"
	. $html
	. "</tbody>\n"
	. "</table>\n\n";
}


/**
 * Traitement des listes
 *
 * On utilise la wheel correspondante
 *
 * @param string $t
 * @return string
 */
function traiter_listes($t) {
	static $wheel = null;

	if (!isset($wheel)) {
		$wheel = new TextWheel(
			SPIPTextWheelRuleset::loader($GLOBALS['spip_wheels']['listes'])
		);
	}

	try {
		$t = $wheel->text($t);
	} catch (Exception $e) {
		erreur_squelette($e->getMessage());
	}

	return $t;
}


// Ces deux constantes permettent de proteger certains caracteres
// en les remplacanat par des caracteres "illegaux". (cf corriger_caracteres)

define('_RACCOURCI_PROTEGER', '{}_-');
define('_RACCOURCI_PROTECTEUR', "\x1\x2\x3\x4");

define('_RACCOURCI_BALISE', ',</?[a-z!][^<>]*[' . preg_quote(_RACCOURCI_PROTEGER) . '][^<>]*>,imsS');

/**
 * mais d'abord, une callback de reconfiguration des raccourcis
 * a partir de globales (est-ce old-style ? on conserve quand meme
 * par souci de compat ascendante)
 *
 * @param $ruleset
 * @return string
 */
function personnaliser_raccourcis(&$ruleset) {
	if ($ruleset) {
		if (isset($GLOBALS['debut_intertitre']) and $rule = $ruleset->getRule('intertitres')) {
			$rule->replace[0] = preg_replace(',<[^>]*>,Uims', $GLOBALS['debut_intertitre'], $rule->replace[0]);
			$rule->replace[1] = preg_replace(',<[^>]*>,Uims', $GLOBALS['fin_intertitre'], $rule->replace[1]);
			$ruleset->addRules(['intertitres' => $rule]);
			if ($rule = $ruleset->getRule('intertitres-compliques')) {
				$rule->replace[0] = preg_replace(',<[^>]*>,Uims', $GLOBALS['debut_intertitre'], $rule->replace[0]);
				$rule->replace[1] = preg_replace(',<[^>]*>,Uims', $GLOBALS['fin_intertitre'], $rule->replace[1]);
				$ruleset->addRules(['intertitres-compliques' => $rule]);
			}
		}
		if (isset($GLOBALS['debut_gras']) and $rule = $ruleset->getRule('gras')) {
			$rule->replace[0] = preg_replace(',<[^>]*>,Uims', $GLOBALS['debut_gras'], $rule->replace[0]);
			$rule->replace[1] = preg_replace(',<[^>]*>,Uims', $GLOBALS['fin_gras'], $rule->replace[1]);
			$ruleset->addRules(['gras' => $rule]);
		}
		if (isset($GLOBALS['debut_italique']) and $rule = $ruleset->getRule('italiques')) {
			$rule->replace[0] = preg_replace(',<[^>]*>,Uims', $GLOBALS['debut_italique'], $rule->replace[0]);
			$rule->replace[1] = preg_replace(',<[^>]*>,Uims', $GLOBALS['fin_italique'], $rule->replace[1]);
			$ruleset->addRules(['italiques' => $rule]);
		}
		if (isset($GLOBALS['ligne_horizontale']) and $rule = $ruleset->getRule('ligne-horizontale')) {
			$rule->replace = preg_replace(',<[^>]*>,Uims', $GLOBALS['ligne_horizontale'], $rule->replace);
			$ruleset->addRules(['ligne-horizontale' => $rule]);
		}
		if (
			isset($GLOBALS['toujours_paragrapher']) and !$GLOBALS['toujours_paragrapher']
			and $rule = $ruleset->getRule('toujours-paragrapher')
		) {
			$rule->disabled = true;
			$ruleset->addRules(['toujours-paragrapher' => $rule]);
		}
	}

	// retourner une signature de l'etat de la fonction, pour la mise en cache
	return implode(
		'/',
		[
			$GLOBALS['debut_intertitre'] ?? '',
			$GLOBALS['debut_gras'] ?? '',
			$GLOBALS['debut_italique'] ?? '',
			$GLOBALS['ligne_horizontale'] ?? '',
			$GLOBALS['toujours_paragrapher'] ?? 1,
		]
	);
}

/**
 * Nettoie un texte, traite les raccourcis autre qu'URL, la typo, etc.
 *
 * @pipeline_appel pre_propre
 * @pipeline_appel post_propre
 *
 * @param string $t
 * @param bool $show_autobr
 * @return string
 */
function traiter_raccourcis($t, $show_autobr = false) {
	static $wheel = [], $notes;
	static $img_br_auto, $img_br_manuel, $img_br_no;
	global $spip_lang, $spip_lang_rtl;

	// hack1: respecter le tag ignore br
	if (
		_AUTOBR_IGNORER
		and strncmp($t, _AUTOBR_IGNORER, strlen(_AUTOBR_IGNORER)) == 0
	) {
		$ignorer_autobr = true;
		$t = substr($t, strlen(_AUTOBR_IGNORER));
	} else {
		$ignorer_autobr = false;
	}

	// Appeler les fonctions de pre_traitement
	$t = pipeline('pre_propre', $t);

	$key = '';
	$key = personnaliser_raccourcis($key);
	if (!isset($wheel[$key])) {
		$ruleset = SPIPTextWheelRuleset::loader(
			$GLOBALS['spip_wheels']['raccourcis'],
			'personnaliser_raccourcis'
		);
		$wheel[$key] = new TextWheel($ruleset);

		if (
			_request('var_mode') == 'wheel'
			and autoriser('debug')
		) {
			$f = $wheel->compile();
			echo "<pre>\n" . spip_htmlspecialchars($f) . "</pre>\n";
			exit;
		}
		$notes = charger_fonction('notes', 'inc');
	}

	// Gerer les notes (ne passe pas dans le pipeline)
	[$t, $mes_notes] = $notes($t);

	try {
		$t = $wheel[$key]->text($t);
	} catch (Exception $e) {
		erreur_squelette($e->getMessage());
	}

	// Appeler les fonctions de post-traitement
	$t = pipeline('post_propre', $t);

	if ($mes_notes) {
		$notes($mes_notes, 'traiter', $ignorer_autobr);
	}

	if (_AUTOBR and !function_exists('aide_lang_dir')) {
		include_spip('inc/lang');
	}

	// hack2: wrap des autobr dans l'espace prive, pour affichage css
	// car en css on ne sait pas styler l'element BR
	if ($ignorer_autobr and _AUTOBR) {
		if (is_null($img_br_no)) {
			$img_br_no = ($show_autobr ? http_img_pack(
				'br-no' . aide_lang_dir($spip_lang, $spip_lang_rtl) . '-10.png',
				'',
				"class='br-no'",
				_T('tw:retour_ligne_ignore')
			) : '');
			$img_br_no = inserer_attribut($img_br_no, 'aria-label', _T('tw:retour_ligne_ignore'));
		}
		$t = str_replace(_AUTOBR, $img_br_no, $t);
	}
	if ($show_autobr and _AUTOBR) {
		if (is_null($img_br_manuel)) {
			$img_br_manuel = http_img_pack(
				'br-manuel' . aide_lang_dir($spip_lang, $spip_lang_rtl) . '-10.png',
				'',
				"class='br-manuel'",
				_T('tw:retour_ligne_manuel')
			);
			$img_br_manuel = inserer_attribut($img_br_manuel, 'aria-label', _T('tw:retour_ligne_manuel'));
		}
		if (is_null($img_br_auto)) {
			$img_br_auto = http_img_pack(
				'br-auto' . aide_lang_dir($spip_lang, $spip_lang_rtl) . '-10.png',
				'',
				"class='br-auto'",
				_T('tw:retour_ligne_auto')
			);
			$img_br_auto = inserer_attribut($img_br_auto, 'aria-label', _T('tw:retour_ligne_auto'));
		}
		if (false !== strpos(strtolower($t), '<br')) {
			$t = preg_replace("/<br\b.*>/UiS", "$img_br_manuel\\0", $t);
			$t = str_replace($img_br_manuel . _AUTOBR, $img_br_auto . _AUTOBR, $t);
		}
	}

	return $t;
}


/**
 * Transforme les raccourcis SPIP, liens et modèles d'un texte en code HTML
 *
 * Filtre à appliquer aux champs du type `#TEXTE*`
 *
 * @filtre
 * @uses echappe_html()
 * @uses expanser_liens()
 * @uses traiter_raccourcis()
 * @uses echappe_retour_modeles()
 * @see  typo()
 *
 * @param string $t
 *     Texte avec des raccourcis SPIP
 * @param string|null $connect
 *     Nom du connecteur à la bdd
 * @param array $env
 *     Environnement (pour les calculs de modèles)
 * @return string $t
 *     Texte transformé
 **/
function propre($t, $connect = null, $env = []) {
	static $wheel;

	$args = func_get_args();

	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrits sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	// FIXME: Trouver une solution pour avoir un type (string) unique sur $connect.
	$interdire_script = false;
	if (is_null($connect) and test_espace_prive()) {
		$connect = '';
		$interdire_script = true;
		$env['espace_prive'] = true;
	}

	// plus vite
	if (!$t and (!is_scalar($t) or !strlen((string)$t))) {
		return '';
	}

	$t = pipeline('pre_echappe_html_propre_args', [
		'args' => [
			'args' => $args,
			'connect' => $connect,
			'env' => $env
		],
		'data' => (string) $t
	]);

	$t = pipeline('pre_echappe_html_propre', $t);

	if (!isset($wheel)) {
		$wheel = new TextWheel(
			SPIPTextWheelRuleset::loader($GLOBALS['spip_wheels']['pre_echappe_html_propre'])
		);
	}
	try {
		$t = $wheel->text($t);
	} catch (Exception $e) {
		erreur_squelette($e->getMessage());
	}

	// Dans l'espace prive on se mefie de tout contenu dangereux
	// avant echappement des balises <html>
	// https://core.spip.net/issues/3371
	// et aussi dans l'espace public si la globale filtrer_javascript = -1
	// https://core.spip.net/issues/4166
	if (
		$interdire_script
		or $GLOBALS['filtrer_javascript'] == -1
		or (!empty($env['espace_prive']) and $GLOBALS['filtrer_javascript'] <= 0)
		or (!empty($env['wysiwyg']) and $GLOBALS['filtrer_javascript'] <= 0)
	) {
		// les balises html et autres sont traitées une par une callback via la fonction safe_(ecrire|public)_echappe_xxx appropriée
		$callback_options = [
		  'secure_prefix' => (!empty($env['espace_prive']) or !empty($env['wysiwyg'])) ? 'safe_ecrire_' :  'safe_public_',
		  'connect' => $connect,
		  'env' => $env,
		];
		$t_echappe = echappe_html($t, '', false, null, '', $callback_options);

		// avant echapper_html_suspect il faut protéger les raccourcis SPIP pas encore traités qui ressemblent à des balises html
		$t_echappe = echappe_html($t_echappe, 'raccourcis', true, "@</?(quote|poesie|poetry)([^>]*?)(/?)>@isS");

		// l'echappement du html suspect est utile pour la moderation dans l'espace prive
		// mais dans l'espace public on veut silencieusement le virer, on passe donc env ici
		$t = echapper_html_suspect($t_echappe, ['strict' => false, 'texte_source_affiche' => $t, 'expanser_liens' => true], $connect, $env);
		$t = echappe_retour($t, 'raccourcis');
	}
	else {
		$t = echappe_html($t, '', false, null, '', ['connect' => $connect, 'env' => $env]);
	}

	$t = expanser_liens($t, $connect ?? '', $env);

	$t = traiter_raccourcis($t, (isset($env['wysiwyg']) and $env['wysiwyg']) ? true : false);
	$t = echappe_retour_modeles($t, $interdire_script);

	$t = pipeline('post_echappe_html_propre', $t);

	$t = pipeline('post_echappe_html_propre_args', [
		'args' => [
			'args' => $args,
			'connect' => $connect,
			'env' => $env
		],
		'data' => $t
	]);


	return $t;
}

function safe_ecrire_traiter_echap_html_dist($regs, $options) {
	$html = $regs[3];
	// bloquons les scripts ici avant eventuel echappement si trop suspect (ca evite de declencher echappement complet si une partie sera echappee par interdire scripts)
	if ($GLOBALS['filtrer_javascript'] <= 0) {
		$html = interdire_scripts($html);
	}
	$html = echapper_html_suspect($html, ['strict' => true, 'wrap_suspect' => "<code class='echappe-js'>"], $options['connect'] ?? '', $options['env'] ?? ['espace_prive' => true]);
	return $html;
}

function safe_public_traiter_echap_html_dist($regs, $options) {
	$html = $regs[3];

	if ($GLOBALS['filtrer_javascript'] == -1) {
		$html = safehtml($html);
	}
	// on ne fait rien : le conrenu sera sanitizé in fine
	return $html;
}

function safe_ecrire_traiter_echap_script_dist($regs, $options) {
	// rendre joli (et inactif) si c'est un script language=php
	if (preg_match(',<script\b[^>]+php,ims', $regs[0])) {
		return highlight_string($regs[0], true);
	}

	$texte = nl2br(spip_htmlspecialchars($regs[0], ENT_NOQUOTES));
	return "<code class=\"echappe-js\">$texte</code>";
}

function safe_public_traiter_echap_script_dist($regs, $options) {
	// on peut enlever directement si mode parano
	if ($GLOBALS['filtrer_javascript'] == -1) {
		return '';
	}

	if (function_exists($f = 'traiter_echap_script') or function_exists($f = $f . '_dist')) {
		return $f($regs, $options);
	}
	return '';
}

function safe_ecrire_traiter_echap_code_dist($regs, $options) {
	if (function_exists($f = 'traiter_echap_code') or function_exists($f = $f . '_dist')) {
		$html = $f($regs, $options);
		// bloquons les scripts ici avant eventuel echappement si trop suspect (ca evite de declencher echappement complet si une partie sera echappee par interdire scripts)
		if ($GLOBALS['filtrer_javascript'] <= 0) {
			$html = interdire_scripts($html);
		}
		$html = echapper_html_suspect($html, ['strict' => true, 'wrap_suspect' => "<code class='echappe-js'>"], $options['connect'] ?? '', $options['env'] ?? ['espace_prive' => true]);
		return $html;
	}
	return '';
}

function safe_public_traiter_echap_code_dist($regs, $options) {
	if (function_exists($f = 'traiter_echap_code') or function_exists($f = $f . '_dist')) {
		$html = $f($regs, $options);
		// bloquons les scripts ou attributs malicieux
		if ($GLOBALS['filtrer_javascript'] == -1) {
			$html = safehtml($html);
		}
		return $html;
	}
	return '';
}
