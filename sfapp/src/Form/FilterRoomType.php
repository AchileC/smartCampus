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
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => null,
                'required' => false,
                'attr' => ['placeholder' => $this->translator->trans('filter.name.placeholder')],
            ])
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    $this->translator->trans('filter.floor.ground') => FloorEnum::GROUND,
                    $this->translator->trans('filter.floor.first') => FloorEnum::FIRST,
                    $this->translator->trans('filter.floor.second') => FloorEnum::SECOND,
                    $this->translator->trans('filter.floor.third') => FloorEnum::THIRD,
                ],
                'required' => false,
                'placeholder' => $this->translator->trans('filter.floor.placeholder'),
                'label' => null,
            ])
            ->add('state', ChoiceType::class, [
                'choices' => [
                    $this->translator->trans('filter.state.stable') => RoomStateEnum::STABLE,
                    $this->translator->trans('filter.state.at_risk') => RoomStateEnum::AT_RISK,
                    $this->translator->trans('filter.state.critical') => RoomStateEnum::CRITICAL,
                ],
                'required' => false,
                'placeholder' => $this->translator->trans('filter.state.placeholder'),
                'label' => null,
                'choice_label' => function ($choice, $key, $value) {
                    return $key;
                },
                'choice_value' => function (?RoomStateEnum $state) {
                    return $state?->value; // Convert enum to string
                },
                'data' => $options['state'] ?? null,
            ]);

        $builder
            ->add('filter', SubmitType::class, [
                'label' => $this->translator->trans('filter.buttons.search'),
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->add('reset', SubmitType::class, [
                'label' => $this->translator->trans('filter.buttons.reset'),
                'attr' => [
                    'class' => 'btn btn-secondary',
                    'formnovalidate' => 'formnovalidate',
                ],
            ]);
    }

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