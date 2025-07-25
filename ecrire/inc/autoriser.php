<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use function SpipLeague\Component\Kernel\app;

/**
 * Gestion de l'API autoriser et fonctions d'autorisations de SPIP
 *
 * @package SPIP\Core\Autorisations
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/abstract_sql');

// Constantes surchargeables, cf. plugin autorite

/**
 * Gérer les admins restreints ?
 *
 * @todo une option à activer
 */
defined('_ADMINS_RESTREINTS') || define('_ADMINS_RESTREINTS', true);

/** Statut par defaut à la creation */
defined('_STATUT_AUTEUR_CREATION') || define('_STATUT_AUTEUR_CREATION', '1comite');

/** statuts associables a des rubriques (separes par des virgules) */
defined('_STATUT_AUTEUR_RUBRIQUE') || define('_STATUT_AUTEUR_RUBRIQUE', _ADMINS_RESTREINTS ? '0minirezo' : '');


if (!function_exists('autoriser')) {
	/**
	 * Autoriser une action
	 *
	 * Teste si une personne (par défaut le visiteur en cours) peut effectuer
	 * une certaine action. Cette fonction est le point d'entrée de toutes
	 * les autorisations.
	 *
	 * La fonction se charge d'appeler des fonctions d'autorisations spécifiques
	 * aux actions demandées si elles existent. Elle cherche donc les fonctions
	 * dans cet ordre :
	 *
	 * - autoriser_{type}_{faire}, sinon avec _dist
	 * - autoriser_{type}, sinon avec _dist
	 * - autoriser_{faire}, sinon avec _dist
	 * - autoriser_{defaut}, sinon avec _dist
	 *
	 * Seul le premier argument est obligatoire.
	 *
	 * @note
	 *     Le paramètre `$type` attend par défaut un type d'objet éditorial, et à ce titre,
	 *     la valeur transmise se verra appliquer la fonction 'objet_type' pour uniformiser
	 *     cette valeur.
	 *
	 *     Si ce paramètre n'a rien n'a voir avec un objet éditorial, par exemple
	 *     'statistiques', un souligné avant le terme est ajouté afin d'indiquer
	 *     explicitement à la fonction autoriser de ne pas transformer la chaîne en type
	 *     d'objet. Cela donne pour cet exemple : `autoriser('detruire', '_statistiques')`
	 *
	 * @note
	 *     Le paramètre `$type`, en plus de l'uniformisation en type d'objet, se voit retirer
	 *     tous les soulignés du terme. Ainsi le type d'objet `livre_art` deviendra `livreart`
	 *     et SPIP cherchera une fonction `autoriser_livreart_{faire}`. Ceci permet
	 *     d'éviter une possible confusion si une fonction `autoriser_livre_art` existait :
	 *     quel serait le type, quel serait l'action ?
	 *
	 *     Pour résumer, si le type d'objet éditorial a un souligné, tel que 'livre_art',
	 *     la fonction d'autorisation correspondante ne l'aura pas.
	 *     Exemple : `function autoriser_livreart_modifier_dist(...){...}`
	 *
	 * @api
	 * @see autoriser_dist()
	 * @see objet_type()
	 *
	 * @param string $faire
	 *   une action ('modifier', 'publier'...)
	 * @param string|null $type
	 *   Type d’objet ou élément sur lequel appliquer l’action.
	 *   - null: indifférent à tout type d’élément ou objet éditorial
	 *   - string: objet éditorial (objet_type() est appliqué pour homogénéiser l’entrée)
	 *   - _string: autre élément (avec un souligné en premier caractère, désactive objet_type()).
	 *   Les soulignés seront retirés (cf. la note).
	 * @param string|int|null $id
	 *   id de l'objet ou élément sur lequel on veut agir, si pertinent.
	 *   - null: non utile pour l’autorisation
	 *   - int: identifiant numérique (cas de tous les objets éditoriaux de SPIP)
	 *   - string: identifiant textuel
	 * @param null|int|array $qui
	 *   - si null on prend alors visiteur_session
	 *   - un id_auteur (on regarde dans la base)
	 *   - un tableau auteur complet, y compris [restreint]
	 * @param array $opt
	 *   options sous forme de tableau associatif
	 * @return bool
	 *   true si la personne peut effectuer l'action
	 */
	function autoriser(string $faire, ?string $type = '', $id = null, $qui = null, array $opt = []): bool {
		// Charger les fonctions d'autorisation supplementaires
		static $pipe;
		if (!isset($pipe)) {
			$pipe = 1;
			pipeline('autoriser');
		}

		return autoriser_dist($faire, $type, $id, $qui, $opt);
	}
}


// mes_fonctions peut aussi declarer des autorisations, il faut donc le charger
// mais apres la fonction autoriser()
if ($f = find_in_path('mes_fonctions.php')) {
	global $dossier_squelettes;
	include_once(app()->getCwd() . DIRECTORY_SEPARATOR . $f);
}


/**
 * Autoriser une action
 *
 * Voir autoriser() pour une description complète
 *
 * @see autoriser()
 *
 * @param string $faire
 *   une action ('modifier', 'publier'...)
 * @param string|null $type
 *   type d'objet ('article') ou élément
 * @param int|string|null $id
 *   id de l'objet ou élément sur lequel on veut agir, si pertinent.
 *   - null: non utile pour l’autorisation
 *   - int: identifiant numérique (cas de tous les objets éditoriaux de SPIP)
 *   - string: identifiant textuel
 * @param null|int|array $qui
 *   - si null on prend alors visiteur_session
 *   - un id_auteur (on regarde dans la base)
 *   - un tableau auteur complet, y compris [restreint]
 * @param array $opt
 *   options sous forme de tableau associatif
 * @return bool
 *   true si la personne peut effectuer l'action
 */
function autoriser_dist(string $faire, ?string $type = '', $id = null, $qui = null, array $opt = []): bool {

	if ($type === null) {
		$type = '';
	}

	// Qui ? visiteur_session ?
	// si null ou '' (appel depuis #AUTORISER) on prend l'auteur loge
	if ($qui === null or $qui === '') {
		$qui = $GLOBALS['visiteur_session'] ?: [];
		$qui = array_merge(['statut' => '', 'id_auteur' => 0, 'webmestre' => 'non'], $qui);
	} elseif (is_numeric($qui)) {
		$qui = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . $qui);
		if (!$qui) {
			$qui = ['statut' => '', 'id_auteur' => 0, 'webmestre' => 'non'];
		}
	}

	// Admins restreints, on construit ici (pas generique mais...)
	// le tableau de toutes leurs rubriques (y compris les sous-rubriques)
	if (_ADMINS_RESTREINTS and is_array($qui)) {
		$qui['restreint'] = isset($qui['id_auteur']) ? liste_rubriques_auteur($qui['id_auteur']) : [];
	}

	spip_log(
		"autoriser $faire $type $id (" . ($qui['nom'] ?? '') . ') ?',
		'autoriser' . _LOG_DEBUG
	);

	$type = autoriser_type($type);

	// Si une exception a ete decretee plus haut dans le code, l'appliquer
	if (
		(isset($GLOBALS['autoriser_exception'][$faire][$type][$id]) and autoriser_exception($faire, $type, $id, 'verifier'))
		or (isset($GLOBALS['autoriser_exception'][$faire][$type]['*']) and autoriser_exception($faire, $type, '*', 'verifier'))
	) {
		spip_log("autoriser ($faire, $type, $id, " . ($qui['nom'] ?? '') . ') : OK Exception', 'autoriser' . _LOG_DEBUG);
		return true;
	}

	// Chercher une fonction d'autorisation
	// Dans l'ordre on va chercher autoriser_type_faire[_dist], autoriser_type[_dist],
	// autoriser_faire[_dist], autoriser_defaut[_dist]
	$fonctions = $type
		? [
			'autoriser_' . $type . '_' . $faire,
			'autoriser_' . $type . '_' . $faire . '_dist',
			'autoriser_' . $type,
			'autoriser_' . $type . '_dist',
			'autoriser_' . $faire,
			'autoriser_' . $faire . '_dist',
			'autoriser_defaut',
			'autoriser_defaut_dist'
		]
		: [
			'autoriser_' . $faire,
			'autoriser_' . $faire . '_dist',
			'autoriser_defaut',
			'autoriser_defaut_dist'
		];

	$a = false;
	foreach ($fonctions as $f) {
		if (function_exists($f)) {
			$a = $f($faire, $type, $id, $qui, $opt);
			break;
		}
	}

	spip_log(
		"$f($faire, $type, $id, " . ($qui['nom'] ?? '') . ') : ' . ($a ? 'OK' : 'niet'),
		'autoriser' . _LOG_DEBUG
	);

	if (!is_bool($a)) {
	     trigger_error(sprintf("Function %s should returns a boolean instead of %s (casts as boolean). This will trigger fatal error in future versions.", $f, gettype($a)), \E_USER_DEPRECATED);
	     $a = (bool) $a;
	}

	return $a;
}

// une globale pour aller au plus vite dans la fonction generique ci dessus
$GLOBALS['autoriser_exception'] = [];

/**
 * Accorder une autorisation exceptionnelle pour le hit en cours, ou la révoquer
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant (* pour tous les ids)
 * @param string|bool $autoriser accorder (true) ou revoquer (false)
 *     - bool Accorder ou révoquer
 *     - 'verifier' : test si une exception existe
 * @return bool
 */
function autoriser_exception(string $faire, ?string $type = '', $id = null, $autoriser = true): bool {
	// une static innaccessible par url pour verifier que la globale est positionnee a bon escient
	static $autorisation;
	// Tolérance avec certains appels
	if ($id === null) {
		$id = 0;
	}
	$type = autoriser_type($type);
	if ($autoriser === 'verifier') {
		return isset($autorisation[$faire][$type][$id]);
	}
	if ($autoriser === true) {
		$GLOBALS['autoriser_exception'][$faire][$type][$id] = $autorisation[$faire][$type][$id] = true;
	}
	if ($autoriser === false) {
		if ($id === '*') {
			unset($GLOBALS['autoriser_exception'][$faire][$type]);
			unset($autorisation[$faire][$type]);
		}
		else {
			unset($GLOBALS['autoriser_exception'][$faire][$type][$id]);
			unset($autorisation[$faire][$type][$id]);
		}
	}

	return false;
}

/**
 * Adapte un type pour les autorisations
 *
 * Cela sert à trouver le nom des autorisations correspondantes.
 *
 * - Si _ en premier caractère, c’est un nom de page
 * - Sinon, c’est un type d’objet éditorial
 * - Les _ sont supprimés
 */
function autoriser_type(?string $type = ''): string {
	// passer par objet_type pour avoir les alias
	// sauf si _ est le premier caractère.
	if ($type && $type[0] !== '_') {
		$type = objet_type($type, false);
	}
	// et supprimer les _
	return str_replace('_', '', (string) $type);
}


/**
 * Autorisation par defaut
 *
 * Les admins complets OK, les autres non
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_defaut_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return
		$qui['statut'] === '0minirezo'
		and !$qui['restreint'];
}

/**
 * Autorisation à se loger ?
 *
 * Retourne true pour tous les statuts sauf 5poubelle
 * Peut être surchargée pour interdire statut=nouveau à se connecter
 * et forcer l'utilisation du lien de confirmation email pour valider le compte
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 */
function autoriser_loger_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	if ($qui['statut'] === '5poubelle') {
		return false;
	}
	return true;
}

/**
 * Autorisation d'accès à l'espace privé ?
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_ecrire_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return isset($qui['statut']) and in_array($qui['statut'], ['0minirezo', '1comite']);
}

/**
 * Autorisation de créer un contenu
 *
 * Accordée par defaut ceux qui accèdent à l'espace privé,
 * peut-être surchargée au cas par cas
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_creer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return in_array($qui['statut'], ['0minirezo', '1comite']);
}

/**
 * Autorisation de prévisualiser un contenu
 *
 * @uses test_previsualiser_objet_champ()
 * @uses decrire_token_previsu()
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_previsualiser_dist(string $faire, string $type, $id, array $qui, array $opt): bool {

	// Le visiteur a-t-il un statut prevu par la config ?
	if (strpos($GLOBALS['meta']['preview'], ',' . $qui['statut'] . ',') !== false) {
		return test_previsualiser_objet_champ($type, $id, $qui, $opt);
	}

	// A-t-on un token de prévisualisation valable ?
	include_spip('inc/securiser_action');
	if (decrire_token_previsu()) {
		return true;
	}

	return false;
}

/**
 * Teste qu'un objet éditorial peut être prévisualisé
 *
 * Cela permet ainsi de commander l'affichage dans l'espace prive du bouton "previsualiser"
 * voir `prive/objets/infos/article.html` etc.
 *
 * Cela dépend du statut actuel de l'objet d'une part, et d'autre part de la
 * clé `previsu` dans le tableau `statut` de la déclaration de l'objet éditorial.
 * Cette clé `previsu` liste des statuts, séparés par des virgules,
 * qui ont le droit d'avoir une prévisualisation. La présence de `xx/auteur` indique que pour le
 * statut `xx`, l'auteur en cours doit être un des auteurs de l'objet éditorial en question
 * pour que ce statut autorise la prévisualisation.
 *
 * Exemple pour les articles : `'previsu' => 'publie,prop,prepa/auteur',`
 *
 * @uses lister_tables_objets_sql()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 */
function test_previsualiser_objet_champ(string $type = '', $id = null, array $qui = [], array $opt = []): bool {

	// si pas de type et statut fourni, c'est une autorisation generale => OK
	if (!$type) {
		return true;
	}

	include_spip('base/objets');
	$infos = lister_tables_objets_sql(table_objet_sql($type));
	if (isset($infos['statut'])) {
		foreach ($infos['statut'] as $c) {
			if (isset($c['publie'])) {
				if (!isset($c['previsu'])) {
					return false;
				} // pas de previsu definie => NIET
				$champ = $c['champ'];
				if (!isset($opt[$champ])) {
					return false;
				} // pas de champ passe a la demande => NIET
				$previsu = explode(',', $c['previsu']);
				// regarder si ce statut est autorise pour l'auteur
				if (in_array($opt[$champ] . '/auteur', $previsu)) {
					// retrouver l’id_auteur qui a filé un lien de prévisu éventuellement,
					// sinon l’auteur en session
					include_spip('inc/securiser_action');
					if ($desc = decrire_token_previsu()) {
						$id_auteur = $desc['id_auteur'];
					} elseif (isset($GLOBALS['visiteur_session']['id_auteur'])) {
						$id_auteur = intval($GLOBALS['visiteur_session']['id_auteur']);
					} else {
						$id_auteur = null;
					}

					if (!$id_auteur) {
						return false;
					} elseif (autoriser('previsualiser' . $opt[$champ], $type, 0, $id_auteur)) {
						// dans ce cas (admin en general), pas de filtrage sur ce statut
					} elseif (
						!sql_countsel(
							'spip_auteurs_liens',
							'id_auteur=' . intval($id_auteur) . ' AND objet=' . sql_quote($type) . ' AND id_objet=' . intval($id)
						)
					) {
						return false;
					} // pas auteur de cet objet => NIET
				} elseif (!in_array($opt[$champ], $previsu)) {
					// le statut n'est pas dans ceux definis par la previsu => NIET
					return false;
				}
			}
		}
	}

	return true;
}

/**
 * Autorisation de changer de langue un contenu
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_changerlangue_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	$multi_objets = explode(',', lire_config('multi_objets'));
	$gerer_trad_objets = explode(',', lire_config('gerer_trad_objets'));
	$table = table_objet_sql($type);
	if (
		in_array($table, $multi_objets)
		or in_array($table, $gerer_trad_objets)
	) { // affichage du formulaire si la configuration l'accepte
		$multi_secteurs = lire_config('multi_secteurs');
		$champs = objet_info($type, 'field');
		if (
			$multi_secteurs === 'oui'
			and array_key_exists('id_rubrique', $champs)
		) {
			// multilinguisme par secteur et objet rattaché à une rubrique
			$primary = id_table_objet($type);
			if ($table != 'spip_rubriques') {
				$id_rubrique = sql_getfetsel('id_rubrique', "$table", "$primary=" . intval($id));
			} else {
				$id_rubrique = $id;
			}
			$id_secteur = sql_getfetsel('id_secteur', 'spip_rubriques', 'id_rubrique=' . intval($id_rubrique));
			if (!$id_secteur > 0) {
				$id_secteur = $id_rubrique;
			}
			$langue_secteur = sql_getfetsel('lang', 'spip_rubriques', 'id_rubrique=' . intval($id_secteur));
			$langue_objet = sql_getfetsel('lang', "$table", "$primary=" . intval($id));
			if ($langue_secteur != $langue_objet) {
				// configuration incohérente, on laisse l'utilisateur corriger la situation
				return true;
			}
			if ($table != 'spip_rubriques') { // le choix de la langue se fait seulement sur les rubriques
				return false;
			} else {
				$id_parent = sql_getfetsel('id_parent', 'spip_rubriques', 'id_rubrique=' . intval($id));
				if ($id_parent != 0) {
					// sous-rubriques : pas de choix de langue
					return false;
				}
			}
		}
	} else {
		return false;
	}

	return autoriser('modifier', $type, $id, $qui, $opt);
}

/**
 * Autorisation de changer le lien de traduction
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_changertraduction_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('modifier', $type, $id, $qui, $opt);
}

/**
 * Autorisation de changer la date d'un contenu
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_dater_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	$table = table_objet($type);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table);
	if (!$desc) {
		return false;
	}

	if (!isset($opt['statut'])) {
		if (isset($desc['field']['statut'])) {
			$statut = sql_getfetsel('statut', $desc['table'], id_table_objet($type) . '=' . intval($id));
		} else {
			$statut = 'publie';
		} // pas de statut => publie
	} else {
		$statut = $opt['statut'];
	}

	// Liste des statuts publiés pour cet objet
	if (isset($desc['statut'][0]['publie'])) {
		$statuts_publies = explode(',', $desc['statut'][0]['publie']);
	}
	// Sinon en dur le statut "publie"
	else {
		$statuts_publies = ['publie'];
	}

	if (
		in_array($statut, $statuts_publies)
		// Ou cas particulier géré en dur ici pour les articles
		or ($statut === 'prop' and $type === 'article' and $GLOBALS['meta']['post_dates'] === 'non')
	) {
		return autoriser('modifier', $type, $id);
	}

	return false;
}

/**
 * Autorisation d'instituer un contenu
 *
 * C'est à dire de changer son statut ou son parent.
 * Par défaut, il faut l'autorisation de modifier le contenu
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_instituer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('modifier', $type, $id, $qui, $opt);
}

/**
 * Autorisation de publier dans une rubrique $id
 *
 * Il faut être administrateur ou administrateur restreint de la rubrique
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_rubrique_publierdans_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return
		($qui['statut'] === '0minirezo')
		and (
			!$qui['restreint'] or !$id
			or in_array($id, $qui['restreint'])
		);
}

/**
 * Autorisation de créer une rubrique
 *
 * Il faut être administrateur pour pouvoir publier à la racine
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_rubrique_creer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	if (!empty($opt['id_parent'])) {
		return autoriser('creerrubriquedans', 'rubrique', $opt['id_parent'], $qui);
	}
	else {
		return autoriser('defaut', null, 0, $qui, $opt);
	}
}

/**
 * Autorisation de créer une sous rubrique dans une rubrique $id
 *
 * Il faut être administrateur et pouvoir publier dans la rubrique
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_rubrique_creerrubriquedans_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return
		($id or ($qui['statut'] === '0minirezo' and !$qui['restreint']))
		and autoriser('voir', 'rubrique', $id)
		and autoriser('publierdans', 'rubrique', $id);
}

/**
 * Autorisation de créer un article dans une rubrique $id
 *
 * Il faut pouvoir voir la rubrique et pouvoir créer un article…
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_rubrique_creerarticledans_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return
		$id
		and autoriser('voir', 'rubrique', $id)
		and autoriser('creer', 'article');
}


/**
 * Autorisation de modifier une rubrique $id
 *
 * Il faut pouvoir publier dans cette rubrique
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_rubrique_modifier_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return
		autoriser('publierdans', 'rubrique', $id, $qui, $opt);
}

/**
 * Autorisation de supprimer une rubrique $id
 *
 * Il faut quelle soit vide (pas d'enfant) et qu'on ait le droit de la modifier
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_rubrique_supprimer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	if (!$id = intval($id)) {
		return false;
	}

	if (sql_countsel('spip_rubriques', 'id_parent=' . intval($id))) {
		return false;
	}

	if (sql_countsel('spip_articles', 'id_rubrique=' . intval($id) . " AND (statut<>'poubelle')")) {
		return false;
	}

	$compte = pipeline(
		'objet_compte_enfants',
		['args' => ['objet' => 'rubrique', 'id_objet' => $id], 'data' => []]
	);
	foreach ($compte as $objet => $n) {
		if ($n) {
			return false;
		}
	}

	return autoriser('modifier', 'rubrique', $id);
}


/**
 * Autorisation de modifier un article $id
 *
 * Il faut pouvoir publier dans le parent
 * ou, si on change le statut en proposé ou préparation être auteur de l'article
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_article_modifier_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	if (!$id) {
		return false;
	}
	$r = sql_fetsel('id_rubrique,statut', 'spip_articles', 'id_article=' . sql_quote($id));

	return
		$r
		and
		(
			autoriser('publierdans', 'rubrique', $r['id_rubrique'], $qui, $opt)
			or (
				(!isset($opt['statut']) or !in_array($opt['statut'], ['publie', 'refuse'], true))
				and in_array($qui['statut'], ['0minirezo', '1comite'])
				and in_array($r['statut'], ['prop', 'prepa', 'poubelle'])
				and auteurs_objet('article', $id, 'id_auteur=' . $qui['id_auteur'])
			)
		);
}

/**
 * Autorisation de créer un article
 *
 * Il faut qu'une rubrique existe et être au moins rédacteur
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_article_creer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	if (!empty($opt['id_parent'])) {
		// creerarticledans rappelle autoriser(creer,article) sans id, donc on verifiera condition du else aussi
		return autoriser('creerarticledans', 'rubrique', $opt['id_parent'], $qui);
	}
	else {
		return (sql_countsel('spip_rubriques') > 0 and in_array($qui['statut'], ['0minirezo', '1comite']));
	}
}

/**
 * Autorisation de voir un article
 *
 * Il faut être admin ou auteur de l'article, sinon il faut que l'article
 * soit publié ou proposé.
 *
 * Peut-être appelée sans $id, mais avec un $opt['statut'] pour tester
 * la liste des status autorisés en fonction de $qui['statut']
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 */
function autoriser_article_voir_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	if ($qui['statut'] === '0minirezo') {
		return true;
	}
	// cas des articles : depend du statut de l'article et de l'auteur
	if (isset($opt['statut'])) {
		$statut = $opt['statut'];
	} else {
		if (!$id) {
			return false;
		}
		$statut = sql_getfetsel('statut', 'spip_articles', 'id_article=' . intval($id));
	}

	return
		// si on est pas auteur de l'article,
		// seuls les propose et publies sont visibles
		in_array($statut, ['prop', 'publie'])
		// sinon si on est auteur, on a le droit de le voir, evidemment !
		or
		($id
			and $qui['id_auteur']
			and auteurs_objet('article', $id, 'id_auteur=' . $qui['id_auteur']));
}


/**
 * Autorisation de voir un objet
 *
 * Tout est visible par défaut, sauf les auteurs où il faut au moins être rédacteur.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_voir_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	# securite, mais on aurait pas du arriver ici !
	if (
		function_exists($f = 'autoriser_' . $type . '_voir')
		or function_exists($f = 'autoriser_' . $type . '_voir_dist')
	) {
		return $f($faire, $type, $id, $qui, $opt);
	}

	if ($qui['statut'] === '0minirezo') {
		return true;
	}
	// admins et redacteurs peuvent voir un auteur
	if ($type === 'auteur') {
		return in_array($qui['statut'], ['0minirezo', '1comite']);
	}
	// sinon par defaut tout est visible
	// sauf cas particuliers traites separemment (ie article)
	return true;
}


/**
 * Autorisation de webmestre
 *
 * Est-on webmestre ? Signifie qu'on n'a même pas besoin de passer par ftp
 * pour modifier les fichiers, cf. notamment inc/admin
 *
 * Soit la liste des webmestres est définie via une constante _ID_WEBMESTRES,
 * soit on regarde l'état "webmestre" de l'auteur
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_webmestre_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return
		(defined('_ID_WEBMESTRES') ?
			in_array($qui['id_auteur'], explode(':', _ID_WEBMESTRES))
			: $qui['webmestre'] === 'oui')
		and $qui['statut'] === '0minirezo'
		and !$qui['restreint'];
}

/**
 * Autorisation Configurer le site
 *
 * Il faut être administrateur complet
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_configurer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return
		$qui['statut'] === '0minirezo'
		and !$qui['restreint'];
}

/**
 * Autorisation de sauvegarder la base de données
 *
 * Il faut être administrateur (y compris restreint)
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_sauvegarder_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return
		$qui['statut'] === '0minirezo';
}

/**
 * Autorisation d'effacer la base de données
 *
 * Il faut être webmestre
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_detruire_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('webmestre', null, 0, $qui, $opt);
}

/**
 * Autorisation de prévisualiser un auteur
 *
 * Il faut être administrateur ou que l'auteur à prévisualiser
 * ait au moins publié un article
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_auteur_previsualiser_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	// les admins peuvent "previsualiser" une page auteur
	if (
		$qui['statut'] === '0minirezo'
		and !$qui['restreint']
	) {
		return true;
	} elseif ($id === 0) {
		return false;
	}
	// "Voir en ligne" si l'auteur a un article publie
	$n = sql_fetsel(
		'A.id_article',
		'spip_auteurs_liens AS L LEFT JOIN spip_articles AS A ON (L.objet=\'article\' AND L.id_objet=A.id_article)',
		"A.statut='publie' AND L.id_auteur=" . sql_quote($id)
	);

	return $n ? true : false;
}


/**
 * Autorisation de créer un auteur
 *
 * Il faut être administrateur (restreint compris).
 *
 * @note
 *     Seuls les administrateurs complets ont accès à tous les
 *     champs du formulaire d'édition d'un auteur. À la création
 *     d'un auteur, son statut est 'poubelle'. C'est l'autorisation
 *     de modifier qui permet de changer les informations sensibles
 *     (statut, login, pass, etc.) à l'institution.
 *
 * @see auteur_inserer()
 * @see auteur_instituer()
 * @see autoriser_auteur_modifier_dist()
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_auteur_creer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return ($qui['statut'] === '0minirezo');
}


/**
 * Autorisation de modifier un auteur
 *
 * Attention tout depend de ce qu'on veut modifier. Il faut être au moins
 * rédacteur, mais on ne peut pas promouvoir (changer le statut) un auteur
 * avec des droits supérieurs au sien.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_auteur_modifier_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	$id = intval($id);

	// Si pas admin : seulement le droit de modifier ses donnees perso, mais pas statut ni login
	// la modif de l'email doit etre verifiee ou notifiee si possible, mais c'est a l'interface de gerer ca
	if (!in_array($qui['statut'], ['0minirezo'])) {
		if (
			$id == $qui['id_auteur']
			&& empty($opt['statut'])
			&& empty($opt['webmestre'])
			&& empty($opt['restreintes'])
			&& empty($opt['login'])
		) {
			return true;
		}
		return false;
	}

	// Un admin restreint peut modifier/creer un auteur non-admin mais il
	// n'a le droit ni de le promouvoir admin, ni de changer les rubriques
	if ($qui['restreint']) {
		if (isset($opt['webmestre']) and $opt['webmestre']) {
			return false;
		} elseif (
			(isset($opt['statut']) and ($opt['statut'] === '0minirezo'))
			or (isset($opt['restreintes']) and $opt['restreintes'])
		) {
			return false;
		} else {
			if ($id == $qui['id_auteur']) {
				if (isset($opt['statut']) and $opt['statut']) {
					return false;
				} else {
					return true;
				}
			} else {
				if ($id_auteur = intval($id)) {
					$t = sql_fetsel('statut', 'spip_auteurs', "id_auteur=$id_auteur");
					if ($t and $t['statut'] != '0minirezo') {
						return true;
					} else {
						return false;
					}
				} // id = 0 => creation
				else {
					return true;
				}
			}
		}
	}

	// Un admin complet fait ce qu'il veut
	// sauf se degrader
	if ($id == $qui['id_auteur'] && (isset($opt['statut']) and $opt['statut'])) {
		return false;
	} elseif (
		isset($opt['webmestre'])
				and $opt['webmestre']
				and (defined('_ID_WEBMESTRES')
				or !autoriser('webmestre'))
	) {
		// et toucher au statut webmestre si il ne l'est pas lui meme
		// ou si les webmestres sont fixes par constante (securite)
		return false;
	} // et modifier un webmestre si il ne l'est pas lui meme
	elseif (intval($id) and !autoriser('webmestre') and autoriser('webmestre', '', 0, $id)) {
		return false;
	} else {
		return true;
	}
}


/**
 * Autorisation d'associer un auteur sur un objet
 *
 * Il faut pouvoir modifier l'objet en question
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_associerauteurs_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('modifier', $type, $id, $qui, $opt);
}


/**
 * Autorisation d'upload FTP
 *
 * Il faut être administrateur.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_chargerftp_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return $qui['statut'] === '0minirezo';
}

/**
 * Autorisation d'activer le mode debug
 *
 * Il faut être administrateur.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_debug_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return $qui['statut'] === '0minirezo';
}

/**
 * Liste les rubriques d'un auteur
 *
 * Renvoie la liste des rubriques liées à cet auteur, independamment de son
 * statut (pour les admins restreints, il faut donc aussi vérifier statut)
 *
 * Mémorise le resultat dans un tableau statique indéxé par les id_auteur.
 * On peut reinitialiser un élément en passant un 2e argument non vide
 *
 * @param int $id_auteur Identifiant de l'auteur
 * @param bool $raz Recalculer le résultat connu pour cet auteur
 * @return array          Liste des rubriques
 **/
function liste_rubriques_auteur($id_auteur, $raz = false) {
	static $restreint = [];

	if (!$id_auteur = intval($id_auteur)) {
		return [];
	}
	if ($raz) {
		unset($restreint[$id_auteur]);
	} elseif (isset($restreint[$id_auteur])) {
		return $restreint[$id_auteur];
	}

	$rubriques = [];
	if (
		(!isset($GLOBALS['meta']['version_installee'])
		or $GLOBALS['meta']['version_installee'] > 16428)
		and $r = sql_allfetsel(
			'id_objet',
			'spip_auteurs_liens',
			'id_auteur=' . intval($id_auteur) . " AND objet='rubrique' AND id_objet!=0"
		)
		and is_countable($r) ? count($r) : 0
	) {
		$r = array_column($r, 'id_objet');

		// recuperer toute la branche, au format chaine enumeration
		include_spip('inc/rubriques');
		$r = calcul_branche_in($r);
		$r = explode(',', $r);

		// passer les rubriques en index, elimine les doublons
		$r = array_flip($r);
		// recuperer les index seuls
		$r = array_keys($r);
		// combiner pour avoir un tableau id_rubrique=>id_rubrique
		// est-ce vraiment utile ? (on preserve la forme donnee par le code precedent)
		$rubriques = array_combine($r, $r);
	}

	// Affecter l'auteur session le cas echeant
	if (
		isset($GLOBALS['visiteur_session']['id_auteur'])
		and $GLOBALS['visiteur_session']['id_auteur'] == $id_auteur
	) {
		$GLOBALS['visiteur_session']['restreint'] = $rubriques;
	}


	return $restreint[$id_auteur] = $rubriques;
}

/**
 * Autorisation de prévisualiser une rubrique
 *
 * Il faut pouvoir prévisualiser.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_rubrique_previsualiser_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('previsualiser');
}

/**
 * Autorisation d'iconifier une rubrique (mettre un logo)
 *
 * Il faut pouvoir publier dans la rubrique.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_rubrique_iconifier_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('publierdans', 'rubrique', $id, $qui, $opt);
}

/**
 * Autorisation d'iconifier un auteur (mettre un logo)
 *
 * Il faut un administrateur ou que l'auteur soit celui qui demande l'autorisation
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_auteur_iconifier_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	$id = intval($id);
	return (($id == $qui['id_auteur']) or
		(($qui['statut'] === '0minirezo') and !$qui['restreint']));
}

/**
 * Autorisation d'iconifier un objet (mettre un logo)
 *
 * Il faut pouvoir modifier l'objet
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_iconifier_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	// par defaut, on a le droit d'iconifier si on a le droit de modifier
	return autoriser('modifier', $type, $id, $qui, $opt);
}


/**
 * Autorisation OK
 *
 * Autorise toujours !
 * Fonction sans surprise pour permettre les tests.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return true
 **/
function autoriser_ok_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return true;
}

/**
 * Autorisation NIET
 *
 * Refuse toujours !
 * Fonction sans surprise pour permettre les tests.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return false
 **/
function autoriser_niet_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return false;
}

/**
 * Autorisation de réparer la base de données
 *
 * Il faut pouvoir la détruire (et ne pas être en cours de réinstallation)
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_base_reparer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	if (!autoriser('detruire') or _request('reinstall')) {
		return false;
	}

	return true;
}

/**
 * Autorisation de voir l'onglet infosperso
 *
 * Toujours OK
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return true
 **/
function autoriser_infosperso_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return true;
}

/**
 * Autorisation de voir le formulaire configurer_langage
 *
 * Toujours OK
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return true
 **/
function autoriser_langage_configurer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return true;
}

/**
 * Autorisation de voir l'onglet configurerlangage
 *
 * Calquée sur l'autorisation de voir le formulaire configurer_langage
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_configurerlangage_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('configurer', '_langage', $id, $qui, $opt);
}

/**
 * Autorisation de voir le formulaire configurer_preferences
 *
 * Toujours OK
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return true
 **/
function autoriser_preferences_configurer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return true;
}

/**
 * Autorisation de voir le formulaire configurer_preferences_menus
 *
 * Calquée sur l'autorisation de voir le formulaire configurer_preferences
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return true
 */
function autoriser_preferencesmenus_configurer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('configurer', '_preferences', $id, $qui, $opt);
}

/**
 * Autorisation de voir l'onglet configurerpreferences
 *
 * Calquée sur l'autorisation de voir le formulaire configurer_preferences
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_configurerpreferences_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('configurer', '_preferences', $id, $qui, $opt);
}

/**
 * Autorisation d'afficher le menu développement
 *
 * Dépend de la préférences utilisateur
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_menudeveloppement_menugrandeentree_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return (isset($GLOBALS['visiteur_session']['prefs']['activer_menudev'])
		and $GLOBALS['visiteur_session']['prefs']['activer_menudev'] === 'oui');
}

/**
 * Autorisation d'afficher une grande entrée de menu
 *
 * Par defaut les grandes entrees (accueil, édition, publication, etc.)
 * sont visibles de tous
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return true
 **/
function autoriser_menugrandeentree_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return true;
}

/**
 * Autorisation de voir la page auteurs
 *
 * Toujours OK
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return true
 **/
function autoriser_auteurs_voir_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return true;
}

/**
 * Autorisation de voir les emails des autres auteurs
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 */
function autoriser_auteurs_voiremails_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return $qui['statut'] === '0minirezo' && !$qui['restreint'];
}

/**
 * Autorisation de voir le menu auteurs
 *
 * Toujours OK
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_auteurs_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('voir', '_auteurs', $id, $qui, $opt);
}

/**
 * Autorisation de voir la page articles
 *
 * Toujours OK
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return true
 **/
function autoriser_articles_voir_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return true;
}

/**
 * Autorisation de voir le menu articles
 *
 * Toujours OK
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_articles_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('voir', '_articles', $id, $qui, $opt);
}

/**
 * Autorisation de voir la page rubriques
 *
 * Toujours OK
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return true
 **/
function autoriser_rubriques_voir_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return true;
}

/**
 * Autorisation de voir le menu rubriques
 *
 * Toujours OK
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_rubriques_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('voir', '_rubriques', $id, $qui, $opt);
}

/**
 * Autorisation de voir le menu articlecreer
 *
 * Il faut au moins une rubrique présente.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_articlecreer_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return verifier_table_non_vide();
}


/**
 * Autorisation de voir le menu auteurcreer
 *
 * Il faut pouvoir créer un auteur !
 *
 * @see autoriser_auteur_creer_dist()
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_auteurcreer_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('creer', 'auteur', $id, $qui, $opt);
}

/**
 * Autorisation de voir le menu "afficher les visiteurs"
 *
 * Être admin complet et il faut qu'il en existe ou que ce soit activé en config
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_visiteurs_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	include_spip('base/abstract_sql');
	return
		$qui['statut'] === '0minirezo' and !$qui['restreint']
		and (
			$GLOBALS['meta']['accepter_visiteurs'] != 'non'
			or sql_countsel('spip_auteurs', 'statut in ("6forum", "nouveau")') > 0
		);
}

/**
 * Autorisation de voir le menu suiviedito
 *
 * Il faut être administrateur (y compris restreint).
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_suiviedito_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return $qui['statut'] === '0minirezo';
}

/**
 * Autorisation de voir le menu synchro
 *
 * Il faut être administrateur (y compris restreint).
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_synchro_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return $qui['statut'] === '0minirezo';
}

/**
 * Autorisation de voir le menu configurer_interactions
 *
 * Il faut avoir accès à la page configurer_interactions
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_configurerinteractions_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('configurer', '_interactions', $id, $qui, $opt);
}

/**
 * Autorisation de voir le menu configurer_langue
 *
 * Il faut avoir accès à la page configurer_langue
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_configurerlangue_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('configurer', '_langue', $id, $qui, $opt);
}

/**
 * Autorisation de voir le menu configurer_multilinguisme
 *
 * Il faut avoir accès à la page configurer_multilinguisme
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_configurermultilinguisme_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('configurer', '_multilinguisme', $id, $qui, $opt);
}

/**
 * Autorisation de voir le menu configurer_contenu
 *
 * Il faut avoir accès à la page configurer_contenu
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_configurercontenu_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('configurer', '_contenu', $id, $qui, $opt);
}

/**
 * Autorisation de voir le menu configurer_avancees
 *
 * Il faut avoir accès à la page configurer_avancees
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_configureravancees_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('configurer', '_avancees', $id, $qui, $opt);
}

/**
 * Autorisation de voir le menu admin_plugin
 *
 * Il faut avoir accès à la page admin_plugin
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_adminplugin_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('configurer', '_plugins', $id, $qui, $opt);
}

/**
 * Autorisation de voir le menu admin_tech
 *
 * Il faut avoir accès à la page admin_tech
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_admintech_menu_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('detruire', $type, $id, $qui, $opt);
}

/**
 * Autorisation de purger la queue de travaux
 *
 * Il faut être webmestre.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_queue_purger_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('webmestre');
}


/**
 * Autorisation l'échafaudage de squelettes en Z
 *
 * Il faut être dans l'espace privé (et authentifié),
 * sinon il faut être webmestre (pas de fuite d'informations publiées)
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_echafauder_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	if (test_espace_prive()) {
		return intval($qui['id_auteur']) ? true : false;
	} else {
		return autoriser('webmestre', '', $id, $qui, $opt);
	}
}


/**
 * Retourne les identifiants d'auteurs liés à un objet
 *
 * @param string $objet
 * @param int $id_objet
 * @param string|array $cond
 *     Condition(s) supplémentaire(s) pour le where de la requête
 * @return int[]
 *     Identifiants d'auteurs
 */
function auteurs_objet($objet, $id_objet, $cond = '') {
	$objet = objet_type($objet);
	$where = [
		'objet=' . sql_quote($objet),
		'id_objet=' . intval($id_objet)
	];
	if (!empty($cond)) {
		if (is_array($cond)) {
			$where = array_merge($where, $cond);
		} else {
			$where[] = $cond;
		}
	}
	$auteurs = sql_allfetsel(
		'id_auteur',
		'spip_auteurs_liens',
		$where
	);
	if (is_array($auteurs)) {
		return array_column($auteurs, 'id_auteur');
	}
	return [];
}

/**
 * Lister les auteurs d'un article
 *
 * @deprecated 3.2
 * @see auteurs_objets()
 * @param int $id_article Identifiant de l'article
 * @param string $cond Condition en plus dans le where de la requête
 * @return array|bool
 *     - array : liste des id_auteur trouvés
 *     - false : serveur SQL indisponible
 */
function auteurs_article($id_article, $cond = '') {
	trigger_deprecation('spip', '3.2', 'Using "%s" is deprecated, use "%s" instead', __FUNCTION__, 'auteurs_objets');
	return sql_allfetsel(
		'id_auteur',
		'spip_auteurs_liens',
		"objet='article' AND id_objet=" . intval($id_article) . ($cond ? " AND $cond" : '')
	);
}


/**
 * Tester si on est admin restreint sur une rubrique donnée
 *
 * Fonction générique utilisee dans des autorisations ou assimilée
 *
 * @param int $id_rubrique Identifiant de la rubrique
 * @return bool             true si administrateur de cette rubrique, false sinon.
 */
function acces_restreint_rubrique($id_rubrique) {

	return (isset($GLOBALS['connect_id_rubrique'][$id_rubrique]));
}


/**
 * Verifier qu'il existe au moins un parent
 *
 * Fonction utilisee dans des autorisations des boutons / menus du prive des objets enfants (articles, breves, sites)
 *
 * @param string $table la table a vérifier
 * @return bool             true si un parent existe
 */
function verifier_table_non_vide($table = 'spip_rubriques') {
	static $done = [];
	if (!isset($done[$table])) {
		$done[$table] = sql_countsel($table) > 0;
	}

	return $done[$table];
}

/**
 * Détermine la possibilité de s'inscire sur le site
 *
 * Pour un statut et un éventuel id_rubrique donné, indique,
 * à l'aide de la liste globale des statuts (tableau mode => nom du mode)
 * si le visiteur peut s'inscrire sur le site.
 *
 * Utile pour le formulaire d'inscription.
 *
 * Par défaut, seuls `6forum` et `1comite` sont possibles, les autres sont
 * en `false`. Pour un nouveau mode il suffit de définir l'autorisation
 * spécifique.
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 */
function autoriser_inscrireauteur_dist($faire, $quoi, $id, $qui, $opt) {

	$s = array_search($quoi, $GLOBALS['liste_des_statuts']);
	switch ($s) {
		case 'info_redacteurs':
			return ($GLOBALS['meta']['accepter_inscriptions'] === 'oui');
		case 'info_visiteurs':
			return ($GLOBALS['meta']['accepter_visiteurs'] === 'oui' or $GLOBALS['meta']['forums_publics'] === 'abo');
	}

	return false;
}

/**
 * Autorisation de relancer une inscription
 *
 * Il faut être administrateur complet
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_inscription_relancer_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return $qui['statut'] === '0minirezo' and !$qui['restreint'];
}

/**
 * Autorisation à voir le phpinfo
 *
 * Il faut être webmestre
 *
 * @see autoriser()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 **/
function autoriser_phpinfos_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return autoriser('webmestre');
}
