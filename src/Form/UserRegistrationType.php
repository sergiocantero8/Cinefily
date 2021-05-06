<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder
            ->add('name', TextType::class, array(
                'label' => 'Nombre'
            ))
            ->add('surname', TextType::class, array(
                'label' => 'Apellidos'
            ))
            ->add('password',PasswordType::class,array(
                'label' => 'Contraseña'
            ))
            ->add('password_repeated',PasswordType::class,array(
                'label' => 'Repite la contraseña'
            ))
            ->add('email',EmailType::class,array(
                'label' => 'Email'
            ))
            ->add('phone_number',TextType::class,array(
                'label' => 'Telefono',
                'required' => false,
                'empty_data' => 'Opcional'
            ))
            ->add('submit', SubmitType::class,array(
                'label' => 'Registrar'
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver):void
    {

    }
}
