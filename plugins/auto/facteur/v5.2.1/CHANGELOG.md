# Changelog

## Unreleasedd

### Changed

- Compatibilité SPIP 4.1 mini

### Fixed

- #44 Réparer l'usage de la constante `_COULEUR_EMAILS_HTML` à l'aide de la balise `#CONST`

## 5.2.0 - 2024-08-14

### Changed

- #38 Mise à jour de la lib PHMailer en 6.9.1

### Fixed

- #25 Si on configure une IP qui ne se résoud pas comme SMTP Host, on declenche une erreur avec demande de confirmation qui permet de passer outre
- #26 Gestion des doublons entre l'email et la configuration générale de facteur

## 5.1.0 - 2024-07-05

### Fixed

- #40 Fatale en PHP 8.3
- #39 Changer l'heure d'essai des envois en cas d'échecs répétés

## 5.0.6 - 2024-03-15

### Fixed

- #27 Pour la génération des logs, chercher explicitement `To:` et pas `Reply-To:`
