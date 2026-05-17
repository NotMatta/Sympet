<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'invalid_message' => 'The password fields must match.',
            'first_options' => ['label' => 'New password'],
            'second_options' => ['label' => 'Confirm password'],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(min: 8, minMessage: 'Password must be at least {{ limit }} characters long.'),
                new Assert\Regex('/^(?=.*[A-Za-z])(?=.*\d).+$/', 'Password must include at least one letter and one number.'),
            ],
        ]);
    }
}
