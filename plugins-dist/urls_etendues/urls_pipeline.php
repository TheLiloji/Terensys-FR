<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function urls_afficher_fiche_objet($flux) {
	if (
		isset($GLOBALS['meta']['urls_activer_controle'])
		and $GLOBALS['meta']['urls_activer_controle'] == 'oui'
		and $objet = $flux['args']['type']
		and $id_objet = $flux['args']['id']
		and objet_info($objet, 'page')
	) {
		$p = strpos($flux['data'], 'fiche_objet');
		$p = strpos($flux['data'], '<!--/hd-->', $p);
		//$p = strrpos(substr($flux['data'],0,$p),'<div');

		$res = recuperer_fond(
			'prive/objets/editer/url',
			['id_objet' => $id_objet, 'objet' => $objet],
			['ajax' => true]
		);
		$flux['data'] = substr_replace($flux['data'], $res, $p, 0);
	}

	return $flux;
}


/**
 * Optimiser la base de donnée en supprimant les urls orphelines
 *
 * @param array $flux
 * @return array
 */
function urls_optimiser_base_disparus($flux) {
	/*
	$n = &$flux['data'];
	# les urls lies a un id_objet inexistant
	$types = sql_allfetsel("DISTINCT type", 'spip_urls');
	$types = array_column($types, 'type');
	$types = array_filter($types);
	foreach ($types as $type) {
		$table = table_objet_sql($type);
		$primary = id_table_objet($type);
		if (lister_tables_objets_sql($table)) {
			$n += $i = sql_delete('spip_urls', [
				'type=' . sql_quote($type),
				sql_in('id_objet', sql_get_select($primary, $table), 'NOT')
			]);
			if ($i) {
				spip_log("Suppression de $i urls $type inexistants", "urls." . _LOG_INFO_IMPORTANTE);
			}
		}
	}
	*/
	return $flux;
}
