<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Utils\FloorEnum;

class RoomForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    'Ground' => FloorEnum::GROUND->value,
                    'First' => FloorEnum::FIRST->value,
                    'Second' => FloorEnum::SECOND->value,
                    'Third' => FloorEnum::THIRD->value,
                ],
                'required' => false,
                'placeholder' => 'Choose Floor',
            ])
            ->add('state', ChoiceType::class, [
                'choices' => [
                    'OK' => 'ok',
                    'Problem' => 'problem',
                    'Critical' => 'critical',
                ],
                'required' => false,
                'placeholder' => 'Select a State',
            ])
            ->add('filter', SubmitType::class, [
                'label' => 'Filter'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
