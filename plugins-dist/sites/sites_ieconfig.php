<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function sites_ieconfig_metas($table) {
	$table['sites_meta']['titre'] = _T('sites:titre_referencement_sites');
	$table['sites_meta']['icone'] = 'site-16.png';
	$table['sites_meta']['metas_brutes'] = 'activer_sites,activer_syndic,proposer_sites,moderation_sites';

	return $table;
}
