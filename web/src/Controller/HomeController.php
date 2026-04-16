<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class HomeController extends AbstractController
{
    public function __invoke(): Response
    {
        if ($this->getUser() instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_post_login_redirect');
        }

        return $this->redirectToRoute('app_login');
    }
}
