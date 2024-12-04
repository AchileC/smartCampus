<?php

namespace App\Form;

use App\Entity\AcquisitionSystem;
use App\Utils\SensorStateEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for filtering AcquisitionSystem entities.
 * Includes fields for name, state, and action buttons for filtering or resetting.
 */
class FilterASType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Search field for the AcquisitionSystem name
            ->add('name', TextType::class, [
                'label' => null,
                'required' => false,
                'attr' => ['placeholder' => 'Search for an as by name'],
            ])
            // Dropdown for filtering by state using the SensorStateEnum
            ->add('state', ChoiceType::class, [
                'choices' => [
                    'Linked' => SensorStateEnum::LINKED,
                    'Not Linked' => SensorStateEnum::NOT_LINKED,
                ],
                'required' => false,
                'placeholder' => 'Select a State',
                'label' => null,
                'choice_label' => function ($choice, $key, $value) {
                    return $key;
                },
                'choice_value' => function (?SensorStateEnum $state) {
                    return $state?->value;
                },
            ])
            // Submit button to apply the filters
            ->add('filter', SubmitType::class, [
                'label' => 'Search',
                'attr' => ['class' => 'btn btn-primary']
            ])
            // Reset button to clear the filters
            ->add('reset', SubmitType::class, [
                'label' => 'Reset',
                'attr' => [
                    'class' => 'btn btn-secondary',
                    'formnovalidate' => 'formnovalidate',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Specifies the class associated with this form
        $resolver->setDefaults([
            'data_class' => AcquisitionSystem::class,
        ]);
    }
}
