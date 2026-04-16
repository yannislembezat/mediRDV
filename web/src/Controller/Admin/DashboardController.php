<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\AppointmentStatus;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\MedecinProfileRepository;
use App\Service\Web\AvailabilityPreviewBuilder;
use App\Service\Web\AdminPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly MedecinProfileRepository $medecinProfileRepository,
        private readonly AvailabilityPreviewBuilder $availabilityPreviewBuilder,
        private readonly AdminPortalContextBuilder $adminPortalContextBuilder,
    ) {
    }

    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): Response
    {
        $activeDoctors = $this->medecinProfileRepository->findForAdmin(null, null, true);
        $availabilityDoctor = $activeDoctors[0] ?? null;
        $today = new \DateTimeImmutable('today');
        $pendingCount = $this->appointmentRepository->countByStatus(AppointmentStatus::PENDING);
        $activeDoctorCount = $this->medecinProfileRepository->countAll(true);
        $inactiveDoctorCount = $this->medecinProfileRepository->countAll(false);

        return $this->render('admin/dashboard.html.twig', array_merge([
            'user' => $user,
            'kpis' => [
                [
                    'label' => 'Demandes en attente',
                    'value' => $pendingCount,
                    'icon' => 'bi-hourglass-split',
                ],
                [
                    'label' => 'Rendez-vous du jour',
                    'value' => $this->appointmentRepository->countForDay($today),
                    'icon' => 'bi-calendar2-check',
                ],
                [
                    'label' => 'Medecins actifs',
                    'value' => $activeDoctorCount,
                    'icon' => 'bi-person-badge',
                ],
                [
                    'label' => 'Medecins inactifs',
                    'value' => $inactiveDoctorCount,
                    'icon' => 'bi-person-slash',
                ],
            ],
            'statusCounts' => [
                AppointmentStatus::PENDING->value => $pendingCount,
                AppointmentStatus::CONFIRMED->value => $this->appointmentRepository->countByStatus(AppointmentStatus::CONFIRMED),
                AppointmentStatus::REFUSED->value => $this->appointmentRepository->countByStatus(AppointmentStatus::REFUSED),
                AppointmentStatus::COMPLETED->value => $this->appointmentRepository->countByStatus(AppointmentStatus::COMPLETED),
                AppointmentStatus::CANCELLED->value => $this->appointmentRepository->countByStatus(AppointmentStatus::CANCELLED),
            ],
            'pendingAppointments' => $this->appointmentRepository->findPendingForAdminDashboard(),
            'doctorSpotlight' => array_slice($activeDoctors, 0, 4),
            'availabilityDoctor' => $availabilityDoctor,
            'availabilityPreview' => $availabilityDoctor !== null ? $this->availabilityPreviewBuilder->buildForMedecin($availabilityDoctor) : [],
        ], $this->adminPortalContextBuilder->build($user)));
    }
}
