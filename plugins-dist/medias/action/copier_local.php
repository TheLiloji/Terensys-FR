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
 * Fonction appellee lorsque l'utilisateur clique sur le bouton
 * 'copier en local' (document/portfolio).
 * Il s'agit de la partie logique, c'est a dire que cette fonction
 * realise la copie.
 *
 * @param null $id_document
 * @return bool|mixed|string
 */
function action_copier_local_dist($id_document = null) {

	if (!$id_document) {
		// Recupere les arguments.
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();

		$id_document = intval($arg);
	}

	if (!$id_document) {
		spip_log("action_copier_local_dist $arg pas compris");

		return false;
	} else {
		// arguments recuperes, on peut maintenant appeler la fonction.
		return action_copier_local_post($id_document);
	}
}

/**
 *
 * @param  $id_document
 * @return bool|mixed|string
 */
function action_copier_local_post($id_document) {

	// Il faut la source du document pour le copier
	$row = sql_fetsel('mode,fichier, descriptif, credits', 'spip_documents', 'id_document=' . intval($id_document));
	$source = $row['fichier'];

	// si la source est bien un fichier distant
	// sinon c'est une donnee moisie, on ne fait rien
	include_spip('inc/distant');
	if (
		tester_url_absolue($source)
		and valider_url_distante($source)
	) {
		// on fait une copie locale en verifiant aussi l'URL finale qui a été récupérée
		$fichier = copie_locale($source, 'auto', null, null, 'valider_url_distante');
		// on revalide la source *apres* copie car si elle est controlee par un serveur dns malicieux elle peut etre changeante
		if (
			$fichier
			and valider_url_distante($source)
		) {
			$fichier = _DIR_RACINE . $fichier;
			$files = [];
			$files[] = ['tmp_name' => $fichier, 'name' => basename($fichier)];
			$ajouter_documents = charger_fonction('ajouter_documents', 'action');
			spip_log("convertit doc $id_document en local: $source => $fichier", 'medias');
			$liste = [];
			$ajouter_documents($id_document, $files, '', 0, $row['mode'], $liste);

			spip_unlink($fichier);

			// ajouter l'origine du document aux credits
			include_spip('action/editer_document');
			document_modifier(
				$id_document,
				['credits' => ($row['credits'] ? $row['credits'] . ', ' : '') . $source]
			);

			return true;
		} else {
			spip_log("echec copie locale $source", 'medias' . _LOG_ERREUR);
			if ($fichier) {
				@unlink(_DIR_RACINE . $fichier);
			}
		}
	} else {
		spip_log("echec copie locale $source n'est pas une URL distante", 'medias' . _LOG_ERREUR);
	}

	return _T('medias:erreur_copie_fichier', ['nom' => $source]);
}
