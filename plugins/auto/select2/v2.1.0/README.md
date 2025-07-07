# Select2 Plugin pour SPIP

Ce plugin pour SPIP intègre la bibliothèque javascript [Select2](https://select2.org/) qui améliore l’usage des sélecteurs (balises `<select>`) natifs des navigateurs en facilitant entre autres la recherche d’un terme dans la liste.


## Fonctionnement

Select2 est actif dans l’espace privé 

- pour tous les `select` ayant la classe CSS `select2`
- pour tous les `input` ayant la classe CSS `select2`

La configuration (Menu : Squelettes > Select2) permet de compléter les sélecteurs utilisés pour les `select`.

Il est possible d’activer Select2 sur l’espace public depuis cette configuration.

**Important:** L’identifiant (l’attribut `id`) de la balise `select` ou `input` doit être unique sur la page ; Select2 ne peut s’activer qu’une fois par identifiant (le dernier appelé).

## Compléter la configuration par défaut

Select2 dispose de 2 manières d’adapter sa configuration :
 
### En appelant directement une méthode javascript

- soit la fonction jQuery `.select2()` de la librairie d’origine, et en lui transmettant des options
- soit la fonction jQuery `.spip_select2()` qui enrichit la précédente
- soit `SpipSelect2.on_select(select, options)`, dont la fonction jQuery `.spip_select2` est un alias
- soit `SpipSelect2.on_input(input, options)`

Par exemple
```javascript
jQuery("select").select2({"placeholder": "La forme ?"});
jQuery("select").spip_select2({"placeholder": "La forme ?"});
SpipSelect2.on_select(document.querySelector("select"), {"placeholder": "Que dire ?"});
SpipSelect2.on_input(document.querySelector("input.text"));
```

### Via des attributs data sur la balise `select` ou `input`

Ces attributs prennent alors le pas sur les options transmises à l’initialisation.

Par exemple 
```html
<select class="select2" id="select1" name="demo" data-placeholder="Ça va ?">
    <option></option>
    <option value="oui">Yep!</option>
    <option value="non">Bof.</option>
</select>
```

La [documentation de Select2](https://select2.org/configuration) sera donc à étudier :)


## Addition du plugin SPIP

Des options supplémentaires non présentes dans le javascript par défaut sont ajoutées au plugin SPIP.
Ces options sont disponibles :

- si vous utilisez les classes CSS prévues (`.select2` ou tout autre configurée en plus par vos soins)
- si vous appelez directement une des fonctions
  - `SpipSelect2.on_select()`
  - `SpipSelect2.on_input()`
  - jQuery `.spip_select2()`

**Mais pas** si vous appelez directement la méthode `.select2()` native.
 
### sortAlpha

- `{sortAlpha: true}` 
- ou `data-sort-alpha="true"`. 

Si cet attribut est présent, les options de sélection seront triées par ordre alphabétique. 
Cela est effectué en interne, en utilisant une fonction `sorter` ; 
En simplifiant cela pourrait ressembler à :

```javascript
    jQuery('#select3a').spip_select2({sortAlpha: true});
    // est à peu près équivalent à :
    jQuery('#select3b').select2({
        /*'sorter': data => data.sort((a, b) => a.text.localeCompare(b.text))*/
        sorter: function(data) {
            return data.sort(function(a, b) {
                return a.text.localeCompare(b.text);
            })
        }
    });
```

### highlightSearchTerm

- `{highlightSearchTerm: true}` 
- ou `data-highlight-search-term="true"`. 

Cette option met en évidence le terme de recherche dans la liste des résultats, en appliquant
un span `.select2-rendered__match` sur le terme recherché, qui est par défaut décoré avec un souligné.

### Action sur les input.select2

Le plugin permet de traiter des input (text) en les gérant via Select2, moyennant un mécanisme javascript qui recrée un select à côté de l’input, masque l’input, et mappe le résultat de la saisie
dans select2 dans la valeur de l’input qui sera posté. Ça gère aussi des saisies multiples
dès lors qu’un `data-separateur` tel que `data-separateur=","` est indiqué.

Cela s’applique 

- automatiquement sur les `input.select2` 
- ou manuellement via la méthode `SpipSelect2.on_input(input, options = {})`


## Addition à la librairie javascript Select2

Le plugin propose une version altérée de Select2 `javascript/select2.fork.full.js` permettant des options supplémentaires impossibles à réaliser autrement.

### onEnterSubmit

- `{onEnterSubmit: true}` 
- ou `data-on-enter-submit="true"`. 

Appuyer la touche Entrée en ayant le focus sur le sélecteur fermé soumet le formulaire (comportement généralement présent sur les navigateurs). Par défaut, Select2 quand à lui ouvre le sélecteur.


## Autocomplete Ajax

On peut indiquer une URL de recherche à Select2 afin qu’il obtienne la liste des choix en fonction de la recherche tapée par l’utilisateur. Un exemple est proposé dans le plugin, visible sur la page de test.

Par exemple : 
```html
<select class="select2" id="chiens" name="chiens[]" 
    data-placeholder="Quelle race de chien ?"
    data-ajax--url="[(#URL_API{select2_autocomplete, demo/dogs}|attribut_html)]"
    data-minimum-input-length="1"
>
</select>
```

L’API doit retourner un format spécifique (cf action/select2_autocomplete.php) pour fonctionner par défaut, mais du javascript supplémentaire peut servir pour adapter des apis déjà existantes. Cf. documentation [Ajax de Select2](https://select2.org/data-sources/ajax).

### onAjaxLoad ...

**Attention** avec l’autocomplete ; Si vous appelez directement `.select2()` (ce n’est pas le cas `.spip_select2()` utilisé par le plugin cependant), chaque requête ajax pour obtenir la liste des éléments recherchés va déclencher la fonction SPIP `onAjaxLoad`. Votre code ne doit pas redémarrer l’initialisation des `select` déjà gérés par `Select2` dans ce cas (sinon vous verrez le sélecteur se refermer aussitôt !).
