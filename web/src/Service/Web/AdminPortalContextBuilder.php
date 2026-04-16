<?php

declare(strict_types=1);

namespace App\Service\Web;

use App\Entity\User;
use App\Repository\NotificationRepository;

final class AdminPortalContextBuilder
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    /**
     * @return array{
     *     portal_notifications: list<\App\Entity\Notification>,
     *     portal_unread_notifications: int
     * }
     */
    public function build(User $user): array
    {
        return [
            'portal_notifications' => $this->notificationRepository->findLatestForUser($user, null, 6),
            'portal_unread_notifications' => $this->notificationRepository->countUnreadForUser($user),
        ];
    }
}
