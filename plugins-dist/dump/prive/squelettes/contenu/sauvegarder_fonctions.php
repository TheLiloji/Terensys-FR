<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/dump');

/**
 * Afficher les erreurs survenues dans la sauvegarde
 *
 * @param string $status_file Nom du fichier qui contient le statut de la sauvegarde sous une forme serialisee
 * @return string               Code HTML a afficher
 */
function dump_afficher_tables_sauvegardees($status_file) {
	$status = dump_lire_status($status_file);
	$tables = $status['tables_copiees'];

	// lister les tables sauvegardees et aller verifier dans le dump
	// qu'on a le bon nombre de donnees
	dump_serveur($status['connect']);
	spip_connect('dump');

	foreach ($tables as $t => $n) {
		$n = abs(intval($n));
		$n_dump = intval(sql_countsel($t, '', '', '', 'dump'));
		$res = "$t ";
		if ($n_dump == 0 and $n == 0) {
			$res .= '(' . _T('dump:aucune_donnee') . ')';
		} else {
			$res .= "($n_dump/$n)";
		}
		if ($n !== $n_dump) {
			$res = "<strong>$res</strong>";
		}
		$tables[$t] = $res;
	}

	$corps = '';
	switch (is_countable($tables) ? count($tables) : 0) {
		case 0:
			break;
		case 1:
			$corps = "<div style='width:49%;float:left;'><ul class='spip'><li class='spip'>" . join("</li><li class='spip'>", $tables) . '</li></ul></div>';
			break;
		default:
			$n = floor((is_countable($tables) ? count($tables) : 0) / 2);
			$corps = "<div style='width:49%;float:left;'><ul class='spip'><li class='spip'>" .
				join("</li><li class='spip'>", array_slice($tables, 0, $n)) . '</li></ul></div>'
				. "<div style='width:49%;float:left;'><ul class='spip'><li>" . join(
					"</li><li class='spip'>",
					array_slice($tables, $n)
				) . '</li></ul></div>';
	}
	$corps .= "<div class='nettoyeur'></div>";

	return $corps;
}

function dump_afficher_erreurs($status_file) {
	$erreurs = '';
	$status = dump_lire_status($status_file);
	if (isset($status['errors'])) {
		$erreurs = implode('<br />', $status['errors']);
	}
	return $erreurs;
}
