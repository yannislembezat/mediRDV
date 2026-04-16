<?php

declare(strict_types=1);

namespace App\Tests\Service\Security;

use App\Entity\User;
use App\Enum\UserRole;
use App\Service\Security\PostLoginRedirectResolver;
use PHPUnit\Framework\TestCase;

final class PostLoginRedirectResolverTest extends TestCase
{
    public function testItPrioritizesAdminRoute(): void
    {
        $user = (new User())
            ->setRoles([UserRole::PATIENT->value, UserRole::ADMIN->value]);

        $resolver = new PostLoginRedirectResolver();

        self::assertSame('admin_dashboard', $resolver->resolveRouteName($user));
    }

    public function testItReturnsDoctorRouteForDoctors(): void
    {
        $user = (new User())
            ->setRoles([UserRole::MEDECIN->value]);

        $resolver = new PostLoginRedirectResolver();

        self::assertSame('doctor_planning', $resolver->resolveRouteName($user));
    }

    public function testItFallsBackToPatientRoute(): void
    {
        $user = (new User())
            ->setRoles([UserRole::PATIENT->value]);

        $resolver = new PostLoginRedirectResolver();

        self::assertSame('patient_dashboard', $resolver->resolveRouteName($user));
    }
}
