<?php

namespace App\Form;

use App\Entity\Centre;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CentreFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Centre name', 'constraints' => [new NotBlank(), new Length(max: 191)]])
            ->add('code', TextType::class, ['label' => 'Short code', 'required' => false, 'attr' => ['placeholder' => 'e.g. NAIROBI-HQ']])
            ->add('parent', EntityType::class, [
                'class'       => Centre::class,
                'choice_label' => 'name',
                'label'       => 'Parent centre',
                'required'    => false,
                'placeholder' => '— Top level —',
            ])
            ->add('level', IntegerType::class, ['label' => 'Hierarchy level (1 = top)', 'data' => 2])
            ->add('location', TextType::class, ['label' => 'Location / town', 'required' => false])
            ->add('phone', TelType::class, ['label' => 'Phone', 'required' => false])
            ->add('email', EmailType::class, ['label' => 'Email', 'required' => false])
            ->add('headerText', TextareaType::class, ['label' => 'Report header text', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('footerText', TextareaType::class, ['label' => 'Report footer text', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('isActive', CheckboxType::class, ['label' => 'Active', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Centre::class,
            'centres'    => [],
        ]);
    }
}
