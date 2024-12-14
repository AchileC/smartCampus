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
     * The form includes fields for room name, floor, number of windows, number of heaters, surface area, and cardinal direction.
     *
     * @param FormBuilderInterface $builder The form builder interface used to create form fields.
     * @param array $options The options for the form.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Room Name Field
            ->add('name', TextType::class, [
                'label' => 'Room Name',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Room name is required.',
                        'groups' => ['add'],
                    ]),
                    new Assert\Length([
                        'min' => 4,
                        'max' => 4,
                        'exactMessage' => 'Room name must be exactly {{ limit }} characters long.',
                        'groups' => ['add'],
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-z]\d{3,}$/',
                        'message' => 'The name must start with a letter followed by three digits.',
                        'groups' => ['add'],
                    ]),
                ],
            ])

            // Floor Selection Field
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

            // Number of Windows Field
            ->add('nbWindows', IntegerType::class, [
                'label' => 'Number of Windows',
                'constraints' => [
                    new Assert\PositiveOrZero([
                        'message' => 'Number of windows cannot be negative.',
                        'groups' => ['add'],
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => 'Ex: 2',
                ],
            ])

            // Number of Heaters Field
            ->add('nbHeaters', IntegerType::class, [
                'label' => 'Number of Heaters',
                'constraints' => [
                    new Assert\PositiveOrZero([
                        'message' => 'Number of heaters cannot be negative.',
                        'groups' => ['add'],
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => 'Ex: 2',
                ],
            ])

            // Surface Area Field
            ->add('surface', NumberType::class, [
                'label' => 'Surface Area (m²)',
                'scale' => 1,
                'constraints' => [
                    new Assert\Positive([
                        'message' => 'Surface area must be greater than zero.',
                        'groups' => ['add'],
                    ]),
                    new Assert\Range([
                        'min' => 10.0,
                        'max' => 30.0,
                        'notInRangeMessage' => 'Surface area must be between {{ min }} m² and {{ max }} m².',
                        'groups' => ['add'],
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'step' => '0.1',
                    'placeholder' => 'Ex: 25.5',
                    'oninput' => 'this.value = this.value.replace(/[^0-9.]/g, "").match(/^\d+(\.\d+)?/)?.[0] || ""',
                ],
            ])

            // Cardinal Direction Field
            ->add('cardinalDirection', ChoiceType::class, [
                'choices' => CardinalEnum::cases(),
                'choice_label' => fn(CardinalEnum $cardinal) => ucfirst($cardinal->value),
                'choice_value' => fn(?CardinalEnum $cardinal) => $cardinal?->value,
                'label' => 'Cardinal Direction',
                'placeholder' => 'Select Direction',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'A direction is required.',
                        'groups' => ['add'],
                    ]),
                ],
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
