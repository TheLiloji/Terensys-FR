# Changelog
## 5.12.0 - 2025-03-20

### Added

- #463 Pouvoir passer un objet/id_objet au sélecteur de document
- #459 Modificateur `:SUBSTR` pour les `afficher_si`

### Fixed

- Côté privé, augmenter la marge supérieur pour les intertitres sur les saisies radio

## 5.11.1 - 2025-02-12

### Fixed

- #468 vu de la saisie `selecteur_document` : prendre en compte l'option `multiple`

### Security

- spip-security/securite#4864 correction d'une faille RCE
## 5.11.0 - 2025-01-22

### Added

- Les vues des saisies recoivent dans l'env une clé `_env` contenant l'ensemble des paramètres d'environnement passés à  `#VOIR_SAISIES`
- spip-contrib-extensions/formidable#277 fonction `saisies_mapper_verifier()`

### Fixed

- #464 Aucune valeur size ou cols par défaut sur les `input` et `textarea`
- Saisie `statuts_objet`: en cas de sélection multiple, prendre des `checkbox` plutot qu'une saisie `selection_multiple`
- Eviter une fatale sur le pipeline `saisies_afficher_si_saisies` lorsqu'il n'y a pas encore de saisies dans l'aide-mémoire
- #465 Saisie `choix_grille` : (re)permettre de visualiser et corriger les enregistrement pour les saisies dont les clés sont numériques
- #413 échapper l'attribut `data-label_enregistrer` sur le bouton de validation
- Valeur par défaut de l'option `colonnes` sur la saisie `conteneur_1line` mis à `1ligne_max` (erreur dans !373)

## 5.10.0 - 2024-12-13

### Added

- #453 Nouvelle option `attributs_data` valable pour toutes les saisies
- #453 Fonction `saisies_afficher_normaliser_options_attributs()`
- Documentation de l'option pour dev `env`
- spip-contrib-extensions/formidable#90 Nouvelle option globale de formulaire `previsualisation_mode`. Valeurs possibles : `''` (par défaut),  `dessus`,  `etape`
- #455 Ajouter une ancre par défaut aux formulaires

### Fixed

- Cohérences des nom des fonctions pour les vérification post saisies en étape : `formulaire_<xxx>_verifier_etape_post_saisies()` (étape au singulier) et pipeline `formulaire_verifier_etape_post_saisie`
- Passer l'étape courante à la fonction `formulaire_<xxx>_verifier_etape_post_saisies()`
- #153 Constructeur de formulaire : réglage des gouttières pour la configuration des options globales
- Saisie `choix_grille` : si une des réponses a pour clé `0` ne pas la cocher par défaut
- Ancre associée à l'action du formulaire : prendre l'option globale `conteneur_id` et non pas `id`, qui n'existe pas
- Afficher correctement le nom de l'étape précédent dans le bouton retour si demandé
- Problème de marge sur les onglets verticaux dans l'espace privé
- spip-contrib-extensions/formidable#266 `saisies_saisie_possede_reponse()` doit renvoyer `false` si une saisie de type fichier ne possède pas de réponse

## 5.9.0 - 2024-11-22
### Added

- Options globales `ajax` (defaut `false`) ; `conteneur_class` (defaut `''`) ; `conteneur_id` (defaut `''`) ; `squelettes_bouton` (défaut `formulaires/inc-saisies-cvt-boutons`).
- Indiquer si un formulaire ne contient aucun champ (fonctionnalité rapatriée depuis formidable)

### Changed

- Les messages de retour globaux des formulaires "full PHP" sont désormais entourés d'une balise `<div>` et non plus `<p>`

### Fixed

- Charger les fonctions de balise privée pour la saisie `explication`
- #451 Ne pas mettre d'attribut `required` sur la saisie `choix_grille` si on est mode multiple
- #452 Ajustement CSS sur la saisie `inline` pour faciliter l'intégration
- Explication sur l'option « Séléction multiple » de la saisie « Grille de questions »

## 5.8.2 - 2024-10-24

### Fixed
- #449 Saisie `choix_grille` : réparation du clic dans firefox, lorsque en mode "cases à cocher" (bug introduit en 5.7.1)
- #448 Pouvoir masquer après chargement une saisie en erreur conditionnée par afficher_si
- #447 : Saisie `selecteur` : précalculer la valeur de `select` pour éviter une erreur de compilation

## 5.8.1 - 2024-10-10

### Fixed

- Chercher le premier onglet avec une erreur dans chaque ensemble d'onglet, pas de manière générale
- Autoriser le chargement d'un onglet qui n'a pas d'onglet voisin actif
- Ne pas indiquer qu'une saisie comportant une erreur est masquée par afficher_si si ce n'est pas le cas
- `saisies_supprimer_sans_reponse()` va chercher dans `_request()` si on ne lui passe aucun second argument (bug introduit en 5.8.0)

### Changed

- Fichiers de langue au nouveau format
### Removed

## 5.8.0 - 2024-10-04
- Compatibilité SPIP < 4.1
### Added

- #439 function `saisies_supprimer_depublie_sans_reponse()`
- #439 function `saisies_saisie_possede_reponse()`
- #439 `saisies_supprimer_sans_reponse()` peut chercher ailleurs que dans `$POST`

### Changed

- spip-contrib-extensions/champs_extras_core#25 La normalisation par défaut d'une saisie `date` insérée via le constructeur devient `date_ou_datetime`

### Fixed

- !458 Documenter le `label_class`
- Refaire fonctionner les `afficher_si` côté JS sur les champs dont le nom est sous la forme `section/sousection`
## 5.7-2 - 2024-09-26


### Fixed

- #440 Dans l'aide-mémoire, afficher uniquement la langue courante pour les libellés
- #440 Pour les vues en affichage compacte, tenir compte du caractère dépubliée ou pas d'une saisie
- #440 En cas d'affichage compacte de la vue, prioriser le `label_case` sur le `label`
- #440 Rétablir l'extraction des chaînes de langues pour la vue compacte des saisies

## 5.7.1 2024-09-20

### Fixed

- Si les saisies sont vu de manière compacte (`_SAISIES_AFFICHAGE_COMPACTE`), se rabattre sur le `label_case` en l'absence de `label`
- #439 Edition d'un formulaire en saisies : indiquer si une saisie est dépubliée
- #439 Vue des saisies : indiquer si une saisie est dépubliée
- #220 Ergonomie du click pour la saisie `choix_grille`
- Ne pas provoquer de fatal à l'affichage d'un formulaire sans saisies
- `afficher_si` : rétablir la recherche d'un sous champ d'une config

### Changed

- Sur la saisie `choix_grille` utilise l'élèment HTML `th` pour les entêtes

## 5.7.0 2024-09-17

### Added

- #435 Fonction `saisies_supprime_depublie()`
- #435 Fonction `saisies_supprimer_callback()`
- #425 Fonction `saisies_verifier_coherence_afficher_si_par_champ()`


### Changed

- #433 #434 Dans le cadre d'un formulaire créé par constructeur, appliquer récursivement la dépublication des saisies dès la récupération de celles-ci
- #433 `saisies_determiner_avance_rapide()` se base sur le nom du fieldset et non pas son contenu complet pour déterminer si une étape est à passer ou pas
- #110 Utiliser du cache statique pour les saisies ne dépendant pas de la base
- #110 Pour les saisies, utiliser `<INCLURE>` plutot que `#INCLURE`

### Fixed

- #435 Ne pas tester les saisies depubliées
- #435 Fonction `saisies_supprimer_sans_reponse()` était buguée si une seule sous-saisies d'un groupe de champ était sans réponse
- #434 En multiétape, ne pas afficher les étapes passées qui ont été ignorées pour cause d'`afficher_si`
- #433 `saisies_appliquer_depublie_recursivement()` n'ajoute plus récursivement une option `depublie` égale à `''`
- #433 Ne pas faire passer par les étapes masquées par `afficher_si`
- #432 Refaire fonctionner correctement le calcul dynamique du nombre d'étape si jamais des étapes sont conditionnées par `afficher_si`
- #428 Attributs `required` sur la saisie `choix_grille` lorsqu'elle est obligatoire
- #426 Pouvoir vérifier si le résultat d'un `choix_grille` fait partie des valeurs acceptables
- #427 Faire fonctionner les `afficher_si` sous la forme `@champ@ && || <expression impliquant champ>`
- #219 Pour vérifier qu'une saisie `choix_grille` obligatoire est bien remplie, il faut vérifier chaque ligne
- #425 Faire fonctionner les `afficher_si` en testant les valeurs dans une grille de choix

## 5.6.1 - 2024-08-15

### Fixed

- spip-contrib-extensions/formidable#255 Ne pas proposer la saisie `mot` si les mots sont désactivés
- spip-contrib-extensions/formidable#254 En mode constructeur de formulaire, forcer à configurer la saisies mots avant d'afficher quelque mot que ce soit

## 5.6.0 - 2024-08-14

### Added

- #229 pouvoir forcer une saisie mot en saisie unique même si le groupe de mot est configuré pour avoir plusieurs mots

## 5.5.1 - 2024-06-04

### Changed

- perf : ne placer un écouteur d'`afficher_si` que sur les champs qui conditionnent d'autres champ

### Fixed

- spip-contrib-extensions/formidable/#243 Permettre à `saisies_dont_avec_option()` de recevoir des valeurs nulles
- #224 `afficher_si` : lorsqu'un champ A conditionne un champ B qui le suit immédiatement, la tabulation depuis A focus sur B si B est affiché

## 5.5.0 - 2024-04-24

### Added

- #417 Fonction `saisies_dont_avec_option($saisie, $option)`

### Fixed

- #417 Faire fonctionner les `afficher_si` pour les formulaires multiétape utilisant AJAX et n'ayant pas d'`afficher_si` sur la première étape

## 5.4.1 - 2024-02-11

### Fixed

- #408 Préserver la valeur numérique des clé pour les saisies `radio`/`checkbox`/`selection` lorsque l'option `choix_alternatif` est activée
- #407 Saisies `radio` / `checkbox` / `selection` : Si choix alternatif + clé numérique, ne pas mettre la valeur par défaut comme valeur par défaut du choix alternatif
- #408 Saisies `radio` et `select` : prendre la valeur du choix alternatif, si utilisé, pour calculer les `afficher_si` dépendant de la saisie
- #409 Indiquer la possibilité de choix alternatif dans l'aide-mémoire

## 5.4.0 - 2024-02-28
### Added

- #24 Les saisies `radio` et `selection` disposent de l'option `choix_alternatif`
- Fonctions `saisies_name_suffixer()` et `saisies_name_supprimer_suffixe()`
- #174 Pouvoir depublier une saisie dans un constructeur de formulaire
	- La saisie n'est plus accessible pour les nouveaux formulaires
	- Mais elle reste accessible pour modifier les anciens enregistrements
	- La valeur reste stockée en base et visible
- #278 `saisies_normaliser_liste_choix()` (ancien `saisies_normaliser_disable_choix()`)
- #278 pour les saisies `selection`, `radio`, `checkbox`, possibilité de dépublier certaines choix, pour ne plus les proposer à l'avenir tout en gardant les valeurs et les correspondances en base

### Fixed

- #400 Lorsqu'on rend inactif par `afficher_si` le tout dernier onglet visible d'une série d'onglets conditionnés par `afficher_si`, masquer non seulement le lien de l'onglet, mais aussi son contenu
- Si on vérifie les valeurs acceptables pour une saisie `selection` avec l'option `multiple`, ne pas renvoyer `false` à tord

### Deprecated

- #278 `saisies_normaliser_disable_choix()` devient `saisies_normaliser_liste_choix()`
## 5.3.0 - 2024-01-27
### Added

- #381 Option `sans_vide` pour la fonction `saisies_lister_champs_par_section()`


### Changed

- formidable/#211 La saisie `champ` ne renvoie rien si aucun champ ne répond aux conditions, sauf si la saisie est obligatoire

### Fixed

- Faire systématiquement fonctionner l'affichage conditionnel des étapes côtés public
- #391 Accolade fermante en trop dans les boutons d'étape (si titrage)
- #389 Passer `_T_ou_typo()` sur le libellé du dernier bouton de validation en multiétapes
- #376 Permettre d'avoir des onglets horizontaux au sein d'un onglet vertical
- #379 Pas de `fieldset` pour une saisie `case` sans `label_case` ou sans `label`
- #381 Pour la saisie `champ`, ne pas afficher les sections vides
- #384 Appliquer les trigger `afficher_si_visible_pre` ; `afficher_si_visible_post` ; `afficher_si_masque_pre` ; `afficher_si_masque_post` lorsqu'on masque/démasque un onglet
- #385 Avant de passer d'un onglet à l'autre, vérifier, le cas échéant, que les champs de l'onglet ont bien une valeur valide (attribut `required` etc)
- #385 Lorsqu'une saisie a été masquée par `afficher_si`, remettre si besoin l'attribut `required` lors du réaffichage
- #385 Se rendre automatiquement sur le premier onglet avec un champ non validé lors de la validation HTML du formulaire
- #385 Pour les saisies masquées par `afficher_si`, désactiver temporairement les attributs entrainant des contraintes de validation (https://developer.mozilla.org/en-US/docs/Web/HTML/Constraint_validation)
pick 374614d fix: saisies `radio` et `select` : prendre la valeur du choix alternatif, si utilisé, pour calculer les `afficher_si` dépendant de la saisie
## 5.2.0 - 2023-12-30

### Added

- #373 Saisies `conteneur_inline` : 2 variantes des modes en ligne, pour prendre le plus de place (utilisé par défaut pour le constructeur)

### Fixed
- formidable/#192 Vue d'une saisie `conteneur_inline`: afficher les sous-saisies plutôt que rien

## 5.1.0 - 2023-12-26

### Added

- #371 Pipeline `saisies_aide_memoire_inserer_debut`
### Changed


- `afficher_si` : mettre le contenu de l'écouteur JS dans un fonctions à part `afficher_si_onchange()`
- `modeles/formulaire_aide_memoire` : renommage de l'option `pre_saisies` en `inserer_avant`, cette option prend désormais un texte arbitraire
### Fixed

- #366 Faire fonctionner correctement les tests `MATCH` et `!MATCH` des `afficher_si`
- dans l'aide-mémoire, une seule liste `<dl>` pour toutes les saisies
## 5.0.2 - 2023-11-21

### Fixed

- La saisie `champ` doit renvoyer un nom de saisie
- Les clés dans la fonction `saisies_lister_champs_par_section()` doivent être les noms de saisies, pas des clés numériques
## 5.0.1 - 2023-11-21

### Fixed

- #362 Retour des fieldsets pliables
## 5.0.0 - 2023-11-21


### Added

- #98 Une saisie `conteneur_inline` permettant de justapoxer horizontalement plusieurs saisies
- formidable/#179 Une saisie `champ` pour choisir un champ dans un formulaire (importée et debuguée depuis `formidable`)
- formidable/#179 Fonction `saisies_saisie_est_avec_sous_saisies()` qui dit si la saisie peut contenir des sous-saisies.
- formidable/#179 Fonction `saisies_saisie_est_labelisable()` qui dit si la saisie peut recevoir un label
- formidable/#179 Fonction `saisies_saisie_est_champ()` qui dit si la saisie correspond à un champ au sens HTML
- formidable/#179 Fonction `saisies_saisie_get_label()` pour trouver le label d'une saisie, ou équivalent
- formidable/#179 Fonction `saisie_lister_champs_par_section()` pour lister les saisies de type `champ` en les regroupant selon la structure logique du formulaire. Plusieurs options possibles. Utilisée par la saisie `champ`.
### Changed

- #300 filtre `picker_selected_par_objet` renommé en `saisies_picker_selected_par_objet`
- #300 fonction `afficher_si_parser_valeur_MATCH()` renommée `saisies_afficher_si_parser_valeur_MATCH()`

### Deprecated

- #300 balise `#DIV`, utiliser `<div>` directement
- #300 filtre `|saisie_balise_structure_formulaire`, utiliser `<div>` directement
- Fonction `saisie_verifier_gel_saisie()` depréciée, utiliser à la place `saisies_saisie_est_gelee()`

### Removed

- #172 Markup HTML: dans la saisie `fieldset`, suppression du `div.fieldset` autour du `fieldset` qui devient `fieldset.fieldset`
- #300 balise `#GLOBAL`
- #336 classes `.editer_odd` et `.editer_even` sur les conteneur des saisies, utiliser le sélecteur CSS `nt:child()` à la place
- #342 option `tagfield` pour la saisie `fieldset`, la légende est désormais systématiquement mise dans `<legend>`
- #341 pour la saisie `fieldset` options `icone` et `taille_icone`, insérer directement les icones dans l'option `label`

## Unreleased

### Fixed

- #358 corriger la vue/la modification d'une saisie `choix_grille` lorsque l'option `multiple` a été activé _a posteriori_
- #350 Pas d'attribut `size` par défaut pour les saisies `input` et `email`

## 4.11.0 - 2023-10-22

### Added

- #239 Pouvoir préremplir un champ avec une valeur stockée en session
- formidable/#176 Ajouter une classe `formulaire_multietapes` sur les formulaires multiétapes pour pouvoir les cibler en css
#### Fixed

- #343: Dans la vue d'un fieldset, encapsuler les champs pour retrouver une structure identique à la saisie dans un formulaire
- #331 Multiétapes : ajuster dynamiquement le libellé des boutons précédents/suivants en fonction des `afficher_si`
- #334 JS : calculer correctement le nombre d'étapes lorsqu'il n'y a pas d'`afficher_si` dans l'étape courante
- #324 `afficher_si` sur saisie `hidden` : ne pas rendre visible le conteneur
- #247 Constructeur : dans le menu déroulant pour déplacer une saisie, les saisies `explication` sont désormais décrites par leur titre ou à défaut le début de leur texte
- #246 Lorsqu'on duplique une saisie, ne pas insérer un label contenant juste ' (copie)' si la saisie initiale n'a pas de label (cas de la saisie `explication` notamment)
- #242 Constructeur de formulaire : lors du déplacement d'une saisie via le menu déroulant, se rendre ensuite au nouveau emplacement de la saisie.

### Changed

- #331 `saisies_determiner_deplacement_rapide()` peut recevoir les saisies déjà triées par étapes
- #331 typage des fonctions `saisies_determiner_deplacement_rapide()`, `saisies_determiner_recul_rapide()` et `saisies_determiner_deplacement_rapide()`

## 4.10.0 - 2023-09-30

### Added

- #170 : Pouvoir transformer les saisies explication en boite d'alerte (espace privé uniquement)

### Fixed

- #318 Saisie `case`: afficher la mention d'obligation à la fin du `label_case` si pas de `label`
- `saisies_aplatir_chaine()` : tenir compte de la syntaxe `/*`
- `saisies_lister_par_identifiant()` et `saisies_lister_par_nom()`: tenir compte de l'option `$avec_conteneur` pour les sous-saisies
- `saisies_lister_disponibles_par_categories_usort()`: toujours travailler sur des titres translittérés
- `saisies_normaliser_disable_choix()`: tenir compte du contenu vide entre virgules
- `saisies_lister_disponibles_par_categories()` : faire fonctionner l'option `inclure_obsoletes`

## 4.9.1 - 2023-08-14

### Fixed

- Ne pas provoquer de warning PHP sur `#SAISIE` lorsque pas d'erreur en jeu

## 4.9.0 - 2023-08-13

### Security

- #261 Appliquer `interdire_scripts()` sur les message d'erreur

### Added

- #252 Fonction d'API `saisies_encapsuler_noms()`
- #288 Fonction d'API `saisies_supprimer_sans_reponse()`
- formidable/#156 Pouvoir faire facilement des vérifications de formulaire APRÈS la vérification des saisies individuelles
### Fixed

- #284 Mise à jour de la saisie `selecteur_lang` en uniformisant avec les autres sélections (option `intro_vide` notamment)
- #262 Prendre en compte tout les statuts possible pour la saisie `statuts_auteur`
- #245 Constructeur de formulaire fonctionnel sur petit écran
- #45 Compatibilité des afficher_si avec `_SPAM_ENCRYPT_NAME` du plugin nospam

## 4.8.0 - 2023-05-29

### Added

- Option `minlength` pour la saisie `input`

### Fixed

- #279 faire fonctionner correctement les tests d'inégalité en afficher_si lorsque les deux nombres n'ont pas le même nombre de caractère.
- #274 Correction de cohérence JS/PHP pour un afficher_si court (`@champ@`) lorsque `@champ@` vaut `'0'` (string)

## 4.7.1 - 2023-03-24

### Fixed

- yaml/#7 compatibilité avec le plugin `YAML` v3.0.3
- #254 #259 #264 refaire fonctionner `_T_ou_typo()` en SPIP 4.2 et >
- #258 permettre d'envoyer une valeur `0` ou `'0'` (mais pas `empty`) dans une saisie multivaluée (type `checkbox`)

## 4.7.0 - 2023-01-07

### Added

- #123 Option `onglet_vertical` pour les `fieldsets` en `onglet` ; si un seul onglet est désigné comme vertical, tous les onglets le sont

### Changed

- #123 Dans un constructeur de formulaire, les onglets sont désormais verticaux
- Dans un constructeur de formulaire, les options globales du formulaire sont en `pleine_largeur`

### Fixed

- auth_email/#1 Lorsqu'on a une erreur dans un ou plusieurs onglets, se rendre au premier onglet avec erreurs
- #240 : Compatibilité entre les fieldset en onglets et `select2`

## 4.6.1 - 2022-12-01


### Added

- #236 Ajout de la saisie `type_mime` (utilisé pour constructeur de formulaire) depuis CVT-Upload

### Fixed

- #236 Correction d'un bug sur constructeur de formulaire lorsque CVT-Upload n'est pas disponible
- #236 Ne pas proposer la vérification `fichiers` pour des saisies non `fichiers`
- #237 `saisie_transformer_option()` ajoute l'option si jamais elle est inexistante (bug introduit en v4.5.0)
- #237 Constructeur de formulaire : toutes les saisies sont en pleine largeur (comportement qui avait été cassé en v4.5.0)
- #237 `saisie_mapper_option()` ajoute l'option si jamais elle est inexistante
- #238 Saisie `destinataires` : ne pas afficher de label si jamais tout est caché

## 4.6.0 - 2022-11-21

### Changed

- Dans le constructeur de formulaires, on ne gère plus directement les exceptions de vérification pour la saisie `fichiers` du plugin CVT-Upload. On n'utilise à la place un pipeline `saisies_verifier_lister_disponibles`.
### Fixed

- #233 Lorsqu'un constructeur de formulaire ajoute ses propres fieldsets racine à la configuration d'une saisie, les afficher en onglet à côté des autres fieldset, pas en dessous
- inserer_modeles/#12 Faire fonctionner la saisie `selecteur_documents` appelée au sein d'une modalbox
- #226 Debug des `afficher_si` au chargement des pages qui chargent également du contenu/javascript en Ajax
- cvt-upload/#12 Constructeur de formulaire : afficher les options de vérification pour la saisie fichier

### Removed

- La saisie `selecteur_documents` n'a plus besoin de `_modalbox_retour`

## 4.5.2 - 2022-09-23

### Fixed

- #225 : un input avec une valeur 0 (ou '0') affichait une chaine vide : saisies_utf8_restaurer_planes() ne retourne plus une chaine vide

## 4.5.1 - 2022-09-14

### Fixed

- Refait fonctionner la vérification de (certains) formulaires


## 4.5.0 - 2022-09-11

### Fixed

- formidable/#120: Pour la saisie explication, seul le bouton d'affichage/masquage affiche/masque, pas les boutons du constructeur de formulaire.
- #208 Générer une exception dans `saisies_lister_disponibles()` et `saisies_charger_infos()` si le plugin `YAML` n'est pas actif.
- formidable_participation/#9 + #207 Dans l'espace privé, éviter les problèmes de marge supérieur lorsqu'un fieldset suit un élèment masqué par `afficher_si`.
- #216 Eviter une rupture de compat brutal en v4 concernant l'emplacement de `nouveau_type_saisie` dans l'argument `$modifs` dans `saisie_modifier()`.

### Added

- #208 Tenir compte des options pour dev lorsqu'on nettoie l'environnement de `#GENERER_SAISIES`
- #222 Nouvelle fonction `saisie_mapper_option()` pour appliquer une fonction de rappel sur une (ou plusieurs) options données d'un ensemble de saisies
- #222 `saisie_transformer_option()` peut recevoir une liste d'options à modifier, plutôt qu'une option unique
## 4.4.1 - 2022-06-06


### Fixed

- #206 Pour la saisie radio, si les clés sont des entiers, ne pas considérer que l'absence de valeur par défaut vaut valeur par défaut == 0
- Correction de `selecteur_document.yaml` mal formaté

## 4.4.0 - 2022-05-31

### Added

- #200 Ajout de `saisies_verifier_coherence_afficher_si()` (utilisable par les constructeurs de formulaire)
- #171 Option de saisie `explication_apres`, pour insérer une explication après le champ, en plus ou à la place de l'explication avant
- #171 Dans l'aide de saisies, les messages d'attention accolés aux options sont affichés
- #185 Les emoji apparaissent sous la forme normale, et non pas la forme entité HTML, dans les saisies `textarea`  et `input`
- #198 Ajout de quatre `trigger` Javascript sur les `afficher_si` :
	* `afficher_si_visible_pre` (avant de rendre visible un champ);
	* `afficher_si_visible_post` (après avoir rendu visible);
	* `afficher_si_masque_pre` (avant de masquer un champ);
	* `afficher_si_masque_post` (après avoir masqué un champ);

### Changed

- formidable/#70 Vue des `fieldset` : utiliser aussi un markup `fieldset`/`legend`, pour affichage correct dans les emails
- #198 `afficher_si` : ne faire les actions de masquage/demasquage que si la saisie n'est pas déjà masquée/démasquée
- #199 Constructeur de saisie : tout ce qui est `afficher_si` dans un
onglet  `condition` à part
- #188 Constructeur de formulaire : positionner l'écran sur la saisie en cours d'édition

### Fixed

- #196 Debug du constructeur de formulaire, qui ne doit pas proposer les saisies obsolètes, même aprés une première vérification de saisies
- #198 Lors de l'édition d'un formulaire, les fieldsets ne sont pas mis en mode onglet
- #198 Les fieldset en onglets restent à leur emplacement ; il est possible de mettre du contenu hors onglet entre deux fieldset
- #194 La saisie `fieldset` n'hérite plus de l'`id` du formulaire
- #193 Afficher correctement les erreurs des champs dont le nom est déclarée selon la syntaxe SPIP `cle/souscle/nom`
- Le `describedby` d'un champ lorsque l'on a une erreur sur une saisie avec un `name` contenant des crochets est corrigé
- #198 Les `afficher_si` fonctionnent désormais sur les onglets
- #198 Attribut `aria-labelledby` correct sur les onglets
- #180 Sous firefox : pouvoir sélectionner la barre de scroll pour les onglets horizontaux + ne pas la superposer avec la bordure des onglets
- #166 Constructeur de formulaire : rendre visible les boutons d'action lorsqu'une saisie non `fieldset` suit un `fieldset`
