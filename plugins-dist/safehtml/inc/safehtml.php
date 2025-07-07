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

/**
 * Sanitization du HTML via la librairie Purifier
 * @param string $t
 * @return false|string
 */
function inc_safehtml_dist(string $t): string {
	static $purifier;

	if (!isset($purifier)) {
		require_spip('lib/htmlpurifier/HTMLPurifier.standalone');
		require_spip('inc/HTMLPurifier.extended');
		require_spip('inc/HTMLPurifier_HTML5.loader');

		$config = HTMLPurifier_HTML5Config::createDefault();

		$config->set('HTML.Forms', true);

		if ($iniFile = find_in_path('safehtml/htmlpurifier.ini')) {
			$config->loadIni($iniFile);
		} else {
			$config->set('Attr.EnableID', true);
			$config->set('HTML.TidyLevel', 'none');
			$config->set('Cache.SerializerPath', rtrim(realpath(sous_repertoire(_DIR_CACHE, 'html_purifier')), '/'));

			// trop severe pour une utilisation generique ?
			#$config->set('HTML.SafeIframe', true);
			#$config->set('URI.SafeIframeRegexp', '%^http[s]?://[a-z0-9\.]*' . $_SERVER['HTTP_HOST'] . '/%iS');

			$config->set('Attr.AllowedFrameTargets', ['_blank']);

			$rels = [
				'about', 'acl', 'alternate', 'amphtml', 'appendix', 'apple-touch-icon', 'apple-touch-startup-image',
				'archives', 'author', 'blocked-by', 'bookmark', 'canonical', 'chapter', 'cite-as', 'collection',
				'contents', 'convertedFrom', 'copyright', 'create-form', 'current', 'describedby', 'describes',
				'disclosure', 'dns-prefetch', 'duplicate', 'edit', 'edit-form', 'edit-media', 'enclosure', 'external',
				'first', 'glossary', 'help', 'hosts', 'hub', 'icon', 'index', 'intervalAfter', 'intervalBefore',
				'intervalContains', 'intervalDisjoint', 'intervalDuring', 'intervalEquals', 'intervalFinishedBy',
				'intervalFinishes', 'intervalIn', 'intervalMeets', 'intervalMetBy', 'intervalOverlappedBy',
				'intervalOverlaps', 'intervalStartedBy', 'intervalStarts', 'item', 'last', 'latest-version', 'license',
				'linkset', 'lrdd', 'manifest', 'mask-icon', 'media-feed', 'memento', 'micropub', 'modulepreload',
				'monitor', 'monitor-group', 'next', 'next-archive', 'nofollow', 'noopener', 'noreferrer', 'opener',
				'openid2.local_id', 'openid2.provider', 'original', 'P3Pv1', 'payment', 'pingback', 'preconnect',
				'predecessor-version', 'prefetch', 'preload', 'prerender', 'prev', 'preview', 'previous',
				'prev-archive', 'privacy-policy', 'profile', 'publication', 'related', 'restconf', 'replies',
				'ruleinput', 'search', 'section', 'self', 'service', 'service-desc', 'service-doc', 'service-meta',
				'sponsored', 'start', 'status', 'stylesheet', 'subsection', 'successor-version', 'sunset', 'tag',
				'terms-of-service', 'timegate', 'timemap', 'type', 'ugc', 'up', 'version-history', 'via', 'webmention',
				'working-copy', 'working-copy-of', 'print'];
			$config->set('Attr.AllowedRel', implode(',', $rels));

			$config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'ftp' => true, 'nntp' => true, 'news' => true, 'tel' => true, 'tcp' => true, 'udp' => true, 'ssh' => true,]);
		}

		$purifier = new HTMLPurifier($config);
	}

	$echappe_retour = false;
	// échapper les SVG si il y en a
	if (stripos($t, '<svg') !== false) {
		include_spip('sanitizer/svg');
		$t = echappe_html($t, 'safehtml', false, ',<(svg)(\b[^>]*)?>(.*)</\1>,UimsS', 'safehtml_');
		$echappe_retour = true;
	}

	$collecteurMetasMicroData = null;
	if (stripos($t, '<meta') !== false) {
		include_spip('src/Texte/Collecteur/AbstractCollecteur');
		include_spip('src/Texte/Collecteur/MetasMicroData');
		$collecteurMetasMicroData = new Textwheel\Texte\Collecteur\MetasMicroData();
		$t = $collecteurMetasMicroData->echapper($t);
	}

	// HTML Purifier prefere l'utf-8
	$charset = (empty($GLOBALS['meta']['charset']) ? _DEFAULT_CHARSET : $GLOBALS['meta']['charset']);
	if ($charset === 'utf-8') {
		$t = $purifier->purify($t);
	} else {
		$t = unicode2charset($purifier->purify(charset2unicode($t)));
	}

	if ($collecteurMetasMicroData) {
		$t = $collecteurMetasMicroData->retablir($t);
	}

	if ($echappe_retour) {
		$t = echappe_retour($t, 'safehtml');
	}

	return $t;
}


// Sanitizer les <svg>...</svg> lors de leur échappement
function safehtml_traiter_echap_svg_dist($regs, $options = []) {
	return sanitizer_svg_string($regs[0]);
}
