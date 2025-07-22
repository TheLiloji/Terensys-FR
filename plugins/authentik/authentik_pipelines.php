<?php
if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/authentik_utils');


function _url_action_authentik($action){
    include_spip('inc/actions');
    return generer_url_action($action, '', '', true); // GET + absolue
}

function _sso_en_cours(){
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    return !empty($_SESSION['authentik_in_progress'])
        || _request('action') === 'authentik_redirect'
        || _request('action') === 'authentik_callback';
}

/** Auto-redirect dans l'espace privé */
function authentik_exec_init($flux){
    if (defined('_DIR_RESTREINT')
        && empty($GLOBALS['visiteur_session']['id_auteur'])
        && _request('skip_sso') != 1
        && !_sso_en_cours()
    ) {
        $exec = _request('exec');
        if (!in_array($exec, ['install','logout'])) {
            include_spip('inc/headers');
            redirige_par_entete(_url_action_authentik('authentik_redirect'));
            exit;
        }
    }
    return $flux;
}

/** Auto-redirect sur la page publique de login */
function authentik_recuperer_fond($flux){
    $fond = $flux['args']['fond'] ?? '';
    $page = _request('page');

    $is_login_public = ($page === 'login')
        || ($fond === 'login')
        || ($fond === 'formulaires/login')
        || preg_match(',(^|/)(login)$,', $fond);

    if ($is_login_public
        && empty($GLOBALS['visiteur_session']['id_auteur'])
        && _request('skip_sso') != 1
        && !_sso_en_cours()
    ) {
        include_spip('inc/headers');
        redirige_par_entete(_url_action_authentik('authentik_redirect'));
        exit;
    }
    return $flux;
}
