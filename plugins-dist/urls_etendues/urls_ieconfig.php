<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function urls_ieconfig_metas($table) {
	$table['urls_meta']['titre'] = _T('urls:titre_type_urls');
	$table['urls_meta']['icone'] = 'url-16.png';
	$table['urls_meta']['metas_brutes'] = 'type_urls,urls_activer_controle';
	$table['urls_meta']['metas_serialize'] = 'urls_propres,urls_arbo';

	return $table;
}
