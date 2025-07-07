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
 * Action editer_document
 *
 * @param int $arg
 * @return array
 */
function action_editer_document_dist($arg = null) {

	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// Envoi depuis le formulaire de creation d'un document
	if (!$id_document = intval($arg)) {
		$id_document = document_inserer();
	}

	if (!$id_document) {
		return [0, ''];
	} // erreur

	$err = document_modifier($id_document);

	return [$id_document, $err];
}

/**
 * Creer un nouveau document
 *
 * @param int $id_parent
 *     inutilise, pas de parent pour les documents
 * @param array|null $set
 * @return int
 */
function document_inserer($id_parent = null, $set = null) {

	$champs = [
		'statut' => 'prop',
		'date' => 'NOW()',
	];

	if ($set) {
		$champs = array_merge($champs, $set);
	}

	// Envoyer aux plugins
	$champs = pipeline(
		'pre_insertion',
		[
			'args' => [
				'table' => 'spip_documents',
			],
			'data' => $champs
		]
	);
	$id_document = sql_insertq('spip_documents', $champs);
	pipeline(
		'post_insertion',
		[
			'args' => [
				'table' => 'spip_documents',
				'id_objet' => $id_document
			],
			'data' => $champs
		]
	);

	return $id_document;
}


/**
 * Enregistre une revision de document.
 * $set est un contenu (par defaut on prend le contenu via _request())
 *
 * @param int $id_document
 * @param array|null $set
 * @return string|null
 */
function document_modifier($id_document, $set = null) {

	include_spip('inc/modifier');
	include_spip('inc/filtres');

	// champs normaux
	$champs = collecter_requests(
		// white list
		objet_info('document', 'champs_editables'),
		// black list
		['parents', 'ajout_parents'],
		// donnees eventuellement fournies
		$set
	);


	$invalideur = '';
	$indexation = false;

	// Si le document est publie, invalider les caches et demander sa reindexation
	$t = sql_getfetsel('statut', 'spip_documents', 'id_document=' . intval($id_document));
	if ($t == 'publie') {
		$invalideur = "id='id_document/$id_document'";
		$indexation = true;
	}

	$ancien_fichier = '';
	// si le fichier est modifie, noter le nom de l'ancien pour faire le menage
	if (isset($champs['fichier'])) {
		$ancien_fichier = sql_getfetsel('fichier', 'spip_documents', 'id_document=' . intval($id_document));
	}

	if (
		$err = objet_modifier_champs(
			'document',
			$id_document,
			[
			'data' => $set,
			'invalideur' => $invalideur,
			'indexation' => $indexation
			],
			$champs
		)
	) {
		return $err;
	}

	// nettoyer l'ancien fichier si necessaire
	if (
		isset($champs['fichier']) // un plugin a pu interdire la modif du fichier en virant le champ
		and $champs['fichier']
		and $ancien_fichier // on avait bien note le nom du fichier avant la modif
		and $ancien_fichier !== $champs['fichier'] // et il a ete modifie
		and !tester_url_absolue($ancien_fichier)
		and @file_exists($f = get_spip_doc($ancien_fichier))
	) {
		spip_unlink($f);
	}

	// Changer le statut du document ?
	// le statut n'est jamais fixe manuellement mais decoule de celui des objets lies
	$champs = collecter_requests(['parents', 'ajouts_parents'], [], $set);
	if (document_instituer($id_document, $champs)) {
		//
		// Post-modifications
		//

		// Invalider les caches
		include_spip('inc/invalideur');
		suivre_invalideur("id='document/$id_document'");
	}
}


/**
 * determiner le statut d'un document : prepa/publie
 * si on trouve un element joint sans champ statut ou avec un statut='publie' alors le doc est publie aussi
 *
 * @param int $id_document
 * @param array $champs
 * @return bool
 */
function document_instituer($id_document, $champs = []) {

	$statut = $champs['statut'] ?? null;
	$date_publication = $champs['date_publication'] ?? null;
	if (isset($champs['parents'])) {
		medias_revision_document_parents($id_document, $champs['parents']);
	}
	if (isset($champs['ajout_parents'])) {
		medias_revision_document_parents($id_document, $champs['ajout_parents'], true);
	}

	$row = sql_fetsel('statut,date_publication', 'spip_documents', 'id_document=' . intval($id_document));
	$statut_ancien = $row['statut'];
	$date_publication_ancienne = $row['date_publication'];

	$champs = [];

	/* Autodetermination du statut si non fourni */
	if (is_null($statut)) {
		$determiner_statut_document = charger_fonction('determiner_statut_document', 'inc');
		$champs = $determiner_statut_document($id_document, $statut_ancien, $date_publication_ancienne);

		// rien a faire
		if ($champs === false) {
			return false;
		}
	}
	else {
		if ($statut !== $statut_ancien) {
			$champs['statut'] = $statut;
		}
	}

	if (
		!is_null($date_publication)
		and empty($champs['date_publication'])
		and $date_publication != $date_publication_ancienne
	) {
		$champs['date_publication'] = $date_publication;
	}

	// Envoyer aux plugins
	$champs = pipeline(
		'pre_edition',
		[
			'args' => [
				'table' => 'spip_documents',
				'id_objet' => $id_document,
				'action' => 'instituer',
				'statut_ancien' => $statut_ancien,
				'date_ancienne' => $date_publication_ancienne,
			],
			'data' => $champs
		]
	);

	if (!(is_countable($champs) ? count($champs) : 0)) {
		return false;
	}

	sql_updateq('spip_documents', $champs, 'id_document=' . intval($id_document));
	if (!empty($champs['statut'])) {
		$publier_rubriques = sql_allfetsel(
			'id_objet',
			'spip_documents_liens',
			"objet='rubrique' AND id_document=" . intval($id_document)
		);
		if (is_countable($publier_rubriques) ? count($publier_rubriques) : 0) {
			include_spip('inc/rubriques');
			foreach ($publier_rubriques as $r) {
				calculer_rubriques_if($r['id_objet'], ['statut' => $champs['statut']], $statut_ancien, false);
			}
		}
	}

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='document/$id_document'");

	pipeline(
		'post_edition',
		[
			'args' => [
				'table' => 'spip_documents',
				'id_objet' => $id_document,
				'action' => 'instituer',
				'statut_ancien' => $statut_ancien,
				'date_ancienne' => $date_publication_ancienne,
			],
			'data' => $champs
		]
	);

	return true;
}


/**
 * Revision des parents d'un document
 * chaque parent est liste au format objet|id_objet
 *
 * @param int $id_document
 * @param array $parents
 * @param bool $ajout
 */
function medias_revision_document_parents($id_document, $parents = null, $ajout = false) {
	include_spip('inc/autoriser');

	if (!is_array($parents)) {
		return;
	}

	$insertions = [];
	$objets_parents = []; // array('article'=>array(12,23))

	// au format objet|id_objet
	foreach ($parents as $p) {
		$p = explode('|', $p);
		if (
			preg_match('/^[a-z0-9_]+$/i', $objet = $p[0])
			and (($p[1] = intval($p[1])) or in_array($objet, ['site', 'rubrique']))
		) { // securite
			$objets_parents[$p[0]][] = $p[1];
		}
	}

	include_spip('action/editer_liens');
	// les liens actuels
	$liens = objet_trouver_liens(['document' => $id_document], '*');
	$deja_parents = [];
	// si ce n'est pas un ajout, il faut supprimer les liens actuels qui ne sont pas dans $objets_parents
	if (!$ajout) {
		foreach ($liens as $k => $lien) {
			if (!isset($objets_parents[$lien['objet']]) or !in_array($lien['id_objet'], $objets_parents[$lien['objet']])) {
				if (autoriser('dissocierdocuments', $lien['objet'], $lien['id_objet'])) {
					objet_dissocier(['document' => $id_document], [$lien['objet'] => $lien['id_objet']]);
				}
				unset($liens[$k]);
			} else {
				$deja_parents[$lien['objet']][] = $lien['id_objet'];
			}
		}
	}

	// trier les objets à traiter : ne pas prendre en compte ceux qui sont déjà associés ou qu'on n'a pas le droit d'associer
	foreach ($objets_parents as $objet => $ids) {
		foreach ($ids as $k => $id) {
			if (
				(
					isset($deja_parents[$objet])
					and in_array($id, $deja_parents[$objet])
				)
				or !autoriser('associerdocuments', $objet, $id)
			) {
				unset($objets_parents[$objet][$k]);
			}
		}
	}
	objet_associer(['document' => $id_document], $objets_parents);
}
