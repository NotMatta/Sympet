<?php

namespace App\Controller\Admin;

use App\Controller\ProduitController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use App\Repository\CommandeRepository;
use App\Repository\UserRepository;
use App\Controller\Admin\ProduitCrudController;
use App\Controller\Admin\CategorieCrudController;
use App\Controller\Admin\CommandeCrudController;
use App\Controller\Admin\UserCrudController;


#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private ProduitRepository $produitRepository,
        private CategorieRepository $categorieRepository,
        private CommandeRepository $commandeRepository,
        private UserRepository $userRepository,
    ) {}

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'total_products' => $this->produitRepository->count([]),
            'total_categories' => $this->categorieRepository->count([]),
            'total_orders' => $this->commandeRepository->count([]),
            'total_users' => $this->userRepository->count([]),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Sympet');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(ProduitCrudController::class, 'Products', 'fas fa-shopping-bag');
        yield MenuItem::linkTo(CategorieCrudController::class, 'Categories', 'fas fa-list');
        yield MenuItem::linkTo(CommandeCrudController::class, 'Orders', 'fas fa-box');
        yield MenuItem::linkTo(UserCrudController::class, 'Users', 'fas fa-users');
    }
}
