<?php

namespace App\Form;

use App\Entity\AcquisitionSystem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AddASType
 *
 * Defines the form used to add a new Acquisition System.
 *
 * @package App\Form
 */
class AddASType extends AbstractType
{
    /**
     * Builds the form for adding an Acquisition System.
     *
     * @param FormBuilderInterface $builder The form builder.
     * @param array                $options An array of options.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('number', TextType::class, [
                'label' => 'Enter a number for the acquisition system',
                'mapped' => false, // Ce champ n'est pas mappé à l'entité
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The number is required.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^\d{3}$/',
                        'message' => 'The number must be exactly three digits.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter a number between 000 and 999',
                    'maxlength' => '3',
                    'pattern' => '\d{3}',
                ],
            ]);
    }

    /**
     * Configures the options for this form type.
     *
     * @param OptionsResolver $resolver The resolver for the options.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AcquisitionSystem::class,
            'validation_groups' => ['Default', 'add'],
        ]);
    }
}
