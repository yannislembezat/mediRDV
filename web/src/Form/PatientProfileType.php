<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\PatientProfileUpdateData;
use App\Enum\Gender;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PatientProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prenom',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telephone',
                'required' => false,
            ])
            ->add('dateOfBirth', TextType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'attr' => [
                    'type' => 'date',
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Genre',
                'required' => false,
                'placeholder' => 'Selectionner',
                'choices' => Gender::choices(),
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PatientProfileUpdateData::class,
        ]);
    }
}
