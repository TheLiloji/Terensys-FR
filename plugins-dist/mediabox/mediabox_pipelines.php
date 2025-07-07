<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Definir la config de la box lity qui est la box par defaut
 * @param $config
 * @return mixed
 */
function mediabox_lity_config($config) {
	$config['_libs']['lity'] = [
		'nom' => 'Lity',
		'css' => [
			'lib/lity/lity.css',
			'lity/css/lity.mediabox.css'
		],
		'js' => [
			'lib/lity/lity.js',
			'lity/js/lity.mediabox.js',
		]
	];

	if (empty($config['lity'])) {
		$config['lity'] = [];
	}

	$config['lity'] = array_merge(
		[
			'skin' => '_simple-dark',
			'maxWidth' => '90%',
			'maxHeight' => '90%',
			'minWidth' => '400px',
			'minHeight' => '',
			'slideshow_speed' => '2500',
			'opacite' => '0.9',
			'defaultCaptionState' => 'expanded',
		],
		$config['lity']
	);

	if (
		!empty($config['lity']['skin'])
		and $box_skin = $config['lity']['skin']
	) {
		if ($box_skin === 'none') {
			$config['_libs']['lity']['css'] = [];
		}
		else {
			$config['_libs']['lity']['css'][] = ($config['_public'] ? '' : 'prive/') . "lity/skins/{$box_skin}/lity.css";
		}
	}

	return $config;
}

/**
 * Calculer la config mediabox en fonction de l'espace prive/public ou l'on se trouve et de la config definie via le formulaire
 * @param null|bool $public
 * @return array
 */
function mediabox_config($public = null) {
	include_spip('inc/filtres');
	include_spip('inc/config');
	$config = lire_config('mediabox', []);

	// conversion a la volee de l'ancienne config toute melangee
	if (empty($config['box_type']) and empty($config['colorbox']) and !empty($config['transition'])) {
		// on convertit l'ancienne config colorbox pour ne pas la perdre
		$config['colorbox'] = [];
		foreach (['skin', 'transition', 'speed', 'maxWidth', 'maxHeight', 'minWidth', 'minHeight', 'slideshow_speed', 'opacite'] as $k) {
			if (!empty($config[$k])) {
				$config['colorbox'][$k] = $config[$k];
				unset($config[$k]);
			}
		}

		// et on passe a lity en heritant des config possibles
		$config['box_type'] = 'lity';
		if (empty($config['lity'])) {
			$config['lity'] = [];
			foreach (['maxWidth', 'maxHeight', 'minWidth', 'minHeight', 'slideshow_speed', 'opacite'] as $k) {
				if (!empty($config['colorbox'][$k])) {
					$config['lity'][$k] = $config['colorbox'][$k];
				}
			}
		}

		ecrire_config('mediabox', $config);
	}

	$config = array_merge([
		'active' => 'oui',
		'namespace' => 'box',
		'traiter_toutes_images' => 'oui',
		'selecteur_galerie' => '#documents_portfolio a[type=\'image/jpeg\'],#documents_portfolio a[type=\'image/png\'],#documents_portfolio a[type=\'image/gif\']',
		'selecteur_commun' => '.mediabox',
		'splash_url' => '',
		'splash_width' => '600px',
		'splash_height' => '90%',
		'box_type' => 'lity',
	], $config);

	$config['_public'] = (is_null($public) ? !test_espace_prive() : !!$public);
	$config['_libs'] = [];

	if ($config['_public'] === false) {
		$config = array_merge($config, [
			'active' => 'oui',
			'selecteur_galerie' => '#portfolios a[type^=\'image/\']',
			'selecteur_commun' => '.mediabox, .iconifier a[href$=jpg],.iconifier a[href$=png],.iconifier a[href$=gif]',
			'splash_url' => '',
			'box_type' => 'lity',
		]);
		$config['lity'] = array_merge(
			!empty($config['lity']) ?  $config['lity'] : [],
			[
				'skin' => 'spip',
				'maxWidth' => '90%',
				'maxHeight' => '95%',
				'minWidth' => '600px',
				'minHeight' => '300px',
				'defaultCaptionState' => 'expanded',
			]
		);
	}

	// Gerer aussi les liens internes de SPIP
	if (!test_espace_prive() and $config['splash_url']) {
		include_spip('inc/filtres_ecrire');
		$config['splash_url'] = url_absolue(extraire_attribut(lien_article_virtuel($config['splash_url']), 'href'));
	}

	// declarer lity
	$config = mediabox_lity_config($config);

	// et d'autres boxs si besoin via un pipeline
	$config = pipeline('mediabox_config', $config);


	// charger la config du theme uniquement dans le public
	// et forcer une config par defaut si rien de selectionne
	if ($config['_public']) {
		if (empty($config['box_type']) or empty($config['_libs'][$config['box_type']])) {
			$config['box_type'] = 'lity';
			if (empty($config['lity'])) {
				$config['lity'] = [];
			}
		}
		$box_type = $config['box_type'];

		if (
			!empty($config[$box_type]['skin'])
			and $box_skin = $config[$box_type]['skin']
			and include_spip("$box_type/skins/$box_skin/mediabox_config_theme")
			and function_exists($f = "mediabox_config_{$box_type}_$box_skin")
			and $config_theme = $f($config)
		) {
			$config = $config_theme;
		}
	}

	return $config;
}

/**
 * Injecter les CSS + le JS inline (qui doit arriver avant les css pour des raisons de chargement non bloquant)
 * @param string $flux
 * @return string
 */
function mediabox_insert_head_css($flux) {
	$config = mediabox_config();
	if ($config['active'] == 'oui') {
		$css_files = [];

		if (
			$box_type = $config['box_type']
			and !empty($config['_libs'][$box_type]['css'])
		) {
			$css_files = array_merge($css_files, $config['_libs'][$box_type]['css']);
		}

		foreach ($css_files as $file) {
			if ($f = find_in_path($file)) {
				if (substr($file, -5) === '.html') {
					$f = produire_fond_statique(substr($file, 0, -5), $config);
				}
				else {
					$f = timestamp(direction_css($f));
				}
				$flux .= "\n" . '<link rel="stylesheet" href="' . $f . '" type="text/css" media="all" />';
			}
		}

		/**
		 * Initialiser la config de la mediabox
		 */
		$js_config = [
			'auto_detect' => true,
			'ns' => $config['namespace'],
			'tt_img' => ($config['traiter_toutes_images'] == 'oui' ? true : false),
			'sel_g' => $config['selecteur_galerie'],
			'sel_c' => $config['selecteur_commun'],
			'str_ssStart' => _T('mediabox:boxstr_slideshowStart'),
			'str_ssStop' => _T('mediabox:boxstr_slideshowStop'),
			'str_cur' => _T('mediabox:boxstr_current', ['current' => '{current}', 'total' => '{total}']),
			'str_prev' => _T('mediabox:boxstr_previous'),
			'str_next' => _T('mediabox:boxstr_next'),
			'str_close' => _T('mediabox:boxstr_close'),
			'str_loading' => _T('mediabox:boxstr_loading'),
			'str_petc' => _T('mediabox:boxstr_press_escape_to_close'),
			'str_dialTitDef' => _T('mediabox:boxstr_dialog_title_default'),
			'str_dialTitMed' => _T('mediabox:boxstr_dialog_title_medias'),
			'splash_url' => $config['splash_url'],
		];
		// plus la config specifique de la box selectionnee
		if (!empty($config['box_type']) and !empty($config[$config['box_type']])) {
			$js_config[$config['box_type']] = $config[$config['box_type']];
		}
		// Si c'est une image, on la chargera avec une redimentionnement automatique
		// Sinon, chargement dans une iframe
		if ($config['splash_url']) {
			$extension = pathinfo($config['splash_url'], PATHINFO_EXTENSION);
			if (in_array($extension, ['gif', 'png', 'jpg', 'jpeg'])) {
				$js_config['splash_iframe'] = false;
			} else {
				$js_config['splash_iframe'] = true;
				$js_config['splash_width'] = $config['splash_width'];
				$js_config['splash_height'] = $config['splash_height'];
			}
			if (isset($config['splash_days'])) {
				$js_config['splash_days'] = $config['splash_days'];
			}
		}
		$configmediabox = '<script>
var mediabox_settings=' . json_encode(mediabox_echappe_js_config($js_config), JSON_INVALID_UTF8_IGNORE) . ';' . "\n";
		$flux = $configmediabox . '</script>' . "\n" . $flux;
	}

	return $flux;
}

/**
 * Encoder/echapper les arguments de la config JS injectee dans le html
 * @param $config
 * @return array|mixed|string|string[]
 */
function mediabox_echappe_js_config($config) {
	if (is_array($config)) {
		return array_map('mediabox_echappe_js_config', $config);
	}

	if (is_string($config)) {
		if (strpos($config, '&') !== false) {
			if (!function_exists('html2unicode')) {
				include_spip('inc/charsets');
			}
			$config = unicode2charset(html2unicode($config));
		}
		if (strpos($config, '<') !== false) {
			$config = str_replace('<', '&lt;', $config);
		}
	}

	return $config;
}

/**
 * Injecter les scripts js
 * @param string $flux
 * @return string
 */
function mediabox_insert_head($flux) {
	$config = mediabox_config();
	if ($config['active'] == 'oui') {
		$js_files = [];
		if (
			$box_type = $config['box_type']
			and !empty($config['_libs'][$box_type]['js'])
		) {
			$js_files = array_merge($js_files, $config['_libs'][$box_type]['js']);
		}

		$js_files[] = 'javascript/spip.mediabox.js';
		if ($config['splash_url']) {
			$js_files[] = 'javascript/splash.mediabox.js';
		}
		foreach ($js_files as $file) {
			if ($f = find_in_path($file)) {
				$flux .= "\n" . '<script src="' . timestamp($f) . '" type="text/javascript"></script>';
			}
		}
	}

	return $flux;
}

/**
 * Activer le plugin js cookie.js si on a configurer la splasg url
 * @param $plugins
 * @return mixed
 */
function mediabox_jquery_plugins($plugins) {
	$config = mediabox_config();
	if ($config['splash_url']) {
		$plugins[] = 'javascript/js.cookie.js';
	}

	return $plugins;
}
