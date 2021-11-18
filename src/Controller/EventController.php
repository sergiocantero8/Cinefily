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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
use Knp\Component\Pager\PaginatorInterface;

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
    public const ROUTE_SEARCH_EVENT = 'search_event';
    public const ROUTE_EDIT_EVENT = 'edit_event';

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
                    'age_rating_types' => $this->getAllAgeRating(),
                    'action' => $this->generateUrl(static::ROUTE_ADD_EVENT))
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

            if ($img_poster !== null) {
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

            if ($img_backdrop !== null) {
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

        if ($ID === null && $tmdbID === null):
            return $this->redirectToRoute('page_error');
        endif;

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
     * Ruta para ver la cartelera dependiendo del cine y fecha elegidos
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
                'years' => range(2021, 2023),
                'data' => new DateTime()
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

            $cinema = $this->getDoctrine()->getRepository(Cinema::class)->find($dataForm['cinema']);

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
                        $event = $session->getEvent();
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

    /**
     * @Route("/search", name="search_event")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function search(Request $request, PaginatorInterface $paginator): Response
    {
        $eventTitle = $request->get('event');
        $eventType = $request->get('event_type');
        $searchResults = null;
        $searchQuery = null;

        if ($eventType !== null):
            if ($eventType === static::FILM_EVENT_TYPE):
                $searchQuery = $this->getDoctrine()->getRepository(EventData::class)->findByEventTypeQuery($eventType);
            else:
                $searchQuery = $this->getDoctrine()->getRepository(EventData::class)->findByEventTypeQuery(null);
            endif;
        elseif ($eventTitle !== null):
            $searchQuery = $this->getDoctrine()->getRepository(EventData::class)->findByTitleQuery($eventTitle);
        endif;

        if ($searchQuery !== null):
            // Paginar los resultados de la consulta
            $searchResults = $paginator->paginate(
            // Consulta Doctrine, no resultados
                $searchQuery,
                // Definir el parámetro de la página
                $request->query->getInt('page', 1),
                // Items per page
                5
            );
        endif;

        if ($searchResults !== null):
            foreach ($searchResults as $event):
                $eventID = $event->getId();
                $sessions[$eventID] = $this->getDoctrine()->getRepository(Session::class)->findByActiveSessionsEvent($event);
            endforeach;
        endif;


        if (empty($searchResults->getItems())):
            $this->addFlash('warning', 'No hay resultados');
        endif;

        return $this->render('event/search.html.twig', array('results' => $searchResults ?? null,
            'sessions' => $sessions ?? null, 'event_title' => $eventTitle, 'event_type' => $eventType));

    }


    /**
     * @Route("/event/delete", name="delete_event")
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteEvent(Request $request): RedirectResponse
    {

        if (!$this->getUser() || ($this->getUser() && !in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;


        $eventID = $request->get('id_event');

        if ($eventID !== null):
            $event = $this->getDoctrine()->getRepository(EventData::class)->find($eventID);
            $em = $this->getDoctrine()->getManager();

            if ($event !== null):
                if ($event->getPosterPhoto() !== null):
                    $fs = new Filesystem();
                    $fs->remove($this->getParameter('images_directory') . '/' . $event->getPosterPhoto());
                endif;

                if ($event->getBackdropPath() !== null):
                    $fs = new Filesystem();
                    $fs->remove($this->getParameter('images_directory') . '/' . $event->getBackdropPath());
                endif;

                $em->remove($event);
                $em->flush();
                $this->addFlash('success', 'El evento se ha eliminado correctamente');
            else:
                $this->addFlash('error', 'No existe el evento con ese ID');
            endif;

        endif;


        return $this->redirectToRoute('home');

    }


    /**
     * @Route("/event/edit", name="edit_event")
     * @param Request $request
     * @return Response
     */
    public function editEvent(Request $request): Response
    {

        if (!$this->getUser() || ($this->getUser() && !in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;


        $eventID = $request->get('id');

        if ($eventID !== null):
            $event = $this->getDoctrine()->getRepository(EventData::class)->find($eventID);

            if ($event !== null):
                $form = $this->createForm(AddEventType::class, NULL,
                    array(
                        'data' => array(
                            'event_types' => $this->getAllEventTypes(),
                            'genders_types' => static::getAllGenresTypes(),
                            'age_rating_types' => $this->getAllAgeRating(),
                            'event' => $event,
                            'action' => '/event/edit?id=' . $eventID)
                    )
                );
                $form->handleRequest($request);

            endif;

        endif;

        if ($form->isSubmitted() && $form->isValid()):
            $dataForm = $form->getData();

            # Creamos un nuevo evento
            $event = new EventData();

            # Seteamos todas las propiedades
            $event->setTitle($dataForm['title']);
            $event->setType($dataForm['type']);
            $event->setGender($dataForm['gender']);
            $event->setDescription($dataForm['description']);
            if ($dataForm['duration'] !== null && $dataForm['duration'] > 0):
                $event->setDuration($dataForm['duration']);
            endif;
            $event->setReleaseDate($dataForm['release_date']);
            $event->setActors($dataForm['actors']);
            $event->setRating($dataForm['rating']);
            $event->setStatus($dataForm['status']);
            $event->setDirector($dataForm['director']);
            $event->setTagLine($dataForm['tag_line']);

            if (($dataForm['youtube_trailer'] !== null) && str_contains($dataForm['youtube_trailer'], 'youtube.com')):
                parse_str(parse_url($dataForm['youtube_trailer'], PHP_URL_QUERY), $vars);
                if ($vars['v'] !== null):
                    $event->setYoutubeTrailer($vars['v']);
                endif;
            endif;

            $imgPoster = $dataForm['poster_photo'];
            $imgBackdrop = $dataForm['backdrop_photo'];


            if ($imgPoster !== null && $imgPoster !== $event->getPosterPhoto()) {

                $fs = new Filesystem();
                $fs->remove($this->getParameter('images_directory') . '/' . $event->getPosterPhoto());

                $fileName = md5(uniqid('', false)) . '' . $imgPoster->guessExtension();

                try {
                    $imgPoster->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    $this->createNotFoundException('Directorio images no encontrado');
                }

                $event->setPosterPhoto($fileName);
            }

            if ($imgBackdrop !== null && $imgBackdrop !== $event->getBackdropPath()) {

                $fs = new Filesystem();
                $fs->remove($this->getParameter('images_directory') . '/' . $event->getBackdropPath());

                $fileName = md5(uniqid('', false)) . '' . $imgBackdrop->guessExtension();

                try {
                    $imgBackdrop->move(
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
            $this->addFlash('success', '¡El evento se ha editado correctamente!');

        endif;


        $data = array(
            'form' => $form->createView(),
            'event' => $event ?? null
        );

        return $this->render('event/add.html.twig', $data);

    }


    /**
     * @Route("/upcoming", name="upcoming")
     * @return Response
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function upcomingFilms(): Response
    {

        # Películas que se estrenarán proximamente
        $upcomingsFilms = $this->getTMDBFilmsUpcoming()['results'];

        $data = null;

        foreach ($upcomingsFilms as $filmTMDB):
            if ($filmTMDB !== NULL):

                $overview = EventData::getShortenSummary($filmTMDB['overview']);

                $data[] = array(
                    'tmdb_id' => $filmTMDB['id'],
                    'title' => strtoupper($filmTMDB['title']),
                    'release_date' => $filmTMDB['release_date'],
                    'summary' => $overview,
                    'poster_photo' => $this->getImageBaseURLIMDB() . 'w154/' . $filmTMDB['poster_path'],
                    'mark' => $filmTMDB['vote_average']
                );

            endif;
        endforeach;

        return $this->render('event/upcoming.html.twig', array('films' => $data));

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
            'https://api.themoviedb.org/3/movie/upcoming?api_key=' . static::API_KEY . '&language=es-ES&page=1'
        );

        if ($response->getStatusCode() === static::SUCCESS_STATUS_CODE):
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
    public static function gendersToString(?array $genres): ?string
    {
        $genresString = '';

        if ($genres !== null):
            foreach ($genres as $genre):
                if (!empty($genresString)):
                    $genresString .= ', ';
                endif;
                $genresString .= ucfirst($genre['name']);
            endforeach;
        else:
            $genresString = null;
        endif;

        return $genresString;
    }

    public static function gendersToArray(string $genres): array
    {
        return explode(',', $genres);
    }

    public static function getSVGQR(): string
    {
        return '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
	            viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
	            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="100" height="100" viewBox="0 0 2000 2000" x="0" y="0" shape-rendering="crispEdges"><defs/><rect x="0" y="0" width="2000" height="2000" fill="#ffffff"/><rect x="629" y="173" width="57" height="57" fill="#000000"/><rect x="686" y="173" width="57" height="57" fill="#000000"/><rect x="743" y="173" width="57" height="57" fill="#000000"/><rect x="800" y="173" width="57" height="57" fill="#000000"/><rect x="857" y="173" width="57" height="57" fill="#000000"/><rect x="1028" y="173" width="57" height="57" fill="#000000"/><rect x="1199" y="173" width="57" height="57" fill="#000000"/><rect x="629" y="230" width="57" height="57" fill="#000000"/><rect x="743" y="230" width="57" height="57" fill="#000000"/><rect x="914" y="230" width="57" height="57" fill="#000000"/><rect x="971" y="230" width="57" height="57" fill="#000000"/><rect x="1142" y="230" width="57" height="57" fill="#000000"/><rect x="629" y="287" width="57" height="57" fill="#000000"/><rect x="743" y="287" width="57" height="57" fill="#000000"/><rect x="857" y="287" width="57" height="57" fill="#000000"/><rect x="914" y="287" width="57" height="57" fill="#000000"/><rect x="971" y="287" width="57" height="57" fill="#000000"/><rect x="1028" y="287" width="57" height="57" fill="#000000"/><rect x="1142" y="287" width="57" height="57" fill="#000000"/><rect x="1199" y="287" width="57" height="57" fill="#000000"/><rect x="686" y="344" width="57" height="57" fill="#000000"/><rect x="800" y="344" width="57" height="57" fill="#000000"/><rect x="971" y="344" width="57" height="57" fill="#000000"/><rect x="1085" y="344" width="57" height="57" fill="#000000"/><rect x="1313" y="344" width="57" height="57" fill="#000000"/><rect x="800" y="401" width="57" height="57" fill="#000000"/><rect x="857" y="401" width="57" height="57" fill="#000000"/><rect x="914" y="401" width="57" height="57" fill="#000000"/><rect x="1028" y="401" width="57" height="57" fill="#000000"/><rect x="1085" y="401" width="57" height="57" fill="#000000"/><rect x="1142" y="401" width="57" height="57" fill="#000000"/><rect x="1199" y="401" width="57" height="57" fill="#000000"/><rect x="629" y="458" width="57" height="57" fill="#000000"/><rect x="800" y="458" width="57" height="57" fill="#000000"/><rect x="914" y="458" width="57" height="57" fill="#000000"/><rect x="971" y="458" width="57" height="57" fill="#000000"/><rect x="1142" y="458" width="57" height="57" fill="#000000"/><rect x="629" y="515" width="57" height="57" fill="#000000"/><rect x="743" y="515" width="57" height="57" fill="#000000"/><rect x="857" y="515" width="57" height="57" fill="#000000"/><rect x="971" y="515" width="57" height="57" fill="#000000"/><rect x="1085" y="515" width="57" height="57" fill="#000000"/><rect x="1199" y="515" width="57" height="57" fill="#000000"/><rect x="1313" y="515" width="57" height="57" fill="#000000"/><rect x="629" y="572" width="57" height="57" fill="#000000"/><rect x="743" y="572" width="57" height="57" fill="#000000"/><rect x="1028" y="572" width="57" height="57" fill="#000000"/><rect x="1085" y="572" width="57" height="57" fill="#000000"/><rect x="1142" y="572" width="57" height="57" fill="#000000"/><rect x="1199" y="572" width="57" height="57" fill="#000000"/><rect x="1256" y="572" width="57" height="57" fill="#000000"/><rect x="1313" y="572" width="57" height="57" fill="#000000"/><rect x="287" y="629" width="57" height="57" fill="#000000"/><rect x="344" y="629" width="57" height="57" fill="#000000"/><rect x="401" y="629" width="57" height="57" fill="#000000"/><rect x="515" y="629" width="57" height="57" fill="#000000"/><rect x="629" y="629" width="57" height="57" fill="#000000"/><rect x="743" y="629" width="57" height="57" fill="#000000"/><rect x="914" y="629" width="57" height="57" fill="#000000"/><rect x="971" y="629" width="57" height="57" fill="#000000"/><rect x="1028" y="629" width="57" height="57" fill="#000000"/><rect x="1370" y="629" width="57" height="57" fill="#000000"/><rect x="1427" y="629" width="57" height="57" fill="#000000"/><rect x="1484" y="629" width="57" height="57" fill="#000000"/><rect x="1655" y="629" width="57" height="57" fill="#000000"/><rect x="1712" y="629" width="57" height="57" fill="#000000"/><rect x="1769" y="629" width="57" height="57" fill="#000000"/><rect x="230" y="686" width="57" height="57" fill="#000000"/><rect x="287" y="686" width="57" height="57" fill="#000000"/><rect x="344" y="686" width="57" height="57" fill="#000000"/><rect x="401" y="686" width="57" height="57" fill="#000000"/><rect x="458" y="686" width="57" height="57" fill="#000000"/><rect x="629" y="686" width="57" height="57" fill="#000000"/><rect x="1598" y="686" width="57" height="57" fill="#000000"/><rect x="230" y="743" width="57" height="57" fill="#000000"/><rect x="401" y="743" width="57" height="57" fill="#000000"/><rect x="515" y="743" width="57" height="57" fill="#000000"/><rect x="572" y="743" width="57" height="57" fill="#000000"/><rect x="1313" y="743" width="57" height="57" fill="#000000"/><rect x="1655" y="743" width="57" height="57" fill="#000000"/><rect x="287" y="800" width="57" height="57" fill="#000000"/><rect x="344" y="800" width="57" height="57" fill="#000000"/><rect x="1370" y="800" width="57" height="57" fill="#000000"/><rect x="1427" y="800" width="57" height="57" fill="#000000"/><rect x="1484" y="800" width="57" height="57" fill="#000000"/><rect x="1541" y="800" width="57" height="57" fill="#000000"/><rect x="173" y="857" width="57" height="57" fill="#000000"/><rect x="230" y="857" width="57" height="57" fill="#000000"/><rect x="401" y="857" width="57" height="57" fill="#000000"/><rect x="458" y="857" width="57" height="57" fill="#000000"/><rect x="515" y="857" width="57" height="57" fill="#000000"/><rect x="629" y="857" width="57" height="57" fill="#000000"/><rect x="1484" y="857" width="57" height="57" fill="#000000"/><rect x="1598" y="857" width="57" height="57" fill="#000000"/><rect x="1712" y="857" width="57" height="57" fill="#000000"/><rect x="230" y="914" width="57" height="57" fill="#000000"/><rect x="401" y="914" width="57" height="57" fill="#000000"/><rect x="572" y="914" width="57" height="57" fill="#000000"/><rect x="1313" y="914" width="57" height="57" fill="#000000"/><rect x="1598" y="914" width="57" height="57" fill="#000000"/><rect x="1712" y="914" width="57" height="57" fill="#000000"/><rect x="230" y="971" width="57" height="57" fill="#000000"/><rect x="344" y="971" width="57" height="57" fill="#000000"/><rect x="401" y="971" width="57" height="57" fill="#000000"/><rect x="515" y="971" width="57" height="57" fill="#000000"/><rect x="629" y="971" width="57" height="57" fill="#000000"/><rect x="1313" y="971" width="57" height="57" fill="#000000"/><rect x="1598" y="971" width="57" height="57" fill="#000000"/><rect x="1655" y="971" width="57" height="57" fill="#000000"/><rect x="173" y="1028" width="57" height="57" fill="#000000"/><rect x="230" y="1028" width="57" height="57" fill="#000000"/><rect x="458" y="1028" width="57" height="57" fill="#000000"/><rect x="572" y="1028" width="57" height="57" fill="#000000"/><rect x="629" y="1028" width="57" height="57" fill="#000000"/><rect x="1313" y="1028" width="57" height="57" fill="#000000"/><rect x="1370" y="1028" width="57" height="57" fill="#000000"/><rect x="1427" y="1028" width="57" height="57" fill="#000000"/><rect x="1484" y="1028" width="57" height="57" fill="#000000"/><rect x="1541" y="1028" width="57" height="57" fill="#000000"/><rect x="1598" y="1028" width="57" height="57" fill="#000000"/><rect x="1712" y="1028" width="57" height="57" fill="#000000"/><rect x="1769" y="1028" width="57" height="57" fill="#000000"/><rect x="230" y="1085" width="57" height="57" fill="#000000"/><rect x="344" y="1085" width="57" height="57" fill="#000000"/><rect x="401" y="1085" width="57" height="57" fill="#000000"/><rect x="515" y="1085" width="57" height="57" fill="#000000"/><rect x="572" y="1085" width="57" height="57" fill="#000000"/><rect x="629" y="1085" width="57" height="57" fill="#000000"/><rect x="1313" y="1085" width="57" height="57" fill="#000000"/><rect x="1484" y="1085" width="57" height="57" fill="#000000"/><rect x="173" y="1142" width="57" height="57" fill="#000000"/><rect x="230" y="1142" width="57" height="57" fill="#000000"/><rect x="344" y="1142" width="57" height="57" fill="#000000"/><rect x="401" y="1142" width="57" height="57" fill="#000000"/><rect x="458" y="1142" width="57" height="57" fill="#000000"/><rect x="1370" y="1142" width="57" height="57" fill="#000000"/><rect x="1484" y="1142" width="57" height="57" fill="#000000"/><rect x="1598" y="1142" width="57" height="57" fill="#000000"/><rect x="1712" y="1142" width="57" height="57" fill="#000000"/><rect x="173" y="1199" width="57" height="57" fill="#000000"/><rect x="287" y="1199" width="57" height="57" fill="#000000"/><rect x="515" y="1199" width="57" height="57" fill="#000000"/><rect x="1313" y="1199" width="57" height="57" fill="#000000"/><rect x="1484" y="1199" width="57" height="57" fill="#000000"/><rect x="1598" y="1199" width="57" height="57" fill="#000000"/><rect x="1655" y="1199" width="57" height="57" fill="#000000"/><rect x="173" y="1256" width="57" height="57" fill="#000000"/><rect x="287" y="1256" width="57" height="57" fill="#000000"/><rect x="344" y="1256" width="57" height="57" fill="#000000"/><rect x="458" y="1256" width="57" height="57" fill="#000000"/><rect x="1427" y="1256" width="57" height="57" fill="#000000"/><rect x="1541" y="1256" width="57" height="57" fill="#000000"/><rect x="1598" y="1256" width="57" height="57" fill="#000000"/><rect x="1712" y="1256" width="57" height="57" fill="#000000"/><rect x="1769" y="1256" width="57" height="57" fill="#000000"/><rect x="173" y="1313" width="57" height="57" fill="#000000"/><rect x="287" y="1313" width="57" height="57" fill="#000000"/><rect x="344" y="1313" width="57" height="57" fill="#000000"/><rect x="401" y="1313" width="57" height="57" fill="#000000"/><rect x="515" y="1313" width="57" height="57" fill="#000000"/><rect x="572" y="1313" width="57" height="57" fill="#000000"/><rect x="1028" y="1313" width="57" height="57" fill="#000000"/><rect x="1142" y="1313" width="57" height="57" fill="#000000"/><rect x="1199" y="1313" width="57" height="57" fill="#000000"/><rect x="1313" y="1313" width="57" height="57" fill="#000000"/><rect x="1370" y="1313" width="57" height="57" fill="#000000"/><rect x="1427" y="1313" width="57" height="57" fill="#000000"/><rect x="1484" y="1313" width="57" height="57" fill="#000000"/><rect x="1541" y="1313" width="57" height="57" fill="#000000"/><rect x="1712" y="1313" width="57" height="57" fill="#000000"/><rect x="1769" y="1313" width="57" height="57" fill="#000000"/><rect x="629" y="1370" width="57" height="57" fill="#000000"/><rect x="686" y="1370" width="57" height="57" fill="#000000"/><rect x="800" y="1370" width="57" height="57" fill="#000000"/><rect x="914" y="1370" width="57" height="57" fill="#000000"/><rect x="1085" y="1370" width="57" height="57" fill="#000000"/><rect x="1199" y="1370" width="57" height="57" fill="#000000"/><rect x="1256" y="1370" width="57" height="57" fill="#000000"/><rect x="1313" y="1370" width="57" height="57" fill="#000000"/><rect x="1541" y="1370" width="57" height="57" fill="#000000"/><rect x="1598" y="1370" width="57" height="57" fill="#000000"/><rect x="743" y="1427" width="57" height="57" fill="#000000"/><rect x="800" y="1427" width="57" height="57" fill="#000000"/><rect x="914" y="1427" width="57" height="57" fill="#000000"/><rect x="1028" y="1427" width="57" height="57" fill="#000000"/><rect x="1256" y="1427" width="57" height="57" fill="#000000"/><rect x="1313" y="1427" width="57" height="57" fill="#000000"/><rect x="1427" y="1427" width="57" height="57" fill="#000000"/><rect x="1541" y="1427" width="57" height="57" fill="#000000"/><rect x="1655" y="1427" width="57" height="57" fill="#000000"/><rect x="1712" y="1427" width="57" height="57" fill="#000000"/><rect x="1769" y="1427" width="57" height="57" fill="#000000"/><rect x="686" y="1484" width="57" height="57" fill="#000000"/><rect x="743" y="1484" width="57" height="57" fill="#000000"/><rect x="1085" y="1484" width="57" height="57" fill="#000000"/><rect x="1142" y="1484" width="57" height="57" fill="#000000"/><rect x="1256" y="1484" width="57" height="57" fill="#000000"/><rect x="1313" y="1484" width="57" height="57" fill="#000000"/><rect x="1541" y="1484" width="57" height="57" fill="#000000"/><rect x="1655" y="1484" width="57" height="57" fill="#000000"/><rect x="1712" y="1484" width="57" height="57" fill="#000000"/><rect x="1769" y="1484" width="57" height="57" fill="#000000"/><rect x="629" y="1541" width="57" height="57" fill="#000000"/><rect x="686" y="1541" width="57" height="57" fill="#000000"/><rect x="743" y="1541" width="57" height="57" fill="#000000"/><rect x="800" y="1541" width="57" height="57" fill="#000000"/><rect x="914" y="1541" width="57" height="57" fill="#000000"/><rect x="971" y="1541" width="57" height="57" fill="#000000"/><rect x="1199" y="1541" width="57" height="57" fill="#000000"/><rect x="1256" y="1541" width="57" height="57" fill="#000000"/><rect x="1313" y="1541" width="57" height="57" fill="#000000"/><rect x="1370" y="1541" width="57" height="57" fill="#000000"/><rect x="1427" y="1541" width="57" height="57" fill="#000000"/><rect x="1484" y="1541" width="57" height="57" fill="#000000"/><rect x="1541" y="1541" width="57" height="57" fill="#000000"/><rect x="1598" y="1541" width="57" height="57" fill="#000000"/><rect x="629" y="1598" width="57" height="57" fill="#000000"/><rect x="686" y="1598" width="57" height="57" fill="#000000"/><rect x="1142" y="1598" width="57" height="57" fill="#000000"/><rect x="1199" y="1598" width="57" height="57" fill="#000000"/><rect x="1313" y="1598" width="57" height="57" fill="#000000"/><rect x="1484" y="1598" width="57" height="57" fill="#000000"/><rect x="1598" y="1598" width="57" height="57" fill="#000000"/><rect x="629" y="1655" width="57" height="57" fill="#000000"/><rect x="857" y="1655" width="57" height="57" fill="#000000"/><rect x="914" y="1655" width="57" height="57" fill="#000000"/><rect x="1142" y="1655" width="57" height="57" fill="#000000"/><rect x="1199" y="1655" width="57" height="57" fill="#000000"/><rect x="1370" y="1655" width="57" height="57" fill="#000000"/><rect x="1541" y="1655" width="57" height="57" fill="#000000"/><rect x="1655" y="1655" width="57" height="57" fill="#000000"/><rect x="1712" y="1655" width="57" height="57" fill="#000000"/><rect x="800" y="1712" width="57" height="57" fill="#000000"/><rect x="857" y="1712" width="57" height="57" fill="#000000"/><rect x="1142" y="1712" width="57" height="57" fill="#000000"/><rect x="1256" y="1712" width="57" height="57" fill="#000000"/><rect x="1370" y="1712" width="57" height="57" fill="#000000"/><rect x="1598" y="1712" width="57" height="57" fill="#000000"/><rect x="1769" y="1712" width="57" height="57" fill="#000000"/><rect x="686" y="1769" width="57" height="57" fill="#000000"/><rect x="800" y="1769" width="57" height="57" fill="#000000"/><rect x="971" y="1769" width="57" height="57" fill="#000000"/><rect x="1028" y="1769" width="57" height="57" fill="#000000"/><rect x="1256" y="1769" width="57" height="57" fill="#000000"/><rect x="1313" y="1769" width="57" height="57" fill="#000000"/><rect x="1427" y="1769" width="57" height="57" fill="#000000"/><rect x="1484" y="1769" width="57" height="57" fill="#000000"/><rect x="1541" y="1769" width="57" height="57" fill="#000000"/><rect x="1598" y="1769" width="57" height="57" fill="#000000"/><rect x="173" y="173" width="399" height="57" fill="#000000"/><rect x="173" y="230" width="57" height="285" fill="#000000"/><rect x="515" y="230" width="57" height="285" fill="#000000"/><rect x="173" y="515" width="399" height="57" fill="#000000"/><rect x="173" y="173" width="57" height="57" fill="#000000"/><rect x="230" y="173" width="57" height="57" fill="#000000"/><rect x="287" y="173" width="57" height="57" fill="#000000"/><rect x="344" y="173" width="57" height="57" fill="#000000"/><rect x="401" y="173" width="57" height="57" fill="#000000"/><rect x="458" y="173" width="57" height="57" fill="#000000"/><rect x="515" y="173" width="57" height="57" fill="#000000"/><rect x="173" y="230" width="57" height="57" fill="#000000"/><rect x="515" y="230" width="57" height="57" fill="#000000"/><rect x="173" y="287" width="57" height="57" fill="#000000"/><rect x="515" y="287" width="57" height="57" fill="#000000"/><rect x="173" y="344" width="57" height="57" fill="#000000"/><rect x="515" y="344" width="57" height="57" fill="#000000"/><rect x="173" y="401" width="57" height="57" fill="#000000"/><rect x="515" y="401" width="57" height="57" fill="#000000"/><rect x="173" y="458" width="57" height="57" fill="#000000"/><rect x="515" y="458" width="57" height="57" fill="#000000"/><rect x="173" y="515" width="57" height="57" fill="#000000"/><rect x="230" y="515" width="57" height="57" fill="#000000"/><rect x="287" y="515" width="57" height="57" fill="#000000"/><rect x="344" y="515" width="57" height="57" fill="#000000"/><rect x="401" y="515" width="57" height="57" fill="#000000"/><rect x="458" y="515" width="57" height="57" fill="#000000"/><rect x="515" y="515" width="57" height="57" fill="#000000"/><rect x="287" y="287" width="171" height="171" fill="#000000"/><rect x="287" y="287" width="57" height="57" fill="#000000"/><rect x="344" y="287" width="57" height="57" fill="#000000"/><rect x="401" y="287" width="57" height="57" fill="#000000"/><rect x="287" y="344" width="57" height="57" fill="#000000"/><rect x="344" y="344" width="57" height="57" fill="#000000"/><rect x="401" y="344" width="57" height="57" fill="#000000"/><rect x="287" y="401" width="57" height="57" fill="#000000"/><rect x="344" y="401" width="57" height="57" fill="#000000"/><rect x="401" y="401" width="57" height="57" fill="#000000"/><rect x="1427" y="173" width="399" height="57" fill="#000000"/><rect x="1427" y="230" width="57" height="285" fill="#000000"/><rect x="1769" y="230" width="57" height="285" fill="#000000"/><rect x="1427" y="515" width="399" height="57" fill="#000000"/><rect x="1427" y="173" width="57" height="57" fill="#000000"/><rect x="1484" y="173" width="57" height="57" fill="#000000"/><rect x="1541" y="173" width="57" height="57" fill="#000000"/><rect x="1598" y="173" width="57" height="57" fill="#000000"/><rect x="1655" y="173" width="57" height="57" fill="#000000"/><rect x="1712" y="173" width="57" height="57" fill="#000000"/><rect x="1769" y="173" width="57" height="57" fill="#000000"/><rect x="1427" y="230" width="57" height="57" fill="#000000"/><rect x="1769" y="230" width="57" height="57" fill="#000000"/><rect x="1427" y="287" width="57" height="57" fill="#000000"/><rect x="1769" y="287" width="57" height="57" fill="#000000"/><rect x="1427" y="344" width="57" height="57" fill="#000000"/><rect x="1769" y="344" width="57" height="57" fill="#000000"/><rect x="1427" y="401" width="57" height="57" fill="#000000"/><rect x="1769" y="401" width="57" height="57" fill="#000000"/><rect x="1427" y="458" width="57" height="57" fill="#000000"/><rect x="1769" y="458" width="57" height="57" fill="#000000"/><rect x="1427" y="515" width="57" height="57" fill="#000000"/><rect x="1484" y="515" width="57" height="57" fill="#000000"/><rect x="1541" y="515" width="57" height="57" fill="#000000"/><rect x="1598" y="515" width="57" height="57" fill="#000000"/><rect x="1655" y="515" width="57" height="57" fill="#000000"/><rect x="1712" y="515" width="57" height="57" fill="#000000"/><rect x="1769" y="515" width="57" height="57" fill="#000000"/><rect x="1541" y="287" width="171" height="171" fill="#000000"/><rect x="1541" y="287" width="57" height="57" fill="#000000"/><rect x="1598" y="287" width="57" height="57" fill="#000000"/><rect x="1655" y="287" width="57" height="57" fill="#000000"/><rect x="1541" y="344" width="57" height="57" fill="#000000"/><rect x="1598" y="344" width="57" height="57" fill="#000000"/><rect x="1655" y="344" width="57" height="57" fill="#000000"/><rect x="1541" y="401" width="57" height="57" fill="#000000"/><rect x="1598" y="401" width="57" height="57" fill="#000000"/><rect x="1655" y="401" width="57" height="57" fill="#000000"/><rect x="173" y="1427" width="399" height="57" fill="#000000"/><rect x="173" y="1484" width="57" height="285" fill="#000000"/><rect x="515" y="1484" width="57" height="285" fill="#000000"/><rect x="173" y="1769" width="399" height="57" fill="#000000"/><rect x="173" y="1427" width="57" height="57" fill="#000000"/><rect x="230" y="1427" width="57" height="57" fill="#000000"/><rect x="287" y="1427" width="57" height="57" fill="#000000"/><rect x="344" y="1427" width="57" height="57" fill="#000000"/><rect x="401" y="1427" width="57" height="57" fill="#000000"/><rect x="458" y="1427" width="57" height="57" fill="#000000"/><rect x="515" y="1427" width="57" height="57" fill="#000000"/><rect x="173" y="1484" width="57" height="57" fill="#000000"/><rect x="515" y="1484" width="57" height="57" fill="#000000"/><rect x="173" y="1541" width="57" height="57" fill="#000000"/><rect x="515" y="1541" width="57" height="57" fill="#000000"/><rect x="173" y="1598" width="57" height="57" fill="#000000"/><rect x="515" y="1598" width="57" height="57" fill="#000000"/><rect x="173" y="1655" width="57" height="57" fill="#000000"/><rect x="515" y="1655" width="57" height="57" fill="#000000"/><rect x="173" y="1712" width="57" height="57" fill="#000000"/><rect x="515" y="1712" width="57" height="57" fill="#000000"/><rect x="173" y="1769" width="57" height="57" fill="#000000"/><rect x="230" y="1769" width="57" height="57" fill="#000000"/><rect x="287" y="1769" width="57" height="57" fill="#000000"/><rect x="344" y="1769" width="57" height="57" fill="#000000"/><rect x="401" y="1769" width="57" height="57" fill="#000000"/><rect x="458" y="1769" width="57" height="57" fill="#000000"/><rect x="515" y="1769" width="57" height="57" fill="#000000"/><rect x="287" y="1541" width="171" height="171" fill="#000000"/><rect x="287" y="1541" width="57" height="57" fill="#000000"/><rect x="344" y="1541" width="57" height="57" fill="#000000"/><rect x="401" y="1541" width="57" height="57" fill="#000000"/><rect x="287" y="1598" width="57" height="57" fill="#000000"/><rect x="344" y="1598" width="57" height="57" fill="#000000"/><rect x="401" y="1598" width="57" height="57" fill="#000000"/><rect x="287" y="1655" width="57" height="57" fill="#000000"/><rect x="344" y="1655" width="57" height="57" fill="#000000"/><rect x="401" y="1655" width="57" height="57" fill="#000000"/><svg version="1.0" id="Layer_1" x="714" y="714" viewBox="0 0 700 700" enable-background="new 0 0 700 700" xml:space="preserve" width="570" height="570" shape-rendering="auto">
<g>
	<g>
		<polygon fill="#000000" points="115.7,584.3 115.7,414.3 87.5,414.3 87.5,584.3 87.5,612.5 115.7,612.5 285.7,612.5 285.7,584.3       "/>
		<polygon fill="#000000" points="115.7,115.7 285.7,115.7 285.7,87.5 115.7,87.5 87.5,87.5 87.5,115.7 87.5,285.7 115.7,285.7       "/>
		<polygon fill="#000000" points="584.3,115.7 584.3,285.7 612.5,285.7 612.5,115.7 612.5,87.5 584.3,87.5 414.3,87.5 414.3,115.7       "/>
		<polygon fill="#000000" points="584.3,584.3 414.3,584.3 414.3,612.5 584.3,612.5 612.5,612.5 612.5,584.3 612.5,414.3     584.3,414.3   "/>
		<g>
			<path fill="#000000" d="M246.1,274c0-3.3-1.2-6-3.6-8.1c-2.4-2-6.5-3.9-12.5-5.7c-10.4-3-18.2-6.5-23.5-10.6     c-5.3-4.1-7.9-9.7-7.9-16.8s3-13,9.1-17.5c6.1-4.5,13.8-6.8,23.3-6.8c9.6,0,17.3,2.5,23.4,7.6c6,5.1,8.9,11.3,8.7,18.8l-0.1,0.4     h-16.9c0-4-1.3-7.3-4-9.8c-2.7-2.5-6.5-3.7-11.3-3.7c-4.7,0-8.3,1-10.8,3.1s-3.8,4.7-3.8,7.9c0,2.9,1.4,5.4,4.1,7.3     c2.7,1.9,7.4,3.9,14.1,6c9.6,2.7,16.9,6.2,21.8,10.6c4.9,4.4,7.4,10.1,7.4,17.3c0,7.4-2.9,13.3-8.8,17.6     c-5.9,4.3-13.6,6.5-23.3,6.5c-9.5,0-17.7-2.4-24.8-7.3c-7.1-4.9-10.5-11.7-10.3-20.5l0.1-0.4h17c0,5.2,1.6,9,4.7,11.4     c3.2,2.4,7.6,3.6,13.2,3.6c4.7,0,8.4-1,10.9-2.9C244.9,279.9,246.1,277.3,246.1,274z"/>
			<path fill="#000000" d="M342.4,267.1l0.1,0.4c0.2,9.4-2.7,16.8-8.6,22.3c-5.9,5.5-14,8.2-24.5,8.2c-10.5,0-19-3.4-25.5-10.1     s-9.8-15.4-9.8-26v-17.3c0-10.6,3.2-19.2,9.6-26c6.4-6.8,14.7-10.2,24.9-10.2c10.8,0,19.2,2.8,25.2,8.3c6.1,5.5,9,13,8.8,22.6     l-0.1,0.4h-17c0-5.7-1.4-10.1-4.1-13.2c-2.7-3.1-7-4.6-12.8-4.6c-5.2,0-9.4,2.1-12.4,6.4c-3.1,4.2-4.6,9.6-4.6,16.2v17.4     c0,6.6,1.6,12.1,4.8,16.3c3.2,4.2,7.6,6.4,13.1,6.4c5.5,0,9.5-1.5,12.1-4.4c2.6-2.9,3.9-7.3,3.9-13.1H342.4z"/>
			<path fill="#000000" d="M401.6,278h-30.5l-5.9,18.8h-17.6l29.9-87h17.9l29.8,87h-17.6L401.6,278z M375.4,264.3h21.9l-10.8-34.2     h-0.4L375.4,264.3z"/>
			<path fill="#000000" d="M503.6,296.7h-17.4l-35.1-59.4l-0.4,0.1v59.4h-17.4v-87h17.4l35.1,59.4l0.4-0.1v-59.3h17.4V296.7z"/>
		</g>
		<g>
			<path fill="#000000" d="M224.4,329.3l51.6,131.6h0.7l51.6-131.6h28v162h-21.9v-64.1l2.2-65.9l-0.6-0.1l-52.5,130.1h-14.6     l-52.3-129.8l-0.6,0.1l2.1,65.5v64.1h-21.9v-162H224.4z"/>
			<path fill="#000000" d="M492.7,416.2h-74.2v57.9h85.6v17.2H396.6v-162h106.3v17.2h-84.4V399h74.2V416.2z"/>
		</g>
	</g>
</g>
</svg></svg>

</svg>
';
    }

}
