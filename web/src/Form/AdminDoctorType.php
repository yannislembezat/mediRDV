<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\AdminDoctorData;
use App\Entity\Specialty;
use App\Enum\Gender;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdminDoctorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<Specialty> $specialties */
        $specialties = $options['specialties'];
        $requirePassword = (bool) $options['require_password'];

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
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => $requirePassword,
                'invalid_message' => 'Les deux mots de passe doivent etre identiques.',
                'first_options' => [
                    'label' => $requirePassword ? 'Mot de passe initial' : 'Nouveau mot de passe',
                    'help' => $requirePassword ? null : 'Laisser vide pour conserver le mot de passe actuel.',
                ],
                'second_options' => [
                    'label' => 'Confirmation du mot de passe',
                ],
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
            ])
            ->add('specialty', ChoiceType::class, [
                'label' => 'Specialite',
                'placeholder' => 'Selectionner une specialite',
                'choices' => $specialties,
                'choice_label' => static fn (Specialty $specialty): string => $specialty->getName(),
                'choice_value' => static fn (?Specialty $specialty): ?string => $specialty?->getId() !== null ? (string) $specialty->getId() : null,
            ])
            ->add('officeLocation', TextType::class, [
                'label' => 'Cabinet',
                'required' => false,
            ])
            ->add('consultationDuration', IntegerType::class, [
                'label' => 'Duree de consultation (minutes)',
                'attr' => [
                    'min' => 10,
                    'max' => 180,
                    'step' => 5,
                ],
            ])
            ->add('yearsExperience', IntegerType::class, [
                'label' => 'Annees d experience',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => 80,
                ],
            ])
            ->add('diploma', TextType::class, [
                'label' => 'Diplome',
                'required' => false,
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdminDoctorData::class,
            'specialties' => [],
            'require_password' => true,
        ]);
        $resolver->setAllowedTypes('specialties', 'array');
        $resolver->setAllowedTypes('require_password', 'bool');
    }
}
