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


class TextwheelPropreTest extends TestCase {
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

		// ajouter le dossier squelettes de test au chemin
		 _chemin(_DIR_PLUGIN_TW . "tests/squelettes/");
	}

	static function propreNotes($texte) {
		$notes = static::$notes;
		$texte = propre($texte);
		if ($r = $notes(array())) {
			$texte .= "<div class='notes'>$r</div>";
			$notes('', 'reset_all');
		}

		return $texte;
	}

	/**
	 * Provider pour propre() sur du texte
	 * @return array
	 */
	static function providerData($dir) {

		$tests = glob(_DIR_PLUGIN_TW . "tests/data/{$dir}/*.txt");

		$texte = $expected = "";
		$essais = array();

		foreach ($tests as $t) {
			$texte = file_get_contents($t);
			$expected = file_get_contents(substr($t, 0, -4) . ".html");
			$name = $dir . basename($t, ".txt");
			$essais[$name] = [
				$texte,
				$expected
			];
		}

		return $essais;
	}



	/**
	 * Provider pour propre() sur du texte
	 * @return array
	 */
	public function providerPropre() {
		return static::providerData('base');
	}

	/**
	 * @dataProvider providerPropre
	 */
	public function testPropre($source, $expected) {
		$this->assertEquals($expected, static::propreNotes($source));
	}

	/**
	 * Provider pour propre() sur du texte avec des raccourcis math
	 * @return array
	 */
	public function providerPropreMath() {
		return static::providerData('math');
	}

	/**
	 * @dataProvider providerPropreMath
	 */
	public function testPropreMath($source, $expected) {

		$result = static::propreNotes($source);

		// on ignore width et height qui dependent de la police utilisee sur le serveur
		$expected = preg_replace(',(width|height)="\d+",','', $expected);
		$result = preg_replace(',(width|height)="\d+",','', $result);

		$this->assertEquals($expected, $result);
	}


	/**
	 * Provider pour propre() sur du modeles de type block
	 * @return array
	 */
	public function providerModelesBlock() {
		return static::providerData('modeles_block');
	}

	/**
	 * @dataProvider providerModelesBlock
	 */
	public function testModelesBlock($source, $expected) {
		$this->assertEquals($expected, static::propreNotes($source));
	}


	/**
	 * Provider pour propre() sur du modeles de type inline
	 * @return array
	 */
	public function providerModelesInline() {
		return static::providerData('modeles_inline');
	}

	/**
	 * @dataProvider providerModelesInline
	 */
	public function testModelesInline($source, $expected) {
		$this->assertEquals($expected, static::propreNotes($source));
	}




	public static function tearDownAfterClass(): void{
		$GLOBALS['class_spip_plus'] = static::$class_spip_plus;
		$GLOBALS['class_spip'] = static::$class_spip;
	}
}
