<?php

namespace App\Controller\Admin;

use App\Entity\Scout;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public function index(): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(ScoutCrudController::class)
            ->generateUrl();

        return $this->redirect($url);



    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('ScoutReady App');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section();
         yield MenuItem::subMenu('Scout', 'fas fa-users')->setSubItems([
             MenuItem::linkToCrud('Liste', 'fa fa-bars', Scout::class),
             MenuItem::linkToCrud('Ajouter un scout', 'fas fa-plus', Scout::class)->setAction(Crud::PAGE_NEW)
         ]);

         yield MenuItem::section();
         yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
    }
}
