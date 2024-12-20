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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Classe AddRoomType
 *
 * Définit un formulaire pour ajouter une nouvelle entité `Room`.
 */
class AddRoomType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * Constructeur
     *
     * @param TranslatorInterface $translator Le service de traduction Symfony.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Construit le formulaire pour ajouter une nouvelle salle.
     *
     * Le formulaire inclut des champs pour le nom de la salle, l'étage, le nombre de fenêtres, le nombre de radiateurs,
     * la surface et l'orientation cardinale.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire utilisé pour créer les champs.
     * @param array $options Les options pour le formulaire.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour le nom de la salle
            ->add('name', TextType::class, [
                'label' => $this->translator->trans('add_room.name.label'),
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => $this->translator->trans('add_room.name.required'),
                        'groups' => ['add'],
                    ]),
                    new Assert\Length([
                        'min' => 4,
                        'max' => 4,
                        'exactMessage' => $this->translator->trans('add_room.name.length'),
                        'groups' => ['add'],
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-z]\d{3,}$/',
                        'message' => $this->translator->trans('add_room.name.pattern'),
                        'groups' => ['add'],
                    ]),
                ],
            ])

            // Champ pour sélectionner l'étage
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    $this->translator->trans('filter.floor.ground') => FloorEnum::GROUND,
                    $this->translator->trans('filter.floor.first') => FloorEnum::FIRST,
                    $this->translator->trans('filter.floor.second') => FloorEnum::SECOND,
                    $this->translator->trans('filter.floor.third') => FloorEnum::THIRD,
                ],
                'choice_value' => fn(?FloorEnum $floor) => $floor?->value, // Convertit l'enum en chaîne pour la valeur
                'label' => $this->translator->trans('add_room.floor.label'),
                'placeholder' => $this->translator->trans('add_room.floor.placeholder'),
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => $this->translator->trans('add_room.floor.required'),
                        'groups' => ['add'],
                    ]),
                ],
            ])

            // Champ pour le nombre de fenêtres
            ->add('nbWindows', IntegerType::class, [
                'label' => $this->translator->trans('add_room.nb_windows.label'),
                'constraints' => [
                    new Assert\PositiveOrZero([
                        'message' => $this->translator->trans('add_room.nb_windows.positive'),
                        'groups' => ['add'],
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => $this->translator->trans('add_room.nb_windows.placeholder'),
                ],
            ])

            // Champ pour le nombre de radiateurs
            ->add('nbHeaters', IntegerType::class, [
                'label' => $this->translator->trans('add_room.nb_heaters.label'),
                'constraints' => [
                    new Assert\PositiveOrZero([
                        'message' => $this->translator->trans('add_room.nb_heaters.positive'),
                        'groups' => ['add'],
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => $this->translator->trans('add_room.nb_heaters.placeholder'),
                ],
            ])

            // Champ pour la surface
            ->add('surface', NumberType::class, [
                'label' => $this->translator->trans('add_room.surface.label'),
                'scale' => 1,
                'constraints' => [
                    new Assert\Positive([
                        'message' => $this->translator->trans('add_room.surface.positive'),
                        'groups' => ['add'],
                    ]),
                    new Assert\Range([
                        'min' => 10.0,
                        'max' => 30.0,
                        'notInRangeMessage' => $this->translator->trans('add_room.surface.range'),
                        'groups' => ['add'],
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'step' => '0.1',
                    'placeholder' => $this->translator->trans('add_room.surface.placeholder'),
                    'oninput' => 'this.value = this.value.replace(/[^0-9.]/g, "").match(/^\d+(\.\d+)?/)?.[0] || ""',
                ],
            ])

            // Champ pour l'orientation cardinale
            ->add('cardinalDirection', ChoiceType::class, [
                'choices' => CardinalEnum::cases(), // Utilise les cases de l'enum pour les choix
                'choice_label' => function (CardinalEnum $cardinal) {
                    // Traduit la valeur de l'enum en utilisant le traducteur
                    return $this->translator->trans(sprintf('_cardinal_direction.%s', strtolower($cardinal->name)));
                },
                'choice_value' => fn(?CardinalEnum $cardinal) => $cardinal?->value, // Convertit l'enum en chaîne pour la valeur
                'label' => $this->translator->trans('add_room.cardinal.label'),
                'placeholder' => $this->translator->trans('add_room.cardinal.placeholder'),
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => $this->translator->trans('add_room.cardinal.required'),
                        'groups' => ['add'],
                    ]),
                ],
            ]);
    }

    /**
     * Configure les options pour le formulaire.
     *
     * Définit la classe de données comme étant `Room` et inclut les groupes de validation.
     *
     * @param OptionsResolver $resolver Le résolveur des options.
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