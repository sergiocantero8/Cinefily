<?php

namespace App\Controller;

use App\Entity\EventData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function count;

class HomeController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const MAX_WORDS = 40;
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
        $events_data[] = $eventController->getIMDBFilmByID(24);
        $events_data[] = $eventController->getIMDBFilmByID(503736);
        $events_data[] = $eventController->getIMDBFilmByID(577922);


        $eventDataRepository = $this->getDoctrine()->getRepository(EventData::class);
        $filmsStored=$eventDataRepository->findAll()[0];

        $data = array();

        $data[$filmsStored->getID()] = array(
            'title' => strtoupper($filmsStored->getTitle()),
            'genres' => $filmsStored->getGender(),
            'release_date' => $filmsStored->getReleaseDate()->format('Y-m-d'),
            'duration' => $filmsStored->getDuration(),
            'summary' => $filmsStored->getDescription(),
            'poster_photo' => $filmsStored->getPosterPhoto()
        );

        foreach ($events_data as $event_data):
            if ($event_data !== NULL):

                $genres = $this->gendersToString($event_data['genres']);

                $overview = $this->getShortenSummary($event_data['overview']);

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

    public function getShortenSummary(string $overview):string
    {

        $words = explode(' ', $overview);

        if (count($words) >= self::MAX_WORDS):
            $overview = '';
            foreach ($words as $number => $word):
                if ($number !== self::MAX_WORDS):
                    $overview .= $word;
                    $overview .= ' ';
                else:
                    $overview .= '...';
                    break;
                endif;
            endforeach;
        endif;

        return $overview;
    }
    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
