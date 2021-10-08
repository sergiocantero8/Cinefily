<?php /** @noinspection PhpParamsInspection */

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\EventData;
use App\Entity\LogInfo;
use App\Entity\Session;
use App\Entity\User;
use DateInterval;
use DateTime;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\DisabledException;
use function array_key_exists;

class MainController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const LIMIT_UPCOMING_FILMS = 5;

    public const ROUTE_HOME = 'home';
    public const ROUTE_LOG_INFO = 'log_page';
    public const ROUTE_SHOW_TIMES = 'show_times';
    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- ROUTES ------------------------------------------------------- #


    /**
     * @Route("/", name="home")
     */
    public function renderHomePage(EventController $eventController): Response
    {

        //$configuration = $eventController->getIMDBConfiguration();

        /*
        $upcomingsFilmsPage = $eventController->getTMDBFilmsUpcoming();


        foreach ($upcomingsFilmsPage as $key => $film):
            if ($key <= static::LIMIT_UPCOMING_FILMS):
                $upcoming_data[] = $eventController->getIMDBFilmByID($film);
            else:
                break;
            endif;
        endforeach;

        */

        // Obtenemos los ids de TMDB que queremos mostrar en el home para cargarlos
        $homeIDSFilms = $this->getTMDB_FilmIDs();

        // Hacemos una llamada a la API de TMDB por cada ID que tengamos almacenado
        foreach ($homeIDSFilms as $filmID):
            $filmsTMDB[] = $eventController->getIMDBFilmByID($filmID);
        endforeach;

        // Obtenemos todos los géneros para eventos
        $genresTypes = EventController::getAllGenresTypes();

        // Y filtramos las películas por su género
        foreach ($genresTypes as $genre):
            $categoryFilms[$genre] = $this->getDoctrine()->getRepository(EventData::class)->findByCategory($genre);
        endforeach;

        $data = array();

        // Si hay alguna película
        if (!empty($categoryFilms)):
            // Por cada categoría con sus películas, obtenemos los datos de cada película que queramos mostrar
            foreach ($categoryFilms as $category => $films):
                foreach ($films as $film):
                    $data[$category][] = array(
                        'id' => $film->getID(),
                        'title' => strtoupper($film->getTitle()),
                        'genres' => $film->getGender(),
                        'release_date' => $film->getReleaseDate()->format('Y-m-d'),
                        'duration' => $film->getDuration(),
                        'summary' => EventData::getShortenSummary($film->getDescription()),
                        'poster_photo' => $film->getPosterPhoto()
                    );
                endforeach;
            endforeach;
        endif;

        // Si hay películas obtenidas a través de la API de TMDB
        if (isset($filmsTMDB)):
            foreach ($filmsTMDB as $filmTMDB):
                if ($filmTMDB !== NULL):

                    $genres = EventController::gendersToString($filmTMDB['genres']);

                    $overview = EventData::getShortenSummary($filmTMDB['overview']);

                    $data['TMDB'][] = array(
                        'tmdb_id' => $filmTMDB['id'],
                        'title' => strtoupper($filmTMDB['title']),
                        'genres' => $genres,
                        'release_date' => $filmTMDB['release_date'],
                        'duration' => $filmTMDB['runtime'],
                        'summary' => $overview,
                        'poster_photo' => $eventController->getImageBaseURLIMDB() . 'w154/' . $filmTMDB['poster_path'],
                    );

                endif;
            endforeach;
        endif;

        return $this->render('home.html.twig', array('films' => $data));

    }

    /**
     * @Route("/admin/log", name="log_page")
     * @param Request $request
     * @return Response
     */
    public function renderLogInfo(Request $request): Response
    {
        # Si el usuario está identificado pero no es administrador no podrá acceder
        if (!$this->getUser() || ($this->getUser() && !\in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        # Obtenemos toda la información del Log
        $logInfo = $this->getDoctrine()->getRepository(LogInfo::class)->findBy([], [], 10, null);

        $data = array();
        foreach ($logInfo as $info):
            $data[] = array(
                'id' => $info->getId(),
                'date' => $info->getDate()->format('Y-m-d H:i'),
                'type' => LogInfo::convertTypeLogInfo($info->getType()),
                'info' => $info->getInfo()
            );
        endforeach;


        return $this->render('log.html.twig', array('log_info' => $data));
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
                    $sessionsByEvent[$session->getEvent()->getId()][] = $session;
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
    private function getTMDB_FilmIDs(): array
    {
        return array(157336, 808, 399566, 24, 615457, 577922, 460465);
    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
