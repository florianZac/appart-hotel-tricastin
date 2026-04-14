<?php

namespace App\Tests\Service;

use App\Service\DateService;
use PHPUnit\Framework\TestCase;

class DateServiceTest extends TestCase
{
    private DateService $dateService;

    protected function setUp(): void
    {
        $this->dateService = new DateService();
    }

    public function testAddOpenDaySimple(): void
    {
        // Lundi 6 juillet 2026 + 3 jours ouvrés = Jeudi 9 juillet
        $date = new \DateTime('2026-07-06'); // Lundi
        $result = $this->dateService->addOpenDay($date, 3);

        $this->assertSame('2026-07-09', $result->format('Y-m-d'));
    }

    public function testAddOpenDaySkipsWeekend(): void
    {
        // Vendredi 3 juillet 2026 + 1 jour ouvré = Lundi 6 juillet
        $date = new \DateTime('2026-07-03'); // Vendredi
        $result = $this->dateService->addOpenDay($date, 1);

        $this->assertSame('2026-07-06', $result->format('Y-m-d'));
    }

    public function testAddOpenDayAcrossWeekend(): void
    {
        // Jeudi 2 juillet 2026 + 3 jours ouvrés = Mardi 7 juillet
        $date = new \DateTime('2026-07-02'); // Jeudi
        $result = $this->dateService->addOpenDay($date, 3);

        $this->assertSame('2026-07-07', $result->format('Y-m-d'));
    }

    public function testAddOpenDayZeroDays(): void
    {
        $date = new \DateTime('2026-07-06');
        $result = $this->dateService->addOpenDay($date, 0);

        $this->assertSame('2026-07-06', $result->format('Y-m-d'));
    }

    public function testAddOpenDayFiveDaysIsOneWeek(): void
    {
        // Lundi + 5 jours ouvrés = Lundi suivant
        $date = new \DateTime('2026-07-06'); // Lundi
        $result = $this->dateService->addOpenDay($date, 5);

        $this->assertSame('2026-07-13', $result->format('Y-m-d')); // Lundi suivant
    }

    public function testAddOpenDayDoesNotModifyOriginalDate(): void
    {
        $date = new \DateTime('2026-07-06');
        $originalStr = $date->format('Y-m-d');

        $this->dateService->addOpenDay($date, 5);

        $this->assertSame($originalStr, $date->format('Y-m-d'));
    }

    public function testAddOpenDayFromSaturday(): void
    {
        // Samedi 4 juillet 2026 + 1 jour ouvré = Lundi 6 juillet
        $date = new \DateTime('2026-07-04'); // Samedi
        $result = $this->dateService->addOpenDay($date, 1);

        $this->assertSame('2026-07-06', $result->format('Y-m-d'));
    }

    public function testAddOpenDayFromSunday(): void
    {
        // Dimanche 5 juillet 2026 + 1 jour ouvré = Lundi 6 juillet
        $date = new \DateTime('2026-07-05'); // Dimanche
        $result = $this->dateService->addOpenDay($date, 1);

        $this->assertSame('2026-07-06', $result->format('Y-m-d'));
    }

    public function testAddOpenDayTenDays(): void
    {
        // Lundi + 10 jours ouvrés = 2 semaines plus tard (Lundi)
        $date = new \DateTime('2026-07-06'); // Lundi
        $result = $this->dateService->addOpenDay($date, 10);

        $this->assertSame('2026-07-20', $result->format('Y-m-d'));
    }
}
