<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Fonctions pour concaténer plusieurs fichiers en un
 *
 * @package SPIP\Compresseur\Concatener
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Concaténer en un seul une liste de fichier,
 * avec appels de callback sur chaque fichier,
 * puis sur le fichier final
 *
 * Gestion d'un cache : le fichier concaténé n'est produit que si il n'existe pas
 * pour la liste de fichiers fournis en entrée
 *
 *
 * @param array $files
 *     Liste des fichiers à concatener, chaque entrée sour la forme html=>fichier
 *     - string $key : html d'insertion du fichier dans la page
 *     - string|array $fichier : chemin du fichier, ou tableau (page,argument) si c'est un squelette
 * @param string $format
 *     js ou css utilisé pour l'extension du fichier de sortie
 * @param array $callbacks
 *     Tableau de fonctions à appeler :
 *     - each_pre : fonction de préparation à appeler sur le contenu de chaque fichier
 *     - each_min : fonction de minification à appeler sur le contenu de chaque fichier
 *     - all_min : fonction de minification à appeler sur le contenu concatene complet, en fin de traitement
 * @return array
 *     Tableau a 2 entrées retournant le nom du fichier et des commentaires HTML à insérer dans la page initiale
 */
function concatener_fichiers($files, $format = 'js', $callbacks = []) {
	$nom_fichier = '';
	if (!is_array($files) && $files) {
		$files = [$files];
	}
	if (count($files)) {
		$callback_min = $callbacks['each_min'] ?? 'concatener_callback_identite';
		$callback_pre = $callbacks['each_pre'] ?? '';
		$url_base = self('&');

		// on trie la liste de files pour calculer le nom
		// necessaire pour retomber sur le meme fichier
		// si on renome une url a la volee pour enlever le var_mode=recalcul
		// mais attention, il faut garder l'ordre initial pour la minification elle meme !
		$dir = sous_repertoire(_DIR_VAR, 'cache-' . $format);
		[$nom_fichier, $lastmodified] = concatener_nom_fichier_concat($dir, $files, $callbacks, $format);
		if (
			(defined('_VAR_MODE') and _VAR_MODE == 'recalcul')
			or !file_exists($nom_fichier)
			or filemtime($nom_fichier) < $lastmodified
		) {
			spip_log("concatener_fichiers: Recalculer $nom_fichier plus a jour", 'compresseur' . _LOG_DEBUG);
			$concatenation = '';
			$comms = [];
			$total = 0;
			$files2 = false;
			foreach ($files as $key => $file) {
				if (!is_array($file)) {
					// c'est un fichier
					$comm = $file;
					// enlever le timestamp si besoin
					$file = preg_replace(',[?].+$,', '', $file);

					// preparer le fichier si necessaire
					if ($callback_pre) {
						$file = $callback_pre($file);
					}

					lire_fichier($file, $contenu);
				} else {
					// c'est un squelette
					if (!isset($file[1])) {
						$file[1] = '';
					}
					$comm = _SPIP_PAGE . "=$file[0]"
						. (strlen($file[1]) ? "($file[1])" : '');
					parse_str($file[1], $contexte);
					$contenu = recuperer_fond($file[0], $contexte);

					// preparer le contenu si necessaire
					if ($callback_pre) {
						$contenu = $callback_pre(
							$contenu,
							url_absolue(_DIR_RESTREINT ? generer_url_public($file[0], $file[1]) : $url_base)
						);
					}
					// enlever le var_mode si present pour retrouver la css minifiee standard
					if (strpos($file[1], 'var_mode') !== false) {
						if (!$files2) {
							$files2 = $files;
						}
						$old_key = $key;
						$key = preg_replace(',(&(amp;)?)?var_mode=[^&\'"]*,', '', $key);
						$file[1] = preg_replace(',&?var_mode=[^&\'"]*,', '', $file[1]);
						if (!strlen($file[1])) {
							unset($file[1]);
						}
						$files2 = array_replace_key($files2, $old_key, $key, $file);
					}
				}
				// passer la balise html initiale en second argument
				$concatenation .= "/* $comm */\n" . $callback_min($contenu, $key) . "\n\n";
				$comms[] = $comm;
				$total += strlen($contenu);
			}

			// calcul du % de compactage
			$pc = intval(1000 * strlen($concatenation) / $total) / 10;
			$comms = "compact [\n\t" . join("\n\t", $comms) . "\n] $pc%";
			$concatenation = "/* $comms */\n\n" . $concatenation;

			// si on a nettoye des &var_mode=recalcul : mettre a jour le nom
			// on ecrit pas dans le nom initial, qui est de toute facon recherche qu'en cas de recalcul
			// donc jamais utile
			if ($files2) {
				$files = $files2;
				[$nom_fichier, $lastmodified] = concatener_nom_fichier_concat($dir, $files, $callbacks, $format);
			}

			$nom_fichier_tmp = $nom_fichier;
			$final_callback = ($callbacks['all_min'] ?? false);
			if ($final_callback) {
				unset($callbacks['all_min']);
				[$nom_fichier_tmp, $lastmodified] = concatener_nom_fichier_concat($dir, $files, $callbacks, $format);
			}
			// ecrire
			ecrire_fichier_calcule_si_modifie($nom_fichier_tmp, $concatenation);

			if ($final_callback) {
				// closure compiler ou autre super-compresseurs
				// a appliquer sur le fichier final
				$encore = $final_callback($nom_fichier_tmp, $nom_fichier);
				// si echec, on se contente de la compression sans cette callback
				if ($encore !== $nom_fichier) {
					// ecrire
					ecrire_fichier_calcule_si_modifie($nom_fichier, $concatenation);
				}
				// on ne supprime pas le fichier temporaire $nom_fichier_tmp
				// car il y a le risque qu'un process concurrent soit juste sur le point de le processer aussi et produirait alors du vide
			}
		}
	}

	// Le commentaire detaille n'apparait qu'au recalcul, pour debug
	return [$nom_fichier, (isset($comms) and $comms) ? "<!-- $comms -->\n" : ''];
}

/**
 * Calculer le nom de fichier concatene
 * en tenant compte des timestamps :
 * un changement de timestamp ne doit pas modifier le nom mais bien forcer une mise a jour du fichier concat si besoin
 * @param string $dir
 * @param array $files
 * @param array $callbacks
 * @param string $format
 * @return array
 */
function concatener_nom_fichier_concat($dir, $files, $callbacks, $format) {
	$lastmodified = 0;
	$file_wo_timestamp = [];
	// on ignore les cles : seul le fichier inclu compte, pas la forme exacte de la balise html dans laquelle il est insere
	foreach ($files as $k => $file) {
		if (!is_array($file)) {
			if (strpos($file, '?') !== false) {
				$file = explode('?', $file, 2);
				if (is_numeric(end($file))) {
					$lastmodified = max($lastmodified, end($file));
				}
				$file = reset($file);
			}
		}
		$file_wo_timestamp[] = $file;
	}
	$nom_fichier_concat = $dir . md5(json_encode($file_wo_timestamp, JSON_THROW_ON_ERROR) . json_encode($callbacks, JSON_THROW_ON_ERROR)) . ".$format";
	return [$nom_fichier_concat, $lastmodified];
}

/**
 * Une callback pour la minification par défaut
 *
 * Mais justement, par défaut on ne minifie rien !
 *
 * @param string $contenu Contenu à minifier
 * @return string          Contenu à minifier
 */
function &concatener_callback_identite(&$contenu) {
	return $contenu;
}

/**
 * Une callback pour ?
 *
 * @param array $tableau
 *
 * @param string $orig_key
 *     Index dont on cherche la valeur actuelle
 * @param string $new_key
 *     Nouvel index que l'on veut voir affecter de la valeur de la clé d'origine
 * @param mixed $new_value
 *     Si rempli, la nouvelle clé prend cette valeur à la place
 *     de la valeur de la clé d'origine
 * @return array
 *
 */
function &array_replace_key($tableau, $orig_key, $new_key, $new_value = null) {
	$t = [];
	foreach ($tableau as $k => $v) {
		if ($k == $orig_key) {
			$k = $new_key;
			if (!is_null($new_value)) {
				$v = $new_value;
			}
		}
		$t[$k] = $v;
	}

	return $t;
}
