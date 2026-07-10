<?php

declare(strict_types=1);

namespace App\Module\Task\Controller\Dashboard;

use App\Module\Task\Service\DashboardPageProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardPageProvider $dashboardPageProvider,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', $this->dashboardPageProvider->getPageData());
    }
}
