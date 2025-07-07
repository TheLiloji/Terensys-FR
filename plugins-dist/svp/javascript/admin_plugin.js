(function($){

	// Les sélecteurs
	const selectors = {
		items: {
			all: '#liste_plugins ul > li',
			visible: '#liste_plugins ul > li:not([hidden])',
			upgradable: '#liste_plugins ul > li.up',
			checked: '#liste_plugins ul > li:has(input.select_plugin:checked)',
			forced: '#liste_plugins ul > li.compat-forcee',
			incompatible: '#liste_plugins ul > li.incompatible',
		},
		item: {
			checkbox: 'input.select_plugin'
		},
		filters: {
			text: 'input#filter_text',
			type: '#dropdown_filter_type button',
			reset: '#svp_filters_reset',
		}
	}

	// Filtrer la liste en fonction d'un terme de recherche
	const filtrer_par_terme = function() {
		const $input = $(selectors.filters.text);
		const searchText = $input.val().toUpperCase();
		const $items_filtres = $(selectors.items.all).filter(function(i, li){
			const listItemText = $(li).find('.resume').text().toUpperCase();
			return ~listItemText.indexOf(searchText);
		});
		$(selectors.items.all).hide().attr('hidden', true);
		$items_filtres.show().attr('hidden', false);
		// On ajoute la valeur en data sur le titre pour la garder en cas de rechargement ajax
		$('h1.grostitre').attr('data-filter-text',$input.val());
		// recalculer le nombre de plugin
		calculer_nbr_plugin();
	}

	const filtrer_par_type = function(type) {
		$(selectors.filters.text).val('');
		if (type == 'all') {
			$(selectors.items.all).show().attr('hidden', false);
		} else if (type == 'checked') {
			$(selectors.items.all).hide().attr('hidden', true);
			$(selectors.items.checked).show().attr('hidden', false);
		} else if (type == 'update') {
			$(selectors.items.all).hide().attr('hidden', true);
			$(selectors.items.upgradable).show().attr('hidden', false);
			$("select#action_globale>option#option_up").prop("selected",true);
		} else if (type == 'forced') {
			$(selectors.items.all).hide();
			$(selectors.items.forced).show().attr('hidden', false);
		} else if (type == 'incompatible') {
			$(selectors.items.all).hide().attr('hidden', true);
			$(selectors.items.incompatible).show().attr('hidden', false);
		}
		$('h1.grostitre').attr('data-filter-type', type);
		calculer_nbr_plugin();
	}

	const presenter_filtre = function(list, filter) {
		const btn = $(selectors.filters.type).filter("[value='" + filter + "']");
		if ($(list).length) {
			btn.removeClass('none');
		} else {
			btn.addClass('none');
		}
	}

	const presenter_filtre_checked = function() {
		presenter_filtre(selectors.items.checked, 'checked');
	}

	const presenter_filtres = function() {
		presenter_filtre_checked();
		presenter_filtre(selectors.items.upgradable, 'update');
		presenter_filtre(selectors.items.forced, 'forced');
		presenter_filtre(selectors.items.incompatible, 'incompatible');
	}

	// Relancer le filtrage s'il y a un terme de recherche enregistré
	const refiltrer = function() {
		const searchText = $('h1.grostitre').attr('data-filter-text');
		const searchType = $('h1.grostitre').attr('data-filter-type');
		if (searchText) {
			$(selectors.filters.text).val(searchText);
			filtrer_par_terme();
		}
		if (searchType) {
			filtrer_par_type(searchType);
		}
	}

	// Calculer et afficher le nombre de plugins visibles
	const calculer_nbr_plugin = function() {
		const nbr_plugin = $(selectors.items.visible).length;
		const title = jQuery('span#nbr_plugin');
		if (nbr_plugin > 1){
			let texte = svp.trads.info_nb_plugins;
			texte = texte.replace('@nb@', nbr_plugin);
			title.text(texte);
		} else if (nbr_plugin === 1){
			title.text(svp.trads.info_1_plugin);
		} else {
			title.text(svp.trads.info_0_plugin);
		}

		const nbr_hiddens = $(selectors.items.all).length - nbr_plugin;
		if (nbr_hiddens > 0) {
			$(selectors.filters.reset).removeClass('none');
		} else {
			$(selectors.filters.reset).addClass('none');
		}
	}

	// lorsqu'il y a de nombreux plugins et comme la remontee ajax est desactivee
	// on ne voit pas forcement les erreurs. A ce monent la, on remonte dessus.
	const remonter_sur_erreurs = function() {
		if ($('#formulaire_admin_plugin .reponse_formulaire_erreur').length) {
			$(document).scrollTop($('#formulaire_admin_plugin').offset().top - 20);
		}
	}

	// Filtrer en live
	$(selectors.filters.text).keyup(function() {
		filtrer_par_terme();
	});

	$(selectors.filters.type).click(function() {
		filtrer_par_type(this.value);
	});

	$(selectors.filters.reset).click(function() {
		filtrer_par_type(this.value);
	});

	// Cocher tous les items visibles
	$(".select_all").click(function() {
		$(selectors.items.visible).find(selectors.item.checkbox).prop("checked", true);
		presenter_filtre_checked();
		return false;
	});

	// Décocher tous les items visibles
	$(".select_none").click(function() {
		$(selectors.items.visible).find(selectors.item.checkbox).prop("checked", false);
		presenter_filtre_checked();
		return false;
	});

	// actualiser les boutons de filtres visibles
	$(selectors.items.all).find(selectors.item.checkbox).change(function() {
		presenter_filtre_checked();
	});

	// Lancements au chargement de la page
	calculer_nbr_plugin();
	presenter_filtres();
	// Lancements lors de rechargements ajax
	onAjaxLoad(remonter_sur_erreurs);
	onAjaxLoad(refiltrer);
})(jQuery);
