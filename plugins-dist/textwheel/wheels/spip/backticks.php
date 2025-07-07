<?php

/**
 * Fonctions utiles pour les wheels SPIP
 *
 * @SPIP\Textwheel\Wheel\SPIP\Fonctions
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/texte');

/**
 * Echapper les tags html qui ont un ` dans un attribut
 * @param array $match
 * @return string
 */
function echappe_tags_sub_backticks($match) {
	$html_tag = $match[0];

	// si on a pas d'attributs, alors c'est un faux tag, on l'echappe pas
	if (strpbrk($html_tag, "\"'") === false) {
		return $match[0];
	}

	// enlevons tout ce qui est entre guillemets
	$html_tag = preg_replace(',=["][^"]*["],', '', $html_tag);
	$html_tag = preg_replace(",=['][^']*['],", '', $html_tag);

	// si il reste un backtick alors ce n'est pas un vrai tag html, on echappe pas
	if (strpos($html_tag, '`') !== false) {
		return $match[0];
	}

	return str_replace("`", chr(2) . chr(15), $match[0]);
}

function replace_backticks($texte) {

	$tick_rules = [
		'```' => "@(?<!`)(```+)([^`]|[^`].*?[^`])(\\1)($|[^`])@imsS",
		'``' => "@(?<!`)(``)([^`]|[^`].*?[^`])(``)($|[^`])@imsS",
		'`' => "@(?<!`)(`)([^`]|[^`].*?[^`])(`)($|[^`])@imsS",
	];

	foreach ($tick_rules as $ticks => $preg) {
		// si il y a un tick echappé parce qu'a l'intérieur d'un tag html, c'est un début de tag dans un tick
		// donc c'est le tick fermant
		$inside_tag_tick = str_repeat(chr(2) . chr(15), strlen($ticks));

		$pos = 0;
		while (strpos($texte, $ticks, $pos) !== false
		  and preg_match($preg, $texte, $match, PREG_OFFSET_CAPTURE, $pos)) {
			$pmatch = $match[0][1];
			$length = strlen($match[0][0]);
			$code = $match[2][0];
			$after = $match[4][0] ?? '';

			if (($p = strpos($code, $inside_tag_tick)) !== false) {
				$after = substr($code, $p + strlen($inside_tag_tick)) . $match[3][0] . $after;
				$code = substr($code, 0, $p);
			}

			if (strlen($ticks) === 3) {
				$langage = '';
				if (preg_match(",^[\w\h\-]+(\r\n?|\n),is", $code, $r)) {
					$langage = trim($r[0]);
					$code = substr($code, strlen($r[0]));
				}
				// pas de premier saut de ligne
				$code = preg_replace(',^(\r\n?|\n),', '', $code, 1);
				$html = spip_balisage_code($code, true, '', $langage);
				$mode = "div";
			}
			else {
				// span code
				$html = spip_balisage_code(trim($code), false);
				$mode = "span";

			}

			$html = code_echappement($html, '', false, $mode);
			$texte = substr_replace($texte, $html . $after, $pmatch, $length);
			$pos = $pmatch + strlen($html);
		}
	}

	return $texte;
}
