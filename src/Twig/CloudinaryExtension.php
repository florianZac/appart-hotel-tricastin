<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @author      Florian Aizac
 * @created     10/04/2026
 * @description Extension Twig pour gérer l'affichage des images hébergées sur
 *              Cloudinary dans les templates du projet Appart Hôtel Tricastin.
 *
 *              Filtre principal :
 *                  {{ valeur|image_url('carousel') }}
 *                  {{ valeur|image_url('w_800,h_500,c_fill,q_auto,f_auto') }}
 *
 *              Comportement du filtre :
 *                  1. URL Cloudinary complète (contient res.cloudinary.com) →
 *                     injecte les transformations entre "/upload/" et le reste.
 *                  2. Autre URL absolue (http/https) → renvoyée telle quelle.
 *                  3. Chemin relatif (ex: "Tulette/Tul_A_Urban-Nest/Urban-Nest.JPG")
 *                     → construit l'URL Cloudinary complète en préfixant par
 *                     le dossier racine "images/" (qui correspond à l'arborescence
 *                     existante sur le compte Cloudinary de Florian).
 *                  4. Valeur null/vide → placeholder Unsplash générique.
 *
 *              Presets disponibles :
 *                  - 'thumb'    : miniature 200x130
 *                  - 'card'     : carte 600x400
 *                  - 'carousel' : image de carrousel 800x500
 *                  - 'hero'     : bannière 1600x700
 */
class CloudinaryExtension extends AbstractExtension
{
	/**
	 * Presets de transformations Cloudinary courants.
	 */
	private const PRESETS = [
		'thumb'    => 'w_200,h_130,c_fill,q_auto,f_auto',
		'card'     => 'w_600,h_400,c_fill,q_auto,f_auto',
		'carousel' => 'w_800,h_500,c_fill,q_auto,f_auto',
		'hero'     => 'w_1600,h_700,c_fill,q_auto,f_auto',
	];

	/**
	 * Dossier racine sur Cloudinary qui contient toutes les images du projet.
	 * (Identique à l'arborescence locale sous public/images/)
	 */
	private const CLOUDINARY_ROOT_FOLDER = 'images';

	/**
	 * Placeholder utilisé lorsque la valeur est null/vide.
	 */
	private const PLACEHOLDER = 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&h=500&fit=crop';

	/**
	 * Nom du cloud Cloudinary (extrait de CLOUDINARY_URL).
	 * Ex: "dn1htcx9r"
	 */
	private string $cloudName;

	public function __construct(string $cloudinaryUrl)
	{
		// Extrait le cloud name depuis une URL du type
		// cloudinary://{api_key}:{api_secret}@{cloud_name}
		$parsed = parse_url($cloudinaryUrl);
		$this->cloudName = $parsed['host'] ?? '';
	}

	public function getFilters(): array
	{
		return [
			new TwigFilter('image_url', [$this, 'imageUrl']),
		];
	}

	/**
	 * Construit l'URL finale à afficher dans un <img src="...">.
	 *
	 * @param string|null $value           Valeur stockée en base (URL Cloudinary
	 *                                     complète, URL externe, chemin relatif,
	 *                                     ou null)
	 * @param string      $transformations Preset ('card', 'thumb'...) ou
	 *                                     transformations brutes Cloudinary
	 *                                     ('w_800,h_500,c_fill,q_auto,f_auto')
	 * @param string|null $fallback        URL de repli personnalisée (optionnel)
	 */
	public function imageUrl(?string $value, string $transformations = '', ?string $fallback = null): string
	{
		// 1. Valeur vide → placeholder
		if ($value === null || trim($value) === '') {
			return $fallback ?? self::PLACEHOLDER;
		}

		// 2. Résolution d'un éventuel preset nommé
		$tr = self::PRESETS[$transformations] ?? $transformations;

		// 3. URL Cloudinary déjà complète → injection des transformations après /upload/
		if (str_contains($value, 'res.cloudinary.com') && str_contains($value, '/upload/')) {
			if ($tr === '') {
				return $value;
			}
			$pos = strpos($value, '/upload/');
			return substr($value, 0, $pos + 8) . $tr . '/' . substr($value, $pos + 8);
		}

		// 4. Autre URL absolue → telle quelle
		if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
			return $value;
		}

		// 5. Chemin relatif → construction de l'URL Cloudinary complète
		//    public_id = "images/{chemin_relatif}" (Cloudinary accepte l'URL avec
		//    l'extension de fichier, et f_auto gère la conversion de format
		//    côté CDN selon le navigateur).
		if ($this->cloudName === '') {
			// Sécurité : si le cloud name n'a pas pu être extrait, on retombe
			// sur l'ancien comportement local pour ne rien casser.
			return '/images/' . ltrim($value, '/');
		}

		$path = self::CLOUDINARY_ROOT_FOLDER . '/' . ltrim($value, '/');
		$transformationSegment = $tr !== '' ? $tr . '/' : '';

		return sprintf(
			'https://res.cloudinary.com/%s/image/upload/%s%s',
			$this->cloudName,
			$transformationSegment,
			$path
		);
	}
}
