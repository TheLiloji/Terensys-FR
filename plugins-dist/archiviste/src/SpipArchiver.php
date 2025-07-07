<?php

namespace Spip\Archiver;

/**
 * {@inheritDoc}
 * Implémentation des méthodes principales.
 */
class SpipArchiver extends AbstractArchiver implements ArchiverInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function informer(): array {
		$liste = [
			'proprietes' => [],
			'fichiers' => [
				/*
				 * filename
				 * checksum
				 * size
				 * mtime
				 * status
				 * raw
				 */
			],
			'commentaire' => ''
		];

		$archive = $this->archiveEnLecture();
		if ($archive) {
			if (1 !== $archive->open($this->fichier_archive, 'lecture')) {
				$this->setErreur(1);

				return $liste;
			}

			$liste['fichiers'] = $archive->list();
			$liste['proprietes']['racine'] = $this->trouverRacine(array_column($liste['fichiers'], 'filename'));
			$liste['commentaire'] = $archive->getComment();
			$archive->close();
		}

		return $liste;
	}

	/**
	 * {@inheritDoc}
	 */
	public function deballer(string $destination = '', array $fichiers = []): bool {
		if (!(is_dir($destination) && is_writable($destination))) {
			$this->setErreur(5);

			return false;
		}

		$archive = $this->archiveEnLecture();
		if ($archive) {
			if (1 === $archive->open($this->fichier_archive, 'lecture')) {
				$retour = $archive->extractTo($destination, $fichiers);
				$archive->close();

				return $retour;
			}

			$this->setErreur(1);
		}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function emballer(array $chemins = [], ?string $racine = null): bool {
		$archive = null;
		$mode = 'creation';
		if (\file_exists($this->fichier_archive)) {
			$this->setErreur(6);
		} elseif (is_writable(dirname($this->fichier_archive))) {
			if ('' === $this->mode_compression) {
				$this->mode_compression = (string) preg_replace(',.+\.([^.]+)$,', '$1', $this->fichier_archive);
			}

			$archive = $this->getArchive();
		} else {
			$this->setErreur(4);
		}

		if (!$archive) {
			return false;
		}

		$source = null;
		if (\array_is_list($chemins)) {
			$source = is_null($racine) ? ($this->trouverRacine($chemins) ?: '.') : $racine;
			if (!(is_dir($source) && is_readable($source))) {
				$this->setErreur(7);

				return false;
			}
		}

		$retour = false;
		if (1 === $archive->open($this->fichier_archive, $mode)) {
			// On établit le tableau [source=>destination] des fichiers en traitant les éventuels répertoires.
			$fichiers = $this->listerFichiers($chemins);
			// On archive la liste des fichiers
			$retour = $archive->compress('', $fichiers);
			$archive->close();
		}
		$this->setErreur(intval(!$retour));

		return $retour;
	}

	/**
	 * {@inheritDoc}
	 */
	public function retirer(array $fichiers = []): bool {
		$retour = false;
		$archive = $this->archiveEnEcriture();

		if ($archive) {
			if (1 === $archive->open($this->fichier_archive, 'edition')) {
				// Vérifier qu'on ne cherche pas à vider l'archive
				$reste = $this->informer();
				$fichiers_restants = array_column($reste['fichiers'], 'filename');
				if (0 !== count(array_diff($fichiers_restants, $fichiers))) {
					$retour = $archive->remove($fichiers);
					$archive->close();
				} else {
					$this->setErreur(8);
				}
			}
		}

		return $retour;
	}

	/**
	 * {@inheritDoc}
	 */
	public function commenter(string $texte = ''): bool {
		$retour = false;
		$archive = $this->archiveEnEcriture();
		if ($archive) {
			if (1 === $archive->open($this->fichier_archive, 'edition')) {
				$retour = $archive->setComment($texte);
				$archive->close();
			}
		}

		if (!$retour) {
			$this->setErreur(2);
		}

		return $retour;
	}
}
