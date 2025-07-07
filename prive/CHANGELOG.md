# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.1 - 2025-04-08

### Fixed

- #80 erreur PHP fatale lors de la regénération d'un mot de passe, dans certains cas
- #79 faire fonctionner le bouton "Envoyer un lien pour choisir son mot de passe" lors de la création d'un nouveau compte
- #78 rétablir la pagination des rubriques d'un·e admin restreint·e
- !64 En RTL, justifié les listes d'objets à droite

## 1.0.0 - 2025-02-14

### Security

- #65 Nettoyer certaines entrées auteurs sur le formulaire d’édition d’auteur (évite de se retrouver dans des logs, si des logs verbeux sont activés)

### Fixed

- #64 Dans l’espace privé, éviter une scrollbar horizontale dans certaines configurations de systèmes d’exploitation.
- #59 aligner le contenu de la bande `en_lignes` avec celui du bandeau
- #13 meilleur affichage des listes d'objets sur petit écran
- spip#6022: Pas de marge basse sur les derniers éléments des réponses

## 1.0.0-beta2 - 2025-01-17

### Security

- spip-security/securite#4860 Bien tester les autorisations d'afficher le contenu des articles/rubriques dans les fragments chargés en ajax

### Added

- #18 utiliser le pipeline `compter_contributions_auteur` dans la boite d'info d'un auteur

### Fixed

- #18 transmettre au pipeline `compter_contributions_auteur` le nombre de contribution sur les articles

## 1.0.0-beta - 2024-12-03

### Added

- #35 Possibilité de personnaliser les chaînes de langue singulier et pluriel du titre de toutes les listes d'objets
- spip/spip#5560 Nouveaux layouts pour le privé (`#LAYOUT_PRIVE`) : `defaut`, `fluide`, `pleine-largeur`, `complements-droite`, `complements-bas`, `complements-bas-inverse`
- spip/spip#6005: Dépréciation de la constante `_DIR_RESTREINT_ABS`
- !20: les valeurs d'environnement explicitement vidées en ajax passent par `var_nullify` pour en être totalement expurgées
- !10: Permettre de trier les visiteurs 'nouveau' par date d'inscription-relance
- #3: Ajouter l'heure de publication à côté de la date
- !1: Utiliser des variables CSS dans l’espace privé pour éviter la compilation des fichiers CSS
- Composerisation

### Changed


- spip/spip#5560 La page de contrôle des tâches utilise le layout `complement-gauche`
- spip/medias#4958 Utilisation de `image_extensions_logos()` à la place de `$GLOBALS['formats_logos']`

### Fixed

- !36 Passer l'url à l'item de langue `pass_reset_url`
- !24 pour le formulaire générique d'institution d'objet, tester l'autorisation `publierdans` en utilisant le parent déclaré par l'API de parenté
- spip/spip#3408 Dans le formulaire générique d'institution d'un objet, utiliser le même jeu de test pour l'affichage du statut `publie` dans `charger` et pour la validation dans `verifier`
- #14 Pouvoir supprimer l'image de l'écran de connexion
- !13 Pouvoir modifier logo principal quand il y a un logo de survol
- spip/spip#3928 Les emails des auteurs sont masqués par défaut

### Removed

- #5505 #5988 Prise en compte de l’environnement de formulaire `_texte_trop_long` qui servait pour IE
