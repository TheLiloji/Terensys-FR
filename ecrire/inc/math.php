<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

//
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * @deprecated 4.4 will be removed in 5.0
 * use `mathjax` plugin instead
 *
**/
function produire_image_math($tex) {
	trigger_error('La fonction `produire_image_math()` est dépréciée, utiliser à la place le plugin `mathjax`', E_USER_DEPRECATED);
	switch ($GLOBALS['traiter_math']) {
		// Attention: mathml desactiv'e pour l'instant
		case 'mathml':
			$ext = '.xhtml';
			$server = $GLOBALS['mathml_server'];
			break;
		case 'tex':
			$ext = '.png';
			$server = $GLOBALS['tex_server'];
			break;
		default:
			return $tex;
	}
	if ($GLOBALS['traiter_math'] != 'tex') {
		trigger_error('La globale `$traiter_math` est dépréciée, utiliser à la place le plugin `mathjax`', E_USER_DEPRECATED);
	}
	if ($GLOBALS['tex_server'] != 'https://math.spip.org/tex.php') {
		trigger_error('La globale `$tex_server` est dépréciée, utiliser à la place le plugin `mathjax`', E_USER_DEPRECATED);
	}

	// Regarder dans le repertoire local des images TeX et blocs MathML
	if (!@is_dir($dir_tex = _DIR_VAR . 'cache-TeX/')) {
		@mkdir($dir_tex, _SPIP_CHMOD);
	}
	$fichier = $dir_tex . md5(trim($tex)) . $ext;


	if (!@file_exists($fichier)) {
		// Aller chercher l'image sur le serveur
		if ($server) {
			spip_log($url = $server . '?' . rawurlencode($tex));
			include_spip('inc/distant');
			recuperer_url($url, ['file' => $fichier]);
		}
	}


	// Composer la reponse selon presence ou non de l'image
	$tex = entites_html($tex);
	if (@file_exists($fichier)) {
		// MathML
		if ($GLOBALS['traiter_math'] == 'mathml') {
			return implode('', file($fichier));
		} else {
			// TeX
			[, , , $size] = @spip_getimagesize($fichier);
			$alt = "alt=\"$tex\" title=\"$tex\"";

			return '<img src="' . attribut_url($fichier) . "\" style=\"vertical-align:middle;\" $size $alt />";
		}
	}  // pas de fichier

	return "<code><span class='spip_code' dir='ltr'>$tex</span></code>";
}


/**
 * @deprecated 4.4, will be removed in 4.4, use instead `mathjax` plugin
 * Active la recherche et l'affichage d'expressions mathématiques dans le texte
 * transmis, dans tous les textes à l'intérieur d'une balise `<math>`.
 *
 * Encadrer un texte de balises `<math> ... </math>` active la recherche
 * d'expressions mathématiques écrites entre caractères `$` au sein de ce texte :
 *
 * - `$$expression$$` traitera l'expression comme un paragraphe centré (p)
 * - `$expression$` traitera l'expression comme un texte en ligne (span)
 *
 * Un serveur distant calculera une image à partir de l'expression mathématique
 * donnée. Cette image est mise en cache localement (local/cache-Tex)
 *
 * @note
 *     Si cette fonction est appelée depuis `propre()` alors un échappement
 *     des caractères `&` en `&amp;` a été réalisé, qu'il faut redéfaire
 *     dans les expressions mathématiques trouvées (utiliser l'option
 *     `$defaire_amp` à true pour cela).
 *
 * @link https://www.spip.net/3016
 * @uses produire_image_math()
 * @uses code_echappement()
 *
 * @param string $letexte
 * @param string $source
 * @param bool $defaire_amp
 *     true pour passer les `&amp;` en `&` dans les expressions mathématiques.
 * @return string
 */
function traiter_math($letexte, $source = '', $defaire_amp = false) {
	trigger_error('La fonction `traiter_math()` native de SPIP est dépréciée, utiliser à la place le plugin `mathjax`', E_USER_DEPRECATED);
	while (($debut = strpos($letexte, '<math>')) !== false) {
		if (!$fin = strpos($letexte, '</math>', $debut)) {
			$fin = strlen($letexte);
		}

		$texte_milieu = substr(
			$letexte,
			$debut + strlen('<math>'),
			$fin - $debut - strlen('<math>')
		);

		$traitements = [
			// Les doubles $$x^2$$ en mode 'div'
			['str' => '$$', 'preg' => ',[$][$]([^$]+)[$][$],', 'pre' => "\n<p class=\"spip\" style=\"text-align: center;\">", "post" => "</p>\n"],
			// Les simples $x^2$ en mode 'span'
			['str' => '$', 'preg' => ',[$]([^$]+)[$],'],
		];
		foreach ($traitements as $t) {
			while (
				str_contains($texte_milieu, $t['str'])
				&& (preg_match($t['preg'], $texte_milieu, $regs))
			) {
				$expression = $regs[1];
				if ($defaire_amp) {
					$expression = str_replace('&amp;', '&', $expression);
				}
				$echap = produire_image_math($expression);
				$echap = ($t['pre'] ?? '') . $echap . ($t['post'] ?? '');
				$echap = code_echappement($echap, $source);

				$pos = strpos($texte_milieu, (string) $regs[0]);
				$texte_milieu = substr_replace($texte_milieu, $echap, $pos, strlen($regs[0]));
			}
		}

		$letexte = substr_replace($letexte, $texte_milieu, $debut, $fin - $debut);
	}

	return $letexte;
}
