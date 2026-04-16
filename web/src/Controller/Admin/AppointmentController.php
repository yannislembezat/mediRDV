<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use App\Repository\MedecinProfileRepository;
use App\Service\AppointmentService;
use App\Service\Exception\WorkflowException;
use App\Service\Web\AdminPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly MedecinProfileRepository $medecinProfileRepository,
        private readonly AppointmentService $appointmentService,
        private readonly AdminPortalContextBuilder $adminPortalContextBuilder,
    ) {
    }

    #[Route('/admin/rendez-vous', name: 'admin_appointments', methods: ['GET'])]
    public function index(Request $request, #[CurrentUser] User $admin): Response
    {
        $selectedStatus = $this->resolveStatus($request->query->get('status'));
        $selectedDate = $this->resolveDate($request->query->get('date'));
        $selectedDoctorId = $this->resolveDoctorId($request->query->get('doctor'));
        $doctors = $this->medecinProfileRepository->findForAdmin(null, null, true);
        $selectedDoctor = $selectedDoctorId !== null ? $this->medecinProfileRepository->findOneForAdmin($selectedDoctorId) : null;
        $appointmentsInScope = $this->appointmentRepository->createAdminListQueryBuilder(null, $selectedDate, $selectedDoctorId)
            ->getQuery()
            ->getResult();
        $appointments = $selectedStatus === null
            ? $appointmentsInScope
            : array_values(array_filter(
                $appointmentsInScope,
                static fn (Appointment $appointment): bool => $appointment->getStatus() === $selectedStatus,
            ));

        return $this->render('admin/appointments/list.html.twig', array_merge([
            'appointments' => $appointments,
            'doctors' => $doctors,
            'selectedDoctor' => $selectedDoctor,
            'selectedDoctorId' => $selectedDoctor?->getId(),
            'selectedDate' => $selectedDate,
            'selectedStatus' => $selectedStatus,
            'statusOptions' => AppointmentStatus::cases(),
            'statusCounts' => $this->buildStatusCounts($appointmentsInScope),
        ], $this->adminPortalContextBuilder->build($admin)));
    }

    #[Route('/admin/rendez-vous/{id}', name: 'admin_appointment_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] User $admin): Response
    {
        $appointment = $this->appointmentRepository->findOneForAdminById($id);

        if ($appointment === null) {
            throw $this->createNotFoundException('Le rendez-vous demande est introuvable.');
        }

        return $this->render('admin/appointments/detail.html.twig', array_merge([
            'appointment' => $appointment,
        ], $this->adminPortalContextBuilder->build($admin)));
    }

    #[Route('/admin/rendez-vous/{id}/approuver', name: 'admin_appointment_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approve(int $id, Request $request, #[CurrentUser] User $admin): Response
    {
        $appointment = $this->appointmentRepository->findOneForAdminById($id);

        if ($appointment === null) {
            throw $this->createNotFoundException('Le rendez-vous demande est introuvable.');
        }

        if (!$this->isCsrfTokenValid(sprintf('approve_appointment_%d', $appointment->getId()), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Le jeton CSRF est invalide. Veuillez reessayer.');

            return $this->redirectToRoute('admin_appointment_show', ['id' => $appointment->getId()]);
        }

        try {
            $this->appointmentService->approve($appointment, $admin, $this->normalizeOptionalString($request->request->get('admin_note')));
            $this->addFlash('success', 'Le rendez-vous a ete approuve.');
        } catch (WorkflowException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_appointment_show', ['id' => $appointment->getId()]);
    }

    #[Route('/admin/rendez-vous/{id}/refuser', name: 'admin_appointment_refuse', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function refuse(int $id, Request $request, #[CurrentUser] User $admin): Response
    {
        $appointment = $this->appointmentRepository->findOneForAdminById($id);

        if ($appointment === null) {
            throw $this->createNotFoundException('Le rendez-vous demande est introuvable.');
        }

        if (!$this->isCsrfTokenValid(sprintf('refuse_appointment_%d', $appointment->getId()), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Le jeton CSRF est invalide. Veuillez reessayer.');

            return $this->redirectToRoute('admin_appointment_show', ['id' => $appointment->getId()]);
        }

        try {
            $this->appointmentService->refuse($appointment, $admin, (string) $request->request->get('admin_note'));
            $this->addFlash('success', 'Le rendez-vous a ete refuse.');
        } catch (WorkflowException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_appointment_show', ['id' => $appointment->getId()]);
    }

    /**
     * @param list<Appointment> $appointments
     *
     * @return array<string, int>
     */
    private function buildStatusCounts(array $appointments): array
    {
        $counts = ['all' => count($appointments)];

        foreach (AppointmentStatus::cases() as $status) {
            $counts[$status->value] = 0;
        }

        foreach ($appointments as $appointment) {
            $status = $appointment->getStatus()->value;
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    private function resolveStatus(mixed $value): ?AppointmentStatus
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue !== '' ? AppointmentStatus::tryFrom($normalizedValue) : null;
    }

    private function resolveDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $normalizedValue);

        return $date instanceof \DateTimeImmutable ? $date : null;
    }

    private function resolveDoctorId(mixed $value): ?int
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '' || !ctype_digit($normalizedValue)) {
            return null;
        }

        return (int) $normalizedValue;
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue !== '' ? $normalizedValue : null;
    }
}
