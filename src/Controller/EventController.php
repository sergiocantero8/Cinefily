<?php

namespace App\Controller;

use App\Entity\EventData;
use App\Entity\User;
use App\Form\AddEventType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
    public const ADVENTURE_EVENT = 'adventure';
    public const ANIMATION_EVENT = 'animation';
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

    # Rango de edades
    public const TO_ALL_PUBLIC = 'A';
    public const OLDER_THAN_7 = '+7';
    public const OLDER_THAN_12 = '+12';
    public const OLDER_THAN_16 = '+16';
    public const OLDER_THAN_18 = '+18';

    # API KEY
    private const API_KEY = '99e1f232e39064087b6bbd5255d957b4';

    #Códigos de estado
    public const SUCCESS_STATUS_CODE = 200;


    # Rutas
    public const ROUTE_EVENT_DETAILS = 'event_details';

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    // Para peticiones HTTP
    private $client;

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }
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
                    'genders_types' => $this->getAllGenresTypes(),
                    'age_rating_types' => $this->getAllAgeRating())
            )
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()):
            $data_form = $form->getData();


            # Creamos un nuevo evento
            $event = new EventData();

            # Seteamos todas las propiedades
            $event->setTitle($data_form['title']);
            $event->setType($data_form['type']);
            $event->setGender($data_form['gender']);
            $event->setDescription($data_form['description']);
            $event->setDuration($data_form['duration']);
            $event->setReleaseDate($data_form['release_date']);
            $event->setActors($data_form['actors']);
            $event->setRating($data_form['rating']);
            $event->setStatus($data_form['status']);

            $img = $data_form['poster_photo'];

            if ($img) {
                $fileName = md5(uniqid('', false)) . '' . $img->guessExtension();

                try {
                    $img->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    $this->createNotFoundException('Directorio images no encontrado');
                }

                $event->setPosterPhoto($fileName);
            }


            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', '¡El evento se ha añadido correctamente!');
        endif;


        $data = array(
            'form' => $form->createView()
        );

        return $this->render('event/add.html.twig', $data);
    }


    /**
     * @Route("/event/details", name="event_details")
     */
    public function seeEventDetails(Request $request): Response
    {

        $form = $this->createFormBuilder(array('csrf_protection' => FALSE))
            ->setMethod(Request::METHOD_POST)
            ->setAction($this->generateUrl(static::ROUTE_EVENT_DETAILS))
            ->add('comment', TextareaType::class, array(
                'label' => 'Comentar',
                'attr' => array(
                    'rows' => 5
                ),
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Enviar',
            ))
            ->getForm();

        $form->handleRequest($request);

        $data = NULL;
        $ID = $request->get('id');
        $tmdbID = $request->get('tmdb_id');


        if ($tmdbID !== NULL):
            $event_data = $this->getIMDBFilmByID($tmdbID);
            if ($event_data !== NULL):
                $data = array(
                    'tmdb_id' => $event_data['id'],
                    'title' => strtoupper($event_data['title']),
                    'genres' => $this->convertTMDBGenresToArray($event_data['genres']),
                    'release_date' => $event_data['release_date'],
                    'duration' => $event_data['runtime'],
                    'summary' => $event_data['overview'],
                    'poster_photo' => $this->getImageBaseURLIMDB() . 'w185/' . $event_data['poster_path'],
                    'age_rating' => 'PG-13',
                    'tagline' => $event_data['tagline'],
                    'backdrop' => $this->getImageBaseURLIMDB() . 'original/' . $event_data['backdrop_path'],
                    'youtube_key' => $this->extractYoutubeTrailerTMDB($event_data['videos']),
                    'vote_average' => $event_data['vote_average'],
                    'form' => $form->createView()
                );
            endif;
        elseif ($ID !== NULL):
            $data = $this->getDoctrine()->getRepository(EventData::class)->find($ID);
        endif;

        return $this->render('event/description.html.twig', array('data' => $data));
    }


    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    /**
     *  Devuelve los datos de una película con el id que se le pase por parámetro,
     *  null si no existe la película con ese id
     * @param int $film_id
     * @return array|null
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getIMDBFilmByID(int $film_id): ?array
    {
        $result = NULL;

        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/movie/' . $film_id . '?api_key=' . self::API_KEY . '&language=es-ES&append_to_response=videos'
        );

        if ($response->getStatusCode() === self::SUCCESS_STATUS_CODE):
            $result = $response->toArray();
        endif;


        return $result;
    }

    /**
     *  Devuelve los datos de las películas que se estrenarán proximamente
     * @return array|null
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getTMDBFilmsUpcoming(): ?array
    {
        $result = NULL;

        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/movie/upcoming?api_key=' . self::API_KEY . '&language=es-ES&page=1'
        );

        if ($response->getStatusCode() === self::SUCCESS_STATUS_CODE):
            $result = $response->toArray();
        endif;


        return $result;
    }

    /**
     *  Devuelve los datos de una película con el id que se le pase por parámetro,
     *  null si no existe la película con ese id
     *
     * @param int $film_id
     * @return array|null
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getIMDB_VideosFilm(int $film_id): ?array
    {
        $result = NULL;

        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/movie/' . $film_id . '/videos' . '?api_key=' . self::API_KEY . '&language=es-ES'
        );

        if ($response->getStatusCode() === self::SUCCESS_STATUS_CODE):
            $result = $response->toArray();
        endif;


        return $result;
    }

    /**
     * Devuelve la url base para visualizar una imagen de la api de the movie database
     * @return string
     */
    public function getImageBaseURLIMDB(): string
    {
        return 'https://image.tmdb.org/t/p/';
    }

    /**
     * Devuelve la configuración basica asocidada a la key de la api
     * @return array|null
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getIMDBConfiguration(): ?array
    {
        $result = NULL;

        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/configuration?api_key=' . self::API_KEY
        );

        if ($response->getStatusCode() === self::SUCCESS_STATUS_CODE):
            $result = $response->toArray();
        endif;

        return $result;
    }

    /**
     * Devuelve todos los tipos de eventos disponibles
     * @return array
     */
    public function getAllEventTypes(): array
    {
        return array(
            'Película' => self::FILM_EVENT_TYPE,
            'Teatro' => self::THEATER_EVENT_TYPE,
            'Conferencia' => self::CONFERENCE_EVENT_TYPE,
            'Concierto' => self::CONCERT_EVENT_TYPE);
    }

    /**
     * Devuelve todos los tipos de géneros disponibles
     * @return array
     */
    public function getAllGenresTypes(): array
    {
        return array(
            'Acción' => self::ACTION_EVENT,
            'Comedia' => self::COMEDY_EVENT,
            'Drama' => self::DRAMA_EVENT,
            'Fantasía' => self::FANTASY_EVENT,
            'Terror' => self::HORROR_EVENT,
            'Intriga' => self::MYSTERY_EVENT,
            'Romántica' => self::ROMANCE_EVENT,
            'Divulgativo' => self::INFORMATIVE_EVENT,
            'Thriller' => self::THRILLER_EVENT,
            'Western' => self::WESTERN_EVENT,
            'Ciencia Ficción' => self::SCIENCE_FICTION_EVENT,
            'Aventuras' => self:: ADVENTURE_EVENT,
            'Animación' => self::ANIMATION_EVENT
        );
    }

    /**
     * Devuelve todos los tipos de géneros disponibles
     * @return array
     */
    public function getAllAgeRating(): array
    {
        return array(
            'Todos los públicos' => self::TO_ALL_PUBLIC,
            'Mayores de 7 años' => self::OLDER_THAN_7,
            'Mayores de 12 años' => self::OLDER_THAN_12,
            'Mayores de 16 años' => self::OLDER_THAN_16,
            'Mayores de 18' => self::OLDER_THAN_18
        );
    }


    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    /**
     * Convierte el array de generos de películas de TMDB en un array standar
     * @param array $genres
     * @return array
     */
    private function convertTMDBGenresToArray(array $genres): array
    {
        $result = array();

        foreach ($genres as $genre):
            $result[] = $genre['name'];
        endforeach;

        return $result;
    }

    private function extractYoutubeTrailerTMDB(array $videos): ?string
    {
        $key = NULL;
        if (!empty($videos['results'])):
            $firstVideo = $videos['results'][0];
            if (isset($firstVideo['key']) && $firstVideo['key'] !== NULL):
                $key=$firstVideo['key'];
            endif;
        endif;

        return $key;
    }

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #
    public static function gendersToString(array $genres): string
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


}
