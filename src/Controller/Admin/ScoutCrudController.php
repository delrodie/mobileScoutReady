<?php

namespace App\Controller\Admin;

use App\Entity\Scout;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Validator\Constraints\Image;

class ScoutCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Scout::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(), // L'ID n'est généralement pas modifiable
            TextField::new('slug')->setLabel('Slug (UUID)')->setFormTypeOption('disabled', 'disabled')->hideOnForm(),

            FormField::addColumn('col-md-6'),
            TextField::new('nom'),

            FormField::addColumn('col-md-6'),
            TextField::new('prenom'),

            FormField::addRow('lg'),
            FormField::addColumn('col-md-6 col-lg-4'),
            DateField::new('dateNaissance'),
            TelephoneField::new('telephone'),

            FormField::addColumn('col-md-6 col-lg-4'),
            ChoiceField::new('sexe')->setChoices([
                'Masculin' => 'M',
                'Feminin' => 'F'
            ]),
            EmailField::new('email'),

            FormField::addColumn('col-md-6 col-lg-4'),
            TextField::new('matricule'),


            TextField::new('code')->hideOnForm(),
            ImageField::new('qrCodeFile')->hideOnForm(),
            ImageField::new('photo')->setUploadDir('uploads/scouts/')->setFileConstraints(
                new Image(maxSize: '100K')
            ),

        ];
    }

}
