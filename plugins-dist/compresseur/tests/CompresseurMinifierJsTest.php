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
 * CompresseurMinifierJsTest test - test la minification JS
 *
 */
class CompresseurMinifierJsTest extends TestCase {

	public static function setUpBeforeClass(): void{
		include_spip('inc/compresseur_minifier');
	}

	public function testLegacyLayerjs() {

		$sourceFile = find_in_path('javascript/layer.js');
		$this->assertNotEmpty($sourceFile);

		$source = file_get_contents($sourceFile);
		$this->assertNotEmpty($source);
		$mini = minifier_js($source);
		$this->assertNotEmpty($mini);


		// verifier qu'on a pas perdu la fonction AjaxSqueeze
		$this->assertMatchesRegularExpression(',function AjaxSqueeze,', $mini);

		// verifier qu'un commentaire present dans le source et bien enleve de la minification
		$this->assertStringContainsString('Son premier argument', $source, "$sourceFile ne contient pas le commentaire contenant 'Son premier argument'");
		$this->assertStringNotContainsString('Son premier argument', $mini, "la minification de $sourceFile contient encore des commentaires");
	}

}
