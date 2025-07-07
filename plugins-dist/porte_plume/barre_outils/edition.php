<?php

/**
 * Déclaration de la barre d'outil d'édition de SPIP
 *
 * @plugin Porte Plume pour SPIP
 * @license GPL
 * @package SPIP\PortePlume\BarreOutils
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Définition de la barre 'edition' pour markitup
 *
 * @return Barre_outils La barre d'outil
 */
function barre_outils_edition() {
	$set = new Barre_outils([
		'nameSpace' => 'edition',
		#'previewAutoRefresh'=> true,
		#'previewParserPath' => url_absolue(generer_url_public('preview')),
		'onShiftEnter' => ['keepDefault' => false, 'replaceWith' => "\n_ "],
		'onCtrlEnter' => ['keepDefault' => false, 'replaceWith' => "\n\n"],
		// garder les listes si on appuie sur entree
		'onEnter' => ['keepDefault' => false, 'selectionType' => 'return', 'replaceWith' => "\n"],
		// Utile quand on saisi du code, mais pas accessible !
		#'onTab'             => array('keepDefault'=>false, 'replaceWith'=>"\t"),
		'markupSet' => [
			// H1 - {{{
			[
				'id' => 'header1',
				'name' => _T('barreoutils:barre_intertitre'),
				'key' => 'H',
				'className' => 'outil_header1',
				'openWith' => "\n{{{",
				'closeWith' => "}}}\n",
				'display' => true,
				'selectionType' => 'line',
			],
			// Bold - {{
			[
				'id' => 'bold',
				'name' => _T('barreoutils:barre_gras'),
				'key' => 'B',
				'className' => 'outil_bold',
				'replaceWith' => "function(h){ return espace_si_accolade(h, '{{', '}}');}",
				//"openWith" => "{{",
				//"closeWith" => "}}",
				'display' => true,
				'selectionType' => 'word',
			],
			// Italic - {
			[
				'id' => 'italic',
				'name' => _T('barreoutils:barre_italic'),
				'key' => 'I',
				'className' => 'outil_italic',
				'replaceWith' => "function(h){ return espace_si_accolade(h, '{', '}');}",
				//"openWith" => "{",
				//"closeWith" => "}",
				'display' => true,
				'selectionType' => 'word',
			],

			// montrer une suppression
			[
				'id' => 'stroke_through',
				'name' => _T('barreoutils:barre_barre'), // :-)
				'className' => 'outil_stroke_through',
				'openWith' => '<del>',
				'closeWith' => '</del>',
				'display' => true,
				'selectionType' => 'word',
			],

			// listes -*
			[
				'id' => 'liste_ul',
				'name' => _T('barreoutils:barre_liste_ul'),
				'className' => 'outil_liste_ul',
				'replaceWith' => "function(h){ return outil_liste(h, '*');}",
				'display' => true,
				'selectionType' => 'line',
				'forceMultiline' => true,
				'dropMenu' => [
					// liste -#
					[
						'id' => 'liste_ol',
						'name' => _T('barreoutils:barre_liste_ol'),
						'className' => 'outil_liste_ol',
						'replaceWith' => "function(h){ return outil_liste(h, '#');}",
						'display' => true,
						'selectionType' => 'line',
						'forceMultiline' => true,
					],
					// desindenter
					[
						'id' => 'desindenter',
						'name' => _T('barreoutils:barre_desindenter'),
						'className' => 'outil_desindenter',
						'replaceWith' => 'function(h){return outil_desindenter(h);}',
						'display' => true,
						'selectionType' => 'line',
						'forceMultiline' => true,
					],
					// indenter
					[
						'id' => 'indenter',
						'name' => _T('barreoutils:barre_indenter'),
						'className' => 'outil_indenter',
						'replaceWith' => 'function(h){return outil_indenter(h);}',
						'display' => true,
						'selectionType' => 'line',
						'forceMultiline' => true,
					],
				],
			],
			// separation
			[
				'id' => 'sepLink', // trouver un nom correct !
				'separator' => '---------------',
				'display' => true,
			],
			// lien spip
			[
				'id' => 'link',
				'name' => _T('barreoutils:barre_lien'),
				'key' => 'L',
				'className' => 'outil_link',
				'openWith' => '[',
				'closeWith' => '->[![' . _T('barreoutils:barre_lien_input') . ']!]]',
				'display' => true,
			],
			// note en bas de page spip
			[
				'id' => 'notes',
				'name' => _T('barreoutils:barre_note'),
				'className' => 'outil_notes',
				'openWith' => '[[',
				'closeWith' => ']]',
				'display' => true,
				'selectionType' => 'word',
			],
			// separation
			[
				'id' => 'sepGuillemets',
				'separator' => '---------------',
				'display' => true,
			],
			// quote spip
			// (affichee dans forum)
			[
				'id' => 'quote',
				'name' => _T('barreoutils:barre_quote'),
				'key' => 'Q',
				'className' => 'outil_quote',
				'openWith' => "\n<quote>",
				'closeWith' => "</quote>\n",
				'display' => true,
				'selectionType' => 'word',
				'dropMenu' => [
					// poesie spip
					[
						'id' => 'barre_poesie',
						'name' => _T('barreoutils:barre_poesie'),
						'className' => 'outil_poesie',
						'openWith' => "\n&lt;poesie&gt;",
						'closeWith' => "&lt;/poesie&gt;\n",
						'display' => true,
						'selectionType' => 'line',
					],
				],
			],
			// guillemets
			[
				'id' => 'guillemets',
				'name' => _T('barreoutils:barre_guillemets'),
				'className' => 'outil_guillemets',
				'openWith' => '&laquo;',
				'closeWith' => '&raquo;',
				'display' => true,
				'lang' => ['fr', 'eo', 'cpf', 'ar', 'es'],
				'selectionType' => 'word',
				'dropMenu' => [
					// guillemets internes
					[
						'id' => 'guillemets_simples',
						'name' => _T('barreoutils:barre_guillemets_simples'),
						'className' => 'outil_guillemets_simples',
						'openWith' => '&ldquo;',
						'closeWith' => '&rdquo;',
						'display' => true,
						'lang' => ['fr', 'eo', 'cpf', 'ar', 'es'],
						'selectionType' => 'word',
					],
				]
			],
			// guillemets de
			[
				'id' => 'guillemets_de',
				'name' => _T('barreoutils:barre_guillemets'),
				'className' => 'outil_guillemets_de',
				'openWith' => '&bdquo;',
				'closeWith' => '&ldquo;',
				'display' => true,
				'lang' => ['bg', 'de', 'pl', 'hr', 'src'],
				'selectionType' => 'word',
				'dropMenu' => [
					// guillemets de, simples
					[
						'id' => 'guillemets_de_simples',
						'name' => _T('barreoutils:barre_guillemets_simples'),
						'className' => 'outil_guillemets_de_simples',
						'openWith' => '&sbquo;',
						'closeWith' => '&lsquo;',
						'display' => true,
						'lang' => ['bg', 'de', 'pl', 'hr', 'src'],
						'selectionType' => 'word',
					],
				]
			],

			// guillemets autres langues
			[
				'id' => 'guillemets_autres',
				'name' => _T('barreoutils:barre_guillemets'),
				'className' => 'outil_guillemets_simples',
				'openWith' => '&ldquo;',
				'closeWith' => '&rdquo;',
				'display' => true,
				'lang_not' => ['fr', 'eo', 'cpf', 'ar', 'es', 'bg', 'de', 'pl', 'hr', 'src'],
				'selectionType' => 'word',
				'dropMenu' => [
					// guillemets simples, autres langues
					[
						'id' => 'guillemets_autres_simples',
						'name' => _T('barreoutils:barre_guillemets_simples'),
						'className' => 'outil_guillemets_uniques',
						'openWith' => '&lsquo;',
						'closeWith' => '&rsquo;',
						'display' => true,
						'lang_not' => ['fr', 'eo', 'cpf', 'ar', 'es', 'bg', 'de', 'pl', 'hr', 'src'],
						'selectionType' => 'word',
					],
				]
			],
			// separation
			[
				'id' => 'sepCaracteres',
				'separator' => '---------------',
				'display' => true,
			],
			// icones clavier
			[
				'id' => 'grpCaracteres',
				'name' => _T('barreoutils:barre_inserer_caracteres'),
				'className' => 'outil_caracteres',
				'display' => true,
				'dropMenu' => [
					// A majuscule accent grave
					[
						'id' => 'A_grave',
						'name' => _T('barreoutils:barre_a_accent_grave'),
						'className' => 'outil_a_maj_grave',
						'replaceWith' => '&Agrave;',
						'display' => true,
						'lang' => ['fr', 'eo', 'cpf'],
					],
					// E majuscule accent aigu
					[
						'id' => 'E_aigu',
						'name' => _T('barreoutils:barre_e_accent_aigu'),
						'className' => 'outil_e_maj_aigu',
						'replaceWith' => '&Eacute;',
						'display' => true,
						'lang' => ['fr', 'eo', 'cpf'],
					],
					// E majuscule accent grave
					[
						'id' => 'E_grave',
						'name' => _T('barreoutils:barre_e_accent_grave'),
						'className' => 'outil_e_maj_grave',
						'replaceWith' => '&Egrave;',
						'display' => true,
						'lang' => ['fr', 'eo', 'cpf'],
					],
					// e dans le a
					[
						'id' => 'aelig',
						'name' => _T('barreoutils:barre_ea'),
						'className' => 'outil_aelig',
						'replaceWith' => '&aelig;',
						'display' => true,
						'lang' => ['fr', 'eo', 'cpf'],
					],
					// e dans le a majuscule
					[
						'id' => 'AElig',
						'name' => _T('barreoutils:barre_ea_maj'),
						'className' => 'outil_aelig_maj',
						'replaceWith' => '&AElig;',
						'display' => true,
						'lang' => ['fr', 'eo', 'cpf'],
					],
					// oe
					[
						'id' => 'oe',
						'name' => _T('barreoutils:barre_eo'),
						'className' => 'outil_oe',
						'replaceWith' => '&oelig;',
						'display' => true,
						'lang' => ['fr'],
					],
					// OE
					[
						'id' => 'OE',
						'name' => _T('barreoutils:barre_eo_maj'),
						'className' => 'outil_oe_maj',
						'replaceWith' => '&OElig;',
						'display' => true,
						'lang' => ['fr'],
					],
					// c cedille majuscule
					[
						'id' => 'Ccedil',
						'name' => _T('barreoutils:barre_c_cedille_maj'),
						'className' => 'outil_ccedil_maj',
						'replaceWith' => '&Ccedil;',
						'display' => true,
						'lang' => ['fr', 'eo', 'cpf'],
					],
					// Transformation en majuscule
					[
						'id' => 'uppercase',
						'name' => _T('barreoutils:barre_gestion_cr_changercassemajuscules'),
						'className' => 'outil_uppercase',
						'replaceWith' => 'function(markitup) { return markitup.selection.toUpperCase() }',
						'display' => true,
						'lang' => ['fr', 'en'],
					],
					// Transformation en minuscule
					[
						'id' => 'lowercase',
						'name' => _T('barreoutils:barre_gestion_cr_changercasseminuscules'),
						'className' => 'outil_lowercase',
						'replaceWith' => 'function(markitup) { return markitup.selection.toLowerCase() }',
						'display' => true,
						'lang' => ['fr', 'en'],
					],
				],
			],

			// Groupe de Codes informatiques.
			[
				'id' => 'sepCode',
				'separator' => '---------------',
				'display' => true,
			],
			[
				// groupe code et bouton <code>
				'id' => 'grpCode',
				'name' => _T('barreoutils:barre_inserer_code'),
				'className' => 'outil_code',
				'openWith' => '`',
				'closeWith' => '`',
				'display' => true,
				'dropMenu' => [
					// bouton <cadre>
					[
						'id' => 'cadre',
						'name' => _T('barreoutils:barre_inserer_cadre'),
						'className' => 'outil_cadre',
						'openWith' => "```\n",
						'closeWith' => "\n```",
						'display' => true,
					],
				],
			],

			/*	inutile (origine de markitup et non de spip)

						// separation
						array(
							"id" => "sepPreview", // trouver un nom correct !
							"separator" => "---------------",
							"display"   => true,
						),
						// clean
						array(
							"id"          => 'clean',
							"name"        => _T('barreoutils:barre_clean'),
							"className"   => "outil_clean",
							"replaceWith" => 'function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") }',
							"display"     => true,
						),
						// preview
						array(
							"id"        => 'preview',
							"name"      => _T('barreoutils:barre_preview'),
							"className" => "outil_preview",
							"call"      => "preview",
							"display"   => true,
						),
			*/

		],

		'functions' => "
				// remplace ou cree -* ou -** ou -# ou -##
				function outil_liste(h, c) {
					if ((s = h.selection) && (r = s.match(/^-([*#]+) (.*)\$/)))	 {
						r[1] = r[1].replace(/[#*]/g, c);
						s = '-'+r[1]+' '+r[2];
					} else {
						s = '-' + c + ' '+s;
					}
					return s;
				}

				// indente des -* ou -#
				function outil_indenter(h) {
					if (s = h.selection) {
						if (s.substr(0,2)=='-*') {
							s = '-**' + s.substr(2);
						} else if (s.substr(0,2)=='-#') {
							s = '-##' + s.substr(2);
						} else {
							s = '-* ' + s;
						}
					}
					return s;
				}

				// desindente des -* ou -** ou -# ou -##
				function outil_desindenter(h){
					if (s = h.selection) {
						if (s.substr(0,3)=='-**') {
							s = '-*' + s.substr(3);
						} else if (s.substr(0,3)=='-* ') {
							s = s.substr(3);
						} else if (s.substr(0,3)=='-##') {
							s = '-#' + s.substr(3);
						} else if (s.substr(0,3)=='-# ') {
							s = s.substr(3);
						}
					}
					return s;
				}

				// ajouter un espace avant, apres un {qqc} pour ne pas que
				// gras {{}} suivi de italique {} donnent {{{}}}, mais { {{}} }
				function espace_si_accolade(h, openWith, closeWith){
					if (s = h.selection) {
						// accolade dans la selection
						if (s.charAt(0)=='{') {
							return openWith + ' ' + s + ' ' + closeWith;
						}
						// accolade avant la selection
						else if (c = h.textarea.selectionStart) {
							if (h.textarea.value.charAt(c-1) == '{') {
								return ' ' + openWith + s + closeWith + ' ';
							}
						}
					}
					return openWith + s + closeWith;
				}
				",
	]);

	$set->cacher([
		'stroke_through',
		'clean',
		'preview',
	]);

	return $set;
}


/**
 * Définitions des liens entre css et icones
 *
 * @return array
 *     Couples identifiant de bouton => nom de l'image (ou tableau nom, position haut, position bas)
 */
function barre_outils_edition_icones() {
	return [
		//'outil_header1' => 'text_heading_1.png',
		'outil_header1' => ['spt-v1.svg', '-2px -2px'], //'intertitre.png'
		'outil_bold' => ['spt-v1.svg', '-2px -22px'], //'text_bold.png'
		'outil_italic' => ['spt-v1.svg', '-2px -42px'], //'text_italic.png'

		'outil_stroke_through' => ['spt-v1.svg', '-2px -62px'], //'text_strikethrough.png'

		'outil_liste_ul' => ['spt-v1.svg', '-2px -442px'], //'text_list_bullets.png'
		'outil_liste_ol' => ['spt-v1.svg', '-2px -462px'], //'text_list_numbers.png'
		'outil_indenter' => ['spt-v1.svg', '-2px -482px'], //'text_indent.png'
		'outil_desindenter' => ['spt-v1.svg', '-2px -502px'], //'text_indent_remove.png'

		//'outil_quote' => 'text_indent.png',
		'outil_quote' => ['spt-v1.svg', '-2px -302px'], //'quote.png'
		'outil_poesie' => ['spt-v1.svg', '-2px -322px'], //'poesie.png'

		//'outil_link' => 'world_link.png',
		'outil_link' => ['spt-v1.svg', '-2px -342px'], //'lien.png'
		'outil_notes' => ['spt-v1.svg', '-2px -362px'], //'notes.png'


		'outil_guillemets' => ['spt-v1.svg', '-2px -522px'], //'guillemets.png'
		'outil_guillemets_simples' => ['spt-v1.svg', '-2px -542px'], //'guillemets-simples.png'
		'outil_guillemets_de' => ['spt-v1.svg', '-2px -562px'], //'guillemets-de.png'
		'outil_guillemets_de_simples' => ['spt-v1.svg', '-2px -582px'], //'guillemets-uniques-de.png'
		'outil_guillemets_uniques' => ['spt-v1.svg', '-2px -602px'], //'guillemets-uniques.png'

		'outil_caracteres' => ['spt-v1.svg', '-2px -282px'], //'keyboard.png'
		'outil_a_maj_grave' => ['spt-v1.svg', '-2px -162px'], //'agrave-maj.png'
		'outil_e_maj_aigu' => ['spt-v1.svg', '-2px -202px'], //'eacute-maj.png'
		'outil_e_maj_grave' => ['spt-v1.svg', '-2px -222px'], //'eagrave-maj.png'
		'outil_aelig' => ['spt-v1.svg', '-2px -142px'], //'aelig.png'
		'outil_aelig_maj' => ['spt-v1.svg', '-2px -122px'], //'aelig-maj.png'
		'outil_oe' => ['spt-v1.svg', '-2px -262px'], //'oelig.png'
		'outil_oe_maj' => ['spt-v1.svg', '-2px -242px'], //'oelig-maj.png'
		'outil_ccedil_maj' => ['spt-v1.svg', '-2px -182px'],  //'ccedil-maj.png'
		'outil_uppercase' => ['spt-v1.svg', '-2px -82px'], //'text_uppercase.png'
		'outil_lowercase' => ['spt-v1.svg', '-2px -102px'], //'text_lowercase.png'

		'outil_code' => ['spt-v1.svg', '-2px -382px'],
		'outil_cadre' => ['spt-v1.svg', '-2px -402px'],

		'outil_clean' => ['spt-v1.svg', '-2px -422px'], //'clean.png'
		'outil_preview' => ['spt-v1.svg', '-2px -622px'], //'eye.png'
	];
}
