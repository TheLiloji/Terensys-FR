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

// acces aux documents joints securise
// verifie soit que le demandeur est authentifie
// soit que le document est publie, c'est-a-dire
// joint a au moins 1 article ou rubrique publie

function action_acceder_document_dist() {
	$doc = [];
	include_spip('inc/documents');

	// $file exige pour eviter le scan id_document par id_document
	$f = rawurldecode(_request('file'));
	$file = get_spip_doc($f);
	$arg = rawurldecode(_request('arg'));

	$status = false;
	if (
		strpos($f, '../') !== false
		or preg_match(',^\w+://,', $f)
	) {
		$status = 403;
	} else {
		if (!file_exists($file) or !is_readable($file)) {
			$status = 404;
		} else {
			$where = 'D.fichier=' . sql_quote(set_spip_doc($file))
				. ($arg ? ' AND D.id_document=' . intval($arg) : '');

			$doc = sql_fetsel(
				'D.id_document, D.titre, D.fichier, T.mime_type, T.inclus, D.extension',
				'spip_documents AS D LEFT JOIN spip_types_documents AS T ON D.extension=T.extension',
				$where
			);
			if (!$doc) {
				$status = 404;
			} else {
				// ETag pour gerer le status 304
				$ETag = md5($file . ': ' . filemtime($file));
				if (
					isset($_SERVER['HTTP_IF_NONE_MATCH'])
					and $_SERVER['HTTP_IF_NONE_MATCH'] == $ETag
				) {
					http_response_code(304); // Not modified
					exit;
				} else {
					header('ETag: ' . $ETag);
				}

				//
				// Verifier les droits de lecture du document
				// en controlant la cle passee en argument
				//
				include_spip('inc/securiser_action');
				$cle = _request('cle');
				if (!verifier_cle_action($doc['id_document'] . ',' . $f, $cle)) {
					spip_log("acces interdit $cle erronee");
					$status = 403;
				}
			}
		}
	}

	switch ($status) {
		case 403:
			include_spip('inc/minipres');
			echo minipres();
			break;

		case 404:
			http_response_code(404);
			include_spip('inc/minipres');
			echo minipres(_T('erreur') . ' 404', _T('medias:info_document_indisponible'));
			break;

		default:
			include_spip('inc/livrer_fichier');

			$options = ['attachment' => false];
			if ($doc['inclus'] == 'non') {
				$options['attachment'] = true;
				// Si le fichier a un titre avec extension,
				// ou si c'est un nom bien connu d'Unix, le prendre
				// sinon l'ignorer car certains navigateurs pataugent
				if (
					isset($doc['titre'])
					and (preg_match('/^\w+[.]\w+$/', $doc['titre']) or $doc['titre'] == 'Makefile')
				) {
					$options['attachment'] = $doc['titre'];
				}
			}
			spip_livrer_fichier($file, $doc['mime_type'], $options);
			exit;
			break;
	}
}
