<?php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\AutorisationPointageActivite;
use App\Entity\Scout;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutorisationActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pointeurs', AutorisationAutocompleteField::class,[
                'required' => true
            ])
//            ->add('createdAt', null, [
//                'widget' => 'single_text',
//            ])
//            ->add('scout', EntityType::class, [
//                'class' => Scout::class,
//                'choice_label' => 'id',
//            ])
//            ->add('activite', EntityType::class, [
//                'class' => Activite::class,
//                'choice_label' => 'id',
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AutorisationPointageActivite::class,
        ]);
    }
}
