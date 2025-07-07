<?php

/**
 * Test unitaire de la fonction heures
 * du fichier inc/filtres.php
 *
 * genere automatiquement par TestBuilder
 * le
 */

	$test = 'table_objet';
	$remonte = '../';
	while (!is_dir($remonte . 'ecrire')) {
		$remonte = "../$remonte";
	}
	require $remonte . 'tests/test.inc';
	find_in_path('base/connect_sql.php', '', true);

	//
	// hop ! on y va
	//
	$err = tester_fun('table_objet', essais_table_objet());

	// si le tableau $err est pas vide ca va pas
	if ($err) {
		die('<dl>' . join('', $err) . '</dl>');
	}

	echo 'OK';


	function essais_table_objet() {
		$essais =  [
   [
	0 => 'breves',
	1 => 'breve',
  ],
   [
	0 => 'breves',
	1 => 'spip_breve',
  ],
   [
	0 => 'breves',
	1 => 'id_breve',
  ],
['breves','breve'],
];
		return $essais;
	}
