<?php

namespace App\Controller\Admin;

use App\Entity\Instance;
use App\Enum\InstanceType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

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
    

}
