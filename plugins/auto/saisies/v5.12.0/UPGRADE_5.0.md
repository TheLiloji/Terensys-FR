# UPGRADE DE `saisies` 4.x à 5.0

Ce fichier liste les points de surveillance lors du passage de la version 4 à la version 5 du plugin `saisies`.


### Changement dans le balisage HTML

- Dans la saisie `fieldset`, suppression du `div.fieldset` autour du `fieldset` qui devient `fieldset.fieldset`
- Suppression des classes `.editer_odd` et `.editer_even` sur les conteneur des saisies, utiliser le sélecteur CSS `nt:child()` à la place

### Fonctions et filtres renommés

Ces fonctions et filtres sont à usage purement interne, toutetefois si vous les utilisiez malgrès tout, il faudra adapter votre code.

- filtre `picker_selected_par_objet` renommé en `saisies_picker_selected_par_objet`
- fonction `afficher_si_parser_valeur_MATCH()` renommée `saisies_afficher_si_parser_valeur_MATCH()`

### Fonctionnalités dépréciées

Ces fonctionnalités n'ont plus lieu d'être et seront supprimées en v6. Anticipez vos changement de code.

- balise `#DIV`, utiliser `<div>` directement
- filtre `|saisie_balise_structure_formulaire`, utiliser `<div>` directement
- Dans la fonction `saisies_modifier()`, `nouveau_type_saisie` doit être appelé à la racine de `$modifs`. L\'appel dans `$options` est deprécié et sera supprimé en v6
- Fonction `saisie_verifier_gel_saisie()` depréciée, utiliser à la place `saisies_saisie_est_gelee()`
### Fonctionnalités supprimées

Ces fonctionnalités correspondent à un état de l'art qui n'existe plus et sont supprimées directement.

- balise `#GLOBAL`
- option `tagfield` pour la saisie `fieldset`, la légende est désormais systématiquement mise dans `<legend>`
- pour la saisie `fieldset` options `icone` et `taille_icone`, insérer directement les icones dans l'option `label`
