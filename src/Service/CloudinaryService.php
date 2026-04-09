<?php

namespace App\Service;

use Cloudinary\Cloudinary;

/**
 * @author      Florian Aizac
 * @created     09/04/2026
 * @description Service gérant l'upload/suppression d'images via l'API Cloudinary
 *              pour le projet Appart Hôtel Tricastin
 *
 *  1. upload()              : Upload une image vers Cloudinary
 *  2. deleteByUrl()         : Supprime une image de Cloudinary à partir de son URL
 *  3. extractPublicId()     : Extrait le public_id d'une URL Cloudinary
 *  4. uploadMultiple()      : Upload plusieurs images (galerie)
 */
class CloudinaryService
{
	private Cloudinary $cloudinary;

	public function __construct(string $cloudinaryUrl)
	{
		$this->cloudinary = new Cloudinary($cloudinaryUrl);
	}

	/**
	 * Upload une image vers Cloudinary
	 *
	 * @param string $filePath Chemin local du fichier temporaire
	 * @param string $folder   Dossier de destination sur Cloudinary
	 * @return string L'URL publique (secure_url) de l'image uploadée
	 */
	public function upload(string $filePath, string $folder = 'appart-hotel-tricastin/appartements'): string
	{
		$result = $this->cloudinary->uploadApi()->upload($filePath, [
			'folder'           => $folder,
			'resource_type'    => 'image',
			'allowed_formats'  => ['jpg', 'jpeg', 'png', 'webp'],
			'transformation'   => [
				'quality' => 'auto:good',
				'fetch_format' => 'auto',
			],
		]);

		return $result['secure_url'];
	}

	/**
	 * Upload plusieurs images d'un coup (pour galerie appartement)
	 *
	 * @param array  $filePaths Liste de chemins de fichiers temporaires
	 * @param string $folder    Dossier de destination
	 * @return array Liste des URLs publiques
	 */
	public function uploadMultiple(array $filePaths, string $folder = 'appart-hotel-tricastin/appartements'): array
	{
		$urls = [];
		foreach ($filePaths as $filePath) {
			$urls[] = $this->upload($filePath, $folder);
		}
		return $urls;
	}

	/**
	 * Supprime une image de Cloudinary à partir de son URL
	 *
	 * @param string $url L'URL complète de l'image Cloudinary
	 * @return bool true si supprimée, false sinon
	 */
	public function deleteByUrl(string $url): bool
	{
		$publicId = $this->extractPublicId($url);

		if (!$publicId) {
			return false;
		}

		try {
			$result = $this->cloudinary->uploadApi()->destroy($publicId);
			return ($result['result'] === 'ok');
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Extrait le public_id d'une URL Cloudinary
	 * Ex: https://res.cloudinary.com/xxx/image/upload/v123/appart-hotel-tricastin/appartements/abc.jpg
	 * → appart-hotel-tricastin/appartements/abc
	 */
	private function extractPublicId(string $url): ?string
	{
		if (strpos($url, 'cloudinary.com') === false) {
			return null;
		}

		$parts = explode('/upload/', $url);
		if (count($parts) < 2) {
			return null;
		}

		// Retire le versioning (v123456789/)
		$path = preg_replace('/^v\d+\//', '', $parts[1]);

		// Retire l'extension du fichier
		$publicId = preg_replace('/\.[^.]+$/', '', $path);

		return $publicId;
	}
}
