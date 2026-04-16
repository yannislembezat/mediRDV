<?php

declare(strict_types=1);

namespace App\Controller\Patient;

use App\DTO\AppointmentRequestData;
use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Form\PatientBookingType;
use App\Repository\MedecinProfileRepository;
use App\Repository\SpecialtyRepository;
use App\Service\AppointmentService;
use App\Service\Exception\AvailabilitySlotUnavailableException;
use App\Service\Exception\WorkflowException;
use App\Service\Web\AvailabilityPreviewBuilder;
use App\Service\Web\PatientPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
final class SearchController extends AbstractController
{
    private const DAY_LABELS = [
        0 => 'Lundi',
        1 => 'Mardi',
        2 => 'Mercredi',
        3 => 'Jeudi',
        4 => 'Vendredi',
        5 => 'Samedi',
        6 => 'Dimanche',
    ];

    public function __construct(
        private readonly MedecinProfileRepository $medecinProfileRepository,
        private readonly SpecialtyRepository $specialtyRepository,
        private readonly AvailabilityPreviewBuilder $availabilityPreviewBuilder,
        private readonly AppointmentService $appointmentService,
        private readonly PatientPortalContextBuilder $patientPortalContextBuilder,
    ) {
    }

    #[Route('/patient/recherche', name: 'patient_search', methods: ['GET'])]
    public function search(Request $request, #[CurrentUser] User $patient): Response
    {
        $search = $this->normalizeNullableString($request->query->get('search'));
        $specialtyId = $this->parseNullablePositiveInt($request->query->get('specialtyId'));
        $specialties = $this->specialtyRepository->findActiveOrdered();
        $doctors = $this->medecinProfileRepository->findActiveByFilters($search, $specialtyId);

        return $this->render('patient/search.html.twig', array_merge([
            'search' => $search,
            'specialtyId' => $specialtyId,
            'specialties' => $specialties,
            'doctors' => $doctors,
        ], $this->patientPortalContextBuilder->build($patient)));
    }

    #[Route('/patient/medecin/{id}', name: 'patient_doctor', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showDoctor(int $id, #[CurrentUser] User $patient): Response
    {
        $doctor = $this->medecinProfileRepository->findOneWithAvailability($id);

        if ($doctor === null) {
            throw $this->createNotFoundException('Le medecin demande est introuvable.');
        }

        $availabilityPreview = $this->availabilityPreviewBuilder->buildForMedecin($doctor, 30, 6, 6);

        return $this->render('patient/doctor_detail.html.twig', array_merge([
            'doctor' => $doctor,
            'availabilityPreview' => $availabilityPreview,
            'availabilityRules' => $this->formatAvailabilityRules($doctor),
        ], $this->patientPortalContextBuilder->build($patient)));
    }

    #[Route('/patient/medecin/{id}/rdv', name: 'patient_booking', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function booking(Request $request, int $id, #[CurrentUser] User $patient): Response
    {
        $doctor = $this->medecinProfileRepository->findOneWithAvailability($id);

        if ($doctor === null) {
            throw $this->createNotFoundException('Le medecin demande est introuvable.');
        }

        $availabilityPreview = $this->availabilityPreviewBuilder->buildForMedecin($doctor, 30, 6, 8);
        $bookingData = new AppointmentRequestData();
        $bookingData->medecinId = $doctor->getId();
        $bookingForm = $this->createForm(PatientBookingType::class, $bookingData);
        $bookingForm->handleRequest($request);

        if ($bookingForm->isSubmitted() && $bookingForm->isValid()) {
            try {
                $appointment = $this->appointmentService->request(
                    $patient,
                    $doctor,
                    $bookingData->getDateTimeAsDateTimeImmutable(),
                    $this->normalizeNullableString($bookingData->reason),
                );

                $this->addFlash('success', 'Votre demande de rendez-vous a bien ete enregistree.');

                return $this->redirectToRoute('patient_appointment_show', [
                    'id' => $appointment->getId(),
                ]);
            } catch (AvailabilitySlotUnavailableException|WorkflowException|\InvalidArgumentException $exception) {
                $bookingForm->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('patient/booking.html.twig', array_merge([
            'doctor' => $doctor,
            'availabilityPreview' => $availabilityPreview,
            'availabilityRules' => $this->formatAvailabilityRules($doctor),
            'selectedDateKey' => $this->resolveSelectedDateKey($availabilityPreview, $bookingData->dateTime),
            'bookingForm' => $bookingForm->createView(),
        ], $this->patientPortalContextBuilder->build($patient)));
    }

    /**
     * @return list<string>
     */
    private function formatAvailabilityRules(MedecinProfile $doctor): array
    {
        $rules = [];

        foreach ($doctor->getAvailabilities() as $availability) {
            if (!$availability->isActive()) {
                continue;
            }

            $timeRange = sprintf('%s - %s', $availability->getStartTime()->format('H:i'), $availability->getEndTime()->format('H:i'));

            if ($availability->isRecurring() && $availability->getDayOfWeek() !== null) {
                $dayLabel = self::DAY_LABELS[$availability->getDayOfWeek()] ?? 'Jour';
                $rules[] = sprintf('Chaque %s, %s', strtolower($dayLabel), $timeRange);

                continue;
            }

            if ($availability->getSpecificDate() !== null) {
                $rules[] = sprintf('Le %s, %s', $availability->getSpecificDate()->format('d/m/Y'), $timeRange);
            }
        }

        return $rules;
    }

    /**
     * @param list<array{dateKey: string}> $availabilityPreview
     */
    private function resolveSelectedDateKey(array $availabilityPreview, string $dateTime): ?string
    {
        if ($availabilityPreview === []) {
            return null;
        }

        if (trim($dateTime) !== '') {
            try {
                $selectedDateKey = (new \DateTimeImmutable($dateTime))->format('Y-m-d');

                foreach ($availabilityPreview as $day) {
                    if ($day['dateKey'] === $selectedDateKey) {
                        return $selectedDateKey;
                    }
                }
            } catch (\Exception) {
            }
        }

        return $availabilityPreview[0]['dateKey'];
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue !== '' ? $normalizedValue : null;
    }

    private function parseNullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        $normalizedValue = (int) $value;

        return $normalizedValue > 0 ? $normalizedValue : null;
    }
}
