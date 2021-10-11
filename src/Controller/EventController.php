<?php /** @noinspection EfferentObjectCouplingInspection */
/** @noinspection PhpParamsInspection */

/** @noinspection MultipleReturnStatementsInspection */

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\Comment;
use App\Entity\EventData;
use App\Entity\Session;
use App\Entity\User;
use App\Form\AddEventType;
use DateInterval;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
use function array_key_exists;
use function in_array;

class EventController extends AbstractController
{


    # -------------------------------------------------- CONST ------------------------------------------------------- #

    # Tipos de eventos
    public const FILM_EVENT_TYPE = 'película';
    public const THEATER_EVENT_TYPE = 'teatro';
    public const CONFERENCE_EVENT_TYPE = 'conferencia';
    public const CONCERT_EVENT_TYPE = 'concierto';


    # Géneros de eventos
    public const ACTION_EVENT = 'acción';
    public const ADVENTURE_EVENT = 'aventuras';
    public const ANIMATION_EVENT = 'animacion';
    public const COMEDY_EVENT = 'comedia';
    public const DRAMA_EVENT = 'drama';
    public const FANTASY_EVENT = 'fantasia';
    public const HORROR_EVENT = 'horror';
    public const MYSTERY_EVENT = 'misterio';
    public const ROMANCE_EVENT = 'romance';
    public const THRILLER_EVENT = 'thriller';
    public const WESTERN_EVENT = 'western';
    public const SCIENCE_FICTION_EVENT = 'ciencia ficción';
    public const INFORMATIVE_EVENT = 'informativa';
    public const TMDB_EVENT = 'TMDB';

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
    public const ROUTE_ADD_EVENT = 'add_event';
    public const ROUTE_SHOW_TIMES = 'show_times';

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
     * Ruta para añadir un evento a través de un formulario. Un evento solo lo puede añadir un usuario con privilegios
     * de administrador
     * @Route("/admin/event/add", name="add_event")
     */
    public function addEvent(Request $request): Response
    {

        if (!$this->getUser() || ($this->getUser() && !in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        $form = $this->createForm(AddEventType::class, NULL,
            array(
                'data' => array(
                    'event_types' => $this->getAllEventTypes(),
                    'genders_types' => static::getAllGenresTypes(),
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
            if ($data_form['duration'] !== null && $data_form['duration'] > 0):
                $event->setDuration($data_form['duration']);
            endif;
            $event->setReleaseDate($data_form['release_date']);
            $event->setActors($data_form['actors']);
            $event->setRating($data_form['rating']);
            $event->setStatus($data_form['status']);
            $event->setDirector($data_form['director']);
            $event->setTagLine($data_form['tag_line']);

            if ($data_form['youtube_trailer'] !== null):
                parse_str(parse_url($data_form['youtube_trailer'], PHP_URL_QUERY), $vars);
                if ($vars['v'] !== null):
                    $event->setYoutubeTrailer($vars['v']);
                endif;
            endif;

            $img_poster = $data_form['poster_photo'];
            $img_backdrop = $data_form['backdrop_photo'];

            if ($img_poster) {
                $fileName = md5(uniqid('', false)) . '' . $img_poster->guessExtension();

                try {
                    $img_poster->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    $this->createNotFoundException('Directorio images no encontrado');
                }

                $event->setPosterPhoto($fileName);
            }

            if ($img_backdrop) {
                $fileName = md5(uniqid('', false)) . '' . $img_backdrop->guessExtension();

                try {
                    $img_backdrop->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    $this->createNotFoundException('Directorio images no encontrado');
                }

                $event->setBackdropPath($fileName);
            }


            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', '¡El evento se ha añadido correctamente!');
            return $this->redirectToRoute('home');
        endif;


        $data = array(
            'form' => $form->createView()
        );

        return $this->render('event/add.html.twig', $data);
    }


    /**
     * Ruta para ver los detalles de un evento: descripción, portada, puntuación, comentarios y detalles del evento
     * @Route("/event/details", name="event_details")
     */
    public function seeEventDetails(Request $request): Response
    {

        # Se crea obtiene el ID de la película y el tmdbID (uno de los dos será nulo)
        $data = NULL;
        $ID = $request->get('id');
        $tmdbID = $request->get('tmdb_id');

        # Se crea el formulario para la sección de comentarios si hay un usuario autenticado, de lo contrario es null
        $commentsForm = NULL;

        if ($this->getUser()):
            if ($ID !== null):
                $action = '?id=' . $ID;
            else:
                $action = '?tmdb_id=' . $tmdbID;
            endif;
            $commentsForm = $this->createFormBuilder(array('csrf_protection' => FALSE))
                ->setMethod(Request::METHOD_POST)
                ->setAction($this->generateUrl(static::ROUTE_EVENT_DETAILS) . $action)
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

            $commentsForm->handleRequest($request);

        endif;


        # Si el usuario está identificado y se ha enviado un comentario, lo registramos en la base de datos
        if ($commentsForm !== NULL && $this->getUser() && $commentsForm->isSubmitted() && $commentsForm->isValid()):
            $comment = new Comment();
            $text = $commentsForm->getData()['comment'];
            $comment->setText($text);
            $comment->setCreatedAt(new DateTime());
            $comment->setUser($this->getUser());

            if ($ID !== null):
                $event = $this->getDoctrine()->getRepository(EventData::class)->findOneBy(array('id' => $ID));
                if ($event !== null):
                    $comment->setEvent($event);
                endif;
            elseif ($tmdbID !== null):
                $comment->setTmdbId((int)$tmdbID);
            endif;

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();
        endif;

        # Si es una película que viene de TMDB
        if ($tmdbID !== NULL):
            $event_data = $this->getIMDBFilmByID($tmdbID);
            $commentsObject = $this->getDoctrine()->getRepository(Comment::class)->findBy(array('tmdb_id' => $tmdbID));

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
                    'form' => $commentsForm !== NULL ? $commentsForm->createView() : $commentsForm,
                );
            endif;

        # Si es una película que tenemos almacenada en la base de datos
        elseif ($ID !== NULL):
            $event_data = $this->getDoctrine()->getRepository(EventData::class)->find($ID);

            if ($event_data !== NULL):

                $commentsObject = $this->getDoctrine()->getRepository(Comment::class)->findBy(array('event' => $event_data->getID()));

                $data = array(
                    'id' => $event_data->getID(),
                    'title' => strtoupper($event_data->getTitle()),
                    'genres' => explode(', ', $event_data->getGender()),
                    'release_date' => $event_data->getReleaseDate(),
                    'duration' => $event_data->getDuration(),
                    'summary' => $event_data->getDescription(),
                    'poster_photo' => $event_data->getPosterPhoto(),
                    'age_rating' => $event_data->getAgeRating(),
                    'tagline' => $event_data->getTagLine(),
                    'backdrop' => $event_data->getBackdropPath(),
                    'director' => $event_data->getDirector(),
                    'actors' => $event_data->getActors(),
                    'youtube_key' => $event_data->getYoutubeTrailer(),
                    'vote_average' => $event_data->getRating(),
                    'form' => $commentsForm !== NULL ? $commentsForm->createView() : $commentsForm,
                );
            endif;

        endif;

        if (isset($commentsObject)):
            $comments = array();
            foreach ($commentsObject as $item):
                $comments[$item->getID()]['text'] = $item->getText();
                $comments[$item->getID()]['username'] = $item->getUser()->getName() . ' ' . $item->getUser()->getSurname();
                $comments[$item->getID()]['createdAt'] = $item->getCreatedAt()->format('Y-m-d H:i:s');
                $comments[$item->getID()]['profilePic'] = $item->getUser()->getPhoto();
                $comments[$item->getID()]['userID'] = $item->getUser()->getId();
            endforeach;

            $data['comments'] = $comments;
        endif;

        if ($this->getUser()):
            $data['user'] = $this->getUser();
        endif;
        // Si los datos son nulos cargamos la página de error
        if ($data === null):
            $template = 'error.html.twig';
        else:
            $template = 'event/description.html.twig';
        endif;

        return $this->render($template, array('data' => $data));
    }

    /**
     * @Route("/showtimes", name="show_times")
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function renderShowTimes(Request $request): Response
    {
        # Obtenemos toda la información del Log
        $cinemasRe = $this->getDoctrine()->getRepository(Cinema::class)->findAll();
        $cinemas = array();
        foreach ($cinemasRe as $cinema):
            $cinemas[$cinema->getName()] = $cinema->getID();
        endforeach;

        $sessionsByEvent = null;

        $form = $this->createFormBuilder(array('csrf_protection' => FALSE))
            ->setMethod(Request::METHOD_GET)
            ->setAction($this->generateUrl(static::ROUTE_SHOW_TIMES))
            ->add('cinema', ChoiceType::class, array(
                'label' => 'Cine',
                'choices' => $cinemas
            ))
            ->add('schedule', DateType::class, array(
                'label' => 'Fecha',
                'placeholder' => [
                    'year' => 'Año', 'month' => 'Mes', 'day' => 'Dia'
                ],
                'years' => range(2021, 2023)
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Buscar',
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()):
            $dataForm = $form->getData();

            $schedule_start = $dataForm['schedule'];
            $schedule_end = new DateTime($schedule_start->format('Y-m-d H:i'));
            $schedule_end->add(new DateInterval(('P1D')));

            $cinema = $this->getDoctrine()->getRepository(Cinema::class)->findOneBy(array('id' => $dataForm['cinema']));

            if ($cinema !== null):
                $sessions = $this->getDoctrine()->getRepository(Session::class)->findByDate($cinema,
                    $schedule_start, $schedule_end);
            endif;

            if (empty($sessions)):
                $this->addFlash('warning', "No hay sesiones programadas para ese día ");
            else:
                $sessionsByEvent = array();
                foreach ($sessions as $session):
                    if (!array_key_exists($session->getEvent()->getId(), $sessionsByEvent)):
                        $event = $this->getDoctrine()->getRepository(EventData::class)->findOneBy(array('id' => $session->getEvent()->getId()));
                        $sessionsByEvent[$session->getEvent()->getId()]['event'] = $event;
                    endif;
                    $sessionsByEvent[$session->getEvent()->getId()][$session->getRoom()->getNumber()][] = $session;
                endforeach;

            endif;


        endif;

        $data = array(
            'form' => $form->createView(),
            'sessionsByEvent' => $sessionsByEvent
        );

        return $this->render('/cinema/showtimes.html.twig', $data);
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
    public static function getAllGenresTypes(): array
    {
        return array(
            'Acción' => static::ACTION_EVENT,
            'Comedia' => static::COMEDY_EVENT,
            'Drama' => static::DRAMA_EVENT,
            'Fantasía' => static::FANTASY_EVENT,
            'Terror' => static::HORROR_EVENT,
            'Intriga' => static::MYSTERY_EVENT,
            'Romántica' => static::ROMANCE_EVENT,
            'Divulgativo' => static::INFORMATIVE_EVENT,
            'Thriller' => static::THRILLER_EVENT,
            'Western' => static::WESTERN_EVENT,
            'Ciencia Ficción' => static::SCIENCE_FICTION_EVENT,
            'Aventuras' => static:: ADVENTURE_EVENT,
            'Animación' => static::ANIMATION_EVENT,
            'TMDB' => static::TMDB_EVENT,
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
                $key = $firstVideo['key'];
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
            $genresString .= ucfirst($genre['name']);
        endforeach;

        return $genresString;
    }


}
