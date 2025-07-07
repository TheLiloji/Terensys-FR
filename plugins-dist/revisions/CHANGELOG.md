# Changelog

## 3.1.9 - 2024-12-03

### Fixed

- spip/spip#6022 Message de retour de formulaire en `div` plutôt qu'en `p`

## 3.1.8 - 2024-11-12

### Fixed

- Description composer.json

## 3.1.7 - 2024-10-08

### Fixed

- spip/spip#5460 Utiliser les propriétés logiques dans la CSS de l'espace privé

## 3.1.6 - 2024-05-07

### Changed

- Mise à jour des chaînes de langues
- Compatibilité max sur SPIP 4.*

### Fixed

- HTML5: Retrait des `CDATA` inutiles, retrait `text/javascript`
- #4860 Rétablir la possibilité de désactiver les révisions sur tous les objets

## 3.1.5 - 2024-04-05

### Fixed

- #4858 Limiter le nombre de lignes dans la requete pour l'affichage des révisions sur la home (perf)

## 3.1.4 - 2024-02-08

### Fixed

- spip/spip#3637 Lever l'ambiguité sur les balises simples des `url()`
- #4857 Éviter une erreur SQL sur l’optimisation des révisions sur des objets éditoriaux disparus

## 3.1.3 - 2023-09-01

### Fixed

- Exclure les tests et fichiers de développement des livrables

## 3.1.2 - 2023-06-07

### Fixed

- #4849 Bouton retour sur les articles avec un titre vide
- #4845 Bouton retour sur les documents

## 3.1.1 - 2023-05-25

### Security

- spip-team/securite#4841 Limiter l’usage de `#ENV**` dans les formulaires.

### Fixed

- #4850 Bien charger les autorisations `voirrevisions` et `revisions_menu`

## 3.1.0 - 2023-01-27

### Added

- Fichier `README.md`

### Changed

- Compatible SPIP 4.2.0-dev

### Fixed

- #4843 Correction du RSS du suivi des révisions
- spip/spip#5274 Homogénéiser les labels des listes
