<?php

declare(strict_types=1);

namespace App\Controller\Patient;

use App\DTO\PatientProfileUpdateData;
use App\Entity\User;
use App\Form\PatientProfileType;
use App\Repository\AppointmentRepository;
use App\Repository\ConsultationRepository;
use App\Service\Exception\DuplicateUserEmailException;
use App\Service\UserService;
use App\Service\Web\PatientPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly ConsultationRepository $consultationRepository,
        private readonly UserService $userService,
        private readonly PatientPortalContextBuilder $patientPortalContextBuilder,
    ) {
    }

    #[Route('/patient/profil', name: 'patient_profile', methods: ['GET', 'POST'])]
    public function show(Request $request, #[CurrentUser] User $patient): Response
    {
        $records = $this->consultationRepository->findCompletedForPatient($patient);

        $profileData = $this->createProfileData($patient);
        $profileForm = $this->createForm(PatientProfileType::class, $profileData);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            try {
                $this->userService->updateProfile($patient, $profileData);
                $this->addFlash('success', 'Votre profil a ete mis a jour.');

                return $this->redirectToRoute('patient_profile');
            } catch (DuplicateUserEmailException $exception) {
                $profileForm->get('email')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('patient/profile/show.html.twig', array_merge([
            'patient' => $patient,
            'nextAppointment' => $this->appointmentRepository->findUpcomingForPatient($patient, 1)[0] ?? null,
            'appointmentCount' => count($this->appointmentRepository->findForPatient($patient)),
            'recordCount' => count($records),
            'latestRecord' => $records[0] ?? null,
            'profileForm' => $profileForm->createView(),
        ], $this->patientPortalContextBuilder->build($patient)));
    }

    private function createProfileData(User $patient): PatientProfileUpdateData
    {
        $profileData = new PatientProfileUpdateData();
        $profileData->email = $patient->getEmail();
        $profileData->firstName = $patient->getFirstName();
        $profileData->lastName = $patient->getLastName();
        $profileData->phone = $patient->getPhone();
        $profileData->dateOfBirth = $patient->getDateOfBirth()?->format('Y-m-d');
        $profileData->gender = $patient->getGender()?->value;
        $profileData->address = $patient->getAddress();

        return $profileData;
    }
}
