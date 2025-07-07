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
class CompresseurSvgEmbedTest extends TestCase {
	public static function setUpBeforeClass(): void {
		include_spip('compresseur_fonctions');
	}

	public static function providerSvgEmbeds() {
		$data = [];

		$dirSource = __DIR__ . '/data/svg_embed/source/';
		$dirExpected = __DIR__ . '/data/svg_embed/expected/';

		$sourceFiles = glob($dirSource . '*.svg');

		foreach ($sourceFiles as $sourceFile) {
			$name = basename($sourceFile);
			$expectedFile = $dirExpected . basename($sourceFile, '.svg') . '.txt';
			if (file_exists($expectedFile)) {
				$source = realpath($sourceFile);
				$expected = file_get_contents($expectedFile);
				$data["$name"] = [$source, $expected];
			}
		}

		return $data;
	}

	/**
	 * @dataProvider providerSvgEmbeds
	 */
	public function testSvgEmbeds($source, $expected) {

		$encoded = filtre_embarque_fichier($source);
		$this->assertEquals($expected, $encoded);
	}
}
