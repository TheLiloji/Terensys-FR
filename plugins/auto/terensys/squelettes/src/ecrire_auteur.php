<?php

// formulaires_ecrire_auteur_traiter_dist();

function formulaires_ecrire_auteur_traiter_dist($mail) {
    echo "hello";
    $adres = _request('email_message_auteur');
    $sujet = _request('sujet_message_auteur');

    $sujet = '[' . supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site'])) . '] '
        . _T('info_message_2') . ' '
        . $sujet;
    $texte = _request('texte_message_auteur');
    $texte .= "\n-- $adres";

    $texte .= "\n\n-- " . _T('envoi_via_le_site') . ' '
        . supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site']))
        . ' (' . $GLOBALS['meta']['adresse_site'] . "/) --\n";
    $envoyer_mail = charger_fonction('envoyer_mail', 'inc');

    $corps = array(
        'texte' => $texte,
        'repondre_a' => $adres,
        'headers' => array(
            "X-Originating-IP: " . $GLOBALS['ip'],
        ),
    );

    if ($envoyer_mail($mail, $sujet, $corps)) {
        $message = _T('form_prop_message_envoye');

        return array('message_ok' => $message);
    } else {
        $message = _T('pass_erreur_probleme_technique');

        return array('message_erreur' => $message);
    }
}