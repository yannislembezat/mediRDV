<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\DoctorConsultationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DoctorConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notes', TextareaType::class, [
                'label' => 'Notes cliniques',
                'required' => false,
                'attr' => [
                    'rows' => 8,
                    'placeholder' => 'Resume de la consultation, examen clinique, conduite a tenir...',
                ],
            ])
            ->add('diagnosis', TextareaType::class, [
                'label' => 'Diagnostic',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Diagnostic retenu ou hypothese principale...',
                ],
            ])
            ->add('bloodPressure', TextType::class, [
                'label' => 'Tension arterielle',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. 12/8',
                ],
            ])
            ->add('heartRate', TextType::class, [
                'label' => 'Frequence cardiaque',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. 72 bpm',
                ],
            ])
            ->add('temperature', TextType::class, [
                'label' => 'Temperature',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. 36.8 C',
                ],
            ])
            ->add('weight', TextType::class, [
                'label' => 'Poids',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. 71 kg',
                ],
            ])
            ->add('oxygenSaturation', TextType::class, [
                'label' => 'Saturation O2',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. 98%',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer le brouillon',
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
            'data_class' => DoctorConsultationData::class,
        ]);
    }
}
