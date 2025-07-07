<?php

/**
 * Déclarations d'autorisations et utilisations de pipelines
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Pipelines
 **/

/**
 * Fonction du pipeline autoriser. N'a rien à faire
 *
 * @pipeline autoriser
 */
function svp_autoriser() {
}

/**
 * Autoriser l'iconification (mettre un logo) d'un dépot
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_depot_iconifier_dist($faire, $type, $id, $qui, $opt) {
	return true;
}

/**
 * Autoriser l'ajout d'un plugin
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_plugins_ajouter_dist($faire, $type, $id, $qui, $opt) {
	if (!defined('_AUTORISER_TELECHARGER_PLUGINS')) {
		define('_AUTORISER_TELECHARGER_PLUGINS', true);
	}

	return _AUTORISER_TELECHARGER_PLUGINS and autoriser('webmestre');
}

/**
 * Autoriser l'ajout d'un dépôt
 *
 * Soit on a l'autorisation de voir un plugin, soit SVP est en mode non runtime
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_depots_ajouter_dist($faire, $type, $id, $qui, $opt) {
	if (autoriser('ajouter', '_plugins', $id, $qui, $opt)) {
		return true;
	}
	// en mode non runtime (site d'api de plugin), on autorise le choix des depots aux webmestres
	if (autoriser('webmestre') and (lire_config('svp/mode_runtime', 'oui') === 'oui' ? false : true)) {
		return true;
	}
	return false;
}

/**
 * Ne pas afficher par défaut les paquets,dépots,plugins locaux dans les boucles
 *
 * On n'affiche dans les boucles (PLUGINS) (DEPOTS) et (PAQUETS)
 * que les éléments distants par défaut (on cache les locaux).
 *
 * Utiliser {tout} pour tout avoir.
 * Utiliser {tout}{id_depot=0} pour avoir les plugins ou paquets locaux.
 *
 * @pipeline pre_boucle
 * @param Boucle $boucle Description de la boucle
 * @return Boucle        Description de la boucle
 **/
function svp_pre_boucle($boucle) {

	// DEPOTS, PAQUETS
	// Pour DEPOTS, on n'a jamais id_depot=0 dedans... donc... pas la peine.
	if (
		$boucle->type_requete == 'paquets'
		# OR $boucle->type_requete == 'depots'
	) {
		$id_table = $boucle->id_table;
		$m_id_depot = $id_table . '.id_depot';
		// Restreindre aux depots distants
		if (
			#!isset($boucle->modificateur['criteres']['id_depot']) &&
			!isset($boucle->modificateur['tout'])
		) {
			$boucle->where[] = ["'>'", "'$m_id_depot'", "'\"0\"'"];
		}
	} // PLUGINS
	elseif ($boucle->type_requete == 'plugins') {
		$id_table = $boucle->id_table;
		/*
		// les modificateurs ne se creent que sur les champs de la table principale
		// pas sur une jointure, il faut donc analyser les criteres passes pour
		// savoir si l'un deux est un 'id_depot'...

		$id_depot = false;
		foreach($boucle->criteres as $c){
			if (($c->op == 'id_depot') // {id_depot} ou {id_depot?}
			OR ($c->param[0][0]->texte == 'id_depot')) // {id_depot=x}
			{
				$id_depot = true;
				break;
			}
		}
		*/
		if (
			#	!$id_depot &&
			!isset($boucle->modificateur['tout'])
		) {
			// Restreindre aux plugins distant (id_depot > 0)
			$boucle->from['depots_plugins'] = 'spip_depots_plugins';
			$boucle->where[] = ["'='", "'depots_plugins.id_plugin'", "'$id_table.id_plugin'"];
			$boucle->where[] = ["'>'", "'depots_plugins.id_depot'", "'\"0\"'"];
		}
	}

	return $boucle;
}

/**
 * Enlever les id_ des tables SVP du critère selections conditionnelles,
 * ailleurs que sur les tables de SVP
 *
 * @param array $flux
 * @return array
 */
function svp_exclure_id_conditionnel($flux) {
	if (
		!in_array(
			$flux['args']['table'],
			['spip_depots', 'spip_plugins', 'spip_paquets']
		)
	) {
		$flux['data'] = array_merge($flux['data'], ['id_depot', 'id_paquet', 'id_plugin']);
	}
	return $flux;
}

/**
 * Ajoute Bootstrap dropdown aux plugins chargés
 *
 * @param array $flux
 *     Liste des js chargés
 * @return array
 *     Liste complétée des js chargés
**/
function svp_jquery_plugins($flux) {
	if (test_espace_prive()) {
		$flux[] = 'lib/bootstrap/js/popper.js';
		$flux[] = 'lib/bootstrap/js/util.js';
		$flux[] = 'lib/bootstrap/js/dropdown.js';
	}
	return $flux;
}

/**
 * Ajoute Bootstrap dropdown aux css chargées dans le privé
 *
 * @param string $flux Contenu du head HTML concernant les CSS
 * @return string       Contenu du head HTML concernant les CSS
**/
function svp_header_prive_css($flux) {

	$css = find_in_path('lib/bootstrap/css/dropdown.css');
	$flux .= '<link rel="stylesheet" href="' . direction_css($css) . '" type="text/css" media="all" />' . "\n";

	return $flux;
}
