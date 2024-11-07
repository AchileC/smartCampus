<?php
// RoomType.php
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

class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Room Name',
            ])
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    'Ground' => FloorEnum::GROUND,
                    'First' => FloorEnum::FIRST,
                    'Second' => FloorEnum::SECOND,
                    'Third' => FloorEnum::THIRD,
                ],
                'label' => 'Floor',
                'choice_label' => function ($choice, $key, $value) {
                    return $key; // Affiche 'Ground', 'First', etc.
                },
                'choice_value' => function (?FloorEnum $floor) {
                    return $floor ? $floor->value : '';
                },
            ])
            ->add('state', ChoiceType::class, [
                'choices' => [
                    'OK' => RoomStateEnum::OK,
                    'Problem' => RoomStateEnum::PROBLEM,
                    'Critical' => RoomStateEnum::CRITICAL,
                ],
                'label' => 'State',
                'choice_label' => function ($choice, $key, $value) {
                    return $key; // Affiche 'OK', 'Problem', etc.
                },
                'choice_value' => function (?RoomStateEnum $state) {
                    return $state ? $state->value : '';
                },
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Add Room',
                'attr' => ['class' => 'btn btn-success'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
        ]);
    }
}