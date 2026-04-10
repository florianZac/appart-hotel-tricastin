<?php

namespace App\Command;

use App\Service\CloudinaryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * @author      Florian Aizac
 * @created     10/04/2026
 * @description Commande de synchronisation des images locales vers Cloudinary.
 *
 *  Parcourt récursivement public/images/, supprime (sauf --no-clean) toutes
 *  les ressources Cloudinary existantes sous le préfixe "images/", puis
 *  ré-uploade chaque fichier en forçant un public_id qui correspond
 *  exactement au chemin relatif local.
 *
 *  Exemple de correspondance :
 *      public/images/Pont_saint_esprit/Pont_2e_le-vignoble/le-vignoble.jpg
 *          → public_id Cloudinary : images/Pont_saint_esprit/Pont_2e_le-vignoble/le-vignoble
 *          → URL finale : https://res.cloudinary.com/<cloud>/image/upload/images/Pont_saint_esprit/Pont_2e_le-vignoble/le-vignoble.jpg
 *
 *  Options :
 *      --dry-run  : affiche les actions sans rien modifier
 *      --no-clean : conserve les ressources existantes sur Cloudinary
 *      --force    : saute la demande de confirmation
 *
 *  Usage :
 *      php bin/console app:cloudinary:sync-images
 *      php bin/console app:cloudinary:sync-images --dry-run
 *      php bin/console app:cloudinary:sync-images --force
 */
#[AsCommand(
  name: 'app:cloudinary:sync-images',
  description: 'Supprime et ré-uploade toutes les images de public/images/ sur Cloudinary avec des public_ids qui correspondent exactement aux chemins locaux.'
)]
class CloudinaryMigrateCommand extends Command
{
  /**
   * Dossier racine sur Cloudinary qui contiendra toutes les images.
   * Identique au dossier local public/images/.
   */
  private const ROOT_FOLDER = 'images';

  /**
   * Extensions de fichiers acceptées (insensible à la casse).
   */
  private const ALLOWED_EXTENSIONS_REGEX = '/\.(jpg|jpeg|png|webp)$/i';

  public function __construct(
    private readonly CloudinaryService $cloudinary,
    private readonly string $projectDir,
  ) {
    parent::__construct();
  }

  protected function configure(): void
  {
    $this
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les actions sans rien modifier sur Cloudinary')
      ->addOption('no-clean', null, InputOption::VALUE_NONE, 'Ne supprime pas les ressources existantes avant l\'upload')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'Saute la demande de confirmation');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);

    $dryRun  = (bool) $input->getOption('dry-run');
    $noClean = (bool) $input->getOption('no-clean');
    $force   = (bool) $input->getOption('force');

    $imagesDir = $this->projectDir . '/public/images';

    if (!is_dir($imagesDir)) {
      $io->error(sprintf('Le dossier "%s" n\'existe pas.', $imagesDir));
      return Command::FAILURE;
    }

    $io->title('Synchronisation des images vers Cloudinary');

    if ($dryRun) {
      $io->warning('Mode DRY-RUN activé : aucune modification ne sera effectuée sur Cloudinary.');
    }

    // ---- 1. Inventaire des fichiers locaux ----
    $finder = (new Finder())
      ->files()
      ->in($imagesDir)
      ->name(self::ALLOWED_EXTENSIONS_REGEX);

    /** @var \Symfony\Component\Finder\SplFileInfo[] $files */
    $files = iterator_to_array($finder, false);
    $count = count($files);

    $io->text(sprintf('Fichiers trouvés dans <info>public/images/</info> : <comment>%d</comment>', $count));

    if ($count === 0) {
      $io->warning('Aucun fichier à uploader. Arrêt.');
      return Command::SUCCESS;
    }

    // ---- 2. Confirmation ----
    if (!$dryRun && !$force) {
      $confirmMessage = $noClean
        ? sprintf('Uploader %d fichier(s) sur Cloudinary sous le préfixe "%s/" ?', $count, self::ROOT_FOLDER)
        : sprintf('⚠️  SUPPRIMER tous les assets Cloudinary sous "%s/" puis uploader %d fichier(s) ?', self::ROOT_FOLDER, $count);

      if (!$io->confirm($confirmMessage, false)) {
        $io->text('Opération annulée.');
        return Command::SUCCESS;
      }
    }

    // ---- 3. Nettoyage des assets existants ----
    if (!$noClean) {
      $io->section(sprintf('Suppression des ressources existantes sous "%s/"', self::ROOT_FOLDER));

      if ($dryRun) {
        $io->text(sprintf('[DRY-RUN] cloudinary.deleteByPrefix("%s/")', self::ROOT_FOLDER));
      } else {
        $deleted = $this->cloudinary->deleteByPrefix(self::ROOT_FOLDER . '/');
        $io->text(sprintf('<info>%d</info> ressource(s) supprimée(s) sur Cloudinary.', $deleted));
      }
    }

    // ---- 4. Upload des nouveaux fichiers ----
    $io->section('Upload vers Cloudinary');
    $io->progressStart($count);

    $errors   = [];
    $uploaded = 0;

    foreach ($files as $file) {
      // Chemin relatif depuis public/images/, normalisé en slash UNIX (au cas où la
      // commande est lancée depuis un poste Windows, le Finder peut renvoyer des \).
      $relativePath = str_replace('\\', '/', $file->getRelativePathname());

      // public_id = "images/<chemin relatif sans extension>"
      $publicId = self::ROOT_FOLDER . '/' . preg_replace('/\.[^.]+$/', '', $relativePath);

      if ($dryRun) {
        $io->newLine();
        $io->text(sprintf('  [DRY-RUN] %s → <info>%s</info>', $relativePath, $publicId));
      } else {
        try {
          $this->cloudinary->uploadWithPublicId($file->getPathname(), $publicId);
          $uploaded++;
        } catch (\Throwable $e) {
          $errors[] = sprintf('%s : %s', $relativePath, $e->getMessage());
        }
      }

      $io->progressAdvance();
    }

    $io->progressFinish();

    // ---- 5. Bilan ----
    if (!empty($errors)) {
      $io->error(sprintf('%d erreur(s) pendant l\'upload :', count($errors)));
      foreach ($errors as $err) {
        $io->text('  • ' . $err);
      }
      return Command::FAILURE;
    }

    if ($dryRun) {
      $io->success(sprintf('[DRY-RUN] %d fichier(s) auraient été uploadés.', $count));
    } else {
      $io->success(sprintf('%d fichier(s) synchronisé(s) avec succès sur Cloudinary.', $uploaded));
    }

    return Command::SUCCESS;
  }
}
