# Changelog

## 4.1.4 - 2024-11-12

### Fixed

- Description composer.json

## 4.1.3 - 2024-05-07

### Changed

- Mise à jour des chaînes de langues
- Compatibilité max sur SPIP 4.*

## 4.1.2 - 2024-04-05

### Fixed

- #4829 Correction du filtrage par objet de la page plan
- spip/spip#3637 Lever l'ambiguité sur les balises simples des `url()`

## 4.1.1 - 2023-09-01

### Fixed

- Exclure les tests et fichiers de développement des livrables

## 4.1.0 - 2023-01-27

### Added

- Fichier `README.md`

### Changed

- #4831 #4828 Utiliser l'API parents/enfants pour listes les objets enfants de chaque rubrique, mais il faut quand même que les objets concernés proposent un squelette `prive/squelettes/inclure/plan-{table}.html` pour qu'ils apparaissent dans le plan
- #4830 Feuille de style plus flexible et icones SVG
- Compatible SPIP 4.2.0-dev
