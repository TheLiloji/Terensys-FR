# Changelog

## 3.3.1 - 2025-02-23
### Added

- #37 L'option `image_web` de la vérification `fichiers` s'appuie sur les types d'image web déclarés par SPIP

### Fixed

- #38 Pour avoir la documentation, nécessité d'avoir le plugin `saisies` activé
- Utiliser `_IMG_MAX_SIZE` pour toutes les images web vectoriel
- #36 Corriger le mode complet de la vérification URL (mauvaise regex)
- Plus de catégorie dans le paquet

## 3.3.0 - 2024-10-14
### Added

- La normalisation `date_ou_datetime` s'appuie en priorité sur la définition du type sql associée à la saisie en entrée, ce qui permet de faciliter la config en mode "champs extras interface"

### Removed

- Compatibilité SPIP < 4.1
## 3.2.0 - 2024-10-01

### Added

- spip-contrib-extensions/champs_extras_core#25 Avoir une normalisation `date_ou_datetime` selon ce qui est reçue en entrée
## 3.1.2 - 2024-06-12


### Fixed

- #34 Éviter notice en vérifiant un telephone quand pays n'est pas défini dans les options

## 3.1.1 - 2023-06-04

### Fixed

- #32 Trimmer la valeur reçue quand on vérifie la disponibilité d'un email

## 3.1.0 - 2023-04-02

### Added

- #16 Pouvoir vérifier que deux champs ont des valeurs (ou éventuellement des types) différentes
- Compatiblité SPIP 4.2

