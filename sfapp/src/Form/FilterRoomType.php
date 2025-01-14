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

use Symfony\Contracts\Translation\TranslatorInterface;

class FilterRoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var TranslatorInterface|null $translator */
        $translator = $options['translator'];

        $builder
            ->add('name', TextType::class, [
                'label' => null,
                'required' => false,
                // Si $translator est fourni, on peut l'utiliser :
                'attr' => [
                    'placeholder' => $translator
                        ? $translator->trans('filter.name.placeholder')
                        : 'filter.name.placeholder'
                ],
            ])
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    $translator ? $translator->trans('filter.floor.ground') : 'Ground' => FloorEnum::GROUND,
                    $translator ? $translator->trans('filter.floor.first') : '1st'   => FloorEnum::FIRST,
                    $translator ? $translator->trans('filter.floor.second') : '2nd' => FloorEnum::SECOND,
                    $translator ? $translator->trans('filter.floor.third') : '3rd'  => FloorEnum::THIRD,
                ],
                'choice_value' => fn(?FloorEnum $floor) => $floor?->value,
                'required' => false,
                'placeholder' => $translator
                    ? $translator->trans('filter.floor.placeholder')
                    : 'Choose a floor',
                'label' => null,
            ])
            ->add('state', ChoiceType::class, [
                'choices' => [
                    $translator ? $translator->trans('filter.state.stable')   : 'Stable'   => RoomStateEnum::STABLE,
                    $translator ? $translator->trans('filter.state.at_risk')  : 'At Risk'  => RoomStateEnum::AT_RISK,
                    $translator ? $translator->trans('filter.state.critical') : 'Critical' => RoomStateEnum::CRITICAL,
                ],
                'required' => false,
                'placeholder' => $translator
                    ? $translator->trans('filter.state.placeholder')
                    : 'Choose a state',
                'label' => null,
                'choice_label' => fn($choice, $key) => $key,
                'choice_value' => fn(?RoomStateEnum $state) => $state?->value,
                'data' => $options['state'] ?? null,
            ])
            ->add('filter', SubmitType::class, [
                'label' => $translator ? $translator->trans('filter.buttons.search') : 'Search',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->add('reset', SubmitType::class, [
                'label' => $translator ? $translator->trans('filter.buttons.reset') : 'Reset',
                'attr' => ['class' => 'btn btn-secondary', 'formnovalidate' => 'formnovalidate'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
            'required_fields' => false,
            'validation_groups' => ['Default'],
            'state' => null,
            'translator' => null, // <- On déclare une option "translator"
        ]);
    }
}