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

function forum_compte_messages_from($email, $id_forum) {
	static $mem = [];

	if (isset($mem[$email])) {
		return $mem[$email];
	}

	// sinon on fait une requete groupee pour essayer de ne le faire qu'une fois pour toute la liste
	$emails = sql_allfetsel(
		'DISTINCT email_auteur',
		'spip_forum',
		'id_forum>' . intval($id_forum - 50) . ' AND id_forum<' . intval($id_forum + 50)
	);
	$emails = array_column($emails, 'email_auteur');
	$emails = array_filter($emails);
	// et compter
	$counts = sql_allfetsel(
		'email_auteur,count(id_forum) AS N',
		'spip_forum',
		sql_in('email_auteur', $emails),
		'email_auteur'
	);

	foreach ($counts as $c) {
		$mem[$c['email_auteur']] = $c['N'];
	}

	return $mem[$email];
}

/**
 * Titre du lien "Répondre à ce ..."
 * @param $objet
 * @return string
 */
function forum_titre_lien_repondre_a($objet) {
	switch ($objet) {
		case 'article':
			$titre = _T('forum:lien_reponse_article');
			break;
		case 'rubrique':
			$titre = _T('forum:lien_reponse_rubrique');
			break;
		case 'breve':
			$titre = _T('forum:lien_reponse_breve_2');
			break;
		case 'site':
		case 'syndic':
			$titre = _T('forum:lien_reponse_site_reference');
			break;
		default:
			$titre = _T($objet . ':lien_reponse_' . $objet, [], ['force' => false]);
			if (!$titre) {
				$titre = _T('forum:lien_reponse_message');
			}
			break;
	}
	return $titre;
}
