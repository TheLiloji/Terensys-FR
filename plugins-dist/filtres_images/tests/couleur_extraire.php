<?php
/**
 * Test unitaire de la fonction couleur_extraire
 * du fichier filtres/couleurs.php
 */


$test = 'couleur_extraire';
$remonte = "";
while (!is_file($remonte."test.inc") and !is_dir($remonte.'ecrire/'))
	$remonte = $remonte."../";
foreach ([$remonte."test.inc", $remonte."tests/test.inc", $remonte."tests/tests/legacy/test.inc"] as $f) {
	if (is_file($f)){
		require $f;
		break;
	}
}
if (!defined('_SPIP_TEST_INC')) {
	die('Impossible de trouver test.inc depuis ' .getcwd());
}

find_in_path('filtres/couleurs.php', '', true);

//
// hop ! on y va
//
$err = tester_fun('couleur_extraire', essais_couleur_extraire());

// si le tableau $err est pas vide ca va pas
if ($err) {
	die('<dl>' . join('', $err) . '</dl>');
}

echo 'OK';


function essais_couleur_extraire() {
	$essais = array(
		0 =>
			array(
				0 => '739cc8',
				1 => url_absolue(find_in_path('tests/degrade-bleu.jpg'), $GLOBALS['meta']['adresse_site'] . '/'),
			),
		1 =>
			array(
				0 => '739cc8',
				1 => find_in_path('tests/degrade-bleu.jpg'),
			),
	);

	return $essais;
}
