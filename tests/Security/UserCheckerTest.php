<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UserChecker();
    }

    public function testActiveUserCanLogin(): void
    {
        $user = new User();
        $user->setIsActive(true);

        // Ne doit pas lancer d'exception
        $this->checker->checkPreAuth($user);
        $this->addToAssertionCount(1);
    }

    public function testInactiveUserCannotLogin(): void
    {
        $user = new User();
        $user->setIsActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Votre compte a été désactivé');

        $this->checker->checkPreAuth($user);
    }

    public function testNonUserObjectIsIgnored(): void
    {
        $user = $this->createMock(UserInterface::class);

        // Ne doit pas lancer d'exception pour un UserInterface non-User
        $this->checker->checkPreAuth($user);
        $this->addToAssertionCount(1);
    }

    public function testCheckPostAuthDoesNothing(): void
    {
        $user = new User();

        // Ne doit rien faire
        $this->checker->checkPostAuth($user);
        $this->addToAssertionCount(1);
    }
}
