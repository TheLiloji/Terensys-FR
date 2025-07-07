<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

namespace Spip\Core\Tests;

use PHPUnit\Framework\TestCase;


class TextwheelNettoyerraccourcistypoTest extends TestCase {

	protected static $lang = 'en';
	protected static $filtrer_javascript;
	protected static $class_spip_plus;
	protected static $class_spip;
	protected static $notes;

	public static function setUpBeforeClass(): void{
		static::$filtrer_javascript = $GLOBALS['filtrer_javascript'];
		include_spip('inc/texte');

		changer_langue(static::$lang);

		static::$class_spip_plus = ($GLOBALS['class_spip_plus'] ?? null);
		static::$class_spip = ($GLOBALS['class_spip'] ?? null);
		$GLOBALS['class_spip_plus'] = '';
		$GLOBALS['class_spip'] = '';

		static::$notes = charger_fonction("notes", "inc");
		(static::$notes)('', 'reset_all');
	}

	/**
	 * @dataProvider providerEssais
	 */
	public function testCouper($source, $expected) {
		$this->assertEquals($expected['couper'], couper($source));
	}

	/**
	 * @dataProvider providerEssais
	 */
	public function testNettoyer($source, $expected) {
		$this->assertEquals($expected['nettoyer'], nettoyer_raccourcis_typo($source));
	}



	/**
	 * Provider pour propre() sur du texte
	 * @return array
	 */
	public function providerEssais() {
		$essais = [];

		$essais['italique1'] = [
			'Un mot {italique}',
			[
				'couper' => 'Un mot italique',
				'nettoyer' => 'Un mot italique',
			]
		];
		$essais['italique2'] = [
			'{Un texte italique}',
			[
				'couper' => 'Un texte italique',
				'nettoyer' => 'Un texte italique',
			]
		];

		$essais['gras1'] = [
			'Un mot {{gras}}',
			[
				'couper' => 'Un mot gras',
				'nettoyer' => 'Un mot gras',
			]
		];
		$essais['gras2'] = [
			'{{Un texte gras}}',
			[
				'couper' => 'Un texte gras',
				'nettoyer' => 'Un texte gras',
			]
		];

		$essais['intertitre1'] = [
			'{{{Un intertitre}}}',
			[
				'couper' => 'Un intertitre',
				'nettoyer' => 'Un intertitre',
			]
		];
		$essais['intertitre2'] = [
			"Ligne\n\n{{{Un intertitre}}}\n\nLigne",
			[
				'couper' => "Ligne\n\nUn intertitre\n\nLigne",
				'nettoyer' => "Ligne\n\nUn intertitre\n\nLigne",
			]
		];

		$essais['liens1'] = [
			'Un lien [interne->article1]',
			[
				'couper' => 'Un lien interne',
				'nettoyer' => 'Un lien interne',
			]
		];
		$essais['liens2'] = [
			'Un lien [externe->http://example.org]',
			[
				'couper' => 'Un lien externe',
				'nettoyer' => 'Un lien externe',
			]
		];

		/**
		 * Les listes sont mises à plat
		 * 1 saut de ligne \n entre chaque, couper les réassemble en espace.
		 *
		 * @note
		 *    Avant SPIP 3.2, 1 saut de paragraphe \n\n entre chaque (couper le laissait).
		 */

		$essais['liste1'] = [
			'texte' =>
				"Une liste
-* un
-* deux
-* trois
",
			[
				'couper' => "Une liste un deux trois",
				'nettoyer' => "Une liste\nun\ndeux\ntrois",
			]
		];
		$essais['liste2'] = [
			'texte' =>
				"Une liste avec ligne

-* un
-* deux
-* trois

",
			[
				'couper' => "Une liste avec ligne un deux trois",
				'nettoyer' => "Une liste avec ligne\nun\ndeux\ntrois",
			]
		];

		/**
		 * Les tableaux sont totalement supprimés.
		 * Enfin presque : remplacés par \n\n
		 * Le texte étant trimmé, \n en fins sont enlevés.
		 *
		 * @note
		 *     Avant SPIP 3.2, les tableaux n’étaient pas toujours correctement supprimés
		 */
		$essais['tableau sans sauts de ligne avant / après'] = [
				"| {{colonneA}} | {{colonneB}} |
| ligneA1 | ligneB1 |
| ligneA2 | ligneB2 |",
			[
				'couper' => "",
				'nettoyer' => "",
			]
		];

		$essais['tableau sans ligne vide avant'] = [
				"Un tableau sans ligne vide avant
| {{colonneA}} | {{colonneB}} |
| ligneA1 | ligneB1 |
| ligneA2 | ligneB2 |
",
			[
				'couper' => "Un tableau sans ligne vide avant",
				'nettoyer' => "Un tableau sans ligne vide avant",
			]
		];
		$essais['tableau avec ligne vide avant'] = [
				"Un tableau avec ligne vide avant

| {{colonneA}} | {{colonneB}} |
| ligneA1 | ligneB1 |
| ligneA2 | ligneB2 |
",
			[
				'couper' => "Un tableau avec ligne vide avant",
				'nettoyer' => "Un tableau avec ligne vide avant",
			]
		];

		$essais['tableau avec ligne avant / après'] = [
				"Un tableau avec ligne avant / après
| {{colonneA}} | {{colonneB}} |
| ligneA1 | ligneB1 |
| ligneA2 | ligneB2 |
Après
",
			[
				'couper' => "Un tableau avec ligne avant / après\n\nAprès",
				'nettoyer' => "Un tableau avec ligne avant / après\n\nAprès",
			]
		];

		/**
		 * Les notes sont supprimées.
		 *
		 * @note
		 *     Avant 3.2 la regexp pouvait tuer pcre sur des textes longs
		 *     ayant des notes mal fermées.
		 */
		$essais['note1'] = [
			'Une note bien fermée [[note 1]]',
			[
				'couper' => 'Une note bien fermée',
				'nettoyer' => 'Une note bien fermée',
			]
		];

		$essais['note2'] = [
			'Une note mal fermée [[note 1]',
			[
				'couper' => 'Une note mal fermée [[note 1]',
				'nettoyer' => 'Une note mal fermée [[note 1]',
			]
		];

		$essais['note3'] = [
			'Un lien dans une note [[note [lien->article1]]]',
			[
				'couper' => 'Un lien dans une note',
				'nettoyer' => 'Un lien dans une note',
			]
		];

		$essais['note4'] = [
			'Lien et note ratée [[note [lien->article1]] ]',
			[
				'couper' => 'Lien et note ratée [[note lien] ]',
				'nettoyer' => 'Lien et note ratée [[note lien] ]',
			]
		];


		return $essais;
	}
}
