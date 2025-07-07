<?php

/*
 * Plugin xxx
 * (c) 2009 xxx
 * Distribue sous licence GPL
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('mediabox_pipelines');
include_spip('inc/mediabox');


function formulaires_configurer_mediabox_charger_dist() {
	$valeurs = mediabox_config(true);

	return $valeurs;
}

function formulaires_configurer_mediabox_traiter_dist() {
	$config = mediabox_config(true);

	include_spip('inc/meta');
	if (_request('reinit')) {
		foreach ($config as $k => $v) {
			set_request($k);
		}
		effacer_meta('mediabox');
	} else {
		// cas particulier de la checkbox :
		if (!_request('active')) {
			set_request('active', 'non');
		}
		foreach ($config as $k => $v) {
			if (strpos($k, '_') === 0) {
				unset($config[$k]);
			}
			else {
				if (!is_null(_request($k))) {
					$config[$k] = _request($k);
				}
			}
		}
		ecrire_meta('mediabox', serialize($config));
	}

	return ['message_ok' => _T('config_info_enregistree'), 'editable' => true];
}
