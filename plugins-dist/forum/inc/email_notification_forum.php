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
 * Construitre l'email personalise de notification d'un forum
 *
 * @param array $t
 * @param string $email
 * @param array $contexte
 * @return string
 */
function inc_email_notification_forum_dist($t, $email, $contexte = []) {
	static $contextes_store = [];

	if (!isset($contextes_store[$t['id_forum']])) {
		$url = '';
		$id_forum = $t['id_forum'];

		if ($t['statut'] == 'prive') { # forum prive
		if ($t['id_objet']) {
				$url = generer_objet_url($t['id_objet'], $t['objet'], '', 'forum' . $id_forum, false);
		}
		} else {
			if ($t['statut'] == 'privrac') { # forum general
			$url = generer_url_ecrire('forum') . '#forum' . $id_forum;
			} else {
				if ($t['statut'] == 'privadm') { # forum des admins
				$url = generer_url_ecrire('forum', 'quoi=admin') . '#forum' . $id_forum;
				} else {
					if ($t['statut'] == 'publie') { # forum publie
					$url = generer_objet_url($id_forum, 'forum', '', 'forum' . $id_forum, true);
					} else #  forum modere, spam, poubelle direct ....
					{
						$url = generer_url_ecrire('controler_forum', 'debut_id_forum=' . $id_forum);
					}
				}
			}
		}

		if (!$url) {
			spip_log("forum $id_forum sans referent", 'notifications');
			$url = './';
		}
		if ($t['id_objet']) {
			include_spip('inc/filtres');
			$t['titre_source'] = generer_objet_info($t['id_objet'], $t['objet'], 'titre');
		}

		$t['url'] = $url;

		// detecter les url des liens du forum
		// pour la moderation (permet de reperer les SPAMS avec des liens caches)
		include_spip('inc/filtres');

		$links = [];
		foreach ($t as $champ => $v) {
			// il faut appliquer le traitement de raccourci car sinon on rate des liens sous forme [->..] utilises par les spammeurs !
			$v = appliquer_traitement_champ($v, $champ, 'forum', $contexte);
			$links = $links + extraire_balises($v, 'a');
		}
		$links = extraire_attribut($links, 'href');
		$links = implode("\n", $links);
		$t['liens'] = $links;

		$contextes_store[$t['id_forum']] = $t;
	}

	$fond = 'notifications/forum_poste';
	if (isset($contexte['fond'])) {
		$fond = $contexte['fond'];
		unset($contexte['fond']);
	}
	$t = array_merge($contextes_store[$t['id_forum']], $contexte);
	// Rechercher eventuellement la langue du destinataire
	if (null !== ($l = sql_getfetsel('lang', 'spip_auteurs', 'email=' . sql_quote($email)))) {
		$l = lang_select($l);
	}

	$parauteur = (strlen($t['auteur']) <= 2) ? '' :
		(' ' . _T('forum_par_auteur', [
					'auteur' => $t['auteur']
				]) .
			($t['email_auteur'] ? ' <' . $t['email_auteur'] . '>' : ''));

	$titre = textebrut(typo($t['titre_source']));
	if ($titre) {
		$forum_poste_par = _T(
			$t['objet'] == 'article' ? 'forum:forum_poste_par' : 'forum:forum_poste_par_generique',
			['parauteur' => $parauteur, 'titre' => $titre, 'objet' => $t['objet']]
		);
	} else {
		$forum_poste_par = _T('forum:forum_poste_par_court', ['parauteur' => $parauteur]);
	}

	$t['par_auteur'] = $forum_poste_par;

	$envoyer_mail = charger_fonction('envoyer_mail', 'inc'); // pour nettoyer_titre_email
	$corps = recuperer_fond($fond, $t);

	if ($l) {
		lang_select();
	}

	return $corps;
}
