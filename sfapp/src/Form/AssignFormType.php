<?php

namespace App\Form;

use App\Entity\AcquisitionSystem;
use App\Entity\Action;
use App\Entity\Room;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssignFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisissez une pièce...',
                'label' => 'Pièce',
            ])
            ->add('acquisitionSystem', EntityType::class, [
                'class' => AcquisitionSystem::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisissez un système...',
                'label' => 'Système d’Acquisition',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
        ]);
    }
}
