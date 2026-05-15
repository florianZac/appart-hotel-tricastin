<?php

namespace App\Service;

use App\Entity\Appartement;
use App\Entity\Frais;
use App\Entity\Localisation;
use App\Repository\AppartementRepository;
use App\Repository\FraisRepository;
use App\Repository\LocalisationRepository;
use App\Repository\ReservationRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Génère un fichier Excel (.xlsx) comptable multi-onglets :
 *
 *  – 1 onglet par localisation  (ex: Pont-Saint-Esprit, Tulette, …)
 *  – 1 onglet récapitulatif global
 *
 * Structure de chaque onglet localisation :
 *  – Réservations mois par mois pour tous les appartements de la localisation
 *  – Taux d'occupation mensuel
 *  – Détail des frais
 *  – Bilan financier
 *
 * Structure de l'onglet récapitulatif :
 *  – Tableau comparatif : revenus, frais, résultat net, taux occupation — par localisation
 *  – Bilan global consolidé
 */
class ComptabiliteExporterXlsx
{
    // ── Palette couleurs ────────────────────────────────────────
    private const CLR_HEADER_BG   = '1A2744'; // bleu marine (entêtes)
    private const CLR_HEADER_FG   = 'FFFFFF';
    private const CLR_SECTION_BG  = 'C8A962'; // or (sections)
    private const CLR_SECTION_FG  = '1A2744';
    private const CLR_SUBTOTAL_BG = 'E8F4E8'; // vert clair (sous-totaux)
    private const CLR_TOTAL_BG    = '2C3E50'; // gris foncé (totaux)
    private const CLR_TOTAL_FG    = 'FFFFFF';
    private const CLR_RECAP_BG    = 'F0F4FF'; // bleu très clair (récap)
    private const CLR_ALTERNANCE  = 'F9F9F9'; // gris très clair (lignes paires)

    private const MOIS_FR = [
        1 => 'Janvier',  2 => 'Février',    3 => 'Mars',
        4 => 'Avril',    5 => 'Mai',         6 => 'Juin',
        7 => 'Juillet',  8 => 'Août',        9 => 'Septembre',
        10 => 'Octobre', 11 => 'Novembre',  12 => 'Décembre',
    ];

    public function __construct(
        private readonly ReservationRepository  $reservationRepo,
        private readonly FraisRepository        $fraisRepo,
        private readonly LocalisationRepository $localisationRepo,
        private readonly AppartementRepository  $appartementRepo,
    ) {}

    // ═══════════════════════════════════════════════════════════
    // POINT D'ENTRÉE
    // ═══════════════════════════════════════════════════════════

    /**
     * Génère la StreamedResponse téléchargeable.
     *
     * @param int               $annee
     * @param Localisation|null $localisationFiltre  null = toutes les localisations
     */
    public function exportXlsx(int $annee, ?Localisation $localisationFiltre = null): StreamedResponse
    {
        // Récupère les localisations à traiter
        $localisations = $localisationFiltre
            ? [$localisationFiltre]
            : $this->localisationRepo->findAllWithAppartements();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Appart Hôtel Tricastin')
            ->setTitle(sprintf('Comptabilité %d', $annee))
            ->setDescription('Export comptable généré automatiquement');

        // ── Supprime la feuille vide par défaut ────────────────
        $spreadsheet->removeSheetByIndex(0);

        // ── Données par localisation ───────────────────────────
        $donneesLocalisations = [];

        foreach ($localisations as $localisation) {
            $donneesLocalisations[] = $this->buildSheetLocalisation(
                $spreadsheet,
                $localisation,
                $annee
            );
        }

        // ── Onglet récapitulatif (toujours en dernier) ─────────
        $this->buildSheetRecapitulatif($spreadsheet, $donneesLocalisations, $annee);

        // ── Activer le premier onglet ──────────────────────────
        $spreadsheet->setActiveSheetIndex(0);

        // ── Nom du fichier ─────────────────────────────────────
        $suffixe  = $localisationFiltre
            ? '_' . $this->slugify($localisationFiltre->getVille())
            : '_tous';
        $filename = sprintf('comptabilite_tricastin%s_%d.xlsx', $suffixe, $annee);

        // ── StreamedResponse ───────────────────────────────────
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    // ═══════════════════════════════════════════════════════════
    // ONGLET PAR LOCALISATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Construit un onglet complet pour une localisation.
     * Retourne un tableau de données agrégées pour le récapitulatif.
     */
    private function buildSheetLocalisation(
        Spreadsheet  $spreadsheet,
        Localisation $localisation,
        int          $annee
    ): array {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($this->truncateTitle($localisation->getVille()));

        $appartements = $localisation->getAppartements()->filter(fn($a) => $a->isActif())->toArray();

        // Données pour cette localisation
        $reservations = $this->getReservationsParMois($annee, null, $localisation);
        $fraisDetail  = $this->fraisRepo->findByAnnee($annee);
        // Filtrer les frais liés aux appartements de cette localisation ou globaux
        $fraisLoc = array_filter($fraisDetail, function (Frais $f) use ($appartements) {
            if ($f->getAppartement() === null) return true; // frais globaux
            return in_array($f->getAppartement(), $appartements, true);
        });

        // ── En-tête de l'onglet ────────────────────────────────
        $row = 1;
        $this->writeTitleRow($sheet, $row,
            sprintf('BILAN COMPTABLE %d — %s', $annee, strtoupper($localisation->getVille())),
            8
        );
        $row++;
        $sheet->setCellValue("A{$row}", sprintf('%d appartement(s) : %s',
            count($appartements),
            implode(', ', array_map(fn($a) => $a->getNom(), $appartements))
        ));
        $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setColor(
            (new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB('555555')
        );
        $row += 2;

        // Accumulateurs annuels
        $totalRevenusAnnee     = 0.0;
        $totalJoursOccupes     = 0;
        $totalJoursDisponibles = 0;

        // ── Tableau : réservations mois par mois ───────────────
        for ($mois = 1; $mois <= 12; $mois++) {
            $joursDispoMois = $this->joursDisponiblesDansMois($annee, $mois) * max(1, count($appartements));
            $totalJoursDisponibles += $joursDispoMois;

            // Titre du mois
            $this->writeSectionRow($sheet, $row, self::MOIS_FR[$mois] . ' ' . $annee, 8);
            $row++;

            // En-têtes colonnes
            $headers = ['Client', 'N° Facture', 'Appartement', 'Arrivée', 'Départ', 'Nuits', 'Prix/nuit (€)', 'Total (€)'];
            $this->writeHeaderRow($sheet, $row, $headers);
            $row++;

            $joursOccupesMois = 0;
            $totalMois        = 0.0;
            $lignesPaires     = 0;

            if (!empty($reservations[$mois])) {
                foreach ($reservations[$mois] as $resa) {
                    $bg = ($lignesPaires % 2 === 0) ? null : self::CLR_ALTERNANCE;
                    $this->writeDataRow($sheet, $row, [
                        $resa['client'],
                        $resa['numFacture'],
                        $resa['appartement'],
                        $resa['dateArrivee'],
                        $resa['dateDepart'],
                        $resa['nbJours'],
                        $resa['prixUnitaire'],
                        $resa['totalHebergement'],
                    ], [6 => 'int', 7 => 'currency', 8 => 'currency'], $bg);

                    $joursOccupesMois += $resa['nbJours'];
                    $totalMois        += $resa['totalHebergement'];
                    $row++;
                    $lignesPaires++;
                }
            } else {
                $sheet->setCellValue("A{$row}", 'Aucune réservation');
                $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
                $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('888888');
                $row++;
            }

            // Sous-total du mois
            $taux = $joursDispoMois > 0
                ? round(($joursOccupesMois / $joursDispoMois) * 100, 1)
                : 0.0;

            $this->writeSubtotalRow($sheet, $row, [
                'SOUS-TOTAL ' . strtoupper(self::MOIS_FR[$mois]),
                '', '', '', '',
                $joursOccupesMois . ' nuits',
                sprintf('Taux : %.1f %%', $taux),
                $totalMois,
            ], [8 => 'currency']);
            $row += 2;

            $totalRevenusAnnee += $totalMois;
            $totalJoursOccupes += $joursOccupesMois;
        }

        // ── Récap taux d'occupation annuel ─────────────────────
        $this->writeSectionRow($sheet, $row, 'TAUX D\'OCCUPATION ANNUEL', 4);
        $row++;
        $this->writeHeaderRow($sheet, $row, ['Mois', 'Nuits occupées', 'Jours disponibles', 'Taux (%)']);
        $row++;

        for ($mois = 1; $mois <= 12; $mois++) {
            $joursDispo   = $this->joursDisponiblesDansMois($annee, $mois) * max(1, count($appartements));
            $joursOccupes = 0;
            if (!empty($reservations[$mois])) {
                foreach ($reservations[$mois] as $r) {
                    $joursOccupes += $r['nbJours'];
                }
            }
            $taux = $joursDispo > 0 ? round(($joursOccupes / $joursDispo) * 100, 1) : 0.0;
            $bg   = ($mois % 2 === 0) ? self::CLR_ALTERNANCE : null;
            $this->writeDataRow($sheet, $row,
                [self::MOIS_FR[$mois], $joursOccupes, $joursDispo, $taux . ' %'],
                [], $bg
            );
            $row++;
        }

        $tauxAnnuel = $totalJoursDisponibles > 0
            ? round(($totalJoursOccupes / $totalJoursDisponibles) * 100, 1)
            : 0.0;
        $this->writeTotalRow($sheet, $row, [
            'TOTAL ANNUEL', $totalJoursOccupes, $totalJoursDisponibles,
            sprintf('%.1f %%', $tauxAnnuel),
        ]);
        $row += 2;

        // ── Détail des frais ───────────────────────────────────
        $this->writeSectionRow($sheet, $row, 'DÉTAIL DES FRAIS — ' . $annee, 6);
        $row++;
        $this->writeHeaderRow($sheet, $row, ['Type', 'Libellé', 'Appartement', 'Périodicité', 'Mois', 'Montant (€)']);
        $row++;

        $totalFraisAnnee = 0.0;
        $ligne = 0;
        foreach ($fraisLoc as $frais) {
            $montant = (float) $frais->getMontant();
            $montantEffectif = $frais->getPeriodicite() === Frais::PERIODICITE_MENSUEL
                ? $montant * 12
                : $montant;
            $totalFraisAnnee += $montantEffectif;

            $bg = ($ligne % 2 === 0) ? null : self::CLR_ALTERNANCE;
            $this->writeDataRow($sheet, $row, [
                $frais->getTypeFraisLabel(),
                $frais->getLibelle(),
                $frais->getAppartement()?->getNom() ?? 'Global',
                Frais::PERIODICITE_LABELS[$frais->getPeriodicite()] ?? $frais->getPeriodicite(),
                $frais->getMois() ? self::MOIS_FR[$frais->getMois()] : '—',
                $montantEffectif,
            ], [6 => 'currency'], $bg);
            $row++;
            $ligne++;
        }

        $this->writeTotalRow($sheet, $row, ['TOTAL FRAIS', '', '', '', '', $totalFraisAnnee], [6 => 'currency']);
        $row += 2;

        // ── Bilan financier ────────────────────────────────────
        $this->writeSectionRow($sheet, $row, 'BILAN FINANCIER — ' . $annee, 6);
        $row++;

        $resultatNet = $totalRevenusAnnee - $totalFraisAnnee;

        $this->writeDataRow($sheet, $row, ['Revenus hébergement', $totalRevenusAnnee], [2 => 'currency']);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $this->writeDataRow($sheet, $row, ['Total frais', $totalFraisAnnee], [2 => 'currency']);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $resultStyle = $resultatNet >= 0 ? '00AA00' : 'CC0000';
        $this->writeTotalRow($sheet, $row, ['RÉSULTAT NET', $resultatNet], [2 => 'currency']);
        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB($resultStyle);
        $row++;

        // ── Largeurs de colonnes ───────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(13);
        $sheet->getColumnDimension('E')->setWidth(13);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(16);

        // Figer la première ligne
        $sheet->freezePane('A3');

        return [
            'localisation'    => $localisation->getVille(),
            'appartements'    => count($appartements),
            'revenus'         => $totalRevenusAnnee,
            'frais'           => $totalFraisAnnee,
            'resultatNet'     => $resultatNet,
            'joursOccupes'    => $totalJoursOccupes,
            'joursDispo'      => $totalJoursDisponibles,
            'tauxOccupation'  => $tauxAnnuel,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // ONGLET RÉCAPITULATIF GLOBAL
    // ═══════════════════════════════════════════════════════════

    private function buildSheetRecapitulatif(
        Spreadsheet $spreadsheet,
        array       $donneesLocalisations,
        int         $annee
    ): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Récapitulatif');
        $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($sheet));

        $row = 1;
        $this->writeTitleRow($sheet, $row,
            sprintf('RÉCAPITULATIF COMPTABLE %d — APPART HÔTEL TRICASTIN', $annee),
            8
        );
        $row += 2;

        // ── Tableau comparatif par localisation ────────────────
        $this->writeSectionRow($sheet, $row, 'COMPARATIF PAR LOCALISATION', 8);
        $row++;

        $this->writeHeaderRow($sheet, $row, [
            'Localisation',
            'Appartements',
            'Nuits occupées',
            'Jours disponibles',
            'Taux occupation',
            'Revenus (€)',
            'Frais (€)',
            'Résultat net (€)',
        ]);
        $row++;

        $totRevenus      = 0.0;
        $totFrais        = 0.0;
        $totResultat     = 0.0;
        $totJoursOcc     = 0;
        $totJoursDispo   = 0;

        foreach ($donneesLocalisations as $idx => $d) {
            $bg = ($idx % 2 === 0) ? self::CLR_RECAP_BG : null;
            $this->writeDataRow($sheet, $row, [
                $d['localisation'],
                $d['appartements'],
                $d['joursOccupes'],
                $d['joursDispo'],
                sprintf('%.1f %%', $d['tauxOccupation']),
                $d['revenus'],
                $d['frais'],
                $d['resultatNet'],
            ], [6 => 'currency', 7 => 'currency', 8 => 'currency'], $bg);

            // Colorer résultat net
            $resultColor = $d['resultatNet'] >= 0 ? '007700' : 'CC0000';
            $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB($resultColor);
            $sheet->getStyle("H{$row}")->getFont()->setBold(true);

            $totRevenus    += $d['revenus'];
            $totFrais      += $d['frais'];
            $totResultat   += $d['resultatNet'];
            $totJoursOcc   += $d['joursOccupes'];
            $totJoursDispo += $d['joursDispo'];
            $row++;
        }

        $totTaux = $totJoursDispo > 0
            ? round(($totJoursOcc / $totJoursDispo) * 100, 1)
            : 0.0;

        $this->writeTotalRow($sheet, $row, [
            'TOTAL GLOBAL',
            array_sum(array_column($donneesLocalisations, 'appartements')),
            $totJoursOcc,
            $totJoursDispo,
            sprintf('%.1f %%', $totTaux),
            $totRevenus,
            $totFrais,
            $totResultat,
        ], [6 => 'currency', 7 => 'currency', 8 => 'currency']);

        $resultGlobalColor = $totResultat >= 0 ? '00DD00' : 'FF4444';
        $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB($resultGlobalColor);
        $row += 2;

        // ── Bilan consolidé ────────────────────────────────────
        $this->writeSectionRow($sheet, $row, 'BILAN CONSOLIDÉ ' . $annee, 2);
        $row++;

        $bilans = [
            ['Total revenus hébergement', $totRevenus],
            ['Total frais',               $totFrais],
        ];
        foreach ($bilans as $bilan) {
            $this->writeDataRow($sheet, $row, $bilan, [2 => 'currency']);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        $this->writeTotalRow($sheet, $row, ['RÉSULTAT NET CONSOLIDÉ', $totResultat], [2 => 'currency']);
        $color = $totResultat >= 0 ? '00DD00' : 'FF4444';
        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB($color);
        $row += 2;

        // ── Note de bas de page ────────────────────────────────
        $sheet->setCellValue("A{$row}",
            sprintf('Généré le %s — Appart Hôtel Tricastin', date('d/m/Y à H:i'))
        );
        $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('888888');

        // ── Largeurs ───────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(30);
        foreach (['B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(18);
        }

        $sheet->freezePane('A3');
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS DE MISE EN FORME
    // ═══════════════════════════════════════════════════════════

    private function writeTitleRow(Worksheet $sheet, int $row, string $titre, int $nbCols): void
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nbCols);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", $titre);
        $style = $sheet->getStyle("A{$row}");
        $style->getFont()->setBold(true)->setSize(14)->setName('Arial');
        $style->getFont()->getColor()->setRGB(self::CLR_HEADER_FG);
        $style->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB(self::CLR_HEADER_BG);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
              ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(32);
    }

    private function writeSectionRow(Worksheet $sheet, int $row, string $titre, int $nbCols): void
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nbCols);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", $titre);
        $style = $sheet->getStyle("A{$row}");
        $style->getFont()->setBold(true)->setSize(11)->setName('Arial');
        $style->getFont()->getColor()->setRGB(self::CLR_SECTION_FG);
        $style->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB(self::CLR_SECTION_BG);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)
              ->getColor()->setRGB(self::CLR_HEADER_BG);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function writeHeaderRow(Worksheet $sheet, int $row, array $headers): void
    {
        foreach ($headers as $col => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue("{$colLetter}{$row}", $header);
        }
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $style   = $sheet->getStyle("A{$row}:{$lastCol}{$row}");
        $style->getFont()->setBold(true)->setName('Arial')->setSize(10);
        $style->getFont()->getColor()->setRGB(self::CLR_HEADER_FG);
        $style->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('2C3E50');
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
              ->getColor()->setRGB('CCCCCC');
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    /**
     * @param array $formats  Clé = indice colonne (1-based), valeur = 'currency'|'int'|'percent'
     */
    private function writeDataRow(Worksheet $sheet, int $row, array $values, array $formats = [], ?string $bgColor = null): void
    {
        foreach ($values as $idx => $value) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$col}{$row}", $value);

            $fmt = $formats[$idx + 1] ?? null;
            if ($fmt === 'currency') {
                $sheet->getStyle("{$col}{$row}")->getNumberFormat()
                      ->setFormatCode('#,##0.00 [$€-fr-FR]');
            }
        }

        if ($bgColor) {
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($values));
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                  ->getFill()->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB($bgColor);
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($values));
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")
              ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR)
              ->getColor()->setRGB('DDDDDD');
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")
              ->getFont()->setName('Arial')->setSize(10);
    }

    private function writeSubtotalRow(Worksheet $sheet, int $row, array $values, array $formats = []): void
    {
        $this->writeDataRow($sheet, $row, $values, $formats, self::CLR_SUBTOTAL_BG);
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($values));
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")
              ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)
              ->getColor()->setRGB('999999');
    }

    private function writeTotalRow(Worksheet $sheet, int $row, array $values, array $formats = []): void
    {
        $this->writeDataRow($sheet, $row, $values, $formats, self::CLR_TOTAL_BG);
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($values));
        $style   = $sheet->getStyle("A{$row}:{$lastCol}{$row}");
        $style->getFont()->setBold(true)->getColor()->setRGB(self::CLR_TOTAL_FG);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
              ->getColor()->setRGB('AAAAAA');
        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    // ═══════════════════════════════════════════════════════════
    // REQUÊTES
    // ═══════════════════════════════════════════════════════════

    /**
     * Réservations de l'année groupées par mois.
     * Filtre optionnel : appartement OU localisation.
     */
    private function getReservationsParMois(
        int           $annee,
        ?Appartement  $appartement = null,
        ?Localisation $localisation = null
    ): array {
        $debut = new \DateTimeImmutable("$annee-01-01");
        $fin   = new \DateTimeImmutable("$annee-12-31");

        $qb = $this->reservationRepo->createQueryBuilder('r')
            ->leftJoin('r.appartement', 'a')
            ->leftJoin('a.localisation', 'l')
            ->where('r.dateArrivee <= :fin')
            ->andWhere('r.dateDepart >= :debut')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('r.dateArrivee', 'ASC');

        if ($appartement !== null) {
            $qb->andWhere('r.appartement = :appart')->setParameter('appart', $appartement);
        } elseif ($localisation !== null) {
            $qb->andWhere('a.localisation = :loc')->setParameter('loc', $localisation);
        }

        $reservations = $qb->getQuery()->getResult();
        $parMois = [];

        foreach ($reservations as $resa) {
            $dateArrivee = $resa->getDateArrivee() < $debut ? $debut : $resa->getDateArrivee();
            $dateDepart  = $resa->getDateDepart()  > $fin   ? $fin   : $resa->getDateDepart();
            $nbJours     = (int) $dateArrivee->diff($dateDepart)->days;

            if ($nbJours <= 0) continue;

            $mois         = (int) $dateArrivee->format('n');
            $montantTotal = (float) ($resa->getMontantTotal() ?? 0);
            $prixUnitaire = $nbJours > 0 ? round($montantTotal / $nbJours, 2) : 0;
            $nomClient    = trim(($resa->getPrenom() ?? '') . ' ' . ($resa->getNom() ?? '')) ?: 'Client inconnu';

            $parMois[$mois][] = [
                'client'           => $nomClient,
                'numFacture'       => $resa->getNumeroFacture() ?? 'N/A',
                'appartement'      => $resa->getAppartement()?->getNom() ?? '—',
                'dateArrivee'      => $resa->getDateArrivee()->format('d/m/Y'),
                'dateDepart'       => $resa->getDateDepart()->format('d/m/Y'),
                'nbJours'          => $nbJours,
                'prixUnitaire'     => $prixUnitaire,
                'totalHebergement' => $montantTotal,
            ];
        }

        return $parMois;
    }

    private function joursDisponiblesDansMois(int $annee, int $mois): int
    {
        return (int) (new \DateTimeImmutable("$annee-$mois-01"))->format('t');
    }

    private function slugify(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        return trim(preg_replace('/[^a-z0-9]+/', '-', $text), '-');
    }

    private function truncateTitle(string $title): string
    {
        return mb_strlen($title) > 31 ? mb_substr($title, 0, 28) . '...' : $title;
    }
}
