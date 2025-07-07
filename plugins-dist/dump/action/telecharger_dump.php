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
include_spip('inc/autoriser');

/**
 * Telecharger un dump quand on est webmestre
 *
 * @param string $arg
 */
function action_telecharger_dump_dist($arg = null) {
	if (!$arg) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	$file = dump_repertoire() . basename($arg, '.sqlite') . '.sqlite';

	if (
		file_exists($file)
		and autoriser('webmestre')
	) {
		// Vider tous les tampons pour ne pas provoquer de Fatal memory exhausted
		$level = @ob_get_level();
		while ($level--) {
			@ob_end_clean();
		}

		$f = basename($file);
		// ce content-type est necessaire pour eviter des corruptions de zip dans ie6
		header('Content-Type: application/octet-stream');

		header("Content-Disposition: attachment; filename=\"$f\";");
		header('Content-Transfer-Encoding: binary');

		// fix for IE catching or PHP bug issue
		header('Pragma: public');
		header('Expires: 0'); // set expiration time
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

		if ($cl = filesize($file)) {
			header('Content-Length: ' . $cl);
		}
		readfile($file);
	} else {
		http_response_code(404);
		include_spip('inc/minipres');
		echo minipres(_T('erreur') . ' 404', _T('info_acces_interdit'));
	}

	// et on finit comme ca d'un coup
	exit;
}
