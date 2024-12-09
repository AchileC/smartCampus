<?php

namespace App\Form;

use App\Entity\Action;
use App\Entity\Room;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UnassignTaskType extends AbstractType
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
                'attr' => ['id' => 'room-select'], // ID pour le JavaScript
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'rooms' => [], // Définissez 'rooms' comme une option
        ]);
        $resolver->setAllowedTypes('rooms', ['array']);
    }
}