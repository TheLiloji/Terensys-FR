<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// On déclare ici la config du core
function mediabox_ieconfig_metas($table) {
	$table['mediabox_meta']['titre'] = _T('mediabox:titre_menu_box');
	$table['mediabox_meta']['icone'] = 'mediabox-16.png';
	$table['mediabox_meta']['metas_serialize'] = 'mediabox';

	return $table;
}
