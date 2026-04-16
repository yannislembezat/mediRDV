<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\PatientRegistrationData;
use App\Entity\User;
use App\Form\PatientRegistrationType;
use App\Service\Exception\DuplicateUserEmailException;
use App\Service\Security\PostLoginRedirectResolver;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('app_post_login_redirect');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/login', name: 'app_login_check', methods: ['POST'])]
    public function loginCheck(): never
    {
        throw new \LogicException('Cette action est interceptee par le firewall de connexion.');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserService $userService): Response
    {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('app_post_login_redirect');
        }

        $registrationData = new PatientRegistrationData();
        $form = $this->createForm(PatientRegistrationType::class, $registrationData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $userService->registerPatient($registrationData);
                $this->addFlash('success', 'Votre compte patient a ete cree. Vous pouvez maintenant vous connecter.');

                return $this->redirectToRoute('app_login');
            } catch (DuplicateUserEmailException $exception) {
                $form->get('email')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/redirect', name: 'app_post_login_redirect', methods: ['GET'])]
    public function redirectAfterLogin(PostLoginRedirectResolver $postLoginRedirectResolver): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute($postLoginRedirectResolver->resolveRouteName($user));
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new \LogicException('Cette action est interceptee par le firewall de deconnexion.');
    }
}
