<?php

namespace App\Form;

use App\Entity\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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

class AddCinemaType extends AbstractType
{
    # -------------------------------------------------- CONST ------------------------------------------------------- #

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #


    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->setAction('/cinema/add')
            ->add('name', TextType::class, array(
                'label' => 'Nombre'
            ))
            ->add('location', TextType::class, array(
                'label' => 'Localización'
            ))
            ->add('n_rooms', IntegerType::class, array(
                'label' => 'Salas (máximo 15)'
            ))
            ->add('n_rows', IntegerType::class, array(
                'label' => 'Filas por sala (máximo 20)'
            ))
            ->add('n_columns', IntegerType::class, array(
                'label' => 'Asientos por fila (máximo 30)'
            ))
            ->add('tickets_price', NumberType::class, array(
                'label' => 'Precio de las entradas (estándar)',
                'attr' => array(
                    'placeholder' => 'Para el precio de eventos especiales se determina al añadir el evento'
                ),
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Añadir'
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {


    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #
}
