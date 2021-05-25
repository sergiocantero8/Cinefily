<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AddEventType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    # Tipos de eventos
    public const FILM_EVENT_TYPE = 'film';
    public const THEATER_EVENT_TYPE = 'theater';
    public const CONFERENCE_EVENT_TYPE = 'conference';
    public const CONCERT_EVENT_TYPE = 'concert';


    # Géneros de eventos
    public const ACTION_EVENT = 'action';
    public const COMEDY_EVENT = 'comedy';
    public const DRAMA_EVENT = 'drama';
    public const FANTASY_EVENT = 'fantasy';
    public const HORROR_EVENT = 'horror';
    public const MYSTERY_EVENT = 'mystery';
    public const ROMANCE_EVENT = 'romance';
    public const THRILLER_EVENT = 'thriller';
    public const WESTERN_EVENT = 'western';
    public const SCIENCE_FICTION_EVENT = 'scienceFiction';
    public const INFORMATIVE_EVENT = 'informative';

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- ROUTES ------------------------------------------------------- #

    /**
     * @Route("/event/add", name="event_add")
     */
    public function addEvent(Request $request): Response
    {

        if ($this->getUser() && !\in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true)):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        $form = $this->createForm(AddEventType::class, NULL,
            array(
                'data' => array(
                    'event_types' => $this->getAllEventTypes(),
                    'genders_types' => $this->getAllGenresTypes())
            )
        );
        $form->handleRequest($request);

        $data= array(
            'form' => $form->createView()
        );

        return $this->render('event/add.html.twig', $data);
    }

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    /**
     * Devuelve todos los tipos de eventos disponibles
     * @return array
     */
    public function getAllEventTypes(): array
    {
        return array(self::FILM_EVENT_TYPE,
            self::THEATER_EVENT_TYPE,
            self::CONFERENCE_EVENT_TYPE,
            self::CONCERT_EVENT_TYPE);
    }

    /**
     * Devuelve todos los tipos de géneros disponibles
     * @return array
     */
    public function getAllGenresTypes(): array
    {
        return array(
            self::ACTION_EVENT, self::COMEDY_EVENT, self::DRAMA_EVENT, self::FANTASY_EVENT, self::HORROR_EVENT,
            self::MYSTERY_EVENT, self::ROMANCE_EVENT, self::INFORMATIVE_EVENT, self::THRILLER_EVENT, self::WESTERN_EVENT,
            self::SCIENCE_FICTION_EVENT
        );
    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #
}
