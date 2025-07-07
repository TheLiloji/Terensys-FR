# Changelog

## 4.3.2 - 2025-02-14

### Fixed

- #4728 modifier le test sur la constante _CONVERT_COMMAND en vue de la définir si besoin, permettant de rotationner les images avec convert (#4728)

## 4.3.1 - 2025-01-17

### Fixed

- Être compatible avec SPIP 4.4 beta…

## 4.3.0 - 2025-11-27

### Added

- !4736 Commande cli pour purger les images cache trop anciennes (`cache-gd2` et `cache-vignettes`)

### Fixed

- #4722 check existance de exif_read_data()

### Deprecated

- #4723 Filtre `|image_typo`, installer le plugin `Images typographiques`
- #4723 Function `rtl_mb_ord()`, installer le plugin `Images typographiques`
- #4723 Function `rtl_reverse()`, installer le plugin `Images typographiques`
- #4723 Function `rtl_visuel()`, installer le plugin `Images typographiques`
- #4723 Function `printWordWrapped()`, installer le plugin `Images typographiques`
- #4723 Function `produire_image_typo()`, installer le plugin `Images typographiques`

## 4.2.1 - 2024-07-26

### Fixed

- spip/spip#5974 Éviter des warnings sur `image_oriente_selon_exif()` en absence d’image

## 4.2.0 - 2024-05-29

### Added

- spip/spip#5925 Filtre `image_oriente_selon_exif()` pour réorienter automatiquement une image selon son exif

### Changed

- spip/spip#5925 Les filtres d’images tel que `image_recadre` réorientent l’image selon l’exif d’orientation

### Fixed

- !4724 Optimisation du filtre `image_aplatir()`
- !4723 Optimisation du filtre `image_renforcement()`
- !4722 Optimisation du filtre `image_flou()`
- !4721 Optimisation du filtre `image_sepia()`
- !4718 Optimisation des filtres `image_flip_vertical()` & `image_flip_horizontal()`
- !4720 Optimisation du filtre `image_nb()`
- !4719 Optimisation du filtre `image_gamma()`
- #4716 Optimisation du filtre `image_rotation()`
- #4716 Correction du paramètre `crop` de `image_rotation()`
