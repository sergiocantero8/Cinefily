<?php

namespace App\Form;

use App\Entity\User;

use ContainerAGVTJbr\getDoctrine_EnsureProductionSettingsCommandService;
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
use Symfony\Component\Validator\Constraints\Choice;

class AddSessionType extends AbstractType
{
    # -------------------------------------------------- CONST ------------------------------------------------------- #

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #


    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('event', ChoiceType::class, array(
                'label' => 'Evento',
                'choices' => $options['data']['events']
            ))
            ->add('cinema', ChoiceType::class, array(
                'label' => 'Cine',
                'choices' => $options['data']['cinemas']
            ))
            ->add('schedule', DateTimeType::class, array(
                'label' => 'Fecha',
                'placeholder' => [
                    'year' => 'Año', 'month' => 'Mes', 'day' => 'Dia', 'hour' => 'Hora', 'minute' => 'Minutos'
                ],
                'years' => range(2021, 2023)
            ))
            ->add('language', ChoiceType::class, array(
                'label' => 'Idioma',
                'choices' => $options['data']['languages']
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Añadir'
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {}

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #
}
