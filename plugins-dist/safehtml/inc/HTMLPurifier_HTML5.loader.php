<?php

spl_autoload_register(function ($name) {

	$vendorDir = dirname(__DIR__) . '/lib';

	$classes = [
		'HTMLPurifier_AttrDef_HTML5_ARel' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/ARel.php',
		'HTMLPurifier_AttrDef_HTML5_Datetime' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/Datetime.php',
		'HTMLPurifier_AttrDef_HTML5_Duration' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/Duration.php',
		'HTMLPurifier_AttrDef_HTML5_Float' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/Float.php',
		'HTMLPurifier_AttrDef_HTML5_FormRel' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/FormRel.php',
		'HTMLPurifier_AttrDef_HTML5_InputType' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/InputType.php',
		'HTMLPurifier_AttrDef_HTML5_IntegrityMetadata' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/IntegrityMetadata.php',
		'HTMLPurifier_AttrDef_HTML5_LinkRel' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/LinkRel.php',
		'HTMLPurifier_AttrDef_HTML5_Rel' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/Rel.php',
		'HTMLPurifier_AttrDef_HTML5_Week' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/Week.php',
		'HTMLPurifier_AttrDef_HTML5_YearlessDate' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML5/YearlessDate.php',
		'HTMLPurifier_AttrDef_HTML_Bool2' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML/Bool2.php',
		'HTMLPurifier_AttrDef_HTML_FontSize' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrDef/HTML/FontSize.php',
		'HTMLPurifier_AttrTransform_HTML5_Data' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrTransform/HTML5/Data.php',
		'HTMLPurifier_AttrTransform_HTML5_Dialog' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrTransform/HTML5/Dialog.php',
		'HTMLPurifier_AttrTransform_HTML5_FrameBorder' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrTransform/HTML5/FrameBorder.php',
		'HTMLPurifier_AttrTransform_HTML5_Input' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrTransform/HTML5/Input.php',
		'HTMLPurifier_AttrTransform_HTML5_Lang' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrTransform/HTML5/Lang.php',
		'HTMLPurifier_AttrTransform_HTML5_Progress' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrTransform/HTML5/Progress.php',
		'HTMLPurifier_AttrTransform_HTML5_Script' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/AttrTransform/HTML5/Script.php',
		'HTMLPurifier_ChildDef_HTML5_Abstract' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Abstract.php',
		'HTMLPurifier_ChildDef_HTML5_Details' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Details.php',
		'HTMLPurifier_ChildDef_HTML5_Div' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Div.php',
		'HTMLPurifier_ChildDef_HTML5_Dl' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Dl.php',
		'HTMLPurifier_ChildDef_HTML5_Fieldset' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Fieldset.php',
		'HTMLPurifier_ChildDef_HTML5_Figure' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Figure.php',
		'HTMLPurifier_ChildDef_HTML5_List' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/List.php',
		'HTMLPurifier_ChildDef_HTML5_Media' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Media.php',
		'HTMLPurifier_ChildDef_HTML5_Picture' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Picture.php',
		'HTMLPurifier_ChildDef_HTML5_Script' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Script.php',
		'HTMLPurifier_ChildDef_HTML5_Table' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Table.php',
		'HTMLPurifier_ChildDef_HTML5_Time' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/ChildDef/HTML5/Time.php',
		'HTMLPurifier_HTML5Config' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTML5Config.php',
		'HTMLPurifier_HTML5Definition' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTML5Definition.php',
		'HTMLPurifier_HTML5URIDefinition' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTML5URIDefinition.php',
		'HTMLPurifier_HTMLModule_HTML5_Bdo' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Bdo.php',
		'HTMLPurifier_HTMLModule_HTML5_CommonAttributes' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/CommonAttributes.php',
		'HTMLPurifier_HTMLModule_HTML5_Edit' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Edit.php',
		'HTMLPurifier_HTMLModule_HTML5_Forms' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Forms.php',
		'HTMLPurifier_HTMLModule_HTML5_Hypertext' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Hypertext.php',
		'HTMLPurifier_HTMLModule_HTML5_Iframe' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Iframe.php',
		'HTMLPurifier_HTMLModule_HTML5_Interactive' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Interactive.php',
		'HTMLPurifier_HTMLModule_HTML5_Legacy' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Legacy.php',
		'HTMLPurifier_HTMLModule_HTML5_Link' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Link.php',
		'HTMLPurifier_HTMLModule_HTML5_List' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/List.php',
		'HTMLPurifier_HTMLModule_HTML5_Media' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Media.php',
		'HTMLPurifier_HTMLModule_HTML5_Ruby' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Ruby.php',
		'HTMLPurifier_HTMLModule_HTML5_SafeForms' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/SafeForms.php',
		'HTMLPurifier_HTMLModule_HTML5_SafeScripting' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/SafeScripting.php',
		'HTMLPurifier_HTMLModule_HTML5_Scripting' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Scripting.php',
		'HTMLPurifier_HTMLModule_HTML5_Tables' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Tables.php',
		'HTMLPurifier_HTMLModule_HTML5_Text' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/HTML5/Text.php',
		'HTMLPurifier_HTMLModule_Tidy_HTML5' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/HTMLModule/Tidy/HTML5.php',
		'HTMLPurifier_Injector_HTML5_DlDiv' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/Injector/HTML5/DlDiv.php',
		'HTMLPurifier_Lexer_HTML5' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/Lexer/HTML5.php',
		'HTMLPurifier_TagTransform_Font2' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/TagTransform/Font2.php',
		'HTMLPurifier_URIFilter_HTML5_SafeLink' => $vendorDir . '/xemlock/htmlpurifier-html5/library/HTMLPurifier/URIFilter/HTML5/SafeLink.php',
	];

	if (isset($classes[$name])) {
		require_once $classes[$name];
		return true;
	}

	return false;
});
