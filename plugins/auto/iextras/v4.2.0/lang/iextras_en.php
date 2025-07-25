<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/iextras?lang_cible=en
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'action_associer' => 'manage this field',
	'action_associer_title' => 'Manage the display of this extra field',
	'action_desassocier' => 'disassociate',
	'action_desassocier_title' => 'Don’t manage the display of this extra field',
	'action_descendre' => 'down',
	'action_descendre_title' => 'Move the field down one position lower',
	'action_modifier' => 'edit',
	'action_modifier_title' => 'Modify the parameters of the extra field',
	'action_monter' => 'up',
	'action_monter_title' => 'Move the field up one position higher',
	'action_supprimer' => 'delete',
	'action_supprimer_title' => 'Totally delete the field from the database',
	'afficher_noms' => 'Display field names',

	// B
	'bouton_importer' => 'Import',

	// C
	'caracteres_autorises_champ' => 'Possible characters: letters without accents, numerals, - and _',
	'caracteres_interdits' => 'Some characters used are inappropriate for this field.',
	'champ_deja_existant' => 'A field with the same name already exists for this table.',
	'champ_sauvegarde' => 'Extra field saved!',
	'champs_extras' => 'Extra Fields',
	'champs_extras_de' => 'Extras fields of : @objet@',

	// E
	'erreur_action' => 'Action @action@ unknown.',
	'erreur_enregistrement_champ' => 'Problem creating the extra field.',
	'erreur_format_export' => 'Export format @format@ unknown.',
	'erreur_nom_champ_mysql_keyword' => 'This field name is a keyword reserved by SQL and cannot be used.',
	'erreur_nom_champ_utilise' => 'This field name is already used by SPIP or an active plugin.',
	'exporter_objet' => 'Export all extra fields from: @objet@ ',
	'exporter_objet_champ' => 'Export extra field: @objet@ / @nom@',
	'exporter_tous' => 'Export all extra fields',
	'exporter_tous_explication' => 'Export all extra fields in YAML format for use in the import form ',
	'exporter_tous_php' => 'PHP Export ',
	'exporter_tous_php_explication' => 'Export to PHP format for reuse in a plugin depending only on Core Extras Fields.',

	// I
	'icone_creer_champ_extra' => 'Create a new extra field',
	'importer_explications' => 'Importing extra fields into this site will complete
all the extra fields already present with the new ones declared in the import file.
The new fields will be added after the extra fields already present. ',
	'importer_fichier' => 'File to import',
	'importer_fichier_explication' => 'Export file in YAML format',
	'importer_fusionner' => 'Modify the fields already present',
	'importer_fusionner_explication' => 'If extra fields to be imported are already present on the site,
the import process ignores them (default). However, you can ask
to modify all the information of these fields with those of the imported file.',
	'importer_fusionner_non' => 'Do not change the fields already present on the site. ',
	'importer_fusionner_oui' => 'Modify the common extra fields with import',
	'info_description_champ_extra' => 'This page is used to manage the extra fields, 
						these being supplementary fields added to SPIP’s default database tables,
						taken into account in the object entry and modification forms.', # MODIF
	'info_description_champ_extra_creer' => 'You can create new fields which will then be displayed on this page under the heading of "List of editorial objects", as well as in the forms.',
	'info_description_champ_extra_presents' => 'Finally, if there are already extra fields in your database,
						but which have not been declared (by a plugin or set of templates), then you
						can ask this plugin to manage them for you. These fields, if there are any,
						will appear under the heading of "List of existing fields not managed".',
	'info_modifier_champ_extra' => 'Modify an extra field',
	'info_nouveau_champ_extra' => 'New extra field',
	'info_saisie' => 'Form entry:',

	// L
	'label_attention' => 'Very important help',
	'label_champ' => 'Field name',
	'label_class' => 'CSS classes',
	'label_conteneur_class' => 'CSS classes of the parent container',
	'label_datas' => 'Value list',
	'label_explication' => 'Data entry help',
	'label_label' => 'Data entry label',
	'label_obligatoire' => 'Compulsory field?',
	'label_rechercher' => 'Search',
	'label_rechercher_ponderation' => 'Weight of search',
	'label_restrictions_auteur' => 'Per author',
	'label_restrictions_branches' => 'By branch',
	'label_restrictions_groupes' => 'Per group',
	'label_restrictions_secteurs' => 'Per section',
	'label_saisie' => 'Type of form entry',
	'label_sql' => 'SQL definition',
	'label_table' => 'Object',
	'label_traitements' => 'Automatic processes',
	'label_versionner' => 'Versioning of the content of that field',
	'legend_declaration' => 'Declaration',
	'legend_options_saisies' => 'Data entry options',
	'legend_options_techniques' => 'Technical options',
	'legend_restriction' => 'Restriction',
	'legend_restrictions_modifier' => 'Edit the entry',
	'legend_restrictions_voir' => 'See the form entry',
	'liste_des_extras' => 'List of extra fields',
	'liste_des_extras_possibles' => 'List of existing fields not managed',
	'liste_objets_applicables' => 'List of editorial objects',

	// M
	'masquer_noms' => 'Hide field names',

	// N
	'nb_element' => '1 element',
	'nb_elements' => '@nb@ elements',

	// P
	'precisions_pour_attention' => 'To be used for VERY important details.
		To be used with moderation!
		May be a "plugin:stringname" idiom..',
	'precisions_pour_class' => 'Add CSS classes for the element,
		separated by a space. Example: "inserer_barre_edition" for a block
		with the Porte Plume plugin',
	'precisions_pour_conteneur_class' => 'To add CSS classes on the parent container, separated by a space. Example: "pleine_largeur" to have the full width on the form',
	'precisions_pour_datas' => 'Some field types require a list of accepted values​​: please indicate one per line, followed by a comma and a description. A blank line for the default value. The description may be a language string.',
	'precisions_pour_explication' => 'You can provide more information about the data field. 
		May be a "plugin:stringname" idiom..', # MODIF
	'precisions_pour_label' => 'May be a "plugin:stringname" idiom.',
	'precisions_pour_nouvelle_saisie' => 'Allow to change the form entry type used for that field',
	'precisions_pour_nouvelle_saisie_attention' => 'Be careful, a change in input type loses the configuration options of the input which are not common with the new selected entry!',
	'precisions_pour_rechercher' => 'Include this field in the search engine?',
	'precisions_pour_rechercher_ponderation' => 'SPIP weights a search in a column per a weighting coefficient.
It allows to highlight the most relevant columns (title for example) compared to others which are less.
The default coefficient applied to the extras fields is 2. To give you an idea, note that SPIP uses 8 for the title, one for text.',
	'precisions_pour_restrictions_branches' => 'Ids of branches to restrict (separator «:»)',
	'precisions_pour_restrictions_groupes' => 'Ids of groups to restrict (separator «:»)',
	'precisions_pour_restrictions_secteurs' => 'Ids of sectors to restrict (separator «:»)',
	'precisions_pour_saisie' => 'Disply a form entry of type:',
	'precisions_pour_traitements' => 'Automatically apply a process
		for the resulting #FIELD_NAME field:',
	'precisions_pour_versionner' => 'The versioning would be apply only if the plugin "revisions" is active and that the editorial object of the extra fields itself is versioned.',

	// R
	'radio_restrictions_auteur_admin' => 'Only the administrators (even restricted)',
	'radio_restrictions_auteur_admin_complet' => 'Only full administrators ',
	'radio_restrictions_auteur_aucune' => 'Everyone can',
	'radio_restrictions_auteur_webmestre' => 'Only the webmasters',
	'radio_traitements_aucun' => 'None',
	'radio_traitements_raccourcis' => 'SPIP shortcut processes (clean)',
	'radio_traitements_typo' => 'Only typographical processes (typo)',

	// S
	'saisies_champs_extras' => 'From "Extra Fields"',
	'saisies_saisies' => 'From "Saisies"',
	'supprimer_reelement' => 'Delete this field?',

	// T
	'titre_iextras' => 'Extras Fields',
	'titre_iextras_exporter' => 'Export Extras Fields',
	'titre_iextras_exporter_importer' => 'Export or import Extras Fields',
	'titre_iextras_importer' => 'Import Extras Fields',
	'titre_page_iextras' => 'Extra Fields',

	// V
	'veuillez_renseigner_ce_champ' => 'Please enter this field!'
);
