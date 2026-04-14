<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testDefaultValues(): void
    {
        $this->assertTrue($this->user->isActive());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->user->getCreatedAt());
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    public function testSettersAndGetters(): void
    {
        $this->user->setNom('Dupont');
        $this->assertSame('Dupont', $this->user->getNom());

        $this->user->setPrenom('Marie');
        $this->assertSame('Marie', $this->user->getPrenom());

        $this->user->setEmail('marie@email.fr');
        $this->assertSame('marie@email.fr', $this->user->getEmail());
        $this->assertSame('marie@email.fr', $this->user->getUserIdentifier());

        $this->user->setTelephone('06 01 02 03 04');
        $this->assertSame('06 01 02 03 04', $this->user->getTelephone());

        $this->user->setAdresse('12 rue de la Paix');
        $this->assertSame('12 rue de la Paix', $this->user->getAdresse());

        $this->user->setVille('Paris');
        $this->assertSame('Paris', $this->user->getVille());

        $this->user->setCodePostal('75001');
        $this->assertSame('75001', $this->user->getCodePostal());

        $this->user->setPassword('hashed_password');
        $this->assertSame('hashed_password', $this->user->getPassword());
    }

    public function testFullName(): void
    {
        $this->user->setPrenom('Jean');
        $this->user->setNom('Martin');

        $this->assertSame('Jean Martin', $this->user->getFullName());
        $this->assertSame('Jean Martin', (string) $this->user);
    }

    public function testFullAddress(): void
    {
        $this->user->setAdresse('12 rue de la Paix');
        $this->user->setCodePostal('75001');
        $this->user->setVille('Paris');

        $this->assertSame('12 rue de la Paix, 75001, Paris', $this->user->getFullAddress());
    }

    public function testFullAddressPartial(): void
    {
        $this->user->setVille('Paris');
        $this->assertSame('Paris', $this->user->getFullAddress());
    }

    public function testRolesAlwaysContainRoleUser(): void
    {
        $this->assertContains('ROLE_USER', $this->user->getRoles());

        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testRolesNoDuplicates(): void
    {
        $this->user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']);
        $roles = $this->user->getRoles();
        $this->assertCount(2, $roles);
    }

    public function testIsActive(): void
    {
        $this->assertTrue($this->user->isActive());

        $this->user->setIsActive(false);
        $this->assertFalse($this->user->isActive());
    }

    public function testResetToken(): void
    {
        $this->assertNull($this->user->getResetToken());

        $this->user->setResetToken('abc123');
        $this->assertSame('abc123', $this->user->getResetToken());

        $this->user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $this->assertTrue($this->user->isResetTokenValid());

        $this->user->setResetTokenExpiresAt(new \DateTimeImmutable('-1 hour'));
        $this->assertFalse($this->user->isResetTokenValid());
    }

    public function testResetTokenInvalidWhenNull(): void
    {
        $this->assertFalse($this->user->isResetTokenValid());
    }

    public function testCollections(): void
    {
        $this->assertCount(0, $this->user->getReservations());
        $this->assertCount(0, $this->user->getPayments());
    }
}
