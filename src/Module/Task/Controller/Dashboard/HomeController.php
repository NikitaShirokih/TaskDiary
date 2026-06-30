<?php

declare(strict_types=1);

namespace App\Module\Task\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->redirectToRoute('app_login');
    }
}
