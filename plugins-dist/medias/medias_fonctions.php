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
 * Fonctions utiles pour les squelettes et déclarations de boucle
 * pour le compilateur
 *
 * @package SPIP\Medias\Fonctions
 **/

// sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// nettoyer les zip abandonnes par l'utilisateur
if (
	isset($GLOBALS['visiteur_session']['zip_to_clean'])
	and test_espace_prive()
	and isset($_SERVER['REQUEST_METHOD'])
	and $_SERVER['REQUEST_METHOD'] !== 'POST'
) {
	$zip_to_clean = unserialize($GLOBALS['visiteur_session']['zip_to_clean']);
	if ($zip_to_clean) {
		foreach ($zip_to_clean as $zip) {
			if (@file_exists($zip)) {
				@unlink($zip);
			}
		}
	}
	session_set('zip_to_clean');
}

// capturer un formulaire POST plus grand que post_max_size
// on genere un minipres car on ne peut rien faire de mieux
if (
	isset($_SERVER['REQUEST_METHOD'])
	and $_SERVER['REQUEST_METHOD'] == 'POST'
	and empty($_POST)
	and isset($_SERVER['CONTENT_TYPE'])
	and strlen($_SERVER['CONTENT_TYPE']) > 0
	and strncmp($_SERVER['CONTENT_TYPE'], 'multipart/form-data', 19) == 0
	and $_SERVER['CONTENT_LENGTH'] > medias_inigetoctets('post_max_size')
) {
	include_spip('inc/minipres');
	echo minipres(_T('medias:upload_limit', ['max' => ini_get('post_max_size')]));
	exit;
}

/**
 * Styliser le modele media : reroute les <img> <doc> <emb> vers <image>, <audio>, <video>, <file> selon le media du document
 * si le document n'est pas trouve c'est <file> qui s'applique
 * @param $modele
 * @param $id
 * @return string
 */
function medias_modeles_styliser($modele, $id) {
	if (defined('_COMPORTEMENT_HISTORIQUE_IMG_DOC_EMB') and _COMPORTEMENT_HISTORIQUE_IMG_DOC_EMB) {
		return $modele;
	}
	switch ($modele) {
		case 'img':
		case 'doc':
		case 'emb':
			$m = 'file';
			if ($doc = sql_fetsel('id_document,media', 'spip_documents', 'id_document=' . intval($id))) {
				$m = $doc['media']; // image, audio, video, file
			}
			if (trouve_modele("{$m}_{$modele}")) {
				// on peut decliner file_emb qui sera utilisable soit par <docXX|emb> soit par <embXX>
				// permet d'embed explicitement des fichiers exotiques qui sinon seraient de simples liens a telecharger
				// tels que text/csv, text/html, text
				$m = "{$m}_{$modele}";
			}
			$modele = $m;
			break;
	}
	return $modele;
}


/**
 * Retourne la taille en octet d'une valeur de configuration php
 *
 * @param string $var
 *     Clé de configuration ; valeur récupérée par `ini_get()`. Exemple `post_max_size`
 * @return int|string
 *     Taille en octet, sinon chaine vide.
 **/
function medias_inigetoctets($var) {
	$last = '';
	$val = trim(@ini_get($var));
	if (is_numeric($val)) {
		return $val;
	}
	// en octet si "32M"
	if ($val != '') {
		$last = strtolower($val[strlen($val) - 1]);
		$val = substr($val, 0, -1);
	}
	switch ($last) { // The 'G' modifier is available since PHP 5.1.0
		case 'g':
			$val *= 1024 * 1024 * 1024;
			break;
		case 'm':
			$val *= 1024 * 1024;
			break;
		case 'k':
			$val *= 1024;
			break;
	}

	return $val;
}

/**
 * Afficher la puce de statut pour les documents
 *
 * @param int $id_document
 *     Identifiant du document
 * @param string $statut
 *     Statut du document
 * @return string
 *     Code HTML de l'image de puce
 */
function medias_puce_statut_document($id_document, $statut) {
	if ($statut === 'publie') {
		$puce = 'puce-publier-xx.svg';
	} elseif ($statut === 'prop') {
		$puce = 'puce-proposer-xx.svg';
	} elseif ($statut === 'prepa') {
		$puce = 'puce-preparer-xx.svg';
	} elseif ($statut === 'poubelle') {
		$puce = 'puce-supprimer-xx.svg';
	} else {
		$puce = 'puce-preparer-xx.svg';
	}

	return http_img_pack($puce, $statut, "class='puce'");
}


/**
 * Compile la boucle `DOCUMENTS` qui retourne une liste de documents multimédia
 *
 * `<BOUCLE(DOCUMENTS)>`
 *
 * @param string $id_boucle
 *     Identifiant de la boucle
 * @param array $boucles
 *     AST du squelette
 * @return string
 *     Code PHP compilé de la boucle
 **/
function boucle_DOCUMENTS($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;

	// on ne veut pas des fichiers de taille nulle,
	// sauf s'ils sont distants (taille inconnue)
	array_unshift($boucle->where, ["'($id_table.taille > 0 OR $id_table.distant=\\'oui\\')'"]);

	/**
	 * N'afficher que les modes de documents que l'on accepte
	 * Utiliser le "pipeline medias_documents_visibles" pour en ajouter
	 */
	if (
		!isset($boucle->modificateur['criteres']['mode'])
		and !isset($boucle->modificateur['tout'])
	) {
		$modes = pipeline('medias_documents_visibles', ['image', 'document']);
		$f = sql_serveur('quote', $boucle->sql_serveur, true);
		$modes = addslashes(join(',', array_map($f, array_unique($modes))));
		array_unshift($boucle->where, ["'IN'", "'$id_table.mode'", "'($modes)'"]);
	}

	return calculer_boucle($id_boucle, $boucles);
}

/**
 * critere {orphelins} selectionne les documents sans liens avec un objet editorial
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DOCUMENTS_orphelins_dist($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];
	$cond = $crit->cond;
	$not = $crit->not ? '' : 'NOT';

	$select = sql_get_select('DISTINCT id_document', 'spip_documents_liens as oooo');
	$where = "'" . $boucle->id_table . ".id_document $not IN ($select)'";
	if ($cond) {
		$_quoi = '@$Pile[0]["orphelins"]';
		$where = "($_quoi)?$where:''";
	}

	$boucle->where[] = $where;
}

/**
 * critere {portrait} qui selectionne
 * - les documents dont les dimensions sont connues
 * - les documents dont la hauteur est superieure a la largeur
 *
 * {!portrait} exclus ces documents
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DOCUMENTS_portrait_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;
	$not = ($crit->not ? 'NOT ' : '');
	$boucle->where[] = "'$not($table.largeur>0 AND $table.hauteur > $table.largeur)'";
}

/**
 * critere {paysage} qui selectionne
 * - les documents dont les dimensions sont connues
 * - les documents dont la hauteur est inferieure a la largeur
 *
 * {!paysage} exclus ces documents
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DOCUMENTS_paysage_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;
	$not = ($crit->not ? 'NOT ' : '');
	$boucle->where[] = "'$not($table.largeur>0 AND $table.largeur > $table.hauteur)'";
}

/**
 * critere {carre} qui selectionne
 * - les documents dont les dimensions sont connues
 * - les documents dont la hauteur est egale a la largeur
 *
 * {!carre} exclus ces documents
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_DOCUMENTS_carre_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;
	$not = ($crit->not ? 'NOT ' : '');
	$boucle->where[] = "'$not($table.largeur>0 AND $table.largeur = $table.hauteur)'";
}


/**
 * Balise `#MIME_TYPE`
 *
 * Calculer la balise mime_type qui peut avoir un contenu variable pour une meme extension
 * cf mp4 notamment
 *
 * @param Spip\Compilateur\Noeud\Champ $p
 * @return Spip\Compilateur\Noeud\Champ
 */
function balise_MIME_TYPE_dist($p) {

	$_mime_type = champ_sql('mime_type', $p, '($Pile[0][\'mime_type\'] ?? \'\')');

	// Dans la table spip_types_documents, on renvoie le champ SQL tel quel
	if ($p->type_requete === 'types_documents') {
		$p->code = $_mime_type;
		return $p;
	}

	$_id_document = champ_sql('id_document', $p);
	$_extension = champ_sql('extension', $p, '($Pile[0][\'extension\'] ?? \'\')');

	// Dans la table spip_documents, on renvoie le mime_type calculé
	if ($p->type_requete === 'documents') {
		$p->code = sprintf('medias_mime_type_document(%s, %s)',  $_id_document, $_extension);
		return $p;
	}

	// En dehors de cas standards, on cherche un id_document (+ extension) pour calculer le mime type,
	// et sinon, on récupèrere un mime_type en remontant la pile jusqu’au env.
	$p->code = sprintf('(%s === null ? %s : medias_mime_type_document(%s, %s))', $_id_document, $_mime_type, $_id_document, $_extension);

	return $p;
}

function medias_mime_type_document(int $id_document, string $extension = ''): string {
	if (!$id_document) {
		return '';
	}
	if (empty($extension)) {
		$extension = sql_getfetsel(
			'extension',
			'spip_documents',
			'id_document = ' . intval($id_document)
		);
	}
	$mime_type = sql_getfetsel(
		'mime_type',
		'spip_types_documents',
		'extension = ' . sql_quote($extension)
	);
	// document introuvable ?
	if ($mime_type === null) {
		return '';
	}
	$mime_type_sanitized = preg_replace('/\W/', '_', $mime_type);
	if ($calculer_mime_type = charger_fonction('calculer_mime_type_' . $mime_type_sanitized, 'inc', true)) {
		$mime_type_document = $calculer_mime_type($id_document, $extension, $mime_type);
		// la fonction peut renvoyer null pour laisser le mime_type initial intact
		if ($mime_type_document) {
			$mime_type = $mime_type_document;
		}
	}

	return $mime_type;
}

function filtre_titre_mime_type_dist(int $id_document, string $extension): string {
	static $types = null;
	if (is_null($types)) {
		$types = sql_allfetsel('extension,titre,mime_type', 'spip_types_documents');
		$types = array_combine(array_column($types, 'extension'), $types);
	}
	if (!empty($types[$extension])) {
		$titre = $types[$extension]['titre'];
		$mime_type = medias_mime_type_document($id_document, $extension);
		if ($mime_type !== $types[$extension]['mime_type'] && str_starts_with($types[$extension]['mime_type'], 'application/')) {
			$mime_type = explode('/', $mime_type);
			$titre .= " " . ucfirst(reset($mime_type));
		}
	}

	return $titre ?? '';
}


/**
 * Calcule la vignette d'une extension (l'image du type de fichier)
 *
 * Utile dans une boucle DOCUMENTS pour afficher une vignette du type
 * du document (balise `#EXTENSION`) alors que ce document a déjà une vignette
 * personnalisée (affichable par `#LOGO_DOCUMENT`).
 *
 * @example
 *     `[(#EXTENSION|vignette)]` produit une balise `<img ... />`
 *     `[(#EXTENSION|vignette{true})]` retourne le chemin de l'image
 *
 * @param string $extension
 *     L'extension du fichier, exemple : png ou pdf
 * @param bool $get_chemin
 *     false pour obtenir une balise img de l'image,
 *     true pour obtenir seulement le chemin du fichier
 * @return string
 *     Balise HTML <img...> ou chemin du fichier
 **/
function filtre_vignette_dist($extension = 'defaut', $get_chemin = false) {
	static $vignette = false;
	static $balise_img = false;

	if (!$vignette) {
		$vignette = charger_fonction('vignette', 'inc');
		$balise_img = charger_filtre('balise_img');
	}

	$fichier = $vignette($extension, false);
	// retourne simplement le chemin du fichier
	if ($get_chemin) {
		return $fichier;
	}

	// retourne une balise <img ... />
	return $balise_img($fichier);
}

/**
 * Determiner les methodes upload en fonction du env de inc-upload_document
 *
 * @param string|array $env
 * @return array
 */
function medias_lister_methodes_upload($env) {
	if (is_string($env)) {
		$env = unserialize($env);
	}

	$methodes = [];
	// méthodes d'upload disponibles
	$methodes = [];
	$methodes['upload'] = ['label_lien' => _T('medias:bouton_download_local'),'label_bouton' => _T('bouton_upload')];

	if ((isset($env['mediatheque']) and $env['mediatheque'])) {
		$methodes['mediatheque'] = ['label_lien' => _T('medias:bouton_download_par_mediatheque'),'label_bouton' => _T('medias:bouton_attacher_document')];
	}

	if ((isset($env['proposer_ftp']) and $env['proposer_ftp'])) {
		$methodes['ftp'] = ['label_lien' => _T('medias:bouton_download_par_ftp'),'label_bouton' => _T('bouton_choisir')];
	}
	$methodes['distant'] = ['label_lien' => _T('medias:bouton_download_sur_le_web'),'label_bouton' => _T('bouton_choisir')];

	// pipeline pour les méthodes d'upload
	$objet = $env['objet'] ?? '';
	$id_objet = $env['id_objet'] ?? '';

	$methodes = pipeline(
		'medias_methodes_upload',
		[
			'args' => ['objet' => $objet, 'id_objet' => $id_objet],
			'data' => $methodes
		]
	);

	return $methodes;
}

function duree_en_secondes($duree, $precis = false) {
	$out = '';
	$heures = $minutes = 0;
	if ($duree > 3600) {
		$heures = intval(floor($duree / 3600));
		$duree -= $heures * 3600;
	}
	if ($duree > 60) {
		$minutes = intval(floor($duree / 60));
		$duree -= $minutes * 60;
	}

	if ($heures > 0 or $minutes > 0) {
		$out = _T('date_fmt_heures_minutes', ['h' => $heures, 'm' => $minutes]);
		if (!$heures) {
			$out = preg_replace(',^0[^\d]+,Uims', '', $out);
		}
	}

	if (!$heures or $precis) {
		$out .= intval($duree) . 's';
	}
	return $out;
}


/**
 * Trouver le fond pour embarquer un document
 * - avec une extension
 * - avec un mime_type donne
 *
 *  En priorité :
 *  - modeles/{modele_base}_emb_{extension}.html si il existe
 *  - modeles/{modele_base}_emb_{mimetype}.html si il existe,
 *          dans {mimetype}, les caractères non alphanumériques (typiquement '/') ont été remplacés par '_'.
 *          Par exemple "text/css" devient "text_css"
 *  - modeles/{modele_base}_emb_{mimetypeprincipal}.html si il existe
 *          {mimetypeprincipal} est la partie du mimetype avant le '/'. C'est par exemple 'text' pour 'text/css'
 *  - modeles/{modele_base} sinon
 *
 * Pour une image jpg cela donne par priorité :
 * modeles/image_emb_jpg.html
 * modeles/image_emb_image_jpeg.html
 * modeles/image_emb_image.html
 * modeles/image.html
 *
 * @param string $extension
 * @param string $mime_type
 * @return string
 */
function medias_trouver_modele_emb($extension, $mime_type, $modele_base = 'file') {
	if ($extension and trouve_modele($fond = $modele_base . '_emb_' . $extension)) {
		return $fond;
	}

	$fond = $modele_base . '_emb_' . preg_replace(',\W,', '_', $mime_type);
	if (trouve_modele($fond)) {
		return $fond;
	}

	$fond = $modele_base . '_emb_' . preg_replace(',\W.*$,', '', $mime_type);
	if (trouve_modele($fond)) {
		return $fond;
	}

	return $modele_base;
}

/**
 * Determiner si un media doit etre mis en autolien dans les modeles document
 * Par defaut utilisé uniquement sur les media image, mais le filtre peut être surchargé pour étendre son fonctionnement
 *
 * @param string $media
 * @param string $extension
 * @param int $largeur
 * @param int $hauteur
 * @param int|null $id_document
 * @return bool
 */
function media_determine_autolien(string $media, string $extension, ?int $largeur, ?int $hauteur, ?int $id_document=0): bool {
	if ($media !== 'image' || $largeur === null || $hauteur === null) {
		return false;
	}
	if (!defined('_IMAGE_TAILLE_MINI_AUTOLIEN')) {
		define('_IMAGE_TAILLE_MINI_AUTOLIEN', 800);
	}
	if ($largeur >= _IMAGE_TAILLE_MINI_AUTOLIEN or $hauteur >=_IMAGE_TAILLE_MINI_AUTOLIEN) {
		return true;
	}
	return false;
}

/**
 * Liste les classes standards des modèles de documents SPIP.
 *
 * @note
 *     le nomage au pluriel est historique.
 *     préférer au singulier pour toute nouvelle classe.
 *
 * @param array $Pile
 * @param int $id_document
 * @param string $media
 * @return string
 */
function filtre_medias_modele_document_standard_classes_dist($Pile, $id_document, $media) {
	$env = $Pile[0];
	$var = $Pile['vars'] ?? [];

	$classes = [];
	$classes[] = "spip_document_$id_document";
	$classes[] = 'spip_document';
	$classes[] = 'spip_documents';
	$classes[] = "spip_document_$media";
	if (!empty($env['align'])) {
		$classes[] = 'spip_documents_' . $env['align'];
		$classes[] = 'spip_document_' . $env['align'];
	} elseif ($media === 'image') {
		$classes[] = 'spip_documents_center';
		$classes[] = 'spip_document_center';
	}
	if (!empty($var['legende'])) {
		$classes[] = 'spip_document_avec_legende';
	}
	if (!empty($env['class'])) {
		$classes[] = $env['class'];
	}
	return implode(' ', $classes);
}


/**
 * Liste les attributs data standards des modèles de documents SPIP.
 *
 * @param array $Pile
 * @param int $id_document
 * @param string $media
 * @return string
 */
function filtre_medias_modele_document_standard_attributs_dist($Pile, $id_document, $media) {
	$var = $Pile['vars'] ?? [];
	$attrs = [];

	if (!empty($var['legende'])) {
		$len = spip_strlen(textebrut($var['legende']));
		// des x. "x" = 32 caratères, "xx" => 64, "xxx" => 128, etc...
		$lenx = medias_str_repeat_log($len, 2, 'x', 4);
		$attrs['data-legende-len'] = $len;
		$attrs['data-legende-lenx'] = $lenx;
	}

	$res = '';
	foreach ($attrs as $attr => $value) {
		$res .= "$attr=\"" . attribut_html($value) . '" ';
	};
	return rtrim($res);
}


/**
 * Retourne une chaine répétée d'autant de fois le logarithme
 *
 * @example medias_str_repeat_log(124, 2)
 *
 *     Avec $base = 2 et $remove = 0
 *
 *     0 =>
 *     2 => x
 *     4 => xx
 *     8 => xxx
 *     16 => xxxx
 *     32 => xxxxx
 *     64 => xxxxxx
 *
 * @example medias_str_repeat_log(124, 2, "x", 4)
 *
 *     Avec $base = 2 et $remove = 4
 *
 *     0 =>
 *     2 =>
 *     4 =>
 *     8 =>
 *     16 =>
 *     32 => x
 *     64 => xx
 *
 * @note
 *     L'inverse (nb caractères => valeur) est donc `pow($base, $nb_char)`
 *
 *     En partant du nombre de "x" on retrouve la fourchette du nombre de départ.
 *     Si $base = 2 et $remove = 4 :
 *
 *    - "xxx" = 2 ^ (strlen("xxx") + 4) = 2 ^ (3 + 4) = 128
 *    - "xxxxx" = 2 ^ (5 + 4) = 512

 *     x = 32,
 *     xx = 64
 *     xxx = 128
 *     xxxx = 256
 *     xxxxx = 512
 *     ...
 *
 *     Ce qui veut dire que "xxx" provient d'une valeur entre 128 et 255.
 *
 * @note
 *     C'est surtout utile pour une sélection en CSS (car CSS ne permet pas de sélecteur "lower than" ou "greater than") :
 *
 *    ```spip
 *    <div class='demo' data-demo-lenx='[(#TEXTE|textebrut|spip_strlen|medias_str_repeat_log{2,x,4})]'>...</div>`
 *    ```
 *
 *    ```css
 *    .demo[data-demo-lenx^="xxxx"] {
 *       // le contenu fait au moins 256 caractères
 *    }
 *    .demo:not([data-demo-lenx^="xxxx"]) {
 *       // le contenu fait au moins 256 caractères
 *    }
 *    ```
 *
 * @param float $num
 * @param float $log
 * @param string $pad_string
 * @param int $remove : Nombre de caractères à enlever.
 *
 * @return string Des x
 */
function medias_str_repeat_log($num, $base = 2, $string = 'x', $remove = 0) {
	$pad = str_repeat($string, (int)log($num, $base));
	return substr($pad, $remove);
}

/*
 * Compile la balise #URL_DOCUMENT_FICHIER, mais en cherchant bien un id_document dans la boucle (et non pas un id_document_fichier)
 */
function balise_URL_DOCUMENT_FICHIER_dist($p) {
	include_spip('balise/url_');

	$_id = interprete_argument_balise(1, $p);

	if (!$_id) {
		$_id = champ_sql('id_document', $p);
	}

	$nom = $p->nom_champ;
	$code = generer_generer_url_arg('document_fichier', $p, $_id);
	$code = champ_sql($nom, $p, $code);
	$p->code = $code;
	if (!$p->etoile) {
		$p->code = "vider_url($code)";
	}
	$p->interdire_scripts = false;

	return $p;
}
