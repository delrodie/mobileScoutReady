<?php

namespace App\Form;

use App\Entity\Scout;
use App\Enum\ScoutStatut;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class AutorisationAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Scout::class,
            'placeholder' => '-- Sélectionnez le chef --',
            'multiple' => true,
            'autocomplete' => true,
            'label' => "Chefs autorisés à pointer",
            'label_attr' => ['class' => 'text-muted fst-italic'],
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                return $er->createQueryBuilder('s')
                    ->where('s.statut = :adulte')
                    ->setParameter('adulte', (ScoutStatut::ADULTE)->value)
                    ->orderBy('s.nom', 'ASC');
            },
            'required' => false
            // 'choice_label' => 'name',

            // choose which fields to use in the search
            // if not passed, *all* fields are used
            // 'searchable_fields' => ['name'],

            // 'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
