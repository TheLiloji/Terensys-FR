<?php
/**
 * Fonctions utiles au plugin Rang
 *
 * @plugin     Rang
 * @copyright  2016
 * @author     Peetdu
 * @licence    GNU/GPL
 * @package    SPIP\Rang\Fonctions
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * réordonner les rangs de la liste suite à un nouveau classement
 *
 * @param array  $tab
 *        tableau des items de la liste suite à une modification du classement
 * @param string $page
 *        quelle pagination
 * @param string $objet
 *        sur quel objet faire le classement
 * @param string $id_parent
 *        id_parent dans lequel faire le classement
 *
 **/

function action_trier_items_dist() {

	include_spip('base/objets');

	$objet = _request('objet');
	$ordre = _request('ordre');
	$pagination = _request('pagination');

	if (is_numeric($pagination) === true) {
		$pagination = ($pagination < 0) ? 0 : $pagination;
	} else {
		$pagination = 0;
	}

	$table = table_objet_sql($objet);
	$id_objet = id_table_objet($objet);

	spip_log("\nobjet : " . $objet . "\ntrier :\n" . print_r($ordre, 1), 'rang.' . _LOG_DEBUG);

	// reclassement !
	foreach ($ordre as $key => $value) {
		$rang = $pagination + $key + 1; // le classement commence à 1, pas à 0
		$where = "$id_objet=" . intval($value);
		sql_updateq(
			$table,
			['rang' => $rang],
			$where
		);
	}

	include_spip('inc/invalideur');
	suivre_invalideur($objet . '/*');

}
