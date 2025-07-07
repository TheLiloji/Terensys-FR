<?php

/**
 * Test unitaire de la fonction heures
 * du fichier inc/filtres.php
 *
 * genere automatiquement par TestBuilder
 * le
 */

	$test = 'table_objet_sql';
	$remonte = '../';
	while (!is_dir($remonte . 'ecrire')) {
		$remonte = "../$remonte";
	}
	require $remonte . 'tests/test.inc';
	find_in_path('base/connect_sql.php', '', true);

	//
	// hop ! on y va
	//
	$err = tester_fun('table_objet_sql', essais_table_objet_sql());

	// si le tableau $err est pas vide ca va pas
	if ($err) {
		die('<dl>' . join('', $err) . '</dl>');
	}

	echo 'OK';


	function essais_table_objet_sql() {
		$essais =  [
   [
	0 => 'spip_breves',
	1 => 'breve',
  ],
   [
	0 => 'spip_breves',
	1 => 'spip_breve',
  ],
   [
	0 => 'spip_breves',
	1 => 'id_breve',
  ],
['spip_breves','breve'],
];
		return $essais;
	}
