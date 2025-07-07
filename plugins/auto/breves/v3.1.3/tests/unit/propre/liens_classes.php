<?php

	$err = [];
	$test = 'liens_classes';
	$remonte = '../';
	while (!is_dir($remonte . 'ecrire')) {
		$remonte = "../$remonte";
	}
	require $remonte . 'tests/test.inc';
	include_spip('inc/texte');
	include_spip('inc/lang');

	$id = sql_getfetsel('id_breve', 'spip_breves', "statut='publie'", '', '', '0,1');
	if (!$id) { echo 'NA Necessite une breve publiee<br />';
	}
	$p0 = "[->breve$id]";
	if (
		!$c = extraire_attribut(propre($p0), 'class')
		or strpos($c, 'spip_in') === false
		or strpos($c, 'spip_out') !== false
	) {
		$err[] = "Classe $c errone dans $p0 : " . PtoBR(propre($p0));
	}

	// si le tableau $err est pas vide ca va pas
	if (count($err)) {
		echo ('<dl><dt>' . join('</dt><dt>', $err) . '</dt></dl>');
	} else {
		echo 'OK';
	}
