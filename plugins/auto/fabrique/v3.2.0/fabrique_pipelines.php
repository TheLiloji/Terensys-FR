<?php

/**
 * Utilisation des pipelines
 *
 * @package SPIP\Fabrique\Pipelines
**/

if (!defined('_ECRIRE_INC_VERSION')) { return;
}


/**
 * Ajout de bulles de compagnon sur la page de création de plugins
 * depuis la Fabrique
 *
 * @param array $flux  Données du pipeline
 * @return array       Données du pipeline
**/
function fabrique_compagnon_messages(array $flux): array {

	$exec = $flux['args']['exec'];
	$pipeline = $flux['args']['pipeline'];
	$aides = &$flux['data'];

	switch ($pipeline) {
		case 'affiche_milieu':
			switch ($exec) {
				case 'fabrique':
					$aides[] = [
						'id' => 'fabrique_info',
						'titre' => _T('fabrique:c_fabrique_info'),
						'texte' => _T('fabrique:c_fabrique_info_texte'),
						'statuts' => ['1comite', '0minirezo', 'webmestre']
					];
					$aides[] = [
						'id' => 'fabrique_git',
						'titre' => _T('fabrique:c_fabrique_git'),
						'texte' => _T('fabrique:c_fabrique_git_texte'),
						'statuts' => ['1comite', '0minirezo', 'webmestre']
					];
					if (!is_writable(_DIR_PLUGINS . rtrim(FABRIQUE_DESTINATION_PLUGINS, '/'))) {
						$aides[] = [
							'id' => 'fabrique_dans_plugins',
							'titre' => _T('fabrique:c_fabrique_dans_plugins'),
							'texte' => _T('fabrique:c_fabrique_dans_plugins_texte', [
								'dir' => rtrim(FABRIQUE_DESTINATION_PLUGINS, '/'),
								'dir_cache' => rtrim(FABRIQUE_DESTINATION_CACHE, '/')]),
							'statuts' => ['1comite', '0minirezo', 'webmestre']
						];
					}
					break;
			}
			break;
	}
	return $flux;
}
