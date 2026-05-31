<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\UserRole;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::ADMIN->value)]
#[Route('/admin', name: 'admin_')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('', name: 'panel', methods: ['GET'])]
    public function panel(): Response
    {
        return $this->render('admin/admin_panel.html.twig');
    }

    #[Route('/tasks', name: 'tasks', methods: ['GET'])]
    public function tasks(): Response
    {
        return $this->render('admin/all_tasks.html.twig', [
            'tasks' => $this->taskRepository->findAll(),
        ]);
    }

    #[Route('/users', name: 'users', methods: ['GET'])]
    public function users(): Response
    {
        return $this->render('admin/admin_users.html.twig', [
            'users' => $this->userRepository->findAll(),
        ]);
    }
}
