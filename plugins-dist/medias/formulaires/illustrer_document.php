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

function formulaires_illustrer_document_charger_dist($id_document) {
	include_spip('inc/documents');
	$valeurs = sql_fetsel('id_document,mode,id_vignette,extension,media', 'spip_documents', 'id_document=' . intval($id_document));
	if (
		!$valeurs
		or in_array($valeurs['mode'], ['logoon', 'logooff'])
	) {
		return ['editable' => false, 'id' => $id_document];
	}

	$valeurs['id'] = $id_document;
	$valeurs['_hidden'] = "<input name='id_document' value='$id_document' type='hidden' />";
	$valeurs['mode'] = 'vignette'; // pour les id dans le dom
	$vignette = sql_fetsel('fichier,largeur,hauteur,id_document', 'spip_documents', 'id_document=' . $valeurs['id_vignette']);
	if ($vignette) {
		$valeurs['vignette'] = get_spip_doc($vignette['fichier']);
		$valeurs['hauteur'] = $vignette['hauteur'];
		$valeurs['largeur'] = $vignette['largeur'];
		$valeurs['id_vignette'] = $vignette['id_document'];
	} else {
		$valeurs['vignette'] = null;
		$valeurs['hauteur'] = null;
		$valeurs['largeur'] = null;
		$valeurs['id_vignette'] = null;
	}
	$valeurs['_pipeline'] = ['editer_contenu_objet', ['type' => 'illustrer_document', 'id' => $id_document]];

	if (intval($id_document) and !autoriser('modifier', 'document', intval($id_document))) {
		$valeurs['editable'] = '';
	}

	return $valeurs;
}

function formulaires_illustrer_document_verifier_dist($id_document) {
	$erreurs = [];
	if (_request('supprimer')) {
	} else {
		$id_vignette = sql_getfetsel('id_vignette', 'spip_documents', 'id_document=' . intval($id_document));
		$verifier = charger_fonction('verifier', 'formulaires/joindre_document');
		$erreurs = $verifier($id_vignette, 0, '', 'vignette');
	}

	return $erreurs;
}

function formulaires_illustrer_document_traiter_dist($id_document) {
	$id_vignette = sql_getfetsel('id_vignette', 'spip_documents', 'id_document=' . intval($id_document));
	$res = ['editable' => true];
	if (_request('supprimer')) {
		$supprimer_document = charger_fonction('supprimer_document', 'action');
		if ($id_vignette and $supprimer_document($id_vignette)) {
			$res['message_ok'] = _T('medias:vignette_supprimee');
		} else {
			$res['message_erreur'] = _T('medias:erreur_suppression_vignette');
		}
	} else {
		$ajouter_documents = charger_fonction('ajouter_documents', 'action');

		include_spip('inc/joindre_document');
		$files = joindre_trouver_fichier_envoye();

		$ajoute = $ajouter_documents($id_vignette, $files, '', 0, 'vignette');

		if (
			is_numeric(reset($ajoute))
			and $id_vignette = reset($ajoute)
		) {
			include_spip('action/editer_document');
			document_modifier($id_document, ['id_vignette' => $id_vignette, 'mode' => 'document']);
			$res['message_ok'] = _T('medias:document_installe_succes');
		} else {
			$res['message_erreur'] = reset($ajoute);
		}
	}

	// todo :
	// generer les case docs si c'est necessaire
	// rediriger sinon
	return $res;
}
