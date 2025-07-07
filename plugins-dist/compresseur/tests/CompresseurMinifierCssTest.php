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


/**
 * CompresseurMinifierCssTest test : teste la minification CSS
 *
 */
class CompresseurMinifierCssTest extends TestCase {

	public static function setUpBeforeClass(): void{
		include_spip('inc/compresseur_minifier');
	}

	protected function provideTestsFor($which, $media, $level = '') {
		$data = [];


		$dirSource = __DIR__ . "/data/minifier_css/$which/source/";
		$dirExpected = __DIR__ . "/data/minifier_css/$which/expected/$media/";
		if ($level) {
			$dirExpected .= "$level/";
		}

		$sourceFiles = glob($dirSource . "*.css");
		foreach ($sourceFiles as $sourceFile) {
			$name = basename($sourceFile);
			$expectedFile = $dirExpected . $name;
			if (file_exists($expectedFile)) {
				$source = file_get_contents($sourceFile);
				// on rtrim expected car les editeurs ajoutent parfois un saut de ligne final
				$expected = rtrim(file_get_contents($expectedFile));
				$data["$which:$name"] = [$source, $expected];
			}
		}

		return $data;
	}

	public function providerSimpleAll() {
		return $this->provideTestsFor('simple', 'all');
	}

	public function providerSimpleScreen() {
		return $this->provideTestsFor('simple', 'screen');
	}


	/**
	 * @dataProvider providerSimpleAll
	 */
	public function testSimpleDefault($source, $expected) {
		$mini = minifier_css($source);
		$this->assertEquals($expected, $mini);
	}

	/**
	 * @dataProvider providerSimpleAll
	 */
	public function testSimpleAll($source, $expected) {
		// media all doit donner le meme resultat que par defaut
		$mini = minifier_css($source, 'all');
		$this->assertEquals($expected, $mini);
	}

	/**
	 * @dataProvider providerSimpleScreen
	 */
	public function testSimpleScreen($source, $expected) {
		$mini = minifier_css($source, 'screen');
		$this->assertEquals($expected, $mini);
	}


	public function providerCsstidyAllHigh() {
		return $this->provideTestsFor('csstidy', 'all', 'high');
	}

	public function providerCsstidyScreenHigh() {
		return $this->provideTestsFor('csstidy', 'screen', 'high');
	}

	public function providerCsstidyScreenHighest() {
		return $this->provideTestsFor('csstidy', 'screen', 'highest');
	}


	/**
	 * @dataProvider providerCsstidyAllHigh
	 */
	public function testCsstidyDefault($source, $expected) {
		// media all doit donner le meme resultat que par defaut
		$mini = minifier_css($source, []);
		$this->assertEquals($expected, $mini);
	}

	/**
	 * @dataProvider providerCsstidyAllHigh
	 */
	public function testCsstidyDefaultHigh($source, $expected) {
		// media all doit donner le meme resultat que par defaut
		$mini = minifier_css($source, ['template' => 'high']);
		$this->assertEquals($expected, $mini);
	}

	/**
	 * @dataProvider providerCsstidyAllHigh
	 */
	public function testCsstidyAll($source, $expected) {
		// media all doit donner le meme resultat que par defaut
		$mini = minifier_css($source, ['media' => 'all']);
		$this->assertEquals($expected, $mini);
	}

	/**
	 * @dataProvider providerCsstidyAllHigh
	 */
	public function testCsstidyAllHigh($source, $expected) {
		// media all doit donner le meme resultat que par defaut
		$mini = minifier_css($source, ['media' => 'all', 'template' => 'high']);
		$this->assertEquals($expected, $mini);
	}

	/**
	 * @dataProvider providerCsstidyScreenHigh
	 */
	public function testCsstidyScreen($source, $expected) {
		// media all doit donner le meme resultat que par defaut
		$mini = minifier_css($source, ['media' => 'screen']);
		$this->assertEquals($expected, $mini);
	}

	/**
	 * @dataProvider providerCsstidyScreenHigh
	 */
	public function testCsstidyScreenHigh($source, $expected) {
		// media all doit donner le meme resultat que par defaut
		$mini = minifier_css($source, ['media' => 'screen', 'template' => 'high']);
		$this->assertEquals($expected, $mini);
	}

	/**
	 * @dataProvider providerCsstidyScreenHighest
	 */
	public function testCsstidyScreenHighest($source, $expected) {
		// media all doit donner le meme resultat que par defaut
		$mini = minifier_css($source, ['media' => 'screen', 'template' => 'highest']);
		$this->assertEquals($expected, $mini);
	}

}
