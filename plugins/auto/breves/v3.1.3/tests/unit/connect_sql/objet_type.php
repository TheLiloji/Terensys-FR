<?php

/**
 * Test unitaire de la fonction heures
 * du fichier inc/filtres.php
 *
 * genere automatiquement par TestBuilder
 * le
 */

	$test = 'objet_type';
	$remonte = '../';
	while (!is_dir($remonte . 'ecrire')) {
		$remonte = "../$remonte";
	}
	require $remonte . 'tests/test.inc';
	find_in_path('base/connect_sql.php', '', true);

	//
	// hop ! on y va
	//
	$err = tester_fun('objet_type', essais_objet_type());

	// si le tableau $err est pas vide ca va pas
	if ($err) {
		die('<dl>' . join('', $err) . '</dl>');
	}

	echo 'OK';


	function essais_objet_type() {
		$essais =  [
   [
	0 => 'breve',
	1 => 'breves',
  ],
   [
	0 => 'breve',
	1 => 'spip_breves',
  ],
   [
	0 => 'breve',
	1 => 'id_breve',
  ],
['breve','breves'],
];
		return $essais;
	}
