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
 * TypographieFrTest test
 */
class AutoliensTest extends TestCase {
	protected static $lang = 'fr';
	protected static $filtrer_javascript;

	public static function setUpBeforeClass(): void{
		static::$filtrer_javascript = $GLOBALS['filtrer_javascript'];
		include_spip('inc/texte');
		changer_langue(static::$lang);
	}

	public function providerAutoLiens() {
		$list = [
			'https://www.spip.net' => '<p><a href="https://www.spip.net" class="spip_url spip_out auto" rel="nofollow external">https://www.spip.net</a></p>',
			'https://www.spip.net/' => '<p><a href="https://www.spip.net/" class="spip_url spip_out auto" rel="nofollow external">https://www.spip.net/</a></p>',
			'http://www.spip.net/' => '<p><a href="http://www.spip.net/" class="spip_url spip_out auto" rel="nofollow external">http://www.spip.net/</a></p>',
			'www.spip.net' => '<p><a href="http://www.spip.net" class="spip_url spip_out auto" rel="nofollow external">www.spip.net</a></p>',
			'http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=OJ:L:2013:020:0033:0043:FR:PDF' => '<p><a href="http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=OJ:L:2013:020:0033:0043:FR:PDF" class="spip_url spip_out auto" rel="nofollow external">http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=OJ:L:2013:020:0033:0043:FR:PDF</a></p>',
		];
		return array_map(null, array_keys($list), array_values($list));
	}


	/**
	 * @dataProvider providerAutoLiens
	 */
	public function testAutoliensNormal($source, $expected) {
		$GLOBALS['filtrer_javascript'] = 0;
		$this->assertEquals($expected, propre($source));
	}

	/**
	 * @dataProvider providerAutoLiens
	 */
	public function testAutoliensParano($source, $expected) {
		$GLOBALS['filtrer_javascript'] = -1;
		$this->assertEquals($expected, propre($source));
	}

	public static function tearDownAfterClass(): void{
		$GLOBALS['filtrer_javascript'] = static::$filtrer_javascript;
	}
}
