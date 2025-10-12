<?php

namespace App\Controller\Admin;

use App\Entity\Instance;
use App\Enum\InstanceType;
use App\Services\InstanceImportService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InstanceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Instance::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Liste des instances')
            ->setPageTitle('new', "Enregistrement d'une nouvelle instance")
            ->setPageTitle('edit', fn(Instance $instance) => sprintf('Modification de <b>%s</b>', $instance->getNom()))

            ->setSearchFields(['instanceParent', 'nom', 'sigle'])
            ->setAutofocusSearch(true)
            ;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn('col-md-6 offset-md-3 mt-5'),
            IdField::new('id')->hideOnForm(),
//            TextField::new('slug')->hideOnForm(),

            ChoiceField::new('type')
                ->setFormType(EnumType::class)
                ->setFormTypeOption('class', InstanceType::class)
                ->formatValue(fn ($value) => $value?->label() ?? ''),
            AssociationField::new('instanceParent')
                ->setFormType(EntityType::class)
                ->setFormTypeOption('class', Instance::class)
            ->autocomplete(),
            TextField::new('nom')
                ->setFormTypeOption('attr', ['autocomplete' => 'off']),
            TextField::new('sigle')
                ->setFormTypeOption('attr', ['autocomplete' => 'off']),
        ];
    }

    #[Route('/admin/instance/import/excel', name: 'admin_import_excel', methods: ['GET','POST'])]
    public function instance(Request $request, InstanceImportService $importService, AdminUrlGenerator $adminUrlGenerator): Response
    {
        if (
            $request->isMethod('POST') &&
            $this->isCsrfTokenValid('instanceImported', $request->getPayload()->getString('_instanceCsrfToken'))
        ){
            $file = $request->files->get('instance_file');

            if (!$file || $file->getClientOriginalExtension() !== 'xlsx') {
                $this->addFlash("error", "Échec, Veuillez uploader un fichierExcel (.xlsx)");
                return $this->redirectToRoute('admin_import_excel_instance');
            }

            try{
                $result = $importService->import($file);
                $this->addFlash('success', "Importation terminée: {$result['imported']} instances ajoutées, {$result['skipped']} ignorés");

                if(!empty($result['errors'])){
                    foreach ($result['errors'] as $error){
                        $this->addFlash("error", $error);
                    }
                }

                return $this->redirectToRoute('admin_instance_index');
            } catch(\Throwable $e){
                $this->addFlash("error", "Erreur d'importation: {$e->getMessage()}");
            }

        }
        return $this->render('@EasyAdmin/page/content.html.twig', [
            'page_title' => 'Test affichage',
            'page-content' => '<div style="padding:2rem;background:#eef">TEST : contenu injecté ici</div>',
        ]);

    }


}
