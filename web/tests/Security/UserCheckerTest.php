<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserCheckerTest extends TestCase
{
    public function testItAllowsActiveUsers(): void
    {
        $user = (new User())
            ->setIsActive(true);

        $checker = new UserChecker();
        $checker->checkPostAuth($user);

        self::assertTrue(true);
    }

    public function testItRejectsInactiveUsers(): void
    {
        $user = (new User())
            ->setIsActive(false);

        $checker = new UserChecker();

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $checker->checkPostAuth($user);
    }
}
