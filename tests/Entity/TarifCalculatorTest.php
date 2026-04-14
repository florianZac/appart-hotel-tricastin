<?php

namespace App\Tests\Service;

use App\Entity\Appartement;
use App\Entity\Tarif;
use App\Repository\TarifRepository;
use App\Service\TarifCalculator;
use PHPUnit\Framework\TestCase;

class TarifCalculatorTest extends TestCase
{
    private function createCalculator(?Tarif $tarif = null): TarifCalculator
    {
        $repo = $this->createMock(TarifRepository::class);
        $repo->method('findTarifForDate')->willReturn($tarif);
        return new TarifCalculator($repo);
    }

    private function createAppartement(float $prixNuit = 80.0, int $id = 1): Appartement
    {
        $appart = $this->createMock(Appartement::class);
        $appart->method('getId')->willReturn($id);
        $appart->method('getPrixParNuit')->willReturn((string) $prixNuit);
        return $appart;
    }

    public function testCalculerPrixSimple(): void
    {
        $calc = $this->createCalculator();
        $appart = $this->createAppartement(80.0);

        $result = $calc->calculerPrix($appart, new \DateTime('2026-07-01'), new \DateTime('2026-07-04'));

        $this->assertSame(3, $result['nombreNuits']);
        $this->assertSame(240.0, $result['total']); // 3 × 80
        $this->assertNotEmpty($result['details']);
    }

    public function testCalculerPrixUneNuit(): void
    {
        $calc = $this->createCalculator();
        $appart = $this->createAppartement(100.0);

        $result = $calc->calculerPrix($appart, new \DateTime('2026-07-01'), new \DateTime('2026-07-02'));

        $this->assertSame(1, $result['nombreNuits']);
        $this->assertSame(100.0, $result['total']);
    }

    public function testCalculerPrixUneSemaine(): void
    {
        $calc = $this->createCalculator();
        $appart = $this->createAppartement(80.0);

        $result = $calc->calculerPrix($appart, new \DateTime('2026-07-01'), new \DateTime('2026-07-08'));

        $this->assertSame(7, $result['nombreNuits']);
        $this->assertSame(560.0, $result['total']); // 7 × 80 (pas de tarif semaine avantageux)
    }

    public function testCalculerPrixAvecTarifSaison(): void
    {
        $tarif = $this->createMock(Tarif::class);
        $tarif->method('getId')->willReturn(1);
        $tarif->method('getSaison')->willReturn('Haute saison');
        $tarif->method('getPrixJour')->willReturn(120.0);
        $tarif->method('getPrixSemaine')->willReturn(750.0);
        $tarif->method('getPrixMois')->willReturn(2800.0);

        $calc = $this->createCalculator($tarif);
        $appart = $this->createAppartement(80.0);

        $result = $calc->calculerPrix($appart, new \DateTime('2026-07-01'), new \DateTime('2026-07-08'));

        $this->assertSame(7, $result['nombreNuits']);
        // 750 (semaine) < 7×120 (840), donc le mix est avantageux
        $this->assertSame(750.0, $result['total']);
        $this->assertSame('Haute saison', $result['details'][0]['saison']);
    }

    public function testCalculerMontantTotal(): void
    {
        $calc = $this->createCalculator();
        $appart = $this->createAppartement(50.0);

        $montant = $calc->calculerMontantTotal($appart, new \DateTime('2026-07-01'), new \DateTime('2026-07-06'));

        $this->assertSame(250.0, $montant); // 5 × 50
    }

    public function testCalculerPrixMemeDate(): void
    {
        $calc = $this->createCalculator();
        $appart = $this->createAppartement(80.0);

        $result = $calc->calculerPrix($appart, new \DateTime('2026-07-01'), new \DateTime('2026-07-01'));

        $this->assertSame(0, $result['nombreNuits']);
        $this->assertSame(0.0, $result['total']);
    }

    public function testCalculerPrixUnMois(): void
    {
        $tarif = $this->createMock(Tarif::class);
        $tarif->method('getId')->willReturn(1);
        $tarif->method('getSaison')->willReturn('Basse saison');
        $tarif->method('getPrixJour')->willReturn(60.0);
        $tarif->method('getPrixSemaine')->willReturn(350.0);
        $tarif->method('getPrixMois')->willReturn(1200.0);

        $calc = $this->createCalculator($tarif);
        $appart = $this->createAppartement(60.0);

        $result = $calc->calculerPrix($appart, new \DateTime('2026-07-01'), new \DateTime('2026-07-31'));

        $this->assertSame(30, $result['nombreNuits']);
        // 1200 (mois) < 30×60 (1800), donc le mois est avantageux
        $this->assertSame(1200.0, $result['total']);
    }

    public function testResultStructure(): void
    {
        $calc = $this->createCalculator();
        $appart = $this->createAppartement(80.0);

        $result = $calc->calculerPrix($appart, new \DateTime('2026-07-01'), new \DateTime('2026-07-04'));

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('nombreNuits', $result);
        $this->assertIsFloat($result['total']);
        $this->assertIsArray($result['details']);
        $this->assertIsInt($result['nombreNuits']);
    }

    public function testDetailStructure(): void
    {
        $calc = $this->createCalculator();
        $appart = $this->createAppartement(80.0);

        $result = $calc->calculerPrix($appart, new \DateTime('2026-07-01'), new \DateTime('2026-07-04'));

        $detail = $result['details'][0];
        $this->assertArrayHasKey('saison', $detail);
        $this->assertArrayHasKey('jours', $detail);
        $this->assertArrayHasKey('prixJour', $detail);
        $this->assertArrayHasKey('prixSemaine', $detail);
        $this->assertArrayHasKey('prixMois', $detail);
        $this->assertArrayHasKey('montant', $detail);
        $this->assertArrayHasKey('detail', $detail);
    }

    public function testFallbackSaisonStandard(): void
    {
        $calc = $this->createCalculator(null); // Pas de tarif spécifique
        $appart = $this->createAppartement(80.0);

        $result = $calc->calculerPrix($appart, new \DateTime('2026-07-01'), new \DateTime('2026-07-04'));

        $this->assertSame('Standard', $result['details'][0]['saison']);
    }
}
