<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion de l'action ajouter_documents
 *
 * @package SPIP\Medias\Action
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/getdocument');
include_spip('inc/documents');
include_spip('inc/choisir_mode_document'); // compat core
include_spip('inc/renseigner_document');

/**
 * Ajouter des documents
 *
 * @param int $id_document
 *   Document à remplacer, ou pour une vignette, l'id_document de maman
 *   0 ou 'new' pour une insertion
 * @param array $files
 *   Tableau de tableaux de propriétés pour chaque document à insérer
 * @param string $objet
 *   Objet auquel associer le document
 * @param int $id_objet
 *   id_objet
 * @param string $mode
 *   Mode par défaut si pas precisé pour le document
 * @return array
 *   Liste des id_documents inserés
 */
function action_ajouter_documents_dist($id_document, $files, $objet, $id_objet, $mode) {
	$ajouter_un_document = charger_fonction('ajouter_un_document', 'action');
	$ajoutes = [];

	// on ne peut mettre qu'un seul document a la place d'un autre ou en vignette d'un autre
	if (intval($id_document)) {
		$ajoutes[] = $ajouter_un_document($id_document, reset($files), $objet, $id_objet, $mode);
	} else {
		foreach ($files as $file) {
			$ajoutes[] = $ajouter_un_document('new', $file, $objet, $id_objet, $mode);
		}
	}

	return $ajoutes;
}

/**
 * Ajouter un document (au format $_FILES)
 *
 * @param int $id_document
 *   Document à remplacer, ou pour une vignette, l'id_document de maman
 *   0 ou 'new' pour une insertion
 * @param array $file
 *   Propriétes au format $_FILE étendu :
 *
 *   - string tmp_name : source sur le serveur
 *   - string name : nom du fichier envoye
 *   - bool titrer : donner ou non un titre a partir du nom du fichier
 *   - bool distant : pour utiliser une source distante sur internet
 *   - string mode : vignette|image|documents|choix
 * @param string $objet
 *   Objet auquel associer le document
 * @param int $id_objet
 *   id_objet
 * @param string $mode
 *   Mode par défaut si pas precisé pour le document
 * @return array|bool|int|mixed|string|unknown
 *
 *   - int : l'id_document ajouté (opération réussie)
 *   - string : une erreur s'est produit, la chaine est le message d'erreur
 *
 */
function action_ajouter_un_document_dist($id_document, $file, $objet, $id_objet, $mode) {

	$source = $file['tmp_name'];
	$nom_envoye = $file['name'];

	// passer en minuscules le nom du fichier, pour eviter les collisions
	// si le file system fait la difference entre les deux il ne detectera
	// pas que Toto.pdf et toto.pdf
	// et on aura une collision en cas de changement de file system
	$file['name'] = strtolower(translitteration($file['name']));


	// Sécurité : si jamais il existe deja une entrée dans la BDD avec ce chemin de document, remettre le document dans tmp, ce qui permettra ensuite qu'il soit dupliqué, et qu'il n'y ait pas deux entrées en base avec la même ligne 'fichier'.
	// Cela n'arrive que si $file indique un document qui se trouve déjà dans IMG.
	while (sql_getfetsel('fichier', 'spip_documents', 'fichier='.sql_quote(set_spip_doc($file['tmp_name'])))) {
		$tmp = tempnam(is_dir(_DIR_TRANSFERT) ? _DIR_TRANSFERT : _DIR_TMP, $file['tmp_name']);
		if (deplacer_fichier_upload($file['tmp_name'], $tmp)) {
			$file['tmp_name'] = $tmp;
		} else {
			spip_log('Erreur lors de la tenative de copie de '.$file['tmp_name'].' en '.$tmp, 'medias' . _LOG_ERREUR);
			break;
		}
	}

	// Pouvoir definir dans mes_options.php que l'on veut titrer tous les documents par d?faut
	if (!defined('_TITRER_DOCUMENTS')) {
		define('_TITRER_DOCUMENTS', false);
	}

	$titrer = $file['titrer'] ?? _TITRER_DOCUMENTS;
	$mode = ((isset($file['mode']) and $file['mode']) ? $file['mode'] : $mode);

	include_spip('inc/modifier');
	if (
		isset($file['distant']) and $file['distant']
		and !in_array($mode, ['choix', 'auto', 'image', 'document'])
	) {
		spip_log("document distant $source accepte sans verification, mode=$mode", 'medias' . _LOG_INFO_IMPORTANTE);
		include_spip('inc/distant');
		$file['tmp_name'] = _DIR_RACINE . copie_locale($source);
		$source = $file['tmp_name'];
		unset($file['distant']);
	}

	// Documents distants : pas trop de verifications bloquantes, mais un test
	// via une requete HEAD pour savoir si la ressource existe (non 404), si le
	// content-type est connu, et si possible recuperer la taille, voire plus.
	if (isset($file['distant']) and $file['distant']) {
		if (!tester_url_absolue($source)) {
			return _T('medias:erreur_chemin_distant', ['nom' => $source]);
		}
		include_spip('inc/distant');
		$source = str_replace(["'",'"','<'], ['%27','%22','%3C'], $source);
		if (is_array($a = renseigner_source_distante($source))) {
			$champs = $a;
			# NB: dans les bonnes conditions (fichier autorise et pas trop gros)
			# $a['copie_locale'] est une copie locale du fichier

			// voir si le document a besoin d'un nettoyage et le cas echeant relire ses infos apres
			if (!empty($champs['copie_locale']) and file_exists($champs['copie_locale'])) {
				$res_sanitize = sanitizer_document($champs['copie_locale'], $champs['extension']);
				$infos = renseigner_taille_dimension_image($champs['copie_locale'], $champs['extension']);
			}
			else {
				$infos = renseigner_taille_dimension_image($champs['fichier'], $champs['extension'], true);
			}

			// on ignore erreur eventuelle sur $infos car on est distant, ca ne marche pas forcement
			if (is_array($infos)) {
				foreach ($infos as $k => $v) {
					if (!empty($v) or empty($champs[$k])) {
						$champs[$k] = $v;
					}
				}
			}

			unset($champs['type_image']);
		} // on ne doit plus arriver ici, car l'url distante a ete verifiee a la saisie !
		else {
			spip_log("Echec du lien vers le document $source, abandon");

			return $a; // message d'erreur
		}
	} else { // pas distant
		$champs = [
			'distant' => 'non'
		];

		$champs['titre'] = '';
		if ($titrer) {
			if ($titrer_document = charger_fonction('titrer_document', 'inc', true)) {
				$champs['titre'] = $titrer_document($nom_envoye);
			}
			else {
				$titre = substr($nom_envoye, 0, strrpos($nom_envoye, '.')); // Enlever l'extension du nom du fichier
				$titre = preg_replace(',[[:punct:][:space:]]+,u', ' ', $titre);
				$champs['titre'] = preg_replace(',\.([^.]+)$,', '', $titre);
			}
		}

		if (!is_array($fichier = fixer_fichier_upload($file, $mode))) {
			return is_string($fichier) ?
				$fichier : _T('medias:erreur_upload_type_interdit', ['nom' => $file['name']]);
		}

		$champs['inclus'] = $fichier['inclus'];
		$champs['extension'] = $fichier['extension'];
		$champs['fichier'] = $fichier['fichier'];

		/**
		 * Récupère les informations du fichier
		 * -* largeur
		 * -* hauteur
		 * -* type_image
		 * -* taille
		 * -* ses metadonnées si une fonction de metadatas/ est présente
		 */
		$infos = renseigner_taille_dimension_image($champs['fichier'], $champs['extension']);
		if (is_string($infos)) {
			// c'est un message d'erreur !
			return $infos;
		}

		// lorsqu’une image arrive avec une mauvaise extension par rapport au mime type, adapter.
		// Exemple : si extension .jpg mais le contenu est un png
		if (!empty($infos['type_image']) and $infos['type_image'] !== $champs['extension']) {
			spip_log('Image `' . $file['name'] . '` mauvaise extension. Correcte : ' . $infos['type_image'], 'medias' . _LOG_INFO);
			$subdir = determiner_sous_dossier_document($infos['type_image'], $file['name'] . '.' . $infos['type_image'], $mode);
			$new = copier_document($infos['type_image'], $file['name'] . '.' . $infos['type_image'], $champs['fichier'], $subdir);
			if ($new) {
				supprimer_fichier($champs['fichier']);
				$champs['fichier'] = $new;
				$champs['extension'] = $infos['type_image'];
				$infos = renseigner_taille_dimension_image($champs['fichier'], $champs['extension']);
				if (is_string($infos)) {
					// c'est un message d'erreur !
					return $infos;
				}
				spip_log('> Image `' . $file['name'] . '` renommée en : ' . basename($champs['fichier']), 'medias' . _LOG_INFO);
			} else {
				spip_log('! Image  `' . $file['name'] . '` non renommée en extension : ' . $champs['extension'], 'medias' . _LOG_INFO_IMPORTANTE);
			}
		}

		// voir si le document a besoin d'un nettoyage et le cas echeant relire ses infos apres
		if (sanitizer_document($champs['fichier'], $champs['extension'])) {
			$infos = renseigner_taille_dimension_image($champs['fichier'], $champs['extension']);
		}

		$champs = array_merge($champs, $infos);

		// Si mode == 'choix', fixer le mode image/document
		if (in_array($mode, ['choix', 'auto'])) {
			$choisir_mode_document = charger_fonction('choisir_mode_document', 'inc');
			$mode = $choisir_mode_document($champs, $champs['inclus'] == 'image', $objet);
		}
		$champs['mode'] = $mode;

		// ne pas tester les tailles max de logos et documents définies dans les defines _MAX_SIZE, _MAX_WIDTH & _MAX_HEIGHT
		// lors de la migration des logos en base cf logo_migrer_en_base()
		if (!isset($GLOBALS['logo_migrer_en_base'])) {
			if (($test = verifier_taille_document_acceptable($champs)) !== true) {
				spip_unlink($champs['fichier']);

				return $test; // erreur sur les dimensions du fichier
			}
		}

		unset($champs['type_image']);
		unset($champs['inclus']);
		$champs['fichier'] = set_spip_doc($champs['fichier']);
	}

	// si le media est pas renseigne, le faire, en fonction de l'extension
	if (!isset($champs['media'])) {
		$champs['media'] = sql_getfetsel(
			'media_defaut',
			'spip_types_documents',
			'extension=' . sql_quote($champs['extension'])
		);
	}

	// lier le parent si necessaire
	// attention au cas particulier du site 0 utilisé pour le logo du site
	if ($objet and (($id_objet = intval($id_objet)) or in_array($objet, ['site', 'rubrique']))) {
		$champs['parents'][] = "$objet|$id_objet";
	}

	// "mettre a jour un document" si on lui
	// passe un id_document
	if ($id_document = intval($id_document)) {
		unset($champs['titre']); // garder le titre d'origine
		unset($champs['date']); // garder la date d'origine
		unset($champs['descriptif']); // garder la desc d'origine
		// unset($a['distant']); # on peut remplacer un doc statique par un doc distant
		// unset($a['mode']); # on peut remplacer une image par un document ?
	}

	include_spip('action/editer_document');
	// Installer le document dans la base
	if (!$id_document) {
		if ($id_document = document_inserer()) {
			spip_log(
				'ajout du document ' . $file['tmp_name'] . ' ' . $file['name'] . "  (M '$mode' T '$objet' L '$id_objet' D '$id_document')",
				'medias'
			);
		} else {
			spip_log(
				'Echec document_inserer() du document ' . $file['tmp_name'] . ' ' . $file['name'] . "  (M '$mode' T '$objet' L '$id_objet' D '$id_document')",
				'medias' . _LOG_ERREUR
			);
		}
	}
	if (!$id_document) {
		return _T('medias:erreur_insertion_document_base', ['fichier' => '<em>' . $file['name'] . '</em>']);
	}

	document_modifier($id_document, $champs);

	// permettre aux plugins de faire des modifs a l'ajout initial
	// ex EXIF qui tourne les images si necessaire
	// Ce plugin ferait quand même mieux de se placer dans metadata/jpg.php
	pipeline(
		'post_edition',
		[
			'args' => [
				'table' => 'spip_documents', // compatibilite
				'table_objet' => 'documents',
				'spip_table_objet' => 'spip_documents',
				'type' => 'document',
				'id_objet' => $id_document,
				'champs' => array_keys($champs),
				'serveur' => '', // serveur par defaut, on ne sait pas faire mieux pour le moment
				'action' => 'ajouter_document',
				'operation' => 'ajouter_document', // compat <= v2.0
			],
			'data' => $champs
		]
	);

	return $id_document;
}

/**
 * Sous-repertoire dans lequel on stocke le document
 * en regle general $ext/ sauf pour les logo
 * @param $ext
 * @param $fichier
 * @param $mode
 * @return mixed
 */
function determiner_sous_dossier_document($ext, $fichier, $mode) {

	// si mode un logoxx on met dans logo/
	if (strncmp($mode, 'logo', 4) === 0) {
		return 'logo';
	}

	return $ext;
}

/**
 * Vérifie la possibilité d'uploader une extension
 *
 * Vérifie aussi si l'extension est autorisée pour le mode demandé
 * si on connait le mode à ce moment là
 *
 * @param string $source
 *     Nom du fichier
 * @param string $mode
 *     Mode d'inclusion du fichier, si connu
 * @return array|bool|string
 *
 *     - array : extension acceptée (tableau descriptif).
 *       Avec un index 'autozip' si il faut zipper
 *     - false ou message d'erreur si l'extension est refusée
 */
function verifier_upload_autorise($source, $mode = '') {
	$infos = ['fichier' => $source];
	$res = false;
	if (
		preg_match(',\.([a-z0-9]+)(\?.*)?$,i', $source, $match)
		and $ext = $match[1]
	) {
		$ext = corriger_extension(strtolower($ext));
		if (
			$res = sql_fetsel(
				'extension,inclus,media_defaut as media',
				'spip_types_documents',
				'extension=' . sql_quote($ext) . " AND upload='oui'"
			)
		) {
			$infos = array_merge($infos, $res);
		}
	}
	if (!$res) {
		if (
			$res = sql_fetsel(
				'extension,inclus,media_defaut as media',
				'spip_types_documents',
				"extension='zip' AND upload='oui'"
			)
		) {
			$infos = array_merge($infos, $res);
			$res['autozip'] = true;
		}
	}
	if ($mode and $res) {
		// verifier en fonction du mode si une fonction est proposee
		if ($verifier_document_mode = charger_fonction('verifier_document_mode_' . $mode, 'inc', true)) {
			$check = $verifier_document_mode($infos); // true ou message d'erreur sous forme de chaine
			if ($check !== true) {
				$res = $check;
			}
		}
	}

	if (!$res or is_string($res)) {
		spip_log("Upload $source interdit ($res)", _LOG_INFO_IMPORTANTE);
	}

	return $res;
}


/**
 * Tester le type de document
 *
 * - le document existe et n'est pas de taille 0 ?
 * - interdit a l'upload ?
 * - quelle extension dans spip_types_documents ?
 * - est-ce "inclus" comme une image ?
 *
 * Le zipper si necessaire
 *
 * @param array $file
 *     Au format $_FILES
 * @param string $mode
 *     Mode d'inclusion du fichier, si connu
 * @return array|string
 */
function fixer_fichier_upload($file, $mode = '') {
	/**
	 * On vérifie que le fichier existe et qu'il contient quelque chose
	 */
	if (is_array($row = verifier_upload_autorise($file['name'], $mode))) {
		$subdir = determiner_sous_dossier_document($row['extension'], $file['name'], $mode);
		if (!isset($row['autozip'])) {
			$row['fichier'] = copier_document($row['extension'], $file['name'], $file['tmp_name'], $subdir);
			/**
			 * On vérifie que le fichier a une taille
			 * si non, on le supprime et on affiche une erreur
			 */
			if ($row['fichier'] && (!$taille = @intval(filesize(get_spip_doc($row['fichier']))))) {
				spip_log('Echec copie du fichier ' . $file['tmp_name'] . ' (taille de fichier indéfinie)');
				spip_unlink(get_spip_doc($row['fichier']));
				return _T('medias:erreur_copie_fichier', ['nom' => $file['tmp_name']]);
			} else {
				return $row;
			}
		} else {
			// creer un zip comme demande
			// pour encapsuler un fichier dont l'extension n'est pas supportee
			unset($row['autozip']);

			$ext = 'zip';
			if (!$tmp_dir = tempnam(_DIR_TMP, 'tmp_upload')) {
				return false;
			}

			spip_unlink($tmp_dir);
			@mkdir($tmp_dir);

			include_spip('inc/charsets');
			$tmp = $tmp_dir . '/' . translitteration($file['name']);

			// conserver l'extension dans le nom de fichier, par exemple toto.js => toto.js.zip
			$file['name'] .= '.' . $ext;

			// deplacer le fichier tmp_name dans le dossier tmp
			deplacer_fichier_upload($file['tmp_name'], $tmp, true);

			$source = _DIR_TMP . basename($tmp_dir) . '.' . $ext;

			include_spip('inc/archives');
			$archive = new Spip\Archives\SpipArchives($source);
			$res = $archive->emballer([$tmp]);

			effacer_repertoire_temporaire($tmp_dir);
			if (!$res) {
				spip_log("Echec creation du zip $source", 'medias' . _LOG_ERREUR);
				return _T('medias:erreur_ecriture_fichier');
			}

			$row['fichier'] = copier_document($row['extension'], $file['name'], $source, $subdir);
			spip_unlink($source);
			/**
			 * On vérifie que le fichier a une taille
			 * si non, on le supprime et on affiche une erreur
			 */
			if ($row['fichier'] && (!$taille = @intval(filesize(get_spip_doc($row['fichier']))))) {
				spip_log('Echec copie du fichier ' . $file['tmp_name'] . ' (taille de fichier indéfinie)');
				spip_unlink(get_spip_doc($row['fichier']));

				return _T('medias:erreur_copie_fichier', ['nom' => $file['tmp_name']]);
			} else {
				return $row;
			}
		}
	} else {
		return $row;
	} // retourner le message d'erreur
}

/**
 * Verifier si le fichier respecte les contraintes de tailles
 *
 * @param  array $infos
 * @return bool|mixed|string
 */
function verifier_taille_document_acceptable(&$infos) {

	$is_logo = in_array($infos['mode'], ['logoon', 'logooff']);

	$verifier_taille_document_acceptable = charger_fonction('verifier_taille_document_acceptable', 'inc');
	$res = $verifier_taille_document_acceptable($infos, $is_logo);

	// si erreur, on arrete la
	if ($res !== true) {
		return $res;
	}

	// verifier en fonction du mode si une fonction est proposee
	if ($verifier_document_mode = charger_fonction('verifier_document_mode_' . $infos['mode'], 'inc', true)) {
		return $verifier_document_mode($infos);
	}

	return true;
}
