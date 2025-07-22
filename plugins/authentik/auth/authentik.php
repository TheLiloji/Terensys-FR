<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

// Pipeline SPIP: ajoute "authentik" comme service d'auth
function authentifier_liste_disponibles($services) {
    $services['authentik'] = _T('authentik:label_serveur');
    return $services;
}
