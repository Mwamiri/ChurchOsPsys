<?php

namespace App\Form;

use App\Entity\Centre;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = $options['is_new'];

        $builder
            ->add('name', TextType::class, ['label' => 'Full name', 'attr' => ['placeholder' => 'Full name'], 'constraints' => [new NotBlank(), new Length(max: 150)]])
            ->add('username', TextType::class, ['label' => 'Username', 'attr' => ['placeholder' => 'username'], 'constraints' => [new NotBlank(), new Length(min: 3, max: 60)]])
            ->add('email', EmailType::class, ['label' => 'Email address', 'required' => false])
            ->add('phone', TelType::class, ['label' => 'Phone', 'required' => false])
            ->add('role', ChoiceType::class, [
                'label'   => 'Role',
                'choices' => array_flip(User::ROLES),
            ])
            ->add('centre', EntityType::class, [
                'class'       => Centre::class,
                'choice_label' => 'name',
                'label'       => 'Centre',
                'required'    => false,
                'placeholder' => '— HQ / all centres —',
            ])
            ->add('isActive', CheckboxType::class, ['label' => 'Active', 'required' => false])
            ->add('plainPassword', RepeatedType::class, [
                'type'          => PasswordType::class,
                'mapped'        => false,
                'required'      => $isNew,
                'first_options'  => ['label' => 'Password', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Confirm password'],
                'constraints'   => $isNew ? [
                    new NotBlank(['message' => 'Password is required for new users.']),
                    new Length(min: 8),
                    new Regex(['pattern' => '/[a-zA-Z]/', 'message' => 'Password must contain letters.']),
                    new Regex(['pattern' => '/[0-9]/', 'message' => 'Password must contain numbers.']),
                ] : [
                    new Length(min: 8),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new'     => false,
        ]);
        $resolver->setAllowedTypes('is_new', 'bool');
    }
}
