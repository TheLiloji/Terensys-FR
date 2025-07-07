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
 * Fonction principale de sauvegarde
 * En mode sqlite on passe par une copie de base a base (dans l'API de SPIP)
 *
 * @param string $status_file Nom du fichier de status (stocke dans _DIR_TMP)
 * @param string $redirect Redirection apres la sauvegarde
 * @return bool
 */
function inc_sauvegarder_dist($status_file, $redirect = '') {
	$status = [];
	$status_file = _DIR_TMP . basename($status_file) . '.txt';
	if (
		!lire_fichier($status_file, $status)
		or !$status = unserialize($status)
	) {
	} else {
		$timeout = ini_get('max_execution_time');
		// valeur conservatrice si on a pas reussi a lire le max_execution_time
		if (!$timeout) {
			$timeout = 30;
		} // parions sur une valeur tellement courante ...
		$max_time = time() + $timeout / 2;

		include_spip('inc/minipres');
		if (function_exists('ini_set')) {
			@ini_set('zlib.output_compression', '0'); // pour permettre l'affichage au fur et a mesure
		}

		$titre = _T('dump:sauvegarde_en_cours') . ' (' . (is_countable($status['tables']) ? count($status['tables']) : 0) . ') ';
		$balise_img = chercher_filtre('balise_img');
		$titre .= $balise_img(chemin_image('loader.svg'), '', 'loader');
		echo(install_debut_html($titre));
		// script de rechargement auto sur timeout
		echo http_script("window.setTimeout('location.href=\"" . $redirect . "\";'," . ($timeout * 1000) . ')');
		echo "<div style='text-align: left'>\n";

		dump_serveur($status['connect']);
		spip_connect('dump');

		// au premier coup on ne fait rien sauf afficher l'ecran de sauvegarde
		$res = false;
		if (_request('step')) {
			$options = [
				'callback_progression' => 'dump_afficher_progres',
				'max_time' => $max_time,
				'no_erase_dest' => lister_tables_noerase(),
				'where' => $status['where'] ?: [],
			];
			$res = base_copier_tables($status_file, $status['tables'], '', 'dump', $options);
		}

		echo("</div>\n");

		if (!$res and $redirect) {
			echo dump_relance($redirect);
		}
		echo(install_fin_html());
		if (@ob_get_contents()) {
			ob_end_flush();
		}
		flush();

		return $res;
	}
}
