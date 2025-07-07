<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImagesPurger extends Command {
	protected array $images_obsoletes = [];

	protected function configure() {
		$this
			->setName('images:purger')
			->addOption(
				'from',
				null,
				InputOption::VALUE_REQUIRED,
				'Purger les images plus anciennes que cette date',
			)
			->setDescription('Purger les images temporaires de local/ plus anciennes que la date fournie en option from')
			->addUsage("--from='-1 year'")
			->addUsage("--from='-6 month'")
			->addUsage("--from='2024-01-01'")
			->addUsage("--from='2024-10'")
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$this->io->title('Purger les images temporaires obsolètes');

		$from = $input->getOption('from');
		if (!$from) {
			$this->io->error('Option --from requis');
			return self::FAILURE;
		}
		$from = strtotime($from);
		if (!$from) {
			$this->io->error('Option --from invalide');
			return self::FAILURE;
		} else {
			$this->io->care(sprintf('Purger avant le <info>%s</info>', date('Y-m-d H:i:s', $from)));
		}

		$dir_caches = [_DIR_VAR . 'cache-gd2/', _DIR_VAR . 'cache-vignettes/'];
		foreach ($dir_caches as $dir_cache) {
			$this->parcourirDossierCacheImages($dir_cache, $from);
		}

		$this->nettoyerCacheImagesObsoletes();

		return self::SUCCESS;
	}

	protected function parcourirDossierCacheImages($dir, $from, $recurs = true) {
		$dir = rtrim($dir, '/') . '/';
		$files = glob($dir . '*');
		foreach ($files as $file) {
			if (is_dir($file)) {
				if ($recurs) {
					$this->parcourirDossierCacheImages($file, $from, $recurs);
				}
			} else {
				if (filemtime($file) < $from) {
					$this->images_obsoletes[] = $file;
				}
			}
		}
	}

	protected function nettoyerCacheImagesObsoletes() {
		include_spip('inc/filtres');
		$nb_deleted = 0;
		$size_deleted = 0;
		foreach ($this->images_obsoletes as $file) {
			$nb_deleted++;
			$size_deleted += filesize($file);
			if (@unlink($file)) {
				$this->io->check("Supprimer: $file");
			} else {
				$this->io->fail("Supprimer: $file");
			}
		}

		$size = taille_en_octets($size_deleted);
		if ($nb_deleted) {
			$this->io->care($nb_deleted . ' fichiers supprimés' . ($size ? ' (' . $size . ')' : ''));
		} else {
			$this->io->care('0 fichier supprimé');
		}
	}
}
