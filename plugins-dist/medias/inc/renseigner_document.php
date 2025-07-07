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
 * Fonctions pour compléter les informations connues d'un document
 *
 * @package SPIP\Medias\Renseigner
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Récuperer les infos distantes d'une URL,
 * et renseigner pour une insertion en base
 *
 * Utilise une variable static car appellée plusieurs fois au cours du même hit
 * (vérification puis traitement)
 *
 * Un plugin peut avec le pipeline renseigner_document_distant renseigner
 * les informations fichier et mode avant l'analyse et traitement par ce plugin,
 * qui dans ce cas ne les fera pas. Exemple : OEmbed
 *
 * @pipeline_appel renseigner_document_distant
 *
 * @param string $source
 *     URL du document
 * @return array|string
 *     Informations sur le fichier distant, sinon message d'erreur.
 *     Sans erreur, retourne un tableau :
 *
 *     - source : URL du fichier
 *     - distant : Est-ce un fichier distant ?
 *     - mode : Mode d'inclusion
 *     - fichier : Chemin local du fichier s'il a été recopié
 */
function renseigner_source_distante($source, $callback_valider_url = '') {
	static $infos = [];
	if (isset($infos[$source])) {
		return $infos[$source];
	}

	include_spip('inc/distant');
	// on passe la source dans le pipeline, le premier plugin
	// qui est capable de renseigner complete
	// fichier et mode + tous les autres champs a son gout
	// ex : oembed
	$a = pipeline('renseigner_document_distant', ['source' => $source]);
	
	// Si un plugin a renvoyé un message d'erreur non vide, c'est ce résultat en priorité
	if (isset($a['message_erreur']) and is_string($a['message_erreur']) and $a['message_erreur']) {
		$a = $a['message_erreur'];
	}
	
	// si la source est encore là et que ce n'est pas déjà une erreur, en revenir à la
	// méthode traditionnelle : chargement de l'url puis analyse
	if (
		!is_string($a)
		&& (!isset($a['fichier']) || !isset($a['mode']))
	) {
		if (!$a = recuperer_infos_distantes($a['source'], ['callback_valider_url' => $callback_valider_url])) {
			return _T('medias:erreur_chemin_distant', ['nom' => $source]);
		}
		# NB: dans les bonnes conditions (fichier autorise et pas trop gros)
		# $a['fichier'] est une copie locale du fichier
		unset($a['body']);
		$a['distant'] = 'oui';
		$a['mode'] = 'document';
		if (!empty($a['fichier'])) {
			$a['copie_locale'] = $a['fichier'];
		}
		$a['fichier'] = set_spip_doc($source);
	}

	// stocker pour la seconde demande
	return $infos[$source] = $a;
}

/**
 * Renseigner les informations de taille et dimension d'un document
 *
 * Récupère les informations de taille (largeur / hauteur / type_image / taille) d'un document
 * Utilise pour cela les fonctions du répertoire metadatas/*
 *
 * Ces fonctions de récupérations peuvent retourner d'autres champs si ces champs sont définis
 * comme editable dans la déclaration de la table spip_documents
 *
 * @todo
 *     Renommer cette fonction sans "_image"
 *
 * @param string $fichier
 *     Le fichier à examiner
 * @param string $ext
 *     L'extension du fichier à examiner
 * @param bool $distant
 *     Indique que le fichier peut etre distant, on essaiera alors d'en recuperer un bout pour en lire les meta infos
 * @return array|string $infos
 *
 *     - Si c'est une chaîne, c'est une erreur
 *     - Si c'est un tableau, l'ensemble des informations récupérées du fichier
 */
function renseigner_taille_dimension_image($fichier, $ext, $distant = false) {

	$infos = [
		'largeur' => 0,
		'hauteur' => 0,
		'type_image' => '',
		'taille' => 0
	];

	// Quelques infos sur le fichier
	if (
		!$fichier
		or !@file_exists($fichier)
		or !$infos['taille'] = @intval(filesize($fichier))
	) {
		if ($distant) {
			// on ne saura pas la taille
			unset($infos['taille']);

			// recuperer un debut de fichier 512ko semblent suffire
			$tmp = _DIR_TMP . md5($fichier);
			$res = recuperer_url($fichier, ['file' => $tmp, 'taille_max' => 512 * 1024]);
			if (!$res) {
				spip_log("Echec copie du fichier $fichier", 'medias');

				return _T('medias:erreur_copie_fichier', ['nom' => $fichier]);
			}
			$fichier = $tmp;
		} else {
			spip_log("Echec copie du fichier $fichier", 'medias');

			return _T('medias:erreur_copie_fichier', ['nom' => $fichier]);
		}
	}

	// chercher une fonction de description
	$meta = [];
	if ($metadata = charger_fonction($ext, 'metadata', true)) {
		$meta = $metadata($fichier);
	} else {
		$media = sql_getfetsel('media_defaut', 'spip_types_documents', 'extension=' . sql_quote($ext));
		if ($metadata = charger_fonction($media, 'metadata', true)) {
			$meta = $metadata($fichier);
		}
	}

	$meta = pipeline(
		'renseigner_document',
		['args' => ['extension' => $ext, 'fichier' => $fichier], 'data' => $meta]
	);

	include_spip('inc/filtres'); # pour objet_info()
	$editables = objet_info('document', 'champs_editables');
	foreach ($meta as $m => $v) {
		if (isset($infos[$m]) or in_array($m, $editables)) {
			$infos[$m] = $v;
		}
	}

	return $infos;
}

/**
 * @param string $fichier
 * @param string $ext
 * @return bool
 */
function sanitizer_document($fichier, $ext) {
	// verifier que le fichier existe, sinon on ne peut rien faire
	if (
		!$fichier
		or !@file_exists($fichier)
	) {
		return false;
	}
	if ($sanitizer = charger_fonction($ext, 'sanitizer', true)) {
		return $sanitizer($fichier);
	}

	return false;
}
