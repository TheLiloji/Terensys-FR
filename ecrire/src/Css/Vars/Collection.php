<?php

namespace Spip\Css\Vars;

/**
 * Collection de variables CSS
 * @internal
 */
class Collection {
	private array $vars = [];

	public function add(string $var, string $value) {
		$this->vars[$var] = $value;
	}

	/**
	 * Ajoute un chemin d’image
	 */
	public function addImage(string $var, string $value) {
		$this->addPath($var, $this->cheminImage($value));
	}

	/**
	 * Ajoute une variable de type url (chemin)
	 */
	public function addPath(string $var, string $value) {
		$this->add($var, $this->quoteString($value));
	}

	public function getString(): string {
		$string = '';
		foreach ($this->vars as $key => $value) {
			$string .= "$key: $value;\n";
		}
		return $string;
	}

	public function __toString(): string {
		return $this->getString();
	}


	protected function cheminImage(string $image): string {
		return protocole_implicite(url_absolue(chemin_image($image)));
	}

	/*
	 * Enveloppe la valeur reçue entre guillemets doubles
	 */
	protected function quoteString(string $value): string {
		return '"' . addslashes($value) . '"';
	}
}
