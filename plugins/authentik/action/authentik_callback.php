<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

use Jumbojett\OpenIDConnectClient;

function action_authentik_callback_dist() {
    // Lib OIDC
    require_once __DIR__ . '/../vendor/autoload.php';

    // SPIP
    include_spip('inc/headers');
    include_spip('inc/filtres');         // url_absolue(), parametre_url() ...
    include_spip('inc/session');         // session_set(), session_get() ...
    include_spip('base/abstract_sql');   // sql_*
    include_spip('inc/actions');         // generer_url_action()
    include_spip('inc/authentik_utils'); // authentik_get_cfg(), authentik_url_action()

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // --- Config ---
    $cfg           = authentik_get_cfg();
    $issuer        = $cfg['issuer'];
    $client_id     = $cfg['client_id'];
    $client_secret = $cfg['client_secret'];

    // URL de callback (absolue)
    $callback = authentik_url_action('authentik_callback');
    $callback = url_absolue($callback);

    // Client OIDC
    $oidc = new OpenIDConnectClient($issuer, $client_id, $client_secret);
    $oidc->setRedirectURL($callback);
    $oidc->addScope(['openid','email','profile']);

    try {
        // Authentification + vérification du token
        $oidc->authenticate();

        // Récupère les infos utilisateur
        $userinfo  = $oidc->getVerifiedClaims();
        $email     = $userinfo->email ?? null;
        if (!$email) {
            throw new Exception("OIDC: email manquant dans les claims");
        }
        $nom       = $userinfo->name  ?? $email;
        $id_token  = method_exists($oidc, 'getIdToken') ? $oidc->getIdToken() : null;

        // Cherche ou crée l’auteur SPIP
        $row = sql_fetsel(
            ['id_auteur','login','statut'],
            'spip_auteurs',
            ['email=' . sql_quote($email)]
        );

        if (!$row) {
            $id = sql_insertq('spip_auteurs', [
                'email'  => $email,
                'login'  => $email,
                'statut' => '0minirezo',
                'nom'    => $nom,
                'pass'   => ''
            ]);
            $row = [
                'id_auteur' => $id,
                'login'     => $email,
                'statut'    => '0minirezo'
            ];
        }

        // Alimente la session SPIP correctement
        $hash_env = md5(($_SERVER['HTTP_USER_AGENT'] ?? '')
            . ($GLOBALS['meta']['alea_ephemere'] ?? ''));

        $visiteur = [
            'id_auteur' => (int) $row['id_auteur'],
            'login'     => $row['login'],
            'statut'    => $row['statut'],
            'nom'       => $nom,
            'email'     => $email,
            'webmestre' => ($row['statut'] === '0minirezo') ? 'oui' : 'non',
            'restreint' => [],
            'hash_env'  => $hash_env,
            'ip_change' => 'non',
            'id_token'  => $id_token,
        ];

        foreach ($visiteur as $k => $v) {
            session_set($k, $v);
            $now = $_SERVER['REQUEST_TIME'] ?? time();
            session_set('date_session', $now);
            session_set('ip', $_SERVER['REMOTE_ADDR'] ?? '');
            session_set('hash_env', md5(($_SERVER['HTTP_USER_AGENT'] ?? '').($GLOBALS['meta']['alea_ephemere'] ?? '')));
            session_set('ip_change', 'non');

        }

        // Fin du flux SSO
        unset($_SESSION['authentik_in_progress']);
        session_write_close();

        // OK → espace privé
        redirige_par_entete(generer_url_ecrire('accueil'));
        exit;

    } catch (Exception $e) {
        // En cas d’erreur, on log et on retourne au login natif (skip possible)
        spip_log('OIDC callback error: '.$e->getMessage(), 'authentik.'._LOG_CRITIQUE);
        unset($_SESSION['authentik_in_progress']);
        session_write_close();

        redirige_par_entete(generer_url_ecrire('login', 'message=erreur_sso'));
        exit;
    }
}
