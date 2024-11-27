<?php
// AddRoomType.php
namespace App\Form;

use App\Entity\Room;
use App\Utils\FloorEnum;
use App\Utils\CardinalEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


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
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-z]\d{3,}$/',
                        'message' => 'The name must start with a letter followed by three digits.',
                    ]),
                ],
            ])
            ->add('floor', ChoiceType::class, [
                'choices' => FloorEnum::cases(),
                'choice_label' => fn(FloorEnum $floor) => ucfirst($floor->value),
                'choice_value' => fn(?FloorEnum $floor) => $floor?->value,
                'label' => 'Floor',
                'placeholder' => 'Select Floor',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'A floor is required.',
                        'groups' => ['add'],
                    ]),
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Optional: Add a room description',
                ],
            ])
            ->add('nbWindows', IntegerType::class, [
                'label' => 'Number of windows',
                'required' => false,
                'constraints' => [
                    new Assert\PositiveOrZero([
                        'message' => 'Number of windows must be greater than zero.',
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => 'Ex: 2',
                ],
            ])
            ->add('nbHeaters', IntegerType::class, [
                'label' => 'Number of heaters',
                'required' => false,
                'constraints' => [
                    new Assert\PositiveOrZero([
                        'message' => 'Number of heaters must be greater than zero.',
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => 'Ex: 1',
                ],
            ])
            ->add('surface', NumberType::class, [
                'label' => 'Surface (m²)',
                'required' => false,
                'scale' => 2,
                'constraints' => [
                    new Assert\Positive([
                        'message' => 'Surface must be greater than zero.',
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'step' => '0.1',
                    'placeholder' => 'Ex: 25.5',
                ],
            ])
            ->add('cardinalDirection', ChoiceType::class, [
                'choices' => CardinalEnum::cases(),
                'choice_label' => fn(CardinalEnum $cardinal) => ucfirst($cardinal->value),
                'choice_value' => fn(?CardinalEnum $cardinal) => $cardinal?->value,
                'label' => 'Cardinal Direction',
                'placeholder' => 'Select Direction',
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
