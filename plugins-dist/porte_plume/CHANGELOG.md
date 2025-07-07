# Changelog

## 3.1.9 - 2025-02-14

### Fixed

- #4833 Pouvoir scroller après avoir quitté le mode plein écran via la touche `<ESC>`

## 3.1.8 - 2024-12-03

### Fixed

- spip/spip#6022 Message de retour de formulaire en `div` plutôt qu'en `p`

## 3.1.7 - 2024-11-12

### Fixed

- Description composer.json

## 3.1.6 - 2024-05-29

### Security

- spip-team/securite#4853 Interdire par défaut l’évaluation de code PHP (qui ne devrait plus être nécessaire) lors de la prévisualisation du porte plume
- spip-team/securite#4853 Limiter par défaut l’usage de la prévisualisation dans l’espace public aux administrateurs

### Added

- spip-team/securite#4853 Autorisation `autoriser_modelesphp_previsualiser_dist` (retourne `false` par défaut)

### Changed

- spip-team/securite#4853 L’autorisation `autoriser_porteplume_previsualiser_dist` est limitée aux admins dans l’espace public.

## 3.1.5 - 2024-05-07

### Changed

- Mise à jour des chaînes de langues
- Compatibilité max sur SPIP 4.*

## 3.1.4 - 2023-09-01

### Fixed

- spip-team/security#4843 Améliorer la prévisu lors de l’utilisation de certains modèles
- Exclure les tests et fichiers de développement des livrables

## 3.1.3 - 2023-06-07

### Security

- spip-team/securite#4841 Limiter l’usage de `#ENV**` dans les formulaires.

### Changed

- Mise à jour des chaînes de langues depuis trad.spip.net

## 3.1.2 - 2023-02-27

### Changed

- Mise à jour des chaînes de langues depuis trad.spip.net


## 3.1.1 - 2023-02-23

### Added

- #4827 Ajout d’un événement `markItUpEditor.loaded` lorsque les barres d’édition sont chargées.


## 3.1.0 - 2023-01-27

### Added

- Fichier `README.md`

### Changed

- #4825 Utiliser backtick et triple backtick pour les blocs de code
- Compatible SPIP 4.2.0-dev
