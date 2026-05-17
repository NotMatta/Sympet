<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayName', TextType::class, [
                'required' => false,
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('avatarUrl', TextType::class, [
                'required' => false,
                'constraints' => [new Assert\Url(protocols: ['http', 'https'])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
