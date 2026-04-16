<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\AppointmentRequestData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PatientBookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateTime', HiddenType::class, [
                'required' => false,
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Motif de consultation',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Precisez le contexte de votre demande si vous le souhaitez.',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppointmentRequestData::class,
        ]);
    }
}
