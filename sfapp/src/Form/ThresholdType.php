<?php

namespace App\Form;

use App\Entity\Threshold;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

class ThresholdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('heatingTempCriticalMin', NumberType::class, [
                'label' => 'Critical Min (Heating)',
                'constraints' => [new Range(['min' => 10, 'max' => 35])],
                'attr' => ['class' => 'form-control', 'step' => '0.5']
            ])
            ->add('heatingTempWarningMin', NumberType::class, [
                'label' => 'Warning Min (Heating)',
                'constraints' => [new Range(['min' => 10, 'max' => 35])],
                'attr' => ['class' => 'form-control', 'step' => '0.5']
            ])
            ->add('heatingTempWarningMax', NumberType::class, [
                'label' => 'Warning Max (Heating)',
                'constraints' => [new Range(['min' => 10, 'max' => 35])],
                'attr' => ['class' => 'form-control', 'step' => '0.5']
            ])
            ->add('heatingTempCriticalMax', NumberType::class, [
                'label' => 'Critical Max (Heating)',
                'constraints' => [new Range(['min' => 10, 'max' => 35])],
                'attr' => ['class' => 'form-control', 'step' => '0.5']
            ])
            ->add('nonHeatingTempCriticalMin', NumberType::class, [
                'label' => 'Critical Min (Non-Heating)',
                'constraints' => [new Range(['min' => 10, 'max' => 35])],
                'attr' => ['class' => 'form-control', 'step' => '0.5']
            ])
            ->add('nonHeatingTempWarningMin', NumberType::class, [
                'label' => 'Warning Min (Non-Heating)',
                'constraints' => [new Range(['min' => 10, 'max' => 35])],
                'attr' => ['class' => 'form-control', 'step' => '0.5']
            ])
            ->add('nonHeatingTempWarningMax', NumberType::class, [
                'label' => 'Warning Max (Non-Heating)',
                'constraints' => [new Range(['min' => 10, 'max' => 35])],
                'attr' => ['class' => 'form-control', 'step' => '0.5']
            ])
            ->add('nonHeatingTempCriticalMax', NumberType::class, [
                'label' => 'Critical Max (Non-Heating)',
                'constraints' => [new Range(['min' => 10, 'max' => 35])],
                'attr' => ['class' => 'form-control', 'step' => '0.5']
            ])
            ->add('humCriticalMin', NumberType::class, [
                'label' => 'Critical Min (Humidity)',
                'constraints' => [new Range(['min' => 0, 'max' => 100])],
                'attr' => ['class' => 'form-control', 'step' => '1']
            ])
            ->add('humWarningMin', NumberType::class, [
                'label' => 'Warning Min (Humidity)',
                'constraints' => [new Range(['min' => 0, 'max' => 100])],
                'attr' => ['class' => 'form-control', 'step' => '1']
            ])
            ->add('humWarningMax', NumberType::class, [
                'label' => 'Warning Max',
                'attr' => ['min' => 0, 'max' => 100, 'step' => 1]
            ])
            ->add('humCriticalMax', NumberType::class, [
                'label' => 'Critical Max',
                'attr' => ['min' => 0, 'max' => 100, 'step' => 1]
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $threshold = $event->getData();
            $form = $event->getForm();

            // Validate heating temperature thresholds
            if (!$threshold->validateHeatingTemperatureThresholds()) {
                $form->addError(new FormError('Heating temperature thresholds must be in ascending order: Critical Min < Warning Min < Warning Max < Critical Max'));
            }

            // Validate non-heating temperature thresholds
            if (!$threshold->validateNonHeatingTemperatureThresholds()) {
                $form->addError(new FormError('Non-heating temperature thresholds must be in ascending order: Critical Min < Warning Min < Warning Max < Critical Max'));
            }

            // Validate humidity thresholds
            if (!$threshold->validateHumidityThresholds()) {
                $form->addError(new FormError('Humidity thresholds must be in ascending order: Critical Min < Warning Min < Warning Max < Critical Max'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Threshold::class,
        ]);
    }
} 