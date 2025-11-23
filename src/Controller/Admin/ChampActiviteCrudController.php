<?php

namespace App\Controller\Admin;

use App\Entity\ChampActivite;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ChampActiviteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ChampActivite::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn('col-8 offset-2 mt-5'),
            IdField::new('id')->hideOnForm(),
            TextField::new('titre')
                ->setFormTypeOption('attr.autocomplete', 'off'),
            TextEditorField::new('description'),
            ImageField::new('media', 'Télécharger la photo d\'illustration')
                ->setUploadDir('public/uploads/champs/')
                ->setBasePath('uploads/champs')
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('new', 'Créer un nouveau champs d\'activité');
    }

}
