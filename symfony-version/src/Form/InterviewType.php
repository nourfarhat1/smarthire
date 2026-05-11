<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class InterviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateTime', DateTimeType::class, [
                'label' => 'Interview Date & Time',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select interview date and time']),
                    new Assert\GreaterThanOrEqual(['value' => 'today', 'message' => 'Interview date cannot be in the past']),
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter interview location']),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Interview Notes',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'SCHEDULED' => 'Scheduled',
                    'COMPLETED' => 'Completed',
                    'CANCELLED' => 'Cancelled',
                    'RESCHEDULED' => 'Rescheduled',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select interview status']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
