<?php

namespace App\Controller;

use App\Entity\EventData;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const LIMIT_UPCOMING_FILMS = 5;
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

        $upcomingsFilmsPage = $eventController->getTMDBFilmsUpcoming();

        foreach ($upcomingsFilmsPage as $key => $film):
            if ($key <= static::LIMIT_UPCOMING_FILMS):
                $upcoming_data[] = $eventController->getIMDBFilmByID($film);
            else:
                break;
            endif;
        endforeach;

        $homeIDSFilms = $this->getTMDB_FilmIDs();

        foreach ($homeIDSFilms as $filmID):
            $events_data[] = $eventController->getIMDBFilmByID($filmID);
        endforeach;


        $eventDataRepository = $this->getDoctrine()->getRepository(EventData::class);
        $filmsStored = $eventDataRepository->findAll();

        $data = array();

        foreach ($filmsStored as $film):
            $data[] = array(
                'id' => $film->getID(),
                'title' => strtoupper($film->getTitle()),
                'genres' => $film->getGender(),
                'release_date' => $film->getReleaseDate()->format('Y-m-d'),
                'duration' => $film->getDuration(),
                'summary' => EventData::getShortenSummary($film->getDescription()),
                'poster_photo' => $film->getPosterPhoto()
            );
        endforeach;

        if (isset($events_data)):
            foreach ($events_data as $event_data):
                if ($event_data !== NULL):

                    $genres = EventController::gendersToString($event_data['genres']);

                    $overview = EventData::getShortenSummary($event_data['overview']);

                    $data[] = array(
                        'tmdb_id' => $event_data['id'],
                        'title' => strtoupper($event_data['title']),
                        'genres' => $genres,
                        'release_date' => $event_data['release_date'],
                        'duration' => $event_data['runtime'],
                        'summary' => $overview,
                        'poster_photo' => $eventController->getImageBaseURLIMDB() . 'w154/' . $event_data['poster_path'],
                    );

                endif;
            endforeach;
        endif;

        return $this->render('home.html.twig', array('films' => $data));

    }

    # ------------------------------------------------- METHODS ------------------------------------------------------ #
    private function getTMDB_FilmIDs(): array
    {
        return array(157336, 808, 399566, 24, 615457, 577922, 460465);
    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
