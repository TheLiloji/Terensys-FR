<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
# not useful as this file is included by the engine itself
# require_once 'engine/textwheel.php';

function tw_def_wrap($t) {
	global $class_spip_plus;

	return "<dl$class_spip_plus>\n$t</dl>\n";
}
