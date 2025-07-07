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
include_spip('inc/headers');
include_spip('auth/ldap');

function install_etape_ldap5_dist() {
	etape_ldap5_save();
	etape_ldap5_suite();
}

function etape_ldap5_save() {
	$conn = null;
	if (!@file_exists(_FILE_CONNECT_TMP)) {
		redirige_url_ecrire('install');
	}

	ecrire_meta('ldap_statut_import', _request('statut_ldap'));

	$conn_db = analyse_fichier_connection(_FILE_CONNECT_TMP);
	if ($conn_db) {
		[$adresse_db, $login_db, $pass_db, $sel_db, $server_db, $table_prefix, $auth, $charset] = $conn_db;
		// il faudrait peut-être contrôler que $auth est bien vide ''
		if (preg_match(',(.*):(.*),', (string) $adresse_db, $r)) {
			[, $adresse_db, $port] = $r;
		} else {
			$port = '';
		}
		$auth_new = _FILE_LDAP;
		// Ceci est nécessaire car on ne maitrise pas ce qui est ajouté au
		// fichier de configuration
		$orig_config = install_connexion(
			$adresse_db,
			$port,
			$login_db,
			$pass_db,
			$sel_db,
			$server_db,
			$table_prefix,
			$auth,
			$charset
		);
		$new_config = install_connexion(
			$adresse_db,
			$port,
			$login_db,
			$pass_db,
			$sel_db,
			$server_db,
			$table_prefix,
			$auth_new,
			$charset
		);


		lire_fichier(_FILE_CONNECT_TMP, $conn);
		// on ne peut pas directement utiliser preg_replace pour ajouter
		// $auth_new car on n'est pas censé connaître le format de la ligne de
		// connexion ici
		$new_conn = str_replace(
			$orig_config,
			$new_config,
			$conn,
			$count
		);
		// on ne casse pas le fichier de connexion si quelque chose se passe
		// mal, peut-être faudrait-il afficher une erreur
		if ($count == 1) {
			ecrire_fichier(
				_FILE_CONNECT_TMP,
				$new_conn
			);
		}
	}

	$adresse_ldap = addcslashes(_request('adresse_ldap'), "'\\");
	$login_ldap = addcslashes(_request('login_ldap'), "'\\");
	$pass_ldap = addcslashes(_request('pass_ldap'), "'\\");
	$port_ldap = addcslashes(_request('port_ldap'), "'\\");
	$tls_ldap = addcslashes(_request('tls_ldap'), "'\\");
	$protocole_ldap = addcslashes(_request('protocole_ldap'), "'\\");
	$base_ldap = addcslashes(_request('base_ldap'), "'\\");
	$base_ldap_text = addcslashes(_request('base_ldap_text'), "'\\");

	$conn = "\$GLOBALS['ldap_base'] = '$base_ldap';\n"
		. "\$GLOBALS['ldap_link'] = @ldap_connect('$adresse_ldap','$port_ldap');\n"
		. "@ldap_set_option(\$GLOBALS['ldap_link'],LDAP_OPT_PROTOCOL_VERSION,'$protocole_ldap');\n"
		. (($tls_ldap != 'oui') ? '' :
			"@ldap_start_tls(\$GLOBALS['ldap_link']);\n")
		. "@ldap_bind(\$GLOBALS['ldap_link'],'$login_ldap','$pass_ldap');\n";

	$champs = is_array($GLOBALS['ldap_attributes']) ? $GLOBALS['ldap_attributes'] : [];
	$res = '';
	foreach ($champs as $champ => $v) {
		$nom = 'ldap_' . $champ;
		$val = trim(_request($nom));
		if (preg_match('/^\w*$/', $val)) {
			if ($val) {
				$val = _q($val);
			}
		} else {
			$val = 'array(' . _q(preg_split('/\W+/', $val)) . ')';
		};
		if ($val) {
			$res .= "'$champ' => " . $val . ',';
		}
	}
	$conn .= "\$GLOBALS['ldap_champs'] = array($res);\n";

	install_fichier_connexion(_DIR_CONNECT . _FILE_LDAP, $conn);
}

function etape_ldap5_suite() {
	$minipage = new Spip\Afficher\Minipage\Installation();
	echo $minipage->installDebutPage(['onload' => 'document.getElementById(\'suivant\').focus();return false;']);

	echo info_etape(
		_T('info_ldap_ok'),
		info_progression_etape(5, 'etape_ldap', 'install/')
	);

	echo generer_form_ecrire('install', (
		"<input type='hidden' name='etape' value='3'>" .
		"<input type='hidden' name='ldap_present' value='true'>"
		. bouton_suivant()));

	echo $minipage->installFinPage();
}
