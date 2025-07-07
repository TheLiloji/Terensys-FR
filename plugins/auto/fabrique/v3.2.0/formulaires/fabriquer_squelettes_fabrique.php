<?php

/**
 * Gestion du formulaire permettant de préparer des gabarits de squelettes
 * pour la fabrique
 *
 * @package SPIP\Fabrique\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) { return;
}


/**
 * Chargement du formulaire d'aide à la création de gabarits de squelette pour la fabrique
 *
 * @return array
 *     Environnement du formulaire
 */
function formulaires_fabriquer_squelettes_fabrique_charger_dist() {
	return [
		'code_squelette' => '',
		'code_resultat' => '',
		'echappements' => [
			'php', 'crochets', 'diese', 'tag_boucle', 'idiome', 'inclure'
		],
		'transformer_objet' => '',
	];
}

/**
 * Traitement du formulaire d'aide à la création de gabarits de squelette pour la fabrique
 *
 * @return array
 *     Retour des traitements
 */
function formulaires_fabriquer_squelettes_fabrique_traiter_dist() {

	$echappements = _request('echappements');
	$source = _request('code_squelette');
	$echap = [
		'diese'       => ['#' => '\#'],
		'crochets'    => ['[' => '\[', ']' => '\]'],
		'parentheses' => ['(' => '\(', ')' => '\)'],
		'accolades'   => ['{' => '\{', '}' => '\}'],
		'php'         => ['<?php' => '#PHP'], // doit être apres le \# et avant le \<
		'tag_boucle'  => ['<B' => '\<B', '</B' => '\</B', '<//B' => '\<//B'],
		'inclure'     => ['<INCLURE' => '\<INCLURE'],
		'idiome'      => ['<:' => '\<:'],
	];

	// 1) Échapper les échappements du squelette.
	$chercher = [];
	$remplacer = [];

	foreach ($echap as $e) {
		foreach ($e as $cherche => $remplace) {
			$chercher[] = $remplace;
			$remplacer[] = '\\' . $remplace;
		}
	}
	$skel = str_replace($chercher, $remplacer, $source);

	// 2) Échapper les caractères de SPIP demandes


	// on ne garde que ceux demandes
	$echap = array_intersect_key($echap, array_flip($echappements));
	$chercher = [];
	$remplacer = [];

	foreach ($echap as $e) {
		foreach ($e as $cherche => $remplace) {
			$chercher[] = $cherche;
			$remplacer[] = $remplace;
		}
	}

	// on remplace.
	$skel = str_replace($chercher, $remplacer, $skel);

	// 3) Si un texte d'objet doit être transformé, le faire.
	// Si l'objet est un article :
	// articles > #LOBJET
	// ARTICLES > #MOBJET
	// article  > #TYPE
	// id_article > #ID_OBJET
	// ID_ARTICLE > #MID_OBJET
	// spip_articles > #TABLE
	if ($table = _request('transformer_objet')) {
		$objet = table_objet($table);
		$id_objet = id_table_objet($table);
		$type = objet_type($table);

		$transform = [
			// d'abord les recherches longues
			// id_article
			$id_objet             => '#ID_OBJET',
			strtoupper($id_objet) => '#MID_OBJET',
			// spip_articles
			$table                => '#TABLE',
			// 'articles' avant 'article'
			$objet                => '#LOBJET',
			strtoupper($objet)    => '#MOBJET',
			// article
			$type . '_edit'       => '[(#TYPE)]_edit',
			$type                 => '#TYPE',
		];


		$skel = str_replace(array_keys($transform), array_values($transform), $skel);
	}

	set_request('code_resultat', $skel);

	$res = [
		'editable' => 'oui',
		'message_ok' => _T('fabrique:calcul_effectue'),
	];
	return $res;
}
