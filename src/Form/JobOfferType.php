<?php

namespace App\Form;

use App\Entity\JobCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class JobOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Job Title',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter a job title']),
                    new Assert\Length(['min' => 5, 'max' => 100]),
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => function () {
                    // This would typically fetch categories from database
                    return [
                        'Technology' => 1,
                        'Healthcare' => 2,
                        'Finance' => 3,
                        'Education' => 4,
                        'Marketing' => 5,
                    ];
                },
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a category']),
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter a location']),
                ],
            ])
            ->add('salaryMin', NumberType::class, [
                'label' => 'Minimum Salary',
                'required' => false,
                'constraints' => [
                    new Assert\Positive(['message' => 'Minimum salary must be positive']),
                ],
            ])
            ->add('salaryMax', NumberType::class, [
                'label' => 'Maximum Salary',
                'required' => false,
                'constraints' => [
                    new Assert\Positive(['message' => 'Maximum salary must be positive']),
                ],
            ])
            ->add('jobType', ChoiceType::class, [
                'label' => 'Job Type',
                'choices' => [
                    'Full Time' => 'Full-time',
                    'Part Time' => 'Part-time',
                    'Remote' => 'Remote',
                    'Contract' => 'Contract',
                    'Internship' => 'Internship',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a job type']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Job Description',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter a job description']),
                    new Assert\Length(['min' => 20, 'max' => 2000]),
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
