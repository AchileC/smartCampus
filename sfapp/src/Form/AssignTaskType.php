<?php

namespace App\Form;

use App\Entity\Action;
use App\Entity\Room;
use App\Entity\AcquisitionSystem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssignTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rooms = $options['rooms'];

        $builder
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'choices' => $rooms,
                'choice_label' => 'name', // Utilisez un champ descriptif
                'placeholder' => 'Choisir une salle...',
                'label' => 'Salle',
            ])
            ->add('acquisitionSystem', EntityType::class, [
                'class' => AcquisitionSystem::class,
                'choices' => $options['acquisitionSystems'] ?? [], // Passez acquisitionSystems via options si nécessaire
                'choice_label' => 'name',
                'placeholder' => 'Choisir un système d\'acquisition...',
                'label' => 'Système d\'acquisition',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'rooms' => [], // Définissez 'rooms' comme une option
            'acquisitionSystems' => [], // Ajoutez 'acquisitionSystems' comme option si nécessaire
        ]);
        $resolver->setAllowedTypes('rooms', ['array']);
        $resolver->setAllowedTypes('acquisitionSystems', ['array']);
    }
}