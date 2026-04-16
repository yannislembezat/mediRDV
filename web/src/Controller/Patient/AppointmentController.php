<?php

declare(strict_types=1);

namespace App\Controller\Patient;

use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use App\Service\AppointmentService;
use App\Service\Exception\WorkflowException;
use App\Service\Web\PatientPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
final class AppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly AppointmentService $appointmentService,
        private readonly PatientPortalContextBuilder $patientPortalContextBuilder,
    ) {
    }

    #[Route('/patient/mes-rdv', name: 'patient_appointments', methods: ['GET'])]
    public function index(Request $request, #[CurrentUser] User $patient): Response
    {
        $selectedStatus = $this->resolveStatus($request->query->get('status'));
        $allAppointments = $this->appointmentRepository->findForPatient($patient);
        $appointments = $selectedStatus === null
            ? $allAppointments
            : array_values(array_filter(
                $allAppointments,
                static fn ($appointment): bool => $appointment->getStatus() === $selectedStatus,
            ));

        return $this->render('patient/appointments/list.html.twig', array_merge([
            'appointments' => $appointments,
            'selectedStatus' => $selectedStatus,
            'statusCounts' => $this->buildStatusCounts($allAppointments),
            'statusOptions' => AppointmentStatus::cases(),
        ], $this->patientPortalContextBuilder->build($patient)));
    }

    #[Route('/patient/mes-rdv/{id}', name: 'patient_appointment_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] User $patient): Response
    {
        $appointment = $this->appointmentRepository->findOneForPatientById($patient, $id);

        if ($appointment === null) {
            throw $this->createNotFoundException('Le rendez-vous demande est introuvable.');
        }

        return $this->render('patient/appointments/detail.html.twig', array_merge([
            'appointment' => $appointment,
        ], $this->patientPortalContextBuilder->build($patient)));
    }

    #[Route('/patient/mes-rdv/{id}/annuler', name: 'patient_appointment_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(int $id, Request $request, #[CurrentUser] User $patient): Response
    {
        $appointment = $this->appointmentRepository->findOneForPatientById($patient, $id);

        if ($appointment === null) {
            throw $this->createNotFoundException('Le rendez-vous demande est introuvable.');
        }

        if (!$this->isCsrfTokenValid(sprintf('cancel_appointment_%d', $appointment->getId()), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Le jeton CSRF est invalide. Veuillez reessayer.');

            return $this->redirectToRoute('patient_appointment_show', ['id' => $appointment->getId()]);
        }

        try {
            $this->appointmentService->cancelByPatient($appointment, $patient);
            $this->addFlash('success', 'Le rendez-vous a ete annule.');
        } catch (WorkflowException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirectToRoute('patient_appointment_show', ['id' => $appointment->getId()]);
    }

    /**
     * @param list<object> $appointments
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

        if ($normalizedValue === '') {
            return null;
        }

        return AppointmentStatus::tryFrom($normalizedValue);
    }
}
