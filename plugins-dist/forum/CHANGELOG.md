# Changelog

## 3.1.12 - 2025-04-08

### Fixed

- #4794 ne pas insérer `actiongroup.js` si ` #AUTORISER{modererlot,forum}` n'est pas satisfait

## 3.1.11 - 2024-12-03

### Fixed

- spip/spip#6022 Message de retour de formulaire en `div` plutôt qu'en `p`

## 3.1.10 - 2024-11-12

### Added

- Fichier composer.json

## 3.1.9 - 2024-10-08

### Fixed

- spip/spip#5460 Utiliser les propriétés logiques dans la CSS de l'espace privé
- !4794 Diverses corrections sur les RSS des forums

### Removed

- !4795 4 fichiers de langues obsolètes et non traduits.

## 3.1.8 - 2024-07-26

### Changed

- Mise à jour des chaînes de langues depuis trad.spip.net

## 3.1.7 - 2024-06-07

### Fixed

- Coquille dans le modèle forum lors de l’introduction d’un `attribut_url`

## 3.1.6 - 2024-05-29

### Security

- spip-team/securite#4853 Appliquer un filtre `attribut_url()` aux endroits pertinents

## 3.1.5 - 2024-05-07

### Changed

- Mise à jour des chaînes de langues
- Compatibilité max sur SPIP 4.*

### Fixed

- #4774 Ne pas appliquer de couleur de fond au bloc d’info d’abonnement dans le formulaire forum
- #3145 Écriture plus inclusive de certaines formulations
- HTML5: Retrait des `CDATA` inutiles, retrait `text/javascript`

## 3.1.4 - 2024-02-08

### Fixed

- #4788 Utiliser le filtre `icone_verticale` et non `icone` qui est déprécié
- #4770 Sur la page de contrôle des forums, ne pas perdre le filtrage par auteur en changeant d’onglet
- #4786 Éviter une erreur SQL sur l’optimisation des forums sur des objets éditoriaux disparus

## 3.1.3 - 2024-01-11

### Fixed

- #4759 Ne pas appliquer l'effet sticky du bloc d'actions en masse de la page controler_forum sur petit écran

## 3.1.2 - 2023-09-01

### Fixed

- spip/spip#3637 Lever l'ambiguité sur les balises simples des `url()`
- Exclure les tests et fichiers de développement des livrables

## 3.1.1 - 2023-06-07

### Security

- spip-team/securite#4841 Limiter l’usage de `#ENV**` dans les formulaires.

## 3.1.0 - 2023-01-27

### Added

- Fichier `CHANGELOG.md`

### Changed

- Compatible SPIP 4.2.0-dev

### Fixed

- #4765 Ajout des icones SVG manquantes sur la page de gestion des forums internes
- spip/spip#5274 Homogénéiser les labels des listes
- #4762 Limiter le nombre d'items à 100 dans les RSS produits

### Removed

- #5343 suppression du filtre |lignes_longues maintenant géré en css

