<?php

namespace App\Form;

use App\Entity\Complaint;
use App\Entity\ComplaintType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Service\ProfanityFilterService;

class ComplaintFormType extends AbstractType
{
    private ProfanityFilterService $profanityFilter;

    public function __construct(ProfanityFilterService $profanityFilter)
    {
        $this->profanityFilter = $profanityFilter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Subject',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Brief summary of your complaint',
                    'data-profanity-check' => 'subject'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a subject']),
                    new Length([
                        'min' => 5,
                        'max' => 100,
                        'minMessage' => 'Subject must be at least {{ limit }} characters',
                        'maxMessage' => 'Subject cannot be longer than {{ limit }} characters'
                    ]),
                    new Callback([$this, 'validateProfanity'])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Please provide detailed information about your complaint...',
                    'data-profanity-check' => 'description'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please describe your complaint']),
                    new Length([
                        'min' => 20,
                        'max' => 2000,
                        'minMessage' => 'Description must be at least {{ limit }} characters',
                        'maxMessage' => 'Description cannot be longer than {{ limit }} characters'
                    ]),
                    new Callback([$this, 'validateProfanity'])
                ]
            ])
            ->add('type', EntityType::class, [
                'class' => ComplaintType::class,
                'choice_label' => 'name',
                'label' => 'Complaint Type',
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Select a complaint type',
                'constraints' => [
                    new NotBlank(['message' => 'Please select a complaint type'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Complaint::class,
            'attr' => [
                'id' => 'complaintForm',
                'novalidate' => 'novalidate'
            ]
        ]);
    }

    public function validateProfanity($value, ExecutionContextInterface $context): void
    {
        if ($value && !$this->profanityFilter->isAppropriate($value)) {
            $validation = $this->profanityFilter->validateText($value);
            
            if ($validation['has_profanity']) {
                $message = 'Your text contains inappropriate language. ';
                
                if (!empty($validation['profanity_words'])) {
                    $message .= 'Found: ' . implode(', ', array_slice($validation['profanity_words'], 0, 3));
                    if (count($validation['profanity_words']) > 3) {
                        $message .= ' and more';
                    }
                    $message .= '. ';
                }
                
                $message .= 'Please revise your submission to be more professional.';
                
                $context->buildViolation($message)
                    ->addViolation();
            }
        }
    }
}
