<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- ROUTES ------------------------------------------------------- #


        /**
        * @Route("/", name="home")
        */
        public function renderHomePage(Request $request, EventController $eventController): Response
        {

            $configuration=$eventController->getIMDBConfiguration();

            $event_data= $eventController->getIMDBFilmByID(5);

            if ($event_data !== NULL):
                $data= array(
                    'title' => $event_data['title'],
                    'genres' => $event_data['genres'],
                    'release_date' => $event_data['release_date'],
                    'summary' => $event_data['overview'],
                    'poster_photo' => $eventController->getImageBaseURLIMDB() . 'w342/' . $event_data['poster_path']
                );

                endif;




            return $this->render('home.html.twig', $data);


        }

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #



}
