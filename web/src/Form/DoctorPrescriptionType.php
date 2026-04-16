<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\DoctorPrescriptionData;
use App\Entity\Medication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DoctorPrescriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<Medication> $medications */
        $medications = $options['medications'];

        $builder
            ->add('notes', TextareaType::class, [
                'label' => 'Notes generales',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Conseils generaux, examens complementaires, rappel de suivi...',
                ],
            ])
            ->add('items', CollectionType::class, [
                'label' => false,
                'entry_type' => DoctorPrescriptionItemType::class,
                'entry_options' => [
                    'medications' => $medications,
                ],
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer l ordonnance',
                'attr' => [
                    'class' => 'btn btn-outline-primary',
                ],
            ])
            ->add('finalize', SubmitType::class, [
                'label' => 'Finaliser la consultation',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DoctorPrescriptionData::class,
            'medications' => [],
        ]);
        $resolver->setAllowedTypes('medications', 'array');
    }
}
