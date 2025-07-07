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
 * Gestion de syndication (RSS,...)
 *
 * @package SPIP\Sites\Syndication
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// ATTENTION
// Cette inclusion charge executer_une_syndication pour compatibilite,
// mais cette fonction ne doit plus etre invoquee directement:
// il faut passer par cron() pour avoir un verrou portable
// Voir un exemple dans action/editer/site
include_spip('genie/syndic');
include_spip('syndic/atomrss');
