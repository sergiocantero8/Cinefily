<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddEventType extends AbstractType
{
    # -------------------------------------------------- CONST ------------------------------------------------------- #

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #


    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    public function buildForm(FormBuilderInterface $builder, array $options):void
    {

        $builder
            ->add('title', TextType::class, array(
                'label' => 'Título'
            ))
            ->add('type', ChoiceType::class, array(
                'label' => 'Tipo',
                'choices' => $options['data']['event_types']
            ))
            ->add('gender',ChoiceType::class,array(
                'label' => 'Género',
                'choices' => $options['data']['genders_types']
            ))
            ->add('description',TextareaType::class,array(
                'label' => 'Descripcion'
            ))
            ->add('duration',IntegerType::class,array(
                'label' => 'Duración'
            ))
            ->add('release_date',DateTimeType::class,array(
                'label' => 'Fecha de estreno',
                'placeholder' => [
                    'year' => 'Año', 'month' => 'Mes', 'day' => 'Dia'
                ],
                'with_minutes' => false,
            ))
            ->add('actors',TextType::class,array(
                'label' => 'Actores'
            ))
            ->add('profile_photo', FileType::class, array(
                'label' => 'Foto de portada',
                'data_class' => null,
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Buscar..'
                )
            ))
            ->add('rating',IntegerType::class,array(
                'label' => 'Valoración',
                'attr' => array(
                    'min' => 0,
                    'max' => 5
                )
            ))
            ->add('submit', SubmitType::class,array(
                'label' => 'Añadir'
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver):void
    {


    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #
}
