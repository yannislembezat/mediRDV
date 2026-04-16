<?php

declare(strict_types=1);

namespace App\Controller\Doctor;

use App\Entity\Appointment;
use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use App\Service\Web\DoctorPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEDECIN')]
final class PlanningController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly DoctorPortalContextBuilder $doctorPortalContextBuilder,
    ) {
    }

    #[Route('/medecin', name: 'doctor_planning', methods: ['GET'])]
    public function __invoke(Request $request, #[CurrentUser] User $doctor): Response
    {
        $medecinProfile = $this->requireMedecinProfile($doctor);
        $planningView = $this->resolvePlanningView($request->query->get('view'));
        $selectedDate = $this->resolveDate($request->query->get('date')) ?? new \DateTimeImmutable('today');
        $window = $this->buildWindow($planningView, $selectedDate);
        $appointments = $this->appointmentRepository->findBetweenForMedecin(
            $medecinProfile,
            $window['start'],
            $window['end'],
            [AppointmentStatus::CONFIRMED, AppointmentStatus::COMPLETED],
        );
        $planningDays = $this->buildPlanningDays($window['start'], $window['end'], $appointments);

        return $this->render('doctor/planning.html.twig', array_merge([
            'doctorProfile' => $medecinProfile,
            'planningView' => $planningView,
            'planningDate' => $selectedDate,
            'planningRangeLabel' => $this->buildRangeLabel($planningView, $window['start'], $window['end']),
            'planningDays' => $planningDays,
            'periodStart' => $window['start'],
            'periodEnd' => $window['end'],
            'previousDate' => $window['previous'],
            'nextDate' => $window['next'],
            'periodAppointments' => $appointments,
            'todayCount' => count(array_filter(
                $appointments,
                static fn (Appointment $appointment): bool => $appointment->getDateTime()->format('Y-m-d') === $selectedDate->format('Y-m-d'),
            )),
            'uniquePatientCount' => $this->appointmentRepository->countDistinctPatientsForMedecin($medecinProfile),
            'recentPatients' => $this->buildRecentPatients($appointments),
        ], $this->doctorPortalContextBuilder->build($doctor)));
    }

    private function requireMedecinProfile(User $doctor): MedecinProfile
    {
        $medecinProfile = $doctor->getMedecinProfile();

        if (!$medecinProfile instanceof MedecinProfile) {
            throw $this->createAccessDeniedException('Le compte medecin courant ne possede pas de profil praticien.');
        }

        return $medecinProfile;
    }

    private function resolvePlanningView(mixed $value): string
    {
        if (!is_scalar($value)) {
            return 'week';
        }

        return trim((string) $value) === 'day' ? 'day' : 'week';
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

    /**
     * @return array{
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     previous: \DateTimeImmutable,
     *     next: \DateTimeImmutable
     * }
     */
    private function buildWindow(string $planningView, \DateTimeImmutable $selectedDate): array
    {
        if ($planningView === 'day') {
            $start = $selectedDate->setTime(0, 0, 0);
            $end = $selectedDate->setTime(23, 59, 59);

            return [
                'start' => $start,
                'end' => $end,
                'previous' => $selectedDate->modify('-1 day'),
                'next' => $selectedDate->modify('+1 day'),
            ];
        }

        $start = $selectedDate->modify('monday this week')->setTime(0, 0, 0);
        $end = $start->modify('+6 days')->setTime(23, 59, 59);

        return [
            'start' => $start,
            'end' => $end,
            'previous' => $selectedDate->modify('-7 days'),
            'next' => $selectedDate->modify('+7 days'),
        ];
    }

    private function buildRangeLabel(string $planningView, \DateTimeImmutable $start, \DateTimeImmutable $end): string
    {
        if ($planningView === 'day') {
            return sprintf('Journee du %s', $start->format('d/m/Y'));
        }

        return sprintf('Semaine du %s au %s', $start->format('d/m/Y'), $end->format('d/m/Y'));
    }

    /**
     * @param list<Appointment> $appointments
     *
     * @return list<array{
     *     date: \DateTimeImmutable,
     *     dateKey: string,
     *     label: string,
     *     appointments: list<Appointment>
     * }>
     */
    private function buildPlanningDays(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        array $appointments,
    ): array {
        $planningDays = [];
        $currentDate = $start->setTime(0, 0, 0);
        $lastDate = $end->setTime(0, 0, 0);

        while ($currentDate <= $lastDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $planningDays[$dateKey] = [
                'date' => $currentDate,
                'dateKey' => $dateKey,
                'label' => $currentDate->format('D d/m'),
                'appointments' => [],
            ];

            $currentDate = $currentDate->modify('+1 day');
        }

        foreach ($appointments as $appointment) {
            $dateKey = $appointment->getDateTime()->format('Y-m-d');

            if (!isset($planningDays[$dateKey])) {
                continue;
            }

            $planningDays[$dateKey]['appointments'][] = $appointment;
        }

        return array_values($planningDays);
    }

    /**
     * @param list<Appointment> $appointments
     *
     * @return list<User>
     */
    private function buildRecentPatients(array $appointments): array
    {
        $patients = [];

        foreach ($appointments as $appointment) {
            $patient = $appointment->getPatient();

            if (!$patient instanceof User || $patient->getId() === null) {
                continue;
            }

            $patients[$patient->getId()] = $patient;
        }

        return array_values($patients);
    }
}
