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


/**
 * Recuperer le nom du fichier selon le mode d'upload choisi
 * et mettre cela au format $_FILES
 *
 * Renvoie une liste de fichier ou un message en cas d'erreur
 *
 * @return string|array
 */
function joindre_trouver_fichier_envoye() {
	static $files = [];
	// on est appele deux fois dans un hit, resservir ce qu'on a trouve a la verif
	// lorsqu'on est appelle au traitement

	if (is_countable($files) ? count($files) : 0) {
		return $files;
	}

	if (_request('joindre_upload')) {
		$files = joindre_trouver_http_post_files();
		// erreur ?
		if (is_string($files)) {
			return $files;
		}
		// rien envoye ?
		if (!count($files)) {
			return _T('medias:erreur_indiquez_un_fichier');
		}
		// verifions les types de fichier envoyes
		include_spip('action/ajouter_documents');
		foreach ($files as $file) {
			if (!is_array(verifier_upload_autorise($file['name']))) {
				return _T('medias:erreur_upload_type_interdit', ['nom' => $file['name']]);
			}
		}

		return $files;
	} elseif (_request('joindre_distant')) {
		$path = _request('url');
		if (!strlen($path) or $path == 'http://') {
			return _T('medias:erreur_indiquez_un_fichier');
		}
		include_spip('inc/distant');
		if (!valider_url_distante($path)) {
			return _T('medias:erreur_upload_type_interdit', ['nom' => $path]);
		}
		include_spip('action/ajouter_documents');
		$infos = renseigner_source_distante($path, 'valider_url_distante');
		if (!is_array($infos)) {
			return $infos;
		} // message d'erreur
		else {
			return [
				[
					'name' => basename($path),
					'tmp_name' => $path,
					'distant' => true,
				]
			];
		}
	} elseif (_request('joindre_ftp')) {
		$path = _request('cheminftp');
		if (!$path || strstr($path, '..')) {
			return _T('medias:erreur_indiquez_un_fichier');
		}

		include_spip('inc/documents');
		include_spip('inc/actions');
		$upload = determine_upload();
		if ($path != '/' and $path != './') {
			$upload .= $path;
		}

		if (!is_dir($upload)) {
			// seul un fichier est demande
			return [
				[
					'name' => basename($upload),
					'tmp_name' => $upload
				]
			];
		} else {
			// on upload tout un repertoire
			$files = [];
			foreach (preg_files($upload) as $fichier) {
				$files[] = [
					'name' => basename($fichier),
					'tmp_name' => $fichier
				];
			}

			return $files;
		}
	} elseif (_request('joindre_zip') and $token_zip = _request('chemin_zip')) {
		$zip_to_clean = (isset($GLOBALS['visiteur_session']['zip_to_clean']) ?
			unserialize($GLOBALS['visiteur_session']['zip_to_clean']) : []);
		if (
			!$zip_to_clean
			or !isset($zip_to_clean[$token_zip])
			or !$path = $zip_to_clean[$token_zip]
		) {
			return _T('avis_operation_impossible');
		}

		include_spip('inc/documents'); //pour creer_repertoire_documents
		define('_TMP_ZIP', $path);
		define('_TMP_DIR', creer_repertoire_documents(md5($path . $GLOBALS['visiteur_session']['id_auteur'])));
		if (_TMP_DIR == _DIR_IMG) {
			return _T('avis_operation_impossible');
		}

		$files = [];
		if (_request('options_upload_zip') == 'deballe') {
			$files = joindre_deballer_lister_zip($path, _TMP_DIR);
		}

		// si le zip doit aussi etre conserve, l'ajouter
		if (_request('options_upload_zip') == 'upload' or _request('options_deballe_zip_conserver')) {
			$files[] = [
				'name' => basename($path),
				'tmp_name' => $path,
			];
		}
		return $files;
	}

	return [];
}

/**
 * Récupérer et mettre en forme la liste des fichiers postes
 * que ce soit via plusieurs input file ou via un input file multiple
 * @param $name : nom de l'input qu'on veut recuperer si on ne veut pas tous les fichiers
 * @return array|string
 *   string en cas d'erreur
 */
function joindre_trouver_http_post_files($name = null) {
	$post = $_FILES;
	$files = [];
	if (is_array($post)) {
		foreach ($post as $input_name => $file) {
			if ($name and $input_name !== $name) {
				continue;
			}
			if (is_array($file['name'])) {
				while (is_countable($file['name']) ? count($file['name']) : 0) {
					$test = [
						'input_name' => $input_name,
						'error' => array_shift($file['error']),
						'name' => array_shift($file['name']),
						'tmp_name' => array_shift($file['tmp_name']),
						'type' => array_shift($file['type']),
					];
					//UPLOAD_ERR_NO_FILE
					if (!($test['error'] == 4)) {
						// un erreur upload
						if (is_string($err = joindre_upload_error($test['error']))) {
							return $err;
						}
						$files[] = $test;
					}
				}
			} else {
				//UPLOAD_ERR_NO_FILE
				if (!($file['error'] == 4)) {
					// un erreur upload
					if (is_string($err = joindre_upload_error($file['error']))) {
						return $err;
					}
					$file['input_name'] = $input_name;
					$files[] = $file;
				}
			}
		}
	}

	return $files;
}


// Erreurs d'upload
// renvoie false si pas d'erreur
// et true si erreur = pas de fichier
// pour les autres erreurs renvoie le message d'erreur
function joindre_upload_error($error) {

	$msg = null;
	if (!$error) {
		return false;
	}
	spip_log("Erreur upload $error -- cf. http://php.net/manual/fr/features.file-upload.errors.php");
	switch ($error) {
		case 4: /* UPLOAD_ERR_NO_FILE */
			return true;

		# on peut affiner les differents messages d'erreur
		case 1: /* UPLOAD_ERR_INI_SIZE */
			$msg = _T(
				'medias:upload_limit',
				['max' => ini_get('upload_max_filesize')]
			);
			break;
		case 2: /* UPLOAD_ERR_FORM_SIZE */
			$msg = _T(
				'medias:upload_limit',
				['max' => ini_get('upload_max_filesize')]
			);
			break;
		case 3: /* UPLOAD_ERR_PARTIAL  */
			$msg = _T(
				'medias:upload_limit',
				['max' => ini_get('upload_max_filesize')]
			);
			break;
		case 6: /* UPLOAD_ERR_NO_TMP_DIR  */
			$msg = _T('medias:erreur_dossier_tmp_manquant');
			break;
		case 7: /* UPLOAD_ERR_CANT_WRITE */
			$msg = _T('medias:erreur_ecriture_fichier');
			break;
		default: /* autre */
			if (!$msg) {
				$msg = _T('pass_erreur') . ' ' . $error
					. '<br />' . propre('[->http://php.net/manual/fr/features.file-upload.errors.php]');
			}
			break;
	}
	spip_log("erreur upload $error", 'medias');
	return $msg;
}

/**
 * Verifier si le fichier poste est un zip
 * Si on sait le deballer, proposer les options necessaires
 *
 * Retourne [liste des fichiers, liste des erreurs, chemin du zip]
 *
 * @param array $files
 * @return false|array<array<string,array>, array<int,string>, string>
 */
function joindre_verifier_zip($files) {
	if (
		function_exists('gzopen')
		&& (count($files) == 1)
		&& !isset($files[0]['distant'])
		&& (
			preg_match('/\.zip$/i', $files[0]['name'])
			|| (isset($files[0]['type']) && $files[0]['type'] == 'application/zip')
		)
	) {
		// on pose le fichier dans le repertoire zip
		// (nota : copier_document n'ecrase pas un fichier avec lui-meme
		// ca autorise a boucler)
		include_spip('inc/getdocument');
		$desc = $files[0];
		$zip = copier_document(
			'zip',
			$desc['name'],
			$desc['tmp_name']
		);

		// Est-ce qu'on sait le lire ?
		include_spip('inc/archives');
		if (
			$zip
			&& ($archive = new Spip\Archives\SpipArchives($zip))
			&& ($infos = $archive->informer())
			&& ($contenu = joindre_decrire_contenu_zip($infos))
			&& ($tmp = sous_repertoire(_DIR_TMP, 'zip'))
			&& rename($zip, $tmp = $tmp . basename($zip))
		) {
			$zip_to_clean = (isset($GLOBALS['visiteur_session']['zip_to_clean']) ?
				unserialize($GLOBALS['visiteur_session']['zip_to_clean']) : []);
			$zip_to_clean[md5($tmp)] = $tmp;
			session_set('zip_to_clean', serialize($zip_to_clean));
			$contenu[] = $tmp;

			return $contenu;
		}
	}

	// ce n'est pas un zip sur lequel il faut demander plus de precisions
	return false;
}

/**
 * Verifier et decrire les fichiers de l'archive, en deux listes :
 * - une liste des noms de fichiers ajoutables
 * - une liste des erreurs (fichiers refuses)
 *
 * @param object $zip
 * @return false|array<array<string,array>, array<int,string>>
 */
function joindre_decrire_contenu_zip($infos) {
	include_spip('action/ajouter_documents');
	// si pas possible de decompacter: installer comme fichier zip joint
	if (empty($infos['fichiers'])) {
		return false;
	}

	// Verifier si le contenu peut etre uploade (verif extension)
	$fichiers = [];
	$erreurs = [];
	foreach ($infos['fichiers'] as $file) {
		if (accepte_fichier_upload($f = $file['filename'])) {
			$fichiers[$f] = $file;
		} else // pas de message pour les dossiers et fichiers caches
		{
			if (substr($f, -1) !== '/' and substr(basename($f), 0, 1) !== '.') {
				$erreurs[] = _T('medias:erreur_upload_type_interdit', ['nom' => $f]);
			}
		}
	}

	// si aucun fichier uploadable : installer comme fichier zip joint
	if (!count($fichiers)) {
		return false;
	}

	ksort($fichiers);

	return [$fichiers, $erreurs];
}


function joindre_deballer_lister_zip($path, $tmp_dir) {
	include_spip('inc/archives');
	$archive = new Spip\Archives\SpipArchives($path);

	if (
		$infos = $archive->informer()
		and $contenu = joindre_decrire_contenu_zip($infos)
	) {
		$fichiers = reset($contenu);
		$fichiers = array_column($fichiers, 'filename');
		if ($archive->deballer($tmp_dir, $fichiers)) {
			$files = [];
			foreach ($fichiers as $fichier) {
				$f = basename($fichier);
				$files[] = [
					'tmp_name' => $tmp_dir . $fichier,
					'name' => $f,
					'titrer' => _request('options_deballe_zip_titrer'),
					'mode' => null
				];
			}
			return $files;
		}
	}

	return _T('avis_operation_impossible');
}

if (!function_exists('fixer_extension_document')) {
	/**
	 * Cherche dans la base le type-mime du tableau representant le document
	 * et corrige le nom du fichier ; retourne array(extension, nom corrige)
	 * s'il ne trouve pas, retourne '' et le nom inchange
	 *
	 * @param array $doc
	 * @return array
	 */
	function fixer_extension_document($doc) {
		$extension = '';
		$name = $doc['name'];
		if (
			preg_match(',\.([^.]+)$,', $name, $r)
			and $t = sql_fetsel(
				'extension',
				'spip_types_documents',
				'extension=' . sql_quote(corriger_extension($r[1]))
			)
		) {
			$extension = $t['extension'];
			$name = preg_replace(',\.[^.]*$,', '', $doc['name']) . '.' . $extension;
		} else {
			if ($t = sql_fetsel('extension', 'spip_types_documents', 'mime_type=' . sql_quote($doc['type']))) {
				$extension = $t['extension'];
				$name = preg_replace(',\.[^.]*$,', '', $doc['name']) . '.' . $extension;
			}
		}

		return [$extension, $name];
	}
}

/**
 * Gestion des fichiers ZIP
 *
 * @param sring $f
 * @return bool|int
 */
function accepte_fichier_upload($f) {
	if (
		!preg_match(',.*__MACOSX/,', $f)
		and !preg_match(',^\.,', basename($f))
	) {
		include_spip('action/ajouter_documents');
		$ext = corriger_extension((strtolower(substr(strrchr($f, '.'), 1))));

		return sql_countsel(
			'spip_types_documents',
			'extension=' . sql_quote($ext) . " AND upload='oui'"
		);
	}
}
