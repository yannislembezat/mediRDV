<?php

declare(strict_types=1);

namespace App\Controller\Patient;

use App\Repository\AppointmentRepository;
use App\Repository\MedecinProfileRepository;
use App\Repository\SpecialtyRepository;
use App\Service\Web\AvailabilityPreviewBuilder;
use App\Service\Web\PatientPortalContextBuilder;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly SpecialtyRepository $specialtyRepository,
        private readonly MedecinProfileRepository $medecinProfileRepository,
        private readonly AvailabilityPreviewBuilder $availabilityPreviewBuilder,
        private readonly PatientPortalContextBuilder $patientPortalContextBuilder,
    ) {
    }

    #[Route('/patient', name: 'patient_home', methods: ['GET'])]
    #[Route('/patient', name: 'patient_dashboard', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): Response
    {
        $featuredDoctors = array_slice($this->medecinProfileRepository->findActiveByFilters(), 0, 6);
        $featuredDoctor = $featuredDoctors[0] ?? null;

        return $this->render('patient/home.html.twig', array_merge([
            'user' => $user,
            'nextAppointment' => $this->appointmentRepository->findUpcomingForPatient($user, 1)[0] ?? null,
            'featuredDoctors' => $featuredDoctors,
            'specialties' => array_slice($this->specialtyRepository->findActiveOrdered(), 0, 6),
            'bookingPreviewDoctor' => $featuredDoctor,
            'bookingPreview' => $featuredDoctor !== null ? $this->availabilityPreviewBuilder->buildForMedecin($featuredDoctor) : [],
        ], $this->patientPortalContextBuilder->build($user)));
    }
}
