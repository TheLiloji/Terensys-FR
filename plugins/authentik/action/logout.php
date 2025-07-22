<?php
/**
 * Surcharge de l'action logout :
 *  - détruit TOUTE la session/cookies
 *  - redirige vers spip.php?page=login&skip_sso=1 (one-shot)
 */
if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/headers');
include_spip('inc/session');
include_spip('inc/cookie');
include_spip('inc/filtres');

function action_logout_dist() {

    // 1) Detruire la session SPIP
    // (supprime le fichier de session et le cookie spip_session)
    if (isset($_COOKIE['spip_session'])) {
        $session = charger_fonction('session', 'inc');
        $session($GLOBALS['visiteur_session']['id_auteur'] ?? 0);
        // cookie spip_session expiré
        spip_setcookie('spip_session', $_COOKIE['spip_session'], [
            'expires'  => time() - 3600,
            'httponly' => true,
        ]);
    }

    // 2) Detruire la session PHP (au cas où)
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    @session_destroy();

    // 3) On nettoie quelques cookies SPIP courants (par sécurité)
    foreach (['spip_session', 'spip_admin', 'spip_lang'] as $c) {
        if (isset($_COOKIE[$c])) {
            setcookie($c, '', time() - 3600, '/');
        }
    }

    $dest = generer_url_public('login');
    $dest = parametre_url($dest, 'skip_sso', '1', '&');
    $dest = url_absolue($dest);

    redirige_par_entete($dest);
    exit;
}
