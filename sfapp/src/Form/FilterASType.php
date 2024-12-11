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
 * Class FilterASType
 *
 * Defines the form used to filter AcquisitionSystem entities.
 * The form includes fields for name, state, and action buttons for filtering or resetting the filters.
 *
 * @package App\Form
 */
class FilterASType extends AbstractType
{
    /**
     * Builds the form for filtering AcquisitionSystem entities.
     *
     * @param FormBuilderInterface $builder The form builder used to construct the form.
     * @param array                $options An array of options passed to the form.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /**
             * Adds a text field for searching AcquisitionSystem by name.
             *
             * @var TextType $name
             */
            ->add('name', TextType::class, [
                'label' => null,
                'required' => false,
                'attr' => ['placeholder' => 'Search for an as by name'],
            ])
            /**
             * Adds a choice field for filtering AcquisitionSystem by state.
             * The choices are derived from the SensorStateEnum.
             *
             * @var ChoiceType $state
             */
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
            /**
             * Adds a submit button to apply the filters.
             *
             * @var SubmitType $filter
             */
            ->add('filter', SubmitType::class, [
                'label' => 'Search',
                'attr' => ['class' => 'btn btn-primary']
            ])
            /**
             * Adds a submit button to reset the filters.
             *
             * @var SubmitType $reset
             */
            ->add('reset', SubmitType::class, [
                'label' => 'Reset',
                'attr' => [
                    'class' => 'btn btn-secondary',
                    'formnovalidate' => 'formnovalidate',
                ],
            ]);
    }

    /**
     * Configures the options for this form type.
     *
     * @param OptionsResolver $resolver The resolver for the options.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Specifies the class associated with this form
        $resolver->setDefaults([
            'data_class' => AcquisitionSystem::class,
        ]);
    }
}
