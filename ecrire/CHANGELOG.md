# Changelog

Changelog de SPIP 4.4

## 4.4.4 - 2025-06-10

### Fixed

- !74 Les urls d’actions de formulaires finissant par `/` ajoutaient inutilement une ancre

## 4.4.3 - 2025-04-08

### Fixed

- spip/prive#82 Définir l’autorisation de voir le formulaire de préférence des menus…
- #58 Ignorer les commentaires dans la création de champs SQL
- #6065 réparer la pagination ajax de la liste des admins dans la boite info d'une rubrique
- spip/tw#4883 éviter une fatale Undefined constant `_PROTOCOLES_STD` en la définissant dans `spip_initialisation_core()`

## 4.4.2 - 2025-02-18

### Fixed

- #39 Fix warning PHP (un report manquait pour #39)

## 4.4.1 - 2025-02-18

### Fixed

- #41 Utiliser `Spip\Afficher\Minipage\Admin` au lieu de `install_debut_html()` et `install_fin_html()` qui lèvent un deprecated
- #41 L’option `onload` de Minipage n’était pas appliquée…
- #39 Notifier uniquement les mises à jour de `patch` de SPIP en entête de page de l’espace privé
- #39 éviter de stocker une info erronée dans la meta `derniere_maj_notifiee`

## 4.4.0 - 2025-02-14

### Fixed

- charger l'autoloader dans le fichier prive.php
- spip/medias#5020 Éviter un warning PHP si le fichier du logo n'est pas présent
- spip/medias#5008 spip/medias!5034 Suivre medias sur `inc_vignette_dist` qui attend un paramètre medias

## 4.4.0-beta4 - 2025-01-29

### Fixed

- #34 Affichage des chaînes de langue en squelettes sur certains cas.
- #34 !39 Revert: transformation des idiomes en balise (sera dans SPIP 5.0 uniquement)
- !37 Si un plugin indique une icone `''` dans son `xml`, ne pas planter
- #35 Pouvoir se déconnecter
- #35 Le second paramètre de `Minipage` est un tableau

## 4.4.0-beta3 - 2025-01-17

## 4.4.0-beta2 - 2025-01-17

### Security

- spip-security/securite#4862 Sécuriser le contenu du message d'erreur affiché par l'API transmettre

### Changed

- #33 Version max PHP 8.4

### Fixed

- spip/medias#5011 utiliser pour `IMAGETYPE_SVG` une valeur qui ne risque pas une collision avec un futur ajout de format image (19 a été pris par `IMAGETYPE_AVIF` entre temps)
- #24 Correction d’une erreur fatale sur l’appel à `phraser_champs_interieurs()`
- Bonne version le dans paquet.xml

### Deprecated

- #26 Inclusion de fichier PHP via `<INCLURE(fichier.php)>` ou `<INCLURE{fond=fichier.php}>`
- #26 Balise fermante `</INCLURE>`


## 4.4.0-beta - 2024-12-03

### Added

- spip/spip#6003 Ne pas envoyer de mot de passe en clair, mais plutôt des liens pour définir son mot de passe
- spip/spip#6049 `copie_locale()` passe une clé `action` au pipeline `post_edition`
- spip/prive#35 Chaînes de langue supplémentaire pour les listes d'articles
- spip/spip#5560 Balise `#LAYOUT_PRIVE`
- spip/spip!5633 Balise `#TRAD{module:cle, #ARRAY{param, val, ..}, #ARRAY{option, val..}}`
- spip/spip#5933 Les actions `ajouter_lien` et `supprimer_lien` peuvent gérer un qualificatif
- spip/spip#5766 Pipeline `ajouter_menus_args`, en complément au pipeline `ajouter_menus`, qui transmet les arguments de `definir_barre_boutons()`
- spip/spip!6051 Purger les variables de `var_nullify` du contexte dans `traiter_appels_inclusions_ajax`
- spip/spip!6044 balise `#PARAM` pour récupérer les paramètres du container de services (Cf [UPGRADE_5.0.md](UPGRADE_5.0.md#Constantes_PHP))
- spip/spip!6034 Le filtre `|affdate` accepte un timestamp en entrée
- spip/medias#4958 Fonction `_image_extensions_logos()` et pipeline `image_extensions_logos`

### Fixed

- !8 Utiliser une variable pour l'url de l'item de langue `pass_reset_url`
- spip/spip!6100 Utiliser `fpassthru()` pour livrer directement les fichiers et eviter un memory limit plutôt que `readfile()` qui passe par un chargement en memoire du fichier
- spip/spip!5633 Possibilité de calculer dynamiquement les paramètres des chaînes de langue
- spip/spip!5633 Possibilité de calculer dynamiquement les paramètre des filtres des chaînes de langue
- spip/spip#5722 Requêter les fichiers distants avec `STREAM_CRYPTO_METHOD_TLS_CLIENT`
- spip/spip#5919 Remplacer les balises `tt` obsolètes par `code`
- spip/spip#3928 Les emails des auteurs sont masqués par défaut

### Deprecated

- spip/spip#5560 Balise `#LARGEUR_ECRAN` pour les squelettes du privé à remplacer par `#LAYOUT_PRIVE`
- spip/spip!5633 Classe interne `Idiome` depréciée, utiliser plutot `phraser_preparer_idiomes()` et la balise interne `#TRAD_IDIOME`
- spip/spip#2536 À partir de SPIP 5, l'appel des chaînes de langues en squelette sera sensible à la casse de la déclaration, il n'y aura plus de conversion automatique en minuscule
- spip/spip!5633 Fonction interne `phraser_boucle_placeholder()` à remplacer par `phraser_placeholder_memoriser_ou_reinjecter()`
- spip/spip!5633 Fonction interne `public_generer_boucle_placeholder()` à remplacer par `public_placeholder_generer()`
- spip/spip#6014 Les fichiers de langue peuplant une `$GLOBALS` sont dépréciés ; renvoyer directement un tableau
- spip/spip#4903 Constante obsolète `_DIR_IMG_PACK`
- spip/spip#5993 Globales `$traiter_math`, `$tex_server`, fonctions `produire_image_math()`, `traiter_math()`, utiliser le plugin `mathjax` à la place
- spip/spip#5992 Modifier la globale `$formats_logos` est déprécié : utiliser le pipeline `image_extensions_logos`
- spip/spip#5992 Appeler la globale `$formats_logos` est déprécié, utiliser la fonction `_images_extensions_logos()`

### Removed

- spip/spip#5505 spip/spip#5988 Fonctions `verif_butineur()`, `editer_texte_recolle()` et environnement `_texte_trop_long` des formulaires (Inutilisé — servait pour IE !)


