<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use Spip\Afficher\Minipage\Admin as MinipageAdmin;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/dump');
include_spip('inc/actions');

/**
 * Restauration d'une sauvegarde
 *
 * @param string $titre Titre de la page
 * @param bool $reprise true s'il s'agit d'une reprise de sauvegarde
 */
function base_restaurer_dist($titre = '', $reprise = false) {
	$status = [];
	$status_file = _DUMP_STATUS_FILE;
	$status_file = _DIR_TMP . basename($status_file) . '.txt';
	if (
		!lire_fichier($status_file, $status)
		or !$status = unserialize($status)
	) {
	} else {
		$redirect = parametre_url(generer_action_auteur('restaurer', _DUMP_STATUS_FILE), 'step', intval(_request('step') + 1), '&');

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

		$titre = _T('dump:restauration_en_cours') . ' (' . (is_countable($status['tables']) ? count($status['tables']) : 0) . ') ';
		$balise_img = chercher_filtre('balise_img');
		$titre .= $balise_img(chemin_image('loader.svg'), '', 'loader');
		$minipage = new MinipageAdmin();
		echo $minipage->installDebutPage([
			'all_inline' => false,
			'titre' => $titre,
		]);
		// script de rechargement auto sur timeout
		echo http_script("window.setTimeout('location.href=\"" . $redirect . "\";'," . ($timeout * 1000) . ')');
		echo "<div style='text-align: left'>\n";

		dump_serveur($status['connect']);
		spip_connect('dump');
		$res = false;

		// au premier coup on ne fait rien sauf afficher l'ecran de sauvegarde
		if (_request('step')) {
			$options = [
				'callback_progression' => 'dump_afficher_progres',
				'max_time' => $max_time,
				'no_erase_dest' => lister_tables_noerase(),
				'where' => $status['where'] ?: [],
				'desc_tables_dest' => []
			];
			if (
				$desc = sql_getfetsel('valeur', 'spip_meta', "nom='dump_structure_temp'", '', '', '', '', 'dump')
				and $desc = unserialize($desc)
			) {
				$options['desc_tables_dest'] = $desc;
			}
			$res = base_copier_tables($status_file, $status['tables'], 'dump', '', $options);
		} else {
			// mais on en profite pour reparer les version base pour etre sur de ne pas les perdre
			sql_updateq('spip_meta', ['impt' => 'oui'], "nom='version_installee'", [], 'dump');
			sql_updateq('spip_meta', ['impt' => 'oui'], "nom LIKE '%_base_version'", [], 'dump');
		}

		echo("</div>\n");

		if (!$res) {
			echo dump_relance($redirect);
		}

		echo $minipage->installFinPage();
		ob_end_flush();
		flush();

		if (!$res) {
			exit;
		}

		// quand on sort de $export avec true c'est qu'on a fini
		dump_end(_DUMP_STATUS_FILE, 'restaurer');
		include_spip('inc/headers');
		echo redirige_formulaire(generer_url_ecrire('restaurer', 'status=' . _DUMP_STATUS_FILE, '', true, true));
	}
}
