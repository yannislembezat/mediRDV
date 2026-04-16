<?php

declare(strict_types=1);

namespace App\Service\Web;

use App\Entity\User;
use App\Repository\NotificationRepository;

final class DoctorPortalContextBuilder
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    /**
     * @return array{
     *     portal_notifications: list<object>,
     *     portal_unread_notifications: int
     * }
     */
    public function build(User $user, int $limit = 6): array
    {
        return [
            'portal_notifications' => $this->notificationRepository->findLatestForUser($user, null, $limit),
            'portal_unread_notifications' => $this->notificationRepository->countUnreadForUser($user),
        ];
    }
}
