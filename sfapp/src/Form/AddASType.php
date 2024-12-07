<?php

namespace App\Form;

use App\Entity\AcquisitionSystem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AddASType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', IntegerType::class, [
                'label' => 'Enter a number for the acquisition system',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The number is required.',
                    ]),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 999,
                        'notInRangeMessage' => 'The number must be between 0 and 999.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter a number between 0 and 999',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AcquisitionSystem::class,
            'validation_groups' => ['Default', 'add'],
        ]);
    }
}