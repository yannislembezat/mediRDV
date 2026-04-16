<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\AdminAvailabilityData;
use App\DTO\AdminDoctorData;
use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Form\AdminDoctorType;
use App\Repository\AppointmentRepository;
use App\Repository\AvailabilityRepository;
use App\Repository\MedecinProfileRepository;
use App\Repository\SpecialtyRepository;
use App\Service\DoctorAdminService;
use App\Service\Exception\DuplicateUserEmailException;
use App\Service\Web\AdminPortalContextBuilder;
use App\Service\Web\AvailabilityPreviewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DoctorController extends AbstractController
{
    public function __construct(
        private readonly MedecinProfileRepository $medecinProfileRepository,
        private readonly SpecialtyRepository $specialtyRepository,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly AvailabilityRepository $availabilityRepository,
        private readonly DoctorAdminService $doctorAdminService,
        private readonly AvailabilityPreviewBuilder $availabilityPreviewBuilder,
        private readonly AdminPortalContextBuilder $adminPortalContextBuilder,
    ) {
    }

    #[Route('/admin/medecins', name: 'admin_medecins', methods: ['GET', 'POST'])]
    public function index(Request $request, #[CurrentUser] User $admin): Response
    {
        $search = $this->normalizeOptionalString($request->query->get('search'));
        $selectedSpecialtyId = $this->resolveId($request->query->get('specialty'));
        $selectedState = $this->resolveStateFilter($request->query->get('state'));

        $doctorData = new AdminDoctorData();
        $createForm = $this->createForm(AdminDoctorType::class, $doctorData, [
            'specialties' => $this->specialtyRepository->findActiveOrdered(),
            'require_password' => true,
        ]);
        $createForm->handleRequest($request);

        if ($createForm->isSubmitted() && $createForm->isValid()) {
            try {
                $doctor = $this->doctorAdminService->create($doctorData);
                $this->addFlash('success', 'Le medecin a ete cree.');

                return $this->redirectToRoute('admin_medecins');
            } catch (DuplicateUserEmailException $exception) {
                $createForm->get('email')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('admin/medecins/list.html.twig', array_merge([
            'doctors' => $this->medecinProfileRepository->findForAdmin($search, $selectedSpecialtyId, $selectedState),
            'specialties' => $this->specialtyRepository->findActiveOrdered(),
            'selectedSearch' => $search,
            'selectedSpecialtyId' => $selectedSpecialtyId,
            'selectedState' => $selectedState,
            'createForm' => $createForm->createView(),
        ], $this->adminPortalContextBuilder->build($admin)));
    }

    #[Route('/admin/medecins/{id}', name: 'admin_medecin_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request, #[CurrentUser] User $admin): Response
    {
        $doctor = $this->medecinProfileRepository->findOneForAdmin($id);

        if (!$doctor instanceof MedecinProfile) {
            throw $this->createNotFoundException('Le medecin demande est introuvable.');
        }

        $doctorData = $this->createDoctorData($doctor);
        $editForm = $this->createForm(AdminDoctorType::class, $doctorData, [
            'specialties' => $this->specialtyRepository->findActiveOrdered(),
            'require_password' => false,
        ]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->doctorAdminService->update($doctor, $doctorData);
                $this->addFlash('success', 'La fiche medecin a ete mise a jour.');

                return $this->redirectToRoute('admin_medecin_show', ['id' => $doctor->getId()]);
            } catch (DuplicateUserEmailException $exception) {
                $editForm->get('email')->addError(new FormError($exception->getMessage()));
            }
        }

        $availabilities = $this->availabilityRepository->findActiveForMedecin($doctor);

        return $this->render('admin/medecins/show.html.twig', array_merge([
            'doctor' => $doctor,
            'availabilityPreview' => $this->availabilityPreviewBuilder->buildForMedecin($doctor, 28, 5, 6),
            'upcomingAppointments' => $this->appointmentRepository->findUpcomingForMedecinAdmin($doctor),
            'activeAvailabilityCount' => count($availabilities),
            'specificAvailabilityCount' => count(array_filter($availabilities, static fn ($availability): bool => !$availability->isRecurring())),
            'stats' => [
                'total' => $this->appointmentRepository->countForMedecin($doctor),
                'pending' => $this->appointmentRepository->countForMedecin($doctor, \App\Enum\AppointmentStatus::PENDING),
                'confirmed' => $this->appointmentRepository->countForMedecin($doctor, \App\Enum\AppointmentStatus::CONFIRMED),
                'completed' => $this->appointmentRepository->countForMedecin($doctor, \App\Enum\AppointmentStatus::COMPLETED),
            ],
            'dayLabels' => AdminAvailabilityData::dayLabels(),
            'availabilities' => $availabilities,
            'editForm' => $editForm->createView(),
        ], $this->adminPortalContextBuilder->build($admin)));
    }

    #[Route('/admin/medecins/{id}/toggle', name: 'admin_medecin_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(int $id, Request $request): Response
    {
        $doctor = $this->medecinProfileRepository->findOneForAdmin($id);

        if (!$doctor instanceof MedecinProfile) {
            throw $this->createNotFoundException('Le medecin demande est introuvable.');
        }

        if (!$this->isCsrfTokenValid(sprintf('toggle_doctor_%d', $doctor->getId()), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Le jeton CSRF est invalide. Veuillez reessayer.');

            return $this->redirectToRoute('admin_medecin_show', ['id' => $doctor->getId()]);
        }

        $doctor = $this->doctorAdminService->toggle($doctor);
        $doctorUser = $doctor->getUser();

        $this->addFlash(
            'success',
            $doctorUser !== null && $doctorUser->isActive()
                ? 'Le medecin a ete reactive.'
                : 'Le medecin a ete desactive.',
        );

        return $this->redirectToRoute('admin_medecin_show', ['id' => $doctor->getId()]);
    }

    private function createDoctorData(MedecinProfile $doctor): AdminDoctorData
    {
        $doctorData = new AdminDoctorData();
        $doctorUser = $doctor->getUser();

        if ($doctorUser instanceof User) {
            $doctorData->email = $doctorUser->getEmail();
            $doctorData->firstName = $doctorUser->getFirstName();
            $doctorData->lastName = $doctorUser->getLastName();
            $doctorData->phone = $doctorUser->getPhone();
            $doctorData->dateOfBirth = $doctorUser->getDateOfBirth()?->format('Y-m-d');
            $doctorData->gender = $doctorUser->getGender()?->value;
            $doctorData->address = $doctorUser->getAddress();
        }

        $doctorData->specialty = $doctor->getSpecialty();
        $doctorData->bio = $doctor->getBio();
        $doctorData->consultationDuration = $doctor->getConsultationDuration();
        $doctorData->officeLocation = $doctor->getOfficeLocation();
        $doctorData->yearsExperience = $doctor->getYearsExperience();
        $doctorData->diploma = $doctor->getDiploma();

        return $doctorData;
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue !== '' ? $normalizedValue : null;
    }

    private function resolveId(mixed $value): ?int
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

    private function resolveStateFilter(mixed $value): ?bool
    {
        if (!is_scalar($value)) {
            return null;
        }

        return match (trim((string) $value)) {
            'active' => true,
            'inactive' => false,
            default => null,
        };
    }
}
