<?php

namespace App\Form;

use App\Entity\ComplaintType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ComplaintTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Type Name',
                'attr' => [
                    'placeholder' => 'e.g. Harassment, Discrimination, Safety Issue',
                    'class' => 'form-control'
                ],
                'required' => true
            ])
            ->add('urgencyLevel', ChoiceType::class, [
                'label' => 'Urgency Level',
                'choices' => [
                    'Low' => 'Low',
                    'Medium' => 'Medium',
                    'High' => 'High',
                    'Critical' => 'Critical'
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ComplaintType::class,
        ]);
    }
}
