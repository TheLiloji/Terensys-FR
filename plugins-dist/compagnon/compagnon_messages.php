<?php

/**
 * Gestion de l'action compagnon
 *
 * @package SPIP\Compagnon\Pipelines
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Définition des messages de compagnon par défaut en fonction
 *
 * Retourne une liste de messages d'aides en fonction du pipeline
 * demandé
 *
 * @pipeline compagnon_messages
 *
 * @param array $flux
 *     Données du pipeline
 * @return array $flux
 *     Données du pipeline
 **/
function compagnon_compagnon_messages($flux) {

	$exec = $flux['args']['exec'];
	$pipeline = $flux['args']['pipeline'];
	$vus = $flux['args']['deja_vus'];
	$aides = &$flux['data'];

	include_spip('inc/config');
	$nom = lire_config('nom_site');
	$nom = typo($nom);

	switch ($pipeline) {
		case 'affiche_milieu':
			switch ($exec) {
				case 'accueil':
					$aides[] = [
						'id' => 'accueil',
						'inclure' => 'compagnon/accueil',
						'statuts' => ['1comite', '0minirezo', 'webmestre']
					];
					$aides[] = [
						'id' => 'accueil_configurer',
						'titre' => _T('compagnon:c_accueil_configurer_site'),
						'texte' => _T('compagnon:c_accueil_configurer_site_texte', ['nom' => $nom]),
						'statuts' => ['webmestre'],
						'target' => '#bando1_menu_configuration',
					];
					$aides[] = [
						'id' => 'accueil_publication',
						'titre' => _T('compagnon:c_accueil_publication'),
						'texte' => _T('compagnon:c_accueil_publication_texte'),
						'statuts' => ['webmestre'],
						'target' => '#bando1_menu_edition',
					];
					break;

				case 'rubriques':
					// eviter si possible une requete sql.
					if (!isset($vus['rubriques']) and !sql_countsel('spip_rubriques')) {
						$aides[] = [
							'id' => 'rubriques',
							'titre' => _T('compagnon:c_rubriques_creer'),
							'texte' => _T('compagnon:c_rubriques_creer_texte'),
							'statuts' => ['webmestre'],
							'target' => '#contenu .icone:first-of-type',
						];
					}
					break;


				case 'rubrique':
					// eviter si possible une requete sql.
					if (!isset($vus['rubrique'])) {
						$statut = sql_getfetsel('statut', 'spip_rubriques', 'id_rubrique=' . $flux['args']['id_rubrique']);
						if ($statut != 'publie') {
							$aides[] = [
								'id' => 'rubrique',
								'titre' => _T('compagnon:c_rubrique_publier'),
								'texte' => _T('compagnon:c_rubrique_publier_texte'),
								'statuts' => ['webmestre'],
								'target' => '#contenu .icone.article-new-24'
							];
						}
					}
					break;

				case 'articles':
					// eviter si possible une requete sql.
					if (!isset($vus['articles']) and !sql_countsel('spip_rubriques')) {
						$aides[] = [
							'id' => 'articles',
							'titre' => _T('compagnon:c_articles_creer'),
							'texte' => _T('compagnon:c_articles_creer_texte'),
							'statuts' => ['webmestre']
						];
					}
					break;

				case 'sites':
					// eviter si possible une requete sql.
					if (!isset($vus['sites']) and !sql_countsel('spip_rubriques')) {
						$aides[] = [
							'id' => 'sites',
							'titre' => _T('compagnon:c_sites_creer'),
							'texte' => _T('compagnon:c_sites_creer_texte'),
							'statuts' => ['webmestre']
						];
					}
					break;

				case 'article':
					$aides[] = [
						'id' => 'article_redaction',
						'inclure' => 'compagnon/article_redaction',
						'statuts' => ['0minirezo', 'webmestre']
					];
					$aides[] = [
						'id' => 'article_redaction_redacteur',
						'inclure' => 'compagnon/article_redaction_redacteur',
						'statuts' => ['1comite']
					];
					break;
			}
			break;

		case 'affiche_gauche':
			switch ($exec) {
				case 'job_queue':
					$aides[] = [
						'id' => 'job_queue',
						'titre' => _T('compagnon:c_job'),
						'texte' => _T('compagnon:c_job_texte'),
						'statuts' => ['webmestre']
					];
					break;
			}
			break;
	}

	return $flux;
}
