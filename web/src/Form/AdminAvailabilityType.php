<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\AdminAvailabilityData;
use App\Entity\MedecinProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdminAvailabilityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<MedecinProfile> $doctors */
        $doctors = $options['doctors'];

        $builder
            ->add('medecin', ChoiceType::class, [
                'label' => 'Medecin',
                'choices' => $doctors,
                'placeholder' => 'Selectionner un medecin',
                'choice_label' => static fn (MedecinProfile $medecin): string => sprintf(
                    '%s · %s',
                    $medecin->getDisplayName(),
                    $medecin->getSpecialty()?->getName() ?? 'Specialite a confirmer',
                ),
                'choice_value' => static fn (?MedecinProfile $medecin): ?string => $medecin?->getId() !== null ? (string) $medecin->getId() : null,
            ])
            ->add('mode', ChoiceType::class, [
                'label' => 'Type de disponibilite',
                'choices' => AdminAvailabilityData::modeChoices(),
                'expanded' => true,
            ])
            ->add('dayOfWeek', ChoiceType::class, [
                'label' => 'Jour de semaine',
                'required' => false,
                'placeholder' => 'Selectionner un jour',
                'choices' => AdminAvailabilityData::dayChoices(),
            ])
            ->add('specificDate', TextType::class, [
                'label' => 'Date precise',
                'required' => false,
                'attr' => [
                    'type' => 'date',
                ],
            ])
            ->add('startTime', TextType::class, [
                'label' => 'Heure de debut',
                'attr' => [
                    'type' => 'time',
                ],
            ])
            ->add('endTime', TextType::class, [
                'label' => 'Heure de fin',
                'attr' => [
                    'type' => 'time',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdminAvailabilityData::class,
            'doctors' => [],
        ]);
        $resolver->setAllowedTypes('doctors', 'array');
    }
}
