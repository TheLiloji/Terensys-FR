<?php

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

function formulaires_contact_form_traiter_dist() {
    $adres = _request('email_message_auteur');
    $sujet = _request('sujet_message_auteur');
    $texte = _request('texte_message_auteur');
    
    $facteur = unserialize($GLOBALS['meta']['facteur']);
    $destinataire = $facteur['adresse_envoi_email'];
    
    $html = recuperer_fond("email",array(
        'texte' => $texte,
        'sujet' => $sujet,
        'adresse' => $adres
    ));

    $corps = array(
        'html' => $html,
        'texte' => $texte,
        'cc' => $adres,
    );

    $envoyer_mail = charger_fonction('envoyer_mail', 'inc');

	try {
		$retour = $envoyer_mail($destinataire, $sujet, $corps);
	} catch (Exception $e) {
		return $e->getMessage();
	}

    if (!$retour) {
		return _T('facteur:erreur') . ' ' . _T('facteur:erreur_dans_log');
	}else {
        return array('message_ok' => _T('form_prop_message_envoye'));
    }

	return '';
}
