<?php

namespace App\Form;

use App\Entity\Scout;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfilEditCivilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
//            ->add('slug')
//            ->add('matricule')
//            ->add('code')
//            ->add('qrCodeToken')
            ->add('nom', TextType::class,[
                'attr' => [
                    'class' => 'form-control rounded-start-pill rounded-end-pill input-style  ps-5',
                    'placeholder' => "Nom de famille", 'autocomplete' => "off"
                    ],
                'label' => "Nom de famille",
                'label_attr' => ['class' => 'ms-3', 'style' => 'color: #b0b7be'],
                'required' => true,
            ])
            ->add('prenom', TextType::class,[
                'attr' => [
                    'class' => 'form-control rounded-start-pill rounded-end-pill input-style  ps-5',
                    'placeholder' => "Prenoms", 'autocomplete' => "off"
                    ],
                'label' => "Prenoms",
                'label_attr' => ['class' => 'ms-3', 'style' => 'color: #b0b7be'],
                'required' => true,
            ])
            ->add('dateNaissance', DateType::class,[
                'attr' => [
                    'class' => 'form-control rounded-start-pill rounded-end-pill input-style  ps-5',
                    'placeholder' => "Date de naissance", 'autocomplete' => "off"
                ],
                'label' => "Date de naissance",
                'label_attr' => ['class' => 'ms-3', 'style' => 'color: #b0b7be'],
                'required' => true,
            ])
            ->add('sexe', ChoiceType::class,[
                'attr' => [
                    'class' => 'form-control rounded-start-pill rounded-end-pill input-style  ps-5',
                    'placeholder' => "Sexe", 'autocomplete' => "off"
                ],
                'label' => "Sexe",
                'label_attr' => ['class' => 'ms-3', 'style' => 'color: #b0b7be'],
                'required' => true,
                'choices' =>[
                    'Homme' => 'HOMME',
                    'Femme' => 'FEMME'
                ]
            ])
            ->add('telephone', TelType::class,[
                'attr' => [
                    'class' => 'form-control rounded-start-pill rounded-end-pill input-style  ps-5',
                    'placeholder' => "Téléphone", 'autocomplete' => "off", 'readonly' => true
                ],
                'label' => "Téléphone",
                'label_attr' => ['class' => 'ms-3', 'style' => 'color: #b0b7be'],
                'required' => true,
            ])
            ->add('email', EmailType::class,[
                'attr' => [
                    'class' => 'form-control rounded-start-pill rounded-end-pill input-style  ps-5',
                    'placeholder' => "Email", 'autocomplete' => "off"
                ],
                'label' => "Adresse email",
                'label_attr' => ['class' => 'ms-3', 'style' => 'color: #b0b7be'],
                'required' => true,
            ])
//            ->add('qrCodeFile')
//            ->add('photo')
//            ->add('statut')
//            ->add('createdAt', null, [
//                'widget' => 'single_text',
//            ])
            ->add('phoneParent', ChoiceType::class,[
                'attr' => [
                    'class' => 'form-control rounded-start-pill rounded-end-pill input-style  ps-5',
                    'placeholder' => "Est-ce le telephone du parent?", 'autocomplete' => "off"
                ],
                'label' => "Est-ce le téléphone du parent?",
                'label_attr' => ['class' => 'ms-3', 'style' => 'color: #b0b7be'],
                'required' => true,
                'choices' => [
                    'Oui' => true,
                    'non' => false
                ]
            ])
//            ->add('utilisateur', EntityType::class, [
//                'class' => Utilisateur::class,
//                'choice_label' => 'id',
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Scout::class,
        ]);
    }
}
