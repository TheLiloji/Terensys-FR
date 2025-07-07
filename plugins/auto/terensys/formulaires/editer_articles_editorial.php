<?php

/**
 * Gestion du formulaire de d'édition de articles_editorial
 *
 * @plugin     Terensys
 * @copyright  2025
 * @author     Terensys
 * @licence    GNU/GPL
 * @package    SPIP\Terensys\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/editer');



/**
 * Identifier le formulaire en faisant abstraction des paramètres qui ne représentent pas l'objet edité
 *
 * @param int|string $id_articles_editorial
 *     Identifiant du articles_editorial. 'new' pour un nouveau articles_editorial.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un articles_editorial source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du articles_editorial, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return string
 *     Hash du formulaire
 */
function formulaires_editer_articles_editorial_identifier_dist($id_articles_editorial = 'new', $retour = '', $lier_trad = 0, $config_fonc = '', $row = [], $hidden = '') {
	return json_encode([intval($id_articles_editorial)]);
}

/**
 * Chargement du formulaire d'édition de articles_editorial
 *
 * Déclarer les champs postés et y intégrer les valeurs par défaut
 *
 * @uses formulaires_editer_objet_charger()
 *
 * @param int|string $id_articles_editorial
 *     Identifiant du articles_editorial. 'new' pour un nouveau articles_editorial.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un articles_editorial source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du articles_editorial, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Environnement du formulaire
 */
function formulaires_editer_articles_editorial_charger_dist($id_articles_editorial = 'new', $retour = '', $lier_trad = 0, $config_fonc = '', $row = [], $hidden = '') {
	$valeurs = formulaires_editer_objet_charger('articles_editorial', $id_articles_editorial, '', $lier_trad, $retour, $config_fonc, $row, $hidden);
	return $valeurs;
}

/**
 * Vérifications du formulaire d'édition de articles_editorial
 *
 * Vérifier les champs postés et signaler d'éventuelles erreurs
 *
 * @uses formulaires_editer_objet_verifier()
 *
 * @param int|string $id_articles_editorial
 *     Identifiant du articles_editorial. 'new' pour un nouveau articles_editorial.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un articles_editorial source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du articles_editorial, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Tableau des erreurs
 */
function formulaires_editer_articles_editorial_verifier_dist($id_articles_editorial = 'new', $retour = '', $lier_trad = 0, $config_fonc = '', $row = [], $hidden = '') {

	$erreurs = formulaires_editer_objet_verifier('articles_editorial', $id_articles_editorial, ['titre']);

	// Vérification personnalisée pour le champ extra 'liens_articles'
	$articles = _request('articles');
	$type = _request('type');

    // Vérifier qu'on a exactement 4 articles sélectionnés
	if ($type == '4_blocs') {
		if (is_array($articles)) {
			$nb = count($articles);
			if ($nb !== 4) {
				$erreurs['articles'] = "Vous devez sélectionner exactement 4 articles.";
			}
		} else {
			$erreurs['articles'] = "Vous devez sélectionner exactement 4 articles.";
		}
	}
    

	return $erreurs;
}

/**
 * Traitement du formulaire d'édition de articles_editorial
 *
 * Traiter les champs postés
 *
 * @uses formulaires_editer_objet_traiter()
 *
 * @param int|string $id_articles_editorial
 *     Identifiant du articles_editorial. 'new' pour un nouveau articles_editorial.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un articles_editorial source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du articles_editorial, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Retours des traitements
 */
function formulaires_editer_articles_editorial_traiter_dist($id_articles_editorial = 'new', $retour = '', $lier_trad = 0, $config_fonc = '', $row = [], $hidden = '') {
	$retours = formulaires_editer_objet_traiter('articles_editorial', $id_articles_editorial, '', $lier_trad, $retour, $config_fonc, $row, $hidden);
	return $retours;
}
