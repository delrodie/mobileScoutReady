<?php

namespace App\Form;

use App\Entity\ChampActivite;
use App\Entity\Instance;
use App\Entity\Reunion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReunionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete'=>'off'],
                'label' => 'Titre <sup class="text-danger">*</sup>',
                'label_html' => true,
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'required' => true
            ])
            ->add('objectif', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'label' => 'Objectifs <sup class="text-danger">*</sup>',
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'label_html' => true,
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'label' => 'Description <sup class="text-danger">*</sup>',
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'label_html' => true,
                'required' => true
            ])
            ->add('attente', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'label' => 'Résultats attendus <sup class="text-danger">*</sup>',
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'label_html' => true,
                'required' => true
            ])
            ->add('lieu', TextType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete'=>'off'],
                'label' => 'Le lieu <sup class="text-danger">*</sup>',
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'label_html' => true,
                'required' => true,
            ])
            ->add('dateAt', DateType::class, [
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autcomplete' =>"off"],
                'label' => 'Date <sup class="text-danger">*</sup>',
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'label_html' => true,
                'required' => true,
            ])
            ->add('heureDebut', TimeType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete' => 'off'],
                'label' => 'Heure début <sup class="text-danger">*</sup>',
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'label_html' => true,
                'required' => true,
            ])
            ->add('heureFin', TimeType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete' => 'off'],
                'label' => 'Heure fin <sup class="text-danger">*</sup>',
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'label_html' => true,
                'required' => true,
            ])
            ->add('cible', ChoiceType::class,[
                'choices' => [
                    '-- Selectionnez la cible -- ' => '',
                    'Tous les jeunes' => 'Tous les jeunes',
                    'Tous les adultes' => 'Tous les adultes',
                    "Tous les chefs d'unités" => "Tous les chefs d'unités",
                    "Equipe régionale" => "Equipe régionale",
                    "CD" => "CD",
                    "Equipe de district" => "Equipe de district",
                    "CG" => "CG",
                    "Maîtrise de groupe" => "Maître de groupe",
                    "Chefs des oisillons" => "Chefs des oisillons",
                    "Chefs de meute" => "Chefs de meute",
                    "Chefs de troupe" => "Chefs de troupe",
                    "Chefs de génération" => "Chefs de génération",
                    "Chefs de communauté" => "Chefs de communauté",
                    "Oisilons" => "Oisillions",
                    "Louveteaux" => "Louveteaux",
                    "Eclaireurs" => "Eclaireurs",
                    "Cheminots" => "Cheminots",
                    "Routiers" => "Routiers"
                ],
                'autocomplete' => true,
                'multiple' => true,
                'label' => 'La cible  <sup class="text-danger">*</sup> ',
                "label_attr" => ['class' => 'text-muted fst-italic'],
                "label_html" => true
            ])
            ->add('branche', ChoiceType::class,[
                'choices' => [
                    '-- Sélectionnez --' => '',
                    'Oisillons' => 'Oisillons',
                    'Meute' => 'Meute',
                    'Troupe' => 'Troupe',
                    'Generation' => 'Generation',
                    'Communaute' => 'Communaute',
                    'Aucune' => ''
                ],
                'autocomplete' => true,
                'label' => 'La branche',
                'label_attr' => ['class' => 'text-muted fst-italic'],
                'required' => false
            ])
//            ->add('urlPointage')
//            ->add('createdAt', null, [
//                'widget' => 'single_text',
//            ])
//            ->add('createdBy')
            ->add('champs', EntityType::class, [
                'class' => ChampActivite::class,
                'choice_label' => 'titre',
                'autocomplete' => true,
                'label' => 'Champs d\'activité  ',
                'label_attr' => ['class' => 'text-muted fst-italic'],
                'label_html' => true,
                'required' => false
            ])
//            ->add('instance', EntityType::class, [
//                'class' => Instance::class,
//                'choice_label' => 'id',
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reunion::class,
        ]);
    }
}
