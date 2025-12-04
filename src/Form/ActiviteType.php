<?php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\AutorisationPointageActivite;
use App\Entity\Instance;
use App\Entity\Scout;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Dropzone\Form\DropzoneType;

class ActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete' => "off", 'required' => true],
                'label' => 'Titre  <sup class="text-danger">*</sup>',
                'label_html' => true,
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'required' => true
            ])
            //->add('slug')
            ->add('theme', TextType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete' => "off", ],
                'label' => 'Thème <sup class="text-danger">*</sup>',
                'label_html' => true,
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'required' => false,
            ])
            ->add('lieu', TextType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete' => "off"],
                'label' => 'Lieu <sup class="text-danger">*</sup>',
                'label_html' => true,
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'required' => true,
            ])
            ->add('dateDebutAt', DateType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete'=>'off'],
                'label' => 'Date début <sup class="text-danger">*</sup>',
                'label_html' => true,
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'required' => true
            ])
            ->add('heureDebut', TimeType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete'=>"off"],
                'label' => 'Heure début <sup class="text-danger">*</sup>',
                'label_html' => true,
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'required' => true
            ])
            ->add('dateFinAt', DateType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete'=>'off'],
                'label' => 'Date fin <sup class="text-danger">*</sup>',
                'label_html' => true,
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'required' => true
            ])
            ->add('heureFin', TimeType::class,[
                'attr' => ['class' => 'form-control form-control-lg rounded-pill', 'autocomplete'=>"off"],
                'label' => 'Heure fin <sup class="text-danger">*</sup>',
                'label_html' => true,
                'label_attr' => ['class' => 'text-muted fst-italic ps-3'],
                'required' => true
            ])
            ->add('description', TextareaType::class,[
                'attr' => ['class' => 'form-control', 'rows' => 5],
                'label' => 'Description  <sup class="text-danger">*</sup>',
                'label_html' => true,
                'label_attr' => ['class' => "text-muted fst-italic"],
                'required' => true
            ])
            ->add('tdr', FileType::class,[
                'attr' => ['class' => 'form-control'],
                'label' => "Téléchargez le TDR",
                'label_attr' => ['class' => 'text-muted fst-italic '],
                'required' => false
            ])
            ->add('affiche', DropzoneType::class,[
                'attr' => ['class' => 'form-control', 'placeholder' => "Cliquez pour télécharger l'affiche (360 x 200 pixels) "],
                'label' => "L'affiche de l'activité  <sup class='text-danger'>*</sup>",
                'label_attr' => ['class' => 'text-muted fst-italic'] ,
                'label_html' => true,
                'required' => false
            ])
            //->add('urlPointage')
//            ->add('instance', EntityType::class, [
//                'class' => Instance::class,
//                'choice_label' => 'id',
//            ])
//            ->add('autorisations', AutorisationAutocompleteField::class)
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activite::class,
        ]);
    }
}
