<?php

// Les URLs brutes sont converties en <a href='url'>url</a>
function tw_autoliens(?string $t): string {

	if (!defined('_EXTRAIRE_LIENS')) {
		define('_EXTRAIRE_LIENS', ',' . '\[[^\[\]]*(?:<-|->).*?\]' . '|<a\b.*?</a\b' . '|<\w.*?>' . '|((?:https?:/|www\.)[^"\'\s\[\]\}\)<>]*)' . ',imsS');
	}

	if ($t === null) {
		return '';
	}

	$t = preg_replace_callback(_EXTRAIRE_LIENS, 'tw_traiter_autoliens', $t);

	return $t;
}


// callback pour la fonction autoliens()
function tw_traiter_autoliens($r) {
	if ((is_countable($r) ? count($r) : 0) < 2) {
		return reset($r);
	}
	[$tout, $l] = $r;
	if (!$l) {
		return $tout;
	}
	// reperer le protocole
	if (preg_match(',^(https?):/*,S', $l, $m)) {
		$l = substr($l, strlen($m[0]));
		$protocol = $m[1];
	} else {
		$protocol = 'http';
	}
	// valider le nom de domaine
	if (!preg_match(_EXTRAIRE_DOMAINE, $l)) {
		return $tout;
	}
	// les ponctuations a la fin d'une URL n'en font certainement pas partie
	// en particulier le "|" quand elles sont dans un tableau a la SPIP
	preg_match('/^(.*?)([,.;?|]?)$/', $l, $k);
	$url = $protocol . '://' . $k[1];
	// si l'original ne contenait pas le 'http:' on le supprime du clic
	$url_echap = echappe_html('<html>' . ($m ? $url : substr($url, strlen('http://'))) . '</html>');


	$class = 'spip_url';
	if (lien_is_url_externe($url)) {
		$class .= ' spip_out';
	}
	$class .= ' auto';

	$lien = charger_fonction('lien', 'inc');
	$r = $lien($url, $url_echap, $class, '', '', 'nofollow') . $k[2];

	return $r;
}
