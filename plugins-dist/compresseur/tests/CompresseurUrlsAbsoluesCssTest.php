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
 * LegacyUnitPhpTest test - runs all the unit/ php tests and check the ouput is 'OK'
 *
 */
class CompresseurUrlsAbsoluesCssTest extends TestCase {

	protected static $urlBase;

	public static function setUpBeforeClass(): void{
		include_spip('inc/filtres');
		self::$urlBase = 'http://example.org/squelettes/source.css';
	}

	public function providerUrlsAbsoluesCss() {
		$data = [];


		$dirSource = __DIR__ . "/data/urls_absolues_css/source/";
		$dirExpected = __DIR__ . "/data/urls_absolues_css/expected/";

		$sourceFiles = glob($dirSource . "*.css");

		foreach ($sourceFiles as $sourceFile) {
			$name = basename($sourceFile);
			$expectedFile = $dirExpected . $name;
			if (file_exists($expectedFile)) {
				$source = file_get_contents($sourceFile);
				$expected = file_get_contents($expectedFile);
				$data["$name"] = [$source, $expected];
			}
		}

		return $data;
	}

	/**
	 * @dataProvider providerUrlsAbsoluesCss
	 */
	public function testUrlsAbsoluesCss($source, $expected) {

		$abs = urls_absolues_css($source, self::$urlBase);
		$this->assertEquals($expected, $abs);
	}
}
