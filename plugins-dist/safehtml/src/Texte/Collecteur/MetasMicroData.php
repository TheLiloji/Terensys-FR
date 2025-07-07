<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 * \***************************************************************************/

namespace Textwheel\Texte\Collecteur;

use Spip\Texte\Collecteur\AbstractCollecteur;

/**
 * Collecte et echappe les <meta itemprop='..' content='...' /> des microsdata
 * (et uniquement ceux là)
 *
 */
class MetasMicroData extends AbstractCollecteur {

	protected static string $markPrefix = 'METAMICRODATA';

	/**
	 * La preg pour découper et collecter les modèles
	 * @var string
	 */
	protected string $preg_meta;

	public function __construct(?string $preg = null) {

		$this->preg_meta = ($preg ?: '@<meta[^>]*/?>@isS');
	}

	/**
	 * Sanitizer une collection d'occurences de multi : on sanitize chaque texte de langue séparemment
	 *
	 * @param array $collection
	 * @param string $sanitize_callback
	 * @return array
	 */
	protected function sanitizer_collection(array $collection, string $sanitize_callback): array {
		return $collection;
	}


	/**
	 * @param string $texte
	 * @param array $options
	 *   bool $collecter_liens
	 * @return array
	 */
	public function collecter(string $texte, array $options = []): array {
		if (!$texte) {
			return [];
		}

		// collecter les matchs de la preg
		$metas = $this->collecteur($texte, '', '<meta', $this->preg_meta);

		if (!function_exists('extraire_attribut')) {
			include_spip('inc/filtres');
		}

		foreach ($metas as $k => &$meta) {

			$tag = $meta['raw'];
			// on n'accepte que les <meta /> qui ont un itemprop et un content
			if (
				  $itemprop = extraire_attribut($meta['raw'], 'itemprop')
			  and ($content = extraire_attribut($meta['raw'], 'content')) !== null
			) {
				if (!empty($options['detecter_presence'])) {
					// si on veut seulement detecter la présence, on peut retourner tel quel avec cette meta verifiee
					return [$meta];
				}
				$tag = vider_attribut($tag, 'itemprop');
				$tag = vider_attribut($tag, 'content');
				// si la balise contient d'autres attributs on la reecrit avec ces 2 seuls là
				if (strpos($tag, "=") !== false) {
					$tag = "<meta itemprop=\"".attribut_html($itemprop)."\" content=\"".attribut_html($content)."\" />";
					$meta['raw'] = $tag;
				}
			}
			else {
				unset($metas[$k]);
			}
		}
		return $metas;
	}

}
