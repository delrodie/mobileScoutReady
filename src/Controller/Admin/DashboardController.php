<?php

namespace App\Controller\Admin;

use App\Entity\ChampActivite;
use App\Entity\Instance;
use App\Entity\Scout;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
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

         yield MenuItem::section('Gestion');
         yield MenuItem::linkToCrud("Champs d'activités", 'fa-solid fa-signs-post', ChampActivite::class);
         yield MenuItem::subMenu('Instances', 'fas fa-layer-group')->setSubItems([
             MenuItem::linkToCrud('Liste des instances', 'fas fa-list', Instance::class),
             MenuItem::linkToRoute('Importer des instances', 'fas fa-file-import', 'admin_import_excel_instances')
         ]);

         yield MenuItem::section('Sécurité');
         yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);

         yield MenuItem::section('Paramètres');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setName($user->getUserIdentifier())
            ->setGravatarEmail($user->getUserIdentifier())
            ->addMenuItems([
                MenuItem::linkToLogout('Deconnexion', 'fa fa-sign-out')
            ]);
    }
}
