<?php

namespace App\Form;

use App\Entity\AutorisationPointageReunion;
use App\Entity\Reunion;
use App\Entity\Scout;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutorisationReunionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
//            ->add('role')
//            ->add('createdAt', null, [
//                'widget' => 'single_text',
//            ])
            ->add('pointeurs', AutorisationAutocompleteField::class,[
                'required' => true
            ])
//            ->add('reunion', EntityType::class, [
//                'class' => Reunion::class,
//                'choice_label' => 'id',
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AutorisationPointageReunion::class,
        ]);
    }
}
