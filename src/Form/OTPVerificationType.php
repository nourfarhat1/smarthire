<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OTPVerificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('otpCode', PasswordType::class, [
                'label' => 'Verification Code',
                'attr' => [
                    'placeholder' => 'Enter 6-digit code',
                    'maxlength' => 6,
                    'pattern' => '[0-9]{6}',
                    'class' => 'form-control text-center',
                    'style' => 'font-size: 24px; letter-spacing: 5px;'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter the verification code']),
                    new Assert\Regex([
                        'pattern' => '/^\d{6}$/',
                        'message' => 'Verification code must be exactly 6 digits',
                    ]),
                ],
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'New Password',
                'attr' => ['placeholder' => 'Enter new password'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter a new password']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Password must be at least 8 characters',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                        'message' => 'Password must contain at least one lowercase letter, one uppercase letter, and one number',
                    ]),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Confirm New Password',
                'attr' => ['placeholder' => 'Confirm new password'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please confirm your new password']),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Reset Password',
                'attr' => ['class' => 'btn btn-primary btn-lg'],
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
