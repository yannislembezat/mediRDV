<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Entity\User;
use App\Enum\UserRole;

final class PostLoginRedirectResolver
{
    public function resolveRouteName(User $user): string
    {
        if ($user->hasRole(UserRole::ADMIN)) {
            return 'admin_dashboard';
        }

        if ($user->hasRole(UserRole::MEDECIN)) {
            return 'doctor_planning';
        }

        if ($user->hasRole(UserRole::PATIENT)) {
            return 'patient_dashboard';
        }

        return 'app_home';
    }
}
