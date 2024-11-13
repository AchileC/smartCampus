<?php
// AddRoomType.php
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
 * Class AddRoomType
 *
 * Defines a form to add a new Room entity.
 */
class AddRoomType extends AbstractType
{
    /**
     * Builds the form for adding a new Room.
     *
     * The form includes fields for room name, floor, state, and an optional description.
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
            ])
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    'Ground' => FloorEnum::GROUND,
                    'First' => FloorEnum::FIRST,
                    'Second' => FloorEnum::SECOND,
                    'Third' => FloorEnum::THIRD,
                ],
                'label' => 'Floor',
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false,
            ]);
    }

    /**
     * Configures the options for the form.
     *
     * Sets the data class to be `Room` and includes validation groups.
     *
     * @param OptionsResolver $resolver The options resolver.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
            'validation_groups' => ['Default', 'add'],
        ]);
    }
}
