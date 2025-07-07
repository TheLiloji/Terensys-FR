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
 * Gestion du formulaire d'édition d'un mot
 *
 * @package SPIP\Mots\Formulaires
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/editer');

/**
 * Chargement du formulaire d'édition d'un mot
 *
 * @see formulaires_editer_objet_charger()
 *
 * @param int|string $id_mot
 *     Identifiant du mot. 'new' pour un nouveau mot.
 * @param int $id_groupe
 *     Identifiant du groupe parent (si connu)
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel 'objet|x' indiquant de lier le mot créé à cet objet,
 *     tel que 'article|3'
 * @param string $dummy1 ?
 * @param string $dummy2 ?
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du mot, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Environnement du formulaire
 **/
function formulaires_editer_mot_charger_dist(
	$id_mot = 'new',
	$id_groupe = 0,
	$retour = '',
	$associer_objet = '',
	$dummy1 = '',
	$dummy2 = '',
	$config_fonc = 'mots_edit_config',
	$row = [],
	$hidden = ''
) {
	$valeurs = formulaires_editer_objet_charger('mot', $id_mot, $id_groupe, '', $retour, $config_fonc, $row, $hidden);
	if ($valeurs['id_parent'] && !$valeurs['id_groupe']) {
		$valeurs['id_groupe'] = $valeurs['id_parent'];
	}

	if (intval($id_mot) and !autoriser('modifier', 'mot', intval($id_mot))) {
		$valeurs['editable'] = '';
	}


	if ($associer_objet) {
		if (intval($associer_objet)) {
			// compat avec l'appel de la forme ajouter_id_article
			$objet = 'article';
			$id_objet = intval($associer_objet);
		} else {
			[$objet, $id_objet] = explode('|', $associer_objet);
		}
	}
	$valeurs['table'] = ($associer_objet ? table_objet($objet) : '');

	// Si nouveau et titre dans l'url : fixer le titre
	if (
		$id_mot === 'oui'
		and $titre = _request('titre')
		and strlen($titre)
	) {
		$valeurs['titre'] = $titre;
	}

	return $valeurs;
}


/**
 * Identifier le formulaire en faisant abstraction des paramètres qui
 * ne representent pas l'objet edité
 *
 * @param int|string $id_mot
 *     Identifiant du mot. 'new' pour un nouveau mot.
 * @param int $id_groupe
 *     Identifiant du groupe parent (si connu)
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel 'objet|x' indiquant de lier le mot créé à cet objet,
 *     tel que 'article|3'
 * @param string $dummy1 ?
 * @param string $dummy2 ?
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du mot, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return string
 *     Hash du formulaire
 **/
function formulaires_editer_mot_identifier_dist(
	$id_mot = 'new',
	$id_groupe = 0,
	$retour = '',
	$associer_objet = '',
	$dummy1 = '',
	$dummy2 = '',
	$config_fonc = 'mots_edit_config',
	$row = [],
	$hidden = ''
) {
	return serialize([intval($id_mot), $associer_objet]);
}

/**
 * Choix par défaut des options de présentation
 *
 * @param array $row
 *     Valeurs de la ligne SQL d'un mot, si connu
 * return array
 *     Configuration pour le formulaire
 */
function mots_edit_config(array $row): array {

	$config = [];
	$config['lignes'] = 8;
	$config['langue'] = $GLOBALS['spip_lang'];
	$config['restreint'] = false;

	return $config;
}

/**
 * Vérification du formulaire d'édition d'un mot
 *
 * @see formulaires_editer_objet_verifier()
 *
 * @param int|string $id_mot
 *     Identifiant du mot. 'new' pour un nouveau mot.
 * @param int $id_groupe
 *     Identifiant du groupe parent (si connu)
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel 'objet|x' indiquant de lier le mot créé à cet objet,
 *     tel que 'article|3'
 * @param string $dummy1 ?
 * @param string $dummy2 ?
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du mot, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Tableau des erreurs
 **/
function formulaires_editer_mot_verifier_dist(
	$id_mot = 'new',
	$id_groupe = 0,
	$retour = '',
	$associer_objet = '',
	$dummy1 = '',
	$dummy2 = '',
	$config_fonc = 'mots_edit_config',
	$row = [],
	$hidden = ''
) {

	$erreurs = formulaires_editer_objet_verifier('mot', $id_mot, ['titre']);
	// verifier qu'un mot du meme groupe n'existe pas avec le meme titre
	// la comparaison accepte un numero absent ou different
	// sinon avertir
	// on ne fait la verification que si c'est une creation de mot ou un retitrage
	if (
		!intval($id_mot)
		or supprimer_numero(_request('titre'))
			!== supprimer_numero(sql_getfetsel('titre', 'spip_mots', 'id_mot=' . intval($id_mot)))
	) {
		if (!(is_countable($erreurs) ? count($erreurs) : 0) and !_request('confirm_titre_mot')) {
			if (
				sql_countsel(
					'spip_mots',
					'titre REGEXP ' . sql_quote('^([0-9]+[.] )?' . preg_quote(supprimer_numero(_request('titre'))) . '$')
					. ' AND id_mot<>' . intval($id_mot)
				)
			) {
				$erreurs['titre'] =
					_T('mots:avis_doublon_mot_cle')
					. " <input type='hidden' name='confirm_titre_mot' value='1' />";
			}
		}
	}

	return $erreurs;
}

/**
 * Traitements du formulaire d'édition d'un mot
 *
 * @param int|string $id_mot
 *     Identifiant du mot. 'new' pour un nouveau mot.
 * @param int $id_groupe
 *     Identifiant du groupe parent (si connu)
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel 'objet|x' indiquant de lier le mot créé à cet objet,
 *     tel que 'article|3'
 * @param string $dummy1 ?
 * @param string $dummy2 ?
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du mot, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Retour des traitements
 **/
function formulaires_editer_mot_traiter_dist(
	$id_mot = 'new',
	$id_groupe = 0,
	$retour = '',
	$associer_objet = '',
	$dummy1 = '',
	$dummy2 = '',
	$config_fonc = 'mots_edit_config',
	$row = [],
	$hidden = ''
) {
	set_request('redirect', '');
	$res = formulaires_editer_objet_traiter('mot', $id_mot, $id_groupe, 0, $retour, $config_fonc, $row, $hidden);

	if (empty($res['message_erreur'])) {
		$id_mot = $res['id_mot'];

		if (!strncmp($retour, 'javascript:', 11) == 0) {
			if (!strlen(parametre_url($retour, 'id_mot') ?? '')) {
				$res['redirect'] = parametre_url($res['redirect'], 'id_mot', '');
			}
		}

		if ($associer_objet) {
			if (intval($associer_objet)) {
				// compat avec l'appel de la forme ajouter_id_article
				$objet = 'article';
				$id_objet = intval($associer_objet);
			} else {
				[$objet, $id_objet] = explode('|', $associer_objet);
			}
			if ($objet and $id_objet and autoriser('modifier', $objet, $id_objet)) {
				include_spip('action/editer_mot');
				mot_associer($id_mot, [$objet => $id_objet]);
				if (isset($res['redirect'])) {
					$res['redirect'] = parametre_url($res['redirect'], 'id_lien_ajoute', $id_mot, '&');
				}
			}
		}
	}

	return $res;
}
