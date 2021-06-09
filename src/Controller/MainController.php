<?php

namespace App\Controller;

use App\Entity\EventData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
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

        //$configuration = $eventController->getIMDBConfiguration();

        $events_data[] = $eventController->getIMDBFilmByID(157336);
        $events_data[] = $eventController->getIMDBFilmByID(399566);
        $events_data[] = $eventController->getIMDBFilmByID(24);
        $events_data[] = $eventController->getIMDBFilmByID(615457);
        $events_data[] = $eventController->getIMDBFilmByID(577922);
        $events_data[] = $eventController->getIMDBFilmByID(460465);



        $eventDataRepository = $this->getDoctrine()->getRepository(EventData::class);
        $filmsStored=$eventDataRepository->findAll()[0];

        $data = array();


        $data[$filmsStored->getID()] = array(
            'title' => strtoupper($filmsStored->getTitle()),
            'genres' => $filmsStored->getGender(),
            'release_date' => $filmsStored->getReleaseDate()->format('Y-m-d'),
            'duration' => $filmsStored->getDuration(),
            'summary' => EventData::getShortenSummary($filmsStored->getDescription()),
            'poster_photo' => $filmsStored->getPosterPhoto()
        );


        foreach ($events_data as $event_data):
            if ($event_data !== NULL):

                $genres = $this->gendersToString($event_data['genres']);

                $overview = EventData::getShortenSummary($event_data['overview']);

                $data[$event_data['id']] = array(
                    'title' => strtoupper($event_data['title']),
                    'genres' => $genres,
                    'release_date' => $event_data['release_date'],
                    'duration' => $event_data['runtime'],
                    'summary' => $overview,
                    'poster_photo' => $eventController->getImageBaseURLIMDB() . 'w154/' . $event_data['poster_path']
                );

            endif;
        endforeach;


        return $this->render('home.html.twig', array('films' => $data));

    }

    # ------------------------------------------------- METHODS ------------------------------------------------------ #
    public function gendersToString(array $genres):string
    {
        $genresString = '';
        foreach ($genres as $genre):
            if (!empty($genresString)):
                $genresString .= ', ';
            endif;
            $genresString .= $genre['name'];
        endforeach;

        return $genresString;
    }


    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
