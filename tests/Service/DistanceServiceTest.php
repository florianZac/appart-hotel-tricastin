<?php

namespace App\Tests\Service;

use App\Service\DistanceService;
use PHPUnit\Framework\TestCase;

class DistanceServiceTest extends TestCase
{
    public function testDistanceParisMarseilleApproximation(): void
    {
        // Paris (48.8566, 2.3522) → Marseille (43.2965, 5.3698)
        $distance = DistanceService::distanceHaversine(48.8566, 2.3522, 43.2965, 5.3698);

        // ~660 km à vol d'oiseau
        $this->assertGreaterThan(650, $distance);
        $this->assertLessThan(680, $distance);
    }

    public function testDistanceSamePoint(): void
    {
        $distance = DistanceService::distanceHaversine(44.3399, 4.7577, 44.3399, 4.7577);
        $this->assertSame(0.0, $distance);
    }

    public function testDistanceShort(): void
    {
        // Saint-Paul-Trois-Châteaux → Tulette (~10 km)
        $distance = DistanceService::distanceHaversine(44.3492, 4.7684, 44.2847, 4.9297);

        $this->assertGreaterThan(8, $distance);
        $this->assertLessThan(20, $distance);
    }

    public function testDistanceReturnsFloat(): void
    {
        $distance = DistanceService::distanceHaversine(0, 0, 1, 1);
        $this->assertIsFloat($distance);
    }

    public function testDistanceSymmetric(): void
    {
        $d1 = DistanceService::distanceHaversine(48.8566, 2.3522, 43.2965, 5.3698);
        $d2 = DistanceService::distanceHaversine(43.2965, 5.3698, 48.8566, 2.3522);

        $this->assertEqualsWithDelta($d1, $d2, 0.001);
    }

    public function testDistancePositive(): void
    {
        $distance = DistanceService::distanceHaversine(0, 0, 10, 10);
        $this->assertGreaterThan(0, $distance);
    }
}
