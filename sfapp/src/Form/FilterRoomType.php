<?php
// FilterRoomType.php

namespace App\Form;

use App\Entity\Room;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FilterRoomType
 *
 * Defines a form to filter Room entities.
 */
class FilterRoomType extends AbstractType
{
    /**
     * Builds the filter form for Room entities.
     *
     * The form includes fields for filtering by name, floor, and state.
     *
     * @param FormBuilderInterface $builder The form builder interface used to create form fields.
     * @param array $options The options for the form.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => null,
                'required' => false,
                'attr' => ['placeholder' => 'Search for a room by name'],
            ])
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    'Ground' => FloorEnum::GROUND,
                    'First' => FloorEnum::FIRST,
                    'Second' => FloorEnum::SECOND,
                    'Third' => FloorEnum::THIRD,
                ],
                'required' => false,
                'placeholder' => 'Select a Floor',
                'label' => null,
            ])
            ->add('state', ChoiceType::class, [
                'choices' => [
                    'Stable' => RoomStateEnum::STABLE,
                    'At Risk' => RoomStateEnum::AT_RISK,
                    'Critical' => RoomStateEnum::CRITICAL,
                ],
                'required' => false,
                'placeholder' => 'Select a State',
                'label' => null,
                'choice_label' => function ($choice, $key, $value) {
                    return $key;
                },
                'choice_value' => function (?RoomStateEnum $state) {
                    return $state?->value; // Convertit l'enum en chaîne
                },
                'data' => $options['state'] ?? null,
            ])
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
     * Configures the options for the form.
     *
     * Sets the data class to be `Room` and allows optional fields.
     *
     * @param OptionsResolver $resolver The options resolver.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
            'required_fields' => false,
            'validation_groups' => ['Default'],
            'state' => null,
        ]);
    }
}
