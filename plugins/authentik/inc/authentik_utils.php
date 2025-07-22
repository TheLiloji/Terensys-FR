<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

/** Charge la config Authentik depuis config/authentik.php ou inc/authentik_config.php */
function authentik_get_cfg() {
     // Récupère la config, où qu'elle soit
    static $cfg = null;
    if ($cfg === null) {
        include_spip('../config/authentik');
        $cfg = $GLOBALS['authentik_config'] ?? [];
    }
    return $cfg;
}

/** URL GET absolue vers une action SPIP */
function authentik_url_action($action){
    include_spip('inc/actions');
    return generer_url_action($action, '', '', true);
}

/** Savoir si on est déjà dans le flux SSO */
function authentik_sso_en_cours(){
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    return !empty($_SESSION['authentik_in_progress'])
        || in_array(_request('action'), ['authentik_redirect','authentik_callback']);
}
