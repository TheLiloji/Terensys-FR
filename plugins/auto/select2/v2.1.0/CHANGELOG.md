# Changelog

## Unreleased

### Added

- #9 Démo de saisie allow-clear + ajax

### Fixed

- #8 Ne pas afficher d’attributs data ajoutés automatiquement, sur la page de démo/test

## 2.0.0 - 2023-05-22

### Added 

- Transformation des `input.select2` en `select.select2` avec l’option tags.
- Si l’api retourne une clé `long_text` (en plus de `text` et `id`), elle est utilisée pour afficher les résultats (mais `text` reste utilisé une fois sélectionné).

### Changed

- Le HTML dans le label des données retournées (via api ajax par exemple) est conservé et non échappé
- Script JS réécrit essentiellement sans jQuery (sauf indispensable pour Select2)
- Nécessite SPIP 4.2+



## 1.2.0 – 2023-04-18

### Added

- #3 Une fonction `select2_generer_url_api()` (utilisée par `#URL_API`) fait le mappage vers `generer_url_api()` du core et sa signature.

### Fixed

- #2 Focus sur les Select à valeur unique

### Changed

- #3 Signature de `#URL_API` (de `#URL_API{script_et_path, args=''}` vers `#URL_API{script, path='', args=''}`)

### Deprecated

- `data-ajax-url` est déprécié (par Select 2). Utiliser `data-ajax--url`
- #3 Certains appels à `#URL_API` 
  - `#URL_API{toto/list}` ou `#URL_API{toto.api/list}` (utiliser `#URL_API{toto.api, list}` ou `#URL_API{toto, list}`)
  - `#URL_API{toto.api/list/choses,param=valeur}` (Utiliser `#URL_API{toto.api, list/choses, param=valeur}`)

### Removed

- #3 Fonction `generer_url_api` (existante dans le core de SPIP 4.1+ avec une autre signature.)