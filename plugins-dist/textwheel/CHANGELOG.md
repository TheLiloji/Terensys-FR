# Changelog

## 3.3.3 - 2025-04-08

### Fixed

- #4485 Autoriser les fusions de cellule dès la 2ème ligne du tableau
- spip/spip#6037 `traiter_lien_explicite()` renvoie un tableau associatif en mode `'tout'`
- spip/spip#6037 `traiter_lien_explicite()` en l'absence de protocole explicite, mettre `https` et plus `http`
- spip/spip#6037 `traiter_lien_explicite()` utiliser `_PROTOCOLES_STD` pour la liste des protocoles à rechercher

## 3.3.2 - 2025-02-14

### Fixed

- Correction du Changelog…

## 3.3.1 - 2025-01-17

### Fixed

- !4886 Tableaux (colspan & rowspan): ignorer un ^ sur le première ligne, ce qui n'a pas de sens, et éviter une variable indéfinie

## 3.3.0 - 2024-11-27

### Deprecated

- spip/spip#5993 `replace_math()`, utiliser le plugin `mathjax` à la place
