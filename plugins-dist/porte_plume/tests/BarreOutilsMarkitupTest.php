<?php

namespace Spip\PortePlume\Tests;

use PHPUnit\Framework\TestCase;
use Barre_outils;

class BarreOutilsMarkitupTest extends TestCase {

	public static function setUpBeforeClass(): void {
		// instancier une barre d'outil
		include_spip('porte_plume_fonctions');
	}

	private function getSimpleDefinition(): array {
		return [
			'nameSpace' => 'spip',
			'markupSet' => [
				// H1 - {{{
				[
					"id" => 'header1',
					"name" => _T('barreoutils:barre_intertitre'),
					"key" => "H",
					"className" => "outil_header1",
					"openWith" => "{{{",
					"closeWith" => "}}}",
					"display" => true,
				]
			]
		];
	}

	private function getExtendedDefinition(): array {
		$bar = $this->getSimpleDefinition();
		$bar['markupSet'][1] = [
			"id" => 'couleurs',
			"name" => _T('barreoutils:barre_couleur'),
			"key" => "C",
			"className" => "outil_couleur",
			"openWith" => '[color=[![Color]!]]',
			"closeWith" => '[/color]',
			"display" => true,
			"dropMenu" => [
				[
					"id" => "couleur_jaune",
					"name" => 'Yellow',
					"openWith" => '[color=yellow]',
					"closeWith" => '[/color]',
					"className" => "outil_couleur",
					"display" => true,
				],
				[
					"id" => "couleur_orange",
					"name" => 'Orange',
					"openWith" => '[color=orange]',
					"closeWith" => '[/color]',
					"className" => "outil_couleur",
					"display" => true,
				],
				[
					"id" => "couleur_rouge",
					"name" => 'Red',
					"openWith" => '[color=red]',
					"closeWith" => '[/color]',
					"className" => "outil_couleur",
					"display" => true,
				],
			],
		];
		return $bar;
	}

	public function testInitialisationBarre(): void {
		// parametres inseres a leur bonne place
		$b = new Barre_outils($this->getSimpleDefinition());
		$this->AssertEquals('spip', $b->nameSpace);
		$this->AssertEquals('header1', $b->markupSet[0]['id']);
		$this->AssertEquals(7, is_countable($b->markupSet[0]) ? count($b->markupSet[0]) : 0);
	}

	public function testInitialisationBarreEtendue(): void {
		// parametres inseres a leur bonne place,
		// meme quand il y a des sous-menu d'icones
		$b = new Barre_outils($this->getExtendedDefinition());
		$this->AssertEquals('spip', $b->nameSpace);
		$this->AssertEquals('header1', $b->markupSet[0]['id']);
		$this->AssertEquals(7, is_countable($b->markupSet[0]) ? count($b->markupSet[0]) : 0);
		$this->AssertEquals('couleurs', $b->markupSet[1]['id']);
		$this->AssertEquals(3, is_countable($b->markupSet[1]['dropMenu']) ? count($b->markupSet[1]['dropMenu']) : 0);
	}

	public function testOptionsIncorrectesNonInclusesVariable(): void {
		$p = $this->getSimpleDefinition();
		$p['fausseVariable'] = "je ne dois pas m'installer";
		$b = new Barre_outils($p);
		$this->AssertEquals('spip', $b->nameSpace);

		$this->expectError();
		$this->expectErrorMessage('Undefined property: Barre_outils::$fausseVariable');
		$b->fausseVariable;

		$this->AssertEquals(7, count($b->markupSet[0] ?? []));
	}

	public function testOptionsIncorrectesNonInclusesParam(): void {
		$p = $this->getSimpleDefinition();
		$p['markupSet'][0]['fauxParam'] = "je ne dois pas m'installer";
		$b = new Barre_outils($p);
		$this->AssertEquals('spip', $b->nameSpace);

		$this->expectError();
		$this->expectErrorMessageMatches('/Undefined ((array key "fauxParam")|(index: fauxParam))/');

		$b->markupSet[0]['fauxParam'];

		$this->AssertEquals(7, count($b->markupSet[0] ?? []));
	}

	public function testRecuperationDeParametreAvecGet(): void {
		// trouver des id de premier niveau
		$p = $this->getSimpleDefinition();
		$b = new Barre_outils($p);
		$this->AssertEquals($b->get('header1'), $p['markupSet'][0]);

		// trouver des id de second niveau
		$p = $this->getExtendedDefinition();
		$b = new Barre_outils($p);
		$this->AssertEquals($b->get('header1'), $p['markupSet'][0]);
		$this->AssertEquals($b->get('couleurs'), $p['markupSet'][1]);
		$this->AssertEquals($b->get('couleur_jaune'), $p['markupSet'][1]['dropMenu'][0]);
		$this->AssertEquals($b->get('couleur_orange'), $p['markupSet'][1]['dropMenu'][1]);
		$this->AssertEquals($b->get('couleur_rouge'), $p['markupSet'][1]['dropMenu'][2]);

		// ne pas trouver d'id inconnu
		$this->assertFalse($b->get('je_nexiste_pas'));
	}

	public function testModificationDeParametresAvecSet(): void {
		$p = $this->getSimpleDefinition();
		$b = new Barre_outils($p);
		$p['markupSet'][0]['name'] = 'New';
		$r = $p['markupSet'][0];
		$x = $b->set('header1', ["name" => "New"]);

		$this->AssertEquals($r, $x); // set retourne la chaine modifiee complete
		$this->AssertEquals($r, $b->get('header1'));

		// on ne peut ajouter de mauvais parametres
		$x = $b->set('header1', ["Je Suis Pas Bon" => "No no no"]);
		$this->AssertEquals($r, $x); // set retourne la chaine modifiee complete
		$this->AssertEquals($r, $b->get('header1'));
	}

	public function testAjoutDeParametresApres(): void {
		$b = new Barre_outils($this->getSimpleDefinition());
		$p = $this->getExtendedDefinition();

		// ajoutons la couleur apres
		$b->ajouterApres('header1', $p['markupSet'][1]);
		$this->AssertEquals(2, count($b->markupSet)); // 2 boutons de premier niveau maintenant
		$this->AssertEquals($b->get('couleurs'), $p['markupSet'][1]); // get renvoie bien le bon ajout
		$this->AssertEquals($b->markupSet[1], $p['markupSet'][1]); // et l'ajout est au bon endroit

		// ajoutons une couleur dans l'ajout
		$coul = $p['markupSet'][1]['dropMenu'][0];
		$coul['id'] = 'couleur_violette';
		$b->ajouterApres('couleur_orange', $coul);
		$this->AssertEquals(4, is_countable($b->markupSet[1]['dropMenu']) ? count($b->markupSet[1]['dropMenu']) : 0); // sous boutons
		$this->AssertEquals($b->get('couleur_violette'), $coul);
		$this->AssertEquals($b->markupSet[1]['dropMenu'][2], $coul); // insertion au bon endroit

		// ajoutons un header2 encore apres
		$p['markupSet'][0]['id'] = 'header2';
		$b->ajouterApres('couleurs', $p['markupSet'][0]);
		$this->AssertEquals(3, count($b->markupSet));
		$this->AssertEquals($b->get('header2'), $p['markupSet'][0]);
		$this->AssertEquals($b->markupSet[2], $p['markupSet'][0]);
	}

	public function testAjoutDeParametresAvant(): void {
		$b = new Barre_outils($this->getSimpleDefinition());
		$p = $this->getExtendedDefinition();

		// ajoutons la couleur apres
		$b->ajouterAvant('header1', $p['markupSet'][1]);
		$this->AssertEquals(2, count($b->markupSet)); // 2 boutons de premier niveau maintenant
		$this->AssertEquals($b->get('couleurs'), $p['markupSet'][1]); // get renvoie bien le bon ajout
		$this->AssertEquals($b->markupSet[0], $p['markupSet'][1]); // et l'ajout est au bon endroit

		// ajoutons une couleur dans l'ajout
		$coul = $p['markupSet'][1]['dropMenu'][0];
		$coul['id'] = 'couleur_violette';
		$b->ajouterAvant('couleur_orange', $coul);
		$this->AssertEquals(4, is_countable($b->markupSet[0]['dropMenu']) ? count($b->markupSet[0]['dropMenu']) : 0); // sous boutons
		$this->AssertEquals($b->get('couleur_violette'), $coul);
		$this->AssertEquals($b->markupSet[0]['dropMenu'][1], $coul); // insertion au bon endroit

		// ajoutons un header2 avant le 1
		$p['markupSet'][0]['id'] = 'header2';
		$b->ajouterAvant('header1', $p['markupSet'][0]);
		$this->AssertEquals(3, count($b->markupSet));
		$this->AssertEquals($b->get('header2'), $p['markupSet'][0]);
		$this->AssertEquals($b->markupSet[1], $p['markupSet'][0]);
	}

	public function testAfficherEtCacher(): void {
		$b = new Barre_outils($this->getSimpleDefinition());
		$b->cacher('header1');
		$this->assertFalse($b->markupSet[0]['display']);
		$b->afficher('header1');
		$this->assertTrue($b->markupSet[0]['display']);
	}

	public function testAfficherEtCacherTout(): void {
		$b = new Barre_outils($this->getExtendedDefinition());
		$b->cacherTout();
		$this->assertFalse($b->markupSet[0]['display']);
		$this->assertFalse($b->markupSet[1]['dropMenu'][0]['display']);

		$b->afficherTout();
		$this->assertTrue($b->markupSet[0]['display']);
		$this->assertTrue($b->markupSet[1]['dropMenu'][0]['display']);
	}

	public function testAfficherEtCacherPlusieursBoutons(): void {
		$b = new Barre_outils($this->getExtendedDefinition());
		$b->cacher(['header1', 'couleur_jaune']);
		$this->assertFalse($b->markupSet[0]['display']);
		$this->assertFalse($b->markupSet[1]['dropMenu'][0]['display']);
		$this->assertTrue($b->markupSet[1]['dropMenu'][1]['display']);

		$b->cacherTout();
		$b->afficher(['header1', 'couleur_jaune']);
		$this->assertTrue($b->markupSet[0]['display']);
		$this->assertTrue($b->markupSet[1]['dropMenu'][0]['display']);
		$this->assertFalse($b->markupSet[1]['dropMenu'][1]['display']);
	}

	public function testSetAvecIdVideNeDoitRienModifier(): void {
		$b = new Barre_outils($this->getExtendedDefinition());
		$b->set([], ['display' => false]);
		$this->assertTrue($b->markupSet[0]['display']);
		$this->assertTrue($b->markupSet[1]['dropMenu'][0]['display']);
	}

	public function testSetAvecIdArrayDoitModifTousLesIds(): void {
		$b = new Barre_outils($this->getExtendedDefinition());
		$b->set(['header1', 'couleur_jaune'], ['display' => false]);
		$this->assertFalse($b->markupSet[0]['display']);
		$this->assertFalse($b->markupSet[1]['dropMenu'][0]['display']);
		$this->assertTrue($b->markupSet[1]['dropMenu'][1]['display']);
	}

	public function testCreerJson(): void {
		$b = new Barre_outils($this->getSimpleDefinition());
		$b->ajouterApres('header1', [
			"id" => 'Caracteres decodes',
			"name" => "&eacute;trange",
			"className" => "outil_fr",
			"openWith" => "[fr]",
			"display" => true,
		]);
		$json = $b->creer_json();
		$this->assertMatchesRegularExpression(',barre_outils_spip = {,', $json);
		$this->assertMatchesRegularExpression(',"name": ",', $json);
		$this->assertDoesNotMatchRegularExpression(',eacute;,', $json);
	}

	public function testBoutonsDUneLangue(): void {
		$b = new Barre_outils($this->getSimpleDefinition());
		$ico2 = $ico1 = [
			"id" => 'ico_fr1',
			"name" => "test apparaissant si langue est le francais",
			"className" => "outil_fr",
			"openWith" => "[fr]",
			"lang" => ["fr"],
			"display" => true,
		];
		$ico2['id'] = 'ico_fr2';
		$ico2['lang'] = ["fr", "en", "es"];

		$b->ajouterApres('header1', $ico1);
		$b->ajouterApres('ico_fr1', $ico2);
		$this->AssertEquals($ico1, $b->get('ico_fr1'));
		$this->AssertEquals($ico2, $b->get('ico_fr2'));

		// verifier que ces nouveaux [)
		// ne posent pas de problemes dans les recursions
		$b->cacherTout();
		$this->assertFalse($b->markupSet[1]['display']);
		$b->afficher('ico_fr1');
		$this->assertTrue($b->markupSet[1]['display']);
		$b->cacherTout();
		$b->afficher(['ico_fr1', 'ico_fr2']);
		$this->assertTrue($b->markupSet[1]['display']);

		// la langue est bien transmise au json
		$json = $b->creer_json();
		$this->assertMatchesRegularExpression(',"lang": \[,', $json);
	}

	public function testFonctionsJavacriptDansParametreNeDoitPasEtreEntreguillemetsDansJson(): void {
		$b = new Barre_outils($this->getSimpleDefinition());
		$clean = [
			"id" => 'clean',
			"name" => _T('barreoutils:barre_clean'),
			"className" => "outil_clean",
			// function doit etre echappe
			"replaceWith" => 'function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") }',
			"display" => true,
		];
		$b->ajouterApres('header1', $clean);
		$json = $b->creer_json();
		// pas de : "function(... ..."
		$this->assertMatchesRegularExpression('/: function\(/', $json);
	}

	public function testParametreFunctionsDansJson(): void {
		$b = new Barre_outils($this->getSimpleDefinition());
		$b->functions = "function dido(){}";
		$json = $b->creer_json();

		// function n'est plus dans la barre
		$this->expectError();
		$this->expectErrorMessage('Undefined property: Barre_outils::$functions');

		$b->functions;
		// mais uniquement en fin du fichier json
		$this->assertMatchesRegularExpression('/function dido\(/', $json);
	}

	public function testAjouterFonctions(): void {
		$b = new Barre_outils($this->getSimpleDefinition());
		$b->ajouterFonction("function dido(){}");
		$this->assertMatchesRegularExpression('/function dido\(/', $b->functions);
	}
}
