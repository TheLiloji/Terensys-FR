<?php

/**
 * Gestion du formulaire de d'édition de partenaire
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
 * @param int|string $id_partenaire
 *     Identifiant du partenaire. 'new' pour un nouveau partenaire.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel `objet|x` indiquant de lier le partenaire créé à cet objet,
 *     tel que `article|3`
 * @param int $lier_trad
 *     Identifiant éventuel d'un partenaire source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du partenaire, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return string
 *     Hash du formulaire
 */
function formulaires_editer_partenaire_identifier_dist($id_partenaire = 'new', $retour = '', $associer_objet = '', $lier_trad = 0, $config_fonc = '', $row = [], $hidden = '') {
	return json_encode([intval($id_partenaire), $associer_objet]);
}

/**
 * Chargement du formulaire d'édition de partenaire
 *
 * Déclarer les champs postés et y intégrer les valeurs par défaut
 *
 * @uses formulaires_editer_objet_charger()
 *
 * @param int|string $id_partenaire
 *     Identifiant du partenaire. 'new' pour un nouveau partenaire.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel `objet|x` indiquant de lier le partenaire créé à cet objet,
 *     tel que `article|3`
 * @param int $lier_trad
 *     Identifiant éventuel d'un partenaire source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du partenaire, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Environnement du formulaire
 */
function formulaires_editer_partenaire_charger_dist($id_partenaire = 'new', $retour = '', $associer_objet = '', $lier_trad = 0, $config_fonc = '', $row = [], $hidden = '') {
	$valeurs = formulaires_editer_objet_charger('partenaire', $id_partenaire, '', $lier_trad, $retour, $config_fonc, $row, $hidden);
	return $valeurs;
}

/**
 * Vérifications du formulaire d'édition de partenaire
 *
 * Vérifier les champs postés et signaler d'éventuelles erreurs
 *
 * @uses formulaires_editer_objet_verifier()
 *
 * @param int|string $id_partenaire
 *     Identifiant du partenaire. 'new' pour un nouveau partenaire.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel `objet|x` indiquant de lier le partenaire créé à cet objet,
 *     tel que `article|3`
 * @param int $lier_trad
 *     Identifiant éventuel d'un partenaire source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du partenaire, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Tableau des erreurs
 */
function formulaires_editer_partenaire_verifier_dist($id_partenaire = 'new', $retour = '', $associer_objet = '', $lier_trad = 0, $config_fonc = '', $row = [], $hidden = '') {

	$erreurs = formulaires_editer_objet_verifier('partenaire', $id_partenaire, ['nom', 'description']);


	return $erreurs;
}

/**
 * Traitement du formulaire d'édition de partenaire
 *
 * Traiter les champs postés
 *
 * @uses formulaires_editer_objet_traiter()
 *
 * @param int|string $id_partenaire
 *     Identifiant du partenaire. 'new' pour un nouveau partenaire.
 * @param string $retour
 *     URL de redirection après le traitement
 * @param string $associer_objet
 *     Éventuel `objet|x` indiquant de lier le partenaire créé à cet objet,
 *     tel que `article|3`
 * @param int $lier_trad
 *     Identifiant éventuel d'un partenaire source d'une traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL du partenaire, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Retours des traitements
 */
function formulaires_editer_partenaire_traiter_dist($id_partenaire = 'new', $retour = '', $associer_objet = '', $lier_trad = 0, $config_fonc = '', $row = [], $hidden = '') {
	$retours = formulaires_editer_objet_traiter('partenaire', $id_partenaire, '', $lier_trad, $retour, $config_fonc, $row, $hidden);

	// Un lien a prendre en compte ?
	if ($associer_objet and $id_partenaire = $retours['id_partenaire']) {
		list($objet, $id_objet) = explode('|', $associer_objet);

		if ($objet and $id_objet and autoriser('modifier', $objet, $id_objet)) {
			include_spip('action/editer_liens');

			objet_associer(['partenaire' => $id_partenaire], [$objet => $id_objet]);

			if (isset($retours['redirect'])) {
				$retours['redirect'] = parametre_url($retours['redirect'], 'id_lien_ajoute', $id_partenaire, '&');
			}
		}
	}

	return $retours;
}
