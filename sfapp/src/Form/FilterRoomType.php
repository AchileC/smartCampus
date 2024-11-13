<?php
// FilterRoomType.php

namespace App\Form;

use App\Entity\Room;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'label' => 'Room Name',
                'required' => false,
                'attr' => ['placeholder' => 'Search or add room name'],
            ])
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    'Ground' => FloorEnum::GROUND,
                    'First' => FloorEnum::FIRST,
                    'Second' => FloorEnum::SECOND,
                    'Third' => FloorEnum::THIRD,
                ],
                'required' => false,
                'placeholder' => 'Choose Floor',
                'label' => 'Floor',
                'choice_label' => function ($choice, $key, $value) {
                    return $key;
                },
                'choice_value' => function (?FloorEnum $floor) {
                    return $floor ? $floor->value : null;
                },
            ])
            ->add('state', ChoiceType::class, [
                'choices' => [
                    'OK' => RoomStateEnum::OK,
                    'Problem' => RoomStateEnum::PROBLEM,
                    'Critical' => RoomStateEnum::CRITICAL,
                ],
                'required' => false,
                'placeholder' => 'Select a State',
                'label' => 'State',
                'choice_label' => function ($choice, $key, $value) {
                    return $key;
                },
                'choice_value' => function (?RoomStateEnum $state) {
                    return $state ? $state->value : null;
                },
            ])
            ->add('filter', SubmitType::class, [
                'label' => 'Filter',
                'attr' => ['class' => 'btn btn-primary'],
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
        ]);
    }
}
