<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/* Fonction qui vient du corps mais non récupérée dans cette surcharge (?) */
function defaut_tri_par($par, $defaut) {
	if (!defined('_TRI_ARTICLES_RUBRIQUE')) {
		return $par;
	}
	$par = array_keys($defaut);

	return reset($par);
}

