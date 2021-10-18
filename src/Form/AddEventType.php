<?php

namespace App\Form;

use App\Controller\EventController;
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
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddEventType extends AbstractType
{
    # -------------------------------------------------- CONST ------------------------------------------------------- #

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #


    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $event = null;


        if (isset($options['data']['event']) && $options['data']['event'] !== null):
            $event = $options['data']['event'];
        endif;

        $builder
            ->setAction($options['data']['action'])
            ->add('title', TextType::class, array(
                'label' => 'Título',
                'data' => $event !== null ? $event->getTitle() : $event
            ))
            ->add('type', ChoiceType::class, array(
                'label' => 'Tipo',
                'choices' => $options['data']['event_types'],
                'data' => $event !== null ? $event->getType() : $event
            ))
            ->add('gender', ChoiceType::class, array(
                'label' => 'Género',
                'choices' => $options['data']['genders_types'],
                'required' => false,
                'multiple' => true,
                'data' => $event !== null ? EventController::gendersToArray($event->getGender()) : $event
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Descripcion',
                'attr' => array(
                    'rows' => 5
                ),
                'data' => $event !== null ? $event->getDescription() : $event

            ))
            ->add('duration', IntegerType::class, array(
                'label' => 'Duración (mins)',
                'data' => $event !== null ? $event->getDuration() : $event
            ))
            ->add('release_date', DateTimeType::class, array(
                'label' => 'Fecha de estreno',
                'placeholder' => [
                    'year' => 'Año', 'month' => 'Mes', 'day' => 'Dia'
                ],
                'years' => range(1900, 2021),
                'with_minutes' => false,
                'data' => $event !== null ? $event->getReleaseDate() : $event
            ))
            ->add('actors', TextareaType::class, array(
                'label' => 'Actores',
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Los nombres de los actores separados por comas',
                    'rows' => 3
                ),
                'data' => $event !== null ? $event->getActors() : $event
            ))
            ->add('poster_photo', FileType::class, array(
                'label' => 'Foto de portada',
                'data_class' => null,
                'required' => false,
                'attr' => array(
                    'placeholder' => $event !== null ? $event->getPosterPhoto() : 'Buscar...'
                ),
                'label_attr' => array(
                    'class' => 'form-control-file'
                ),
                'data' => $event !== null ? $event->getPosterPhoto() : null
            ))
            ->add('backdrop_photo', FileType::class, array(
                'label' => 'Foto de fondo ',
                'data_class' => null,
                'required' => false,
                'attr' => array(
                    'placeholder' => $event !== null ? $event->getBackdropPath() : 'Buscar...'
                ),
                'label_attr' => array(
                    'class' => ''
                ),
                'data' => $event !== null ? $event->getBackdropPath() : null
            ))
            ->add('rating', NumberType::class, array(
                'label' => 'Valoración',
                'required' => false,
                'attr' => array(
                    'min' => 0,
                    'max' => 10
                ),
                'data' => $event !== null ? $event->getRating() : $event
            ))
            ->add('age_rating', ChoiceType::class, array(
                'label' => 'Recomendada para',
                'choices' => $options['data']['age_rating_types'],
                'data' => $event !== null ? $event->getAgeRating() : $event
            ))
            ->add('director', TextType::class, array(
                'label' => 'Director/es',
                'required' => false,
                'data' => $event !== null ? $event->getDirector() : $event
            ))
            ->add('youtube_trailer', TextType::class, array(
                'label' => 'Trailer (URL Youtube)',
                'required' => false,
                'data' => $event !== null ? $event->getYoutubeTrailer() : $event
            ))
            ->add('tag_line', TextType::class, array(
                'label' => 'Frase de película',
                'data' => $event !== null ? $event->getTagLine() : $event,
                'required' => false
            ))
            ->add('status', CheckboxType::class, array(
                'label' => 'En cartelera',
                'data' => $event !== null ? $event->getStatus() : $event,
                'required' => false,
                'label_attr' => array(
                    'class' => 'switch-custom'
                ),

            ))
            ->add('submit', SubmitType::class, array(
                'label' => $event !== null ? 'Editar' : 'Añadir'
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {


    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #
}
