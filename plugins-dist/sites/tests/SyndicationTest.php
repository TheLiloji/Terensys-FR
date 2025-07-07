<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

namespace Spip\Core\Tests;

use PHPUnit\Framework\TestCase;


/**
 * LegacyUnitPhpTest test - runs all the unit/ php tests and check the ouput is 'OK'
 *
 */
class SyndicationTest extends TestCase {

	public function testAnalyseAtom() {
		include_spip('inc/syndic');
		$GLOBALS['controler_dates_rss'] = false;

		$xml = file_get_contents(__DIR__.'/data/test-atom1-1.xml');
		$rss = analyser_backend($xml);

		$this->assertEquals('http://localhost/spip/spip.php?article1', $rss[0]['url'], "erreur d'url item 0 sur test-atom1-1.xml");
		$this->assertEquals('delenda carthago',$rss[0]['titre'], "erreur de titre item 0 sur test-atom1-1.xml");
		$this->assertEquals(strtotime('2007-05-13T21:33:24Z'),$rss[0]['date'],"erreur de date item 0 sur test-atom1-1.xml");
		$this->assertEquals('Caton l ancien, Caton le jeune',$rss[0]['lesauteurs'],"erreur de lesauteurs item 0 sur test-atom1-1.xml");
		$this->assertStringStartsWith(
			'Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tatio',
			$rss[0]['descriptif'],
			"erreur de description item 0 sur test-atom1-1.xml"
		);
		$this->assertEquals('fr',$rss[0]['lang'],"erreur de langue item 0 sur test-atom1-1.xml");
		$this->assertEquals(
			'<a rel="enclosure" href="http://localhost/spip/IMG/txt/test-3.txt" type="text/plain" title="272">test-3.txt</a>',
			$rss[0]['enclosures'],
			"erreur d'enclosure item 0 sur test-atom1-1.xml"
		);

		// verfier les tags
		$erreurtag = "erreur de tag (num 0) item 0 sur test-atom1-1.xml";
		$this->assertNotEmpty($rss[0]['tags'][0],$erreurtag);
		$this->assertEquals('http://localhost/spip/rub1',extraire_attribut($rss[0]['tags'][0], 'href'),$erreurtag);
		$this->assertEquals("directory",extraire_attribut($rss[0]['tags'][0], 'rel'),$erreurtag);
		$this->assertEquals('Nouvelle rubrique',supprimer_tags($rss[0]['tags'][0]), $erreurtag);

		$erreurtag = "erreur de tag (num 1) item 0 sur test-atom1-1.xml";
		$this->assertNotEmpty($rss[0]['tags'][1],$erreurtag);
		$this->assertEquals('http://localhost/spip/rubrique2',extraire_attribut($rss[0]['tags'][1], 'href'),$erreurtag);
		$this->assertEquals("directory",extraire_attribut($rss[0]['tags'][1], 'rel'),$erreurtag);
		$this->assertEquals('Nouvelle rubrique',supprimer_tags($rss[0]['tags'][1]), $erreurtag);

		$erreurtag = "erreur de tag (num 2, rubrique4) item 0 sur test-atom1-1.xml";
		$this->assertNotEmpty($rss[0]['tags'][2],$erreurtag);
		$this->assertEquals('http://localhost/spip/toto',extraire_attribut($rss[0]['tags'][2], 'href'),$erreurtag);
		$this->assertEquals("directory",extraire_attribut($rss[0]['tags'][2], 'rel'),$erreurtag);
		$this->assertEquals('Nouvelle rubrique4',supprimer_tags($rss[0]['tags'][2]), $erreurtag);

	}

	public function testAnalyseBackend() {
		include_spip('inc/syndic');
		$GLOBALS['controler_dates_rss'] = false;

		$rdf = file_get_contents(__DIR__.'/data/libre-en-fete.rdf');
		$rss = analyser_backend($rdf);

		$this->assertEquals(strtotime('2007-03-20T14:00+01:00'),$rss[0]['date'],"erreur de date item 0 sur libre-en-fete.rdf");
	}

	public function testAnalyseRss(){
		include_spip('inc/syndic');
		$GLOBALS['controler_dates_rss'] = false;

		$xml = file_get_contents(__DIR__ . '/data/test-rss2-1.xml');
		$rss = analyser_backend($xml);

		$this->assertEquals('http://localhost/spip/spip.php?article1', $rss[0]['url'], "erreur d'url item 0 sur test-rss2-1.xml");
		$this->assertEquals('delenda carthago', $rss[0]['titre'], "erreur de titre item 0 sur test-rss2-1.xml");
		$this->assertEquals(strtotime('2007-05-13T21:33:24Z'), $rss[0]['date'], "erreur de date item 0 sur test-rss2-1.xml");
		$this->assertEquals('Caton l ancien, Caton le jeune', $rss[0]['lesauteurs'], "erreur de lesauteurs item 0 sur test-rss2-1.xml");
		$this->assertStringStartsWith(
			'Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tatio',
			$rss[0]['descriptif'],
			"erreur de description item 0 sur test-rss2-1.xml");
		$this->assertEquals('fr', $rss[0]['lang'], "erreur de langue item 0 sur test-rss2-1.xml");
		$this->assertEquals('<a rel="enclosure" href="http://localhost/spip/IMG/txt/test-3.txt" type="text/plain" title="272">test-3.txt</a>', $rss[0]['enclosures'], "erreur d'enclosure item 0 sur test-rss2-1.xml");
		$this->assertEquals('<a href="http://localhost/spip/spip.php?rubrique1" rel="directory">Nouvelle rubrique</a>', $rss[0]['tags'][0], "erreur de tag item 0 sur test-rss2-1.xml");
	}

	public function testDailymotion(){
		include_spip('inc/syndic');
		$GLOBALS['controler_dates_rss'] = false;

		$xml = file_get_contents(__DIR__.'/data/dailymotion.rss');
		$rss = analyser_backend($xml);

		$this->assertCount(4,extraire_balises($rss[0]['enclosures'], 'a'),"mauvais compte d'enclosures sur le premier item");
	}
}