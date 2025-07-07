<?php

/**
 * Utilisation de l'action supprimer pour l'objet personne
 *
 * @plugin     Terensys
 * @copyright  2025
 * @author     Terensys
 * @licence    GNU/GPL
 * @package    SPIP\Terensys\Action
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}



/**
 * Action pour supprimer un·e personne
 *
 * Vérifier l'autorisation avant d'appeler l'action.
 *
 * @param null|int $arg
 *     Identifiant à supprimer.
 *     En absence de id utilise l'argument de l'action sécurisée.
**/
function action_supprimer_personne_dist($arg = null) {
	$need_confirm = false;
	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
		$need_confirm = true;
	}
	$arg = intval($arg);

	if ($need_confirm) {
		$ok = confirmer_supprimer_personne_avant_action(_T('personne:confirmer_supprimer_personne'), _T('item_oui') . '! ' . _T('personne:supprimer_personne'));
	}

	// cas suppression
	if (autoriser('supprimer', 'personne', $arg)) {
		if ($arg) {
			$objet = sql_fetsel('*', 'spip_personnes', 'id_personne=' . sql_quote($arg));
			$qui = (!empty($GLOBALS['visiteur_session']['id_auteur']) ? 'auteur #' . $GLOBALS['visiteur_session']['id_auteur'] : 'IP ' . $GLOBALS['ip']);
			spip_log("SUPPRESSION personne#$arg par $qui : " . json_encode($objet), 'suppressions' . _LOG_INFO_IMPORTANTE);

			sql_delete('spip_personnes', 'id_personne=' . sql_quote($arg));
			include_spip('action/editer_logo');
			logo_supprimer('spip_personnes', $arg, 'on');
			logo_supprimer('spip_personnes', $arg, 'off');

			// invalider le cache
			include_spip('inc/invalideur');
			suivre_invalideur("id='personne/$arg'");
		}
		else {
			spip_log("action_supprimer_personne_dist $arg pas compris");
		}
	}
}

/**
 * Confirmer avant suppression si on arrive par un bouton action
 * @param string $titre
 * @param string $titre_bouton
 * @param string|null $url_action
 * @return bool
 */
function confirmer_supprimer_personne_avant_action($titre, $titre_bouton, $url_action = null) {
	$options = [];
	include_spip('inc/filtres');
	if(_request('redirect')) {
		$options['footer'] = lien_ou_expose(_request('redirect'), _T('retour'));
	}
	
	if (!$url_action) {
		$url_action = self();
		$action = _request('action');
		$url_action = parametre_url($url_action, 'action', $action, '&');
	}
	else {
		$action = parametre_url($url_action, 'action');
	}
	$arg = parametre_url($url_action, 'arg');
	$confirm = md5("$action:$arg:" . realpath(__FILE__));
	if (_request('confirm_action') === $confirm) {
		return true;
	}

	$url_confirm = parametre_url($url_action, 'confirm_action', $confirm, '&');
	$bouton_action = bouton_action($titre_bouton, $url_confirm);
	$corps = "<div style='text-align:center;'>$bouton_action</div>";

	include_spip('inc/minipres');
	echo minipres($titre, $corps, $options);
	exit;
}
