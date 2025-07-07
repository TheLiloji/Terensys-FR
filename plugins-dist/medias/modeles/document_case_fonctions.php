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

if (!defined('_BOUTON_MODE_IMAGE')) {
	define('_BOUTON_MODE_IMAGE', true);
}

function affiche_bouton_mode_image_portfolio($inclus) {
	if (!defined('_COMPORTEMENT_HISTORIQUE_PORTFOLIO') or _COMPORTEMENT_HISTORIQUE_PORTFOLIO === false) {
		return '';
	}
	if ($inclus === 'image' and _BOUTON_MODE_IMAGE) {
		return ' ';
	}
	return '';
}

include_spip('inc/documents'); // pour la fonction affiche_raccourci_doc
function medias_raccourcis_doc(
	$id_document,
	$titre,
	$descriptif,
	$inclus,
	$largeur,
	$hauteur,
	$mode,
	$vu,
	$media = null
) {
	$raccourci = '';
	$doc = 'doc';

	if (!defined('_COMPORTEMENT_HISTORIQUE_PORTFOLIO') or _COMPORTEMENT_HISTORIQUE_PORTFOLIO === false) {
		// Affichage du raccourci <doc...> correspondant
		$raccourci = medias_raccourcis_doc_groupe($doc, $id_document);
	}
	else {
		// DEPRECATED
		// on le garde juste pour la version SPIP 4.0, activable par la constante _COMPORTEMENT_HISTORIQUE_PORTFOLIO
		if ($mode == 'image' and (strlen($descriptif . $titre) == 0)) {
			$doc = 'img';
		}

		// Affichage du raccourci <doc...> correspondant
		$raccourci = medias_raccourcis_doc_groupe($doc, $id_document);

		if (
			$mode == 'document'
			and ($inclus == 'embed' or $inclus == 'image')
			and (($largeur > 0 and $hauteur > 0)
				or in_array($media, ['video', 'audio']))
		) {
			$raccourci =
				'<span class="raccourcis_group_label">' . _T('medias:info_inclusion_vignette') . '</span>'
				. $raccourci
				. '<span class="raccourcis_group_label">' . _T('medias:info_inclusion_directe') . '</span>'
				. medias_raccourcis_doc_groupe('emb', $id_document);
		}
	}

	return "<div class='raccourcis'>" . $raccourci . '</div>';
}


function medias_raccourcis_doc_groupe($doc, $id_document): string {
	$raccourci =
		affiche_raccourci_doc($doc, $id_document, '')
		. affiche_raccourci_doc($doc, $id_document, 'left', true)
		. affiche_raccourci_doc($doc, $id_document, 'center', true)
		. affiche_raccourci_doc($doc, $id_document, 'right', true);
	return "<div class='groupe-btns'>$raccourci</div>";
}
