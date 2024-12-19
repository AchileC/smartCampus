<?php

namespace App\Form;

use App\Entity\AcquisitionSystem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Classe AddASType
 *
 * Définit le formulaire utilisé pour ajouter un nouveau système d'acquisition.
 *
 * @package App\Form
 */
class AddASType extends AbstractType
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
     * Construit le formulaire pour ajouter un système d'acquisition.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire.
     * @param array                $options Un tableau d'options.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour entrer un numéro (non mappé à l'entité)
            ->add('number', TextType::class, [
                'label' => null, // Pas de label, utilisé avec un placeholder
                'mapped' => false, // Ce champ n'est pas lié à une propriété de l'entité
                'constraints' => [
                    // Contrainte : le champ ne doit pas être vide
                    new Assert\NotBlank([
                        'message' => $this->translator->trans('add_as.number.required'),
                    ]),
                    // Contrainte : le champ doit contenir exactement trois chiffres
                    new Assert\Regex([
                        'pattern' => '/^\d{3}$/',
                        'message' => $this->translator->trans('add_as.number.invalid'),
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control', // Classe CSS pour styliser le champ
                    'placeholder' => $this->translator->trans('add_as.number.placeholder'), // Texte affiché dans le champ
                    'maxlength' => '3', // Longueur maximale
                    'pattern' => '\d{3}', // Validation côté client
                ],
            ]);
    }

    /**
     * Configure les options pour ce type de formulaire.
     *
     * @param OptionsResolver $resolver Le résolveur des options.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AcquisitionSystem::class, // Associe ce formulaire à l'entité AcquisitionSystem
            'validation_groups' => ['Default', 'add'], // Groupes de validation utilisés pour ce formulaire
        ]);
    }
}