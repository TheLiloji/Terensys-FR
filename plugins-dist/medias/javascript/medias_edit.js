(function(){
	function editbox_init(){
		jQuery(':where(.formulaire_editer_logo, #documents_joints, #portfolios) a.editbox:not(.nobox)')
		.attr("onclick","").addClass('nobox').click(function(){
			var casedoc = jQuery(this).parents('div.item').eq(0);
			jQuery(casedoc).animateLoading();
			jQuery.modalboxload(parametre_url(parametre_url(jQuery(this).attr('href'),'popin','oui'),'var_zajax','contenu'),{
				onClose: function (dialog) {jQuery(casedoc).ajaxReload();}
			});
			return false;
		});
	}
	if (window.jQuery){
		(function($){
			if(typeof onAjaxLoad == "function") {
				onAjaxLoad(editbox_init);
			}
			$(editbox_init);
		})(jQuery);
	}
})();