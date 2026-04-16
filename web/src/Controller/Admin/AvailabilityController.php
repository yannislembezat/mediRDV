<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\AdminAvailabilityData;
use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Form\AdminAvailabilityType;
use App\Repository\AvailabilityRepository;
use App\Repository\MedecinProfileRepository;
use App\Service\AvailabilityAdminService;
use App\Service\Web\AdminPortalContextBuilder;
use App\Service\Web\AvailabilityPreviewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AvailabilityController extends AbstractController
{
    public function __construct(
        private readonly MedecinProfileRepository $medecinProfileRepository,
        private readonly AvailabilityRepository $availabilityRepository,
        private readonly AvailabilityAdminService $availabilityAdminService,
        private readonly AvailabilityPreviewBuilder $availabilityPreviewBuilder,
        private readonly AdminPortalContextBuilder $adminPortalContextBuilder,
    ) {
    }

    #[Route('/admin/disponibilites', name: 'admin_availability', methods: ['GET'])]
    public function index(Request $request, #[CurrentUser] User $admin): Response
    {
        $doctors = $this->medecinProfileRepository->findForAdmin(null, null, true);
        $selectedDoctor = $this->resolveSelectedDoctor($request->query->get('doctor'), $doctors);
        $availabilities = $selectedDoctor !== null ? $this->availabilityRepository->findActiveForMedecin($selectedDoctor) : [];

        return $this->render('admin/availability/calendar.html.twig', array_merge([
            'doctors' => $doctors,
            'selectedDoctor' => $selectedDoctor,
            'recurringAvailabilities' => array_values(array_filter(
                $availabilities,
                static fn ($availability): bool => $availability->isRecurring(),
            )),
            'specificAvailabilities' => array_values(array_filter(
                $availabilities,
                static fn ($availability): bool => !$availability->isRecurring(),
            )),
            'availabilityPreview' => $selectedDoctor !== null ? $this->availabilityPreviewBuilder->buildForMedecin($selectedDoctor, 28, 6, 8) : [],
            'dayLabels' => AdminAvailabilityData::dayLabels(),
        ], $this->adminPortalContextBuilder->build($admin)));
    }

    #[Route('/admin/disponibilites/ajouter', name: 'admin_availability_add', methods: ['GET', 'POST'])]
    public function add(Request $request, #[CurrentUser] User $admin): Response
    {
        $doctors = $this->medecinProfileRepository->findForAdmin(null, null, true);

        if ($doctors === []) {
            $this->addFlash('warning', 'Ajoutez d abord un medecin actif avant de creer une disponibilite.');

            return $this->redirectToRoute('admin_medecin_create');
        }

        $availabilityData = new AdminAvailabilityData();
        $availabilityData->medecin = $this->resolveSelectedDoctor($request->query->get('doctor'), $doctors) ?? $doctors[0];

        $availabilityForm = $this->createForm(AdminAvailabilityType::class, $availabilityData, [
            'doctors' => $doctors,
        ]);
        $availabilityForm->handleRequest($request);

        if ($availabilityForm->isSubmitted() && $availabilityForm->isValid()) {
            $availability = $this->availabilityAdminService->create($availabilityData);
            $this->addFlash('success', 'Le creneau de disponibilite a ete ajoute.');

            return $this->redirectToRoute('admin_availability', [
                'doctor' => $availability->getMedecin()?->getId(),
            ]);
        }

        return $this->render('admin/availability/add.html.twig', array_merge([
            'availabilityForm' => $availabilityForm->createView(),
        ], $this->adminPortalContextBuilder->build($admin)));
    }

    #[Route('/admin/disponibilites/{id}/supprimer', name: 'admin_availability_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): Response
    {
        $availability = $this->availabilityRepository->findOneForAdminById($id);

        if ($availability === null) {
            throw $this->createNotFoundException('La disponibilite demandee est introuvable.');
        }

        if (!$this->isCsrfTokenValid(sprintf('delete_availability_%d', $availability->getId()), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Le jeton CSRF est invalide. Veuillez reessayer.');

            return $this->redirectToRoute('admin_availability', [
                'doctor' => $availability->getMedecin()?->getId(),
            ]);
        }

        $doctorId = $availability->getMedecin()?->getId();
        $this->availabilityAdminService->delete($availability);
        $this->addFlash('success', 'Le creneau de disponibilite a ete supprime.');

        return $this->redirectToRoute('admin_availability', [
            'doctor' => $doctorId,
        ]);
    }

    /**
     * @param list<MedecinProfile> $doctors
     */
    private function resolveSelectedDoctor(mixed $value, array $doctors): ?MedecinProfile
    {
        $selectedDoctorId = null;

        if (is_scalar($value)) {
            $normalizedValue = trim((string) $value);

            if ($normalizedValue !== '' && ctype_digit($normalizedValue)) {
                $selectedDoctorId = (int) $normalizedValue;
            }
        }

        if ($selectedDoctorId !== null) {
            foreach ($doctors as $doctor) {
                if ($doctor->getId() === $selectedDoctorId) {
                    return $doctor;
                }
            }
        }

        return $doctors[0] ?? null;
    }
}
