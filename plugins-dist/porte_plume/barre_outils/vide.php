<?php

/**
 * Déclaration de la barre d'outil vide de SPIP
 *
 * @plugin Porte Plume pour SPIP
 * @license GPL
 * @package SPIP\PortePlume\BarreOutils
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Définition de la barre 'vide' pour markitup
 *
 * @return Barre_outils La barre d'outil
 */

function barre_outils_vide() {
	$set = new Barre_outils([
		'nameSpace' => 'vide',
		'markupSet' => [],
	]);
	$set->cacherTout();
	return $set;
}
