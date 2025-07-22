<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

use Jumbojett\OpenIDConnectClient;

function action_authentik_redirect_dist() {
    require_once __DIR__ . '/../vendor/autoload.php';
    include_spip('inc/filtres');
    include_spip('inc/headers');
    include_spip('inc/session');
    include_spip('inc/authentik_utils');

    $cfg = authentik_get_cfg();
    $issuer        = $cfg['issuer'];
    $client_id     = $cfg['client_id'];
    $client_secret = $cfg['client_secret'];

    $callback_url = authentik_url_action('authentik_callback');
    $callback_url = url_absolue($callback_url);

    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $_SESSION['authentik_in_progress'] = true;
    session_write_close();

    $oidc = new OpenIDConnectClient($issuer, $client_id, $client_secret);
    $oidc->setRedirectURL($callback_url);
    $oidc->addScope(['openid','email','profile']);
    $oidc->authenticate();
    exit;
}
