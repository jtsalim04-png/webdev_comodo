<?php

namespace App\Controller\Admin;

use App\Service\AdminDashboardDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    #[Route('/dashboard', name: 'admin_dashboard_alt', methods: ['GET'])]
    public function dashboard(AdminDashboardDataProvider $dashboardDataProvider): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard.html.twig', $dashboardDataProvider->getDashboardData());
    }
}
