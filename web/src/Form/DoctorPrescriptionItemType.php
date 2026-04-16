<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\DoctorPrescriptionItemData;
use App\Entity\Medication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DoctorPrescriptionItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<Medication> $medications */
        $medications = $options['medications'];

        $builder
            ->add('medication', ChoiceType::class, [
                'label' => 'Medicament du catalogue',
                'required' => false,
                'placeholder' => 'Selectionner un medicament',
                'choices' => $medications,
                'choice_label' => static function (Medication $medication): string {
                    $suffix = $medication->getDefaultDosage() !== null ? ' · '.$medication->getDefaultDosage() : '';

                    return $medication->getName().$suffix;
                },
                'choice_value' => static fn (?Medication $medication): ?string => $medication?->getId() !== null ? (string) $medication->getId() : null,
            ])
            ->add('customName', TextType::class, [
                'label' => 'Ou nom libre',
                'required' => false,
            ])
            ->add('dosage', TextType::class, [
                'label' => 'Posologie',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. 1 comprime matin et soir',
                ],
            ])
            ->add('duration', TextType::class, [
                'label' => 'Duree',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. 7 jours',
                ],
            ])
            ->add('frequency', TextType::class, [
                'label' => 'Frequence',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. 2 fois par jour',
                ],
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'Instructions',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Conseils particuliers de prise...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DoctorPrescriptionItemData::class,
            'medications' => [],
        ]);
        $resolver->setAllowedTypes('medications', 'array');
    }
}
