# Changelog

## 4.2.6 - 2025-02-14

### Fixed

- rétablir le masquage des documents vus dans le bloc des documents

## 4.2.5 - 2024-10-08

### Added

- Composerisation de la version 4.2 de `spip/dist`

### Fixed

- !4885 Correction de coquille dans les flux RSS (sur `<thr:in-reply-to>`)

## 4.2.4 - 2024-05-29

### Security

- spip-team/securite#4853 Appliquer un filtre `attribut_url()` aux endroits pertinents

## 4.2.3 - 2024-05-07

### Changed

- Mise à jour des chaînes de langues
- Compatibilité max sur SPIP 4.*

## 4.2.2 - 2024-04-05

### Fixed

- Pétouilles doc & css

## 4.2.1 - 2023-12-18

### Fixed

- #4876 Corriger les tailles de typo sur les `label` et les `legend`

## 4.2.0 - 2023-01-27

### Added

-  spip/spip#5366 Afficher le `language` des blocs de code en haut à droite

### Fixed

- #4839 Compléter les CSS responsives pour les balises `video`, `canvas`, ou `svg`
- #4851 Afficher les documents joints aux brèves dans l'espace public
- spip/spip!5351 Distinguer les styles du .spip_code inline et block
- #4834 Réparer mieux la fonctionnalité d'embed de document unique (non image) sur les articles vides
- #4847 Permettre d'insérer deux formulaires de recherche dans la même page
- #4845 Éviter que les paginations débordent sur petit écran

### Removed

- spip/spip#5343 suppression du filtre |lignes_longues maintenant géré en css
- spip/spip#5402 Suppression des 3 formulaires inscription mot_de_passe et oubli (déplacés dans le core)
