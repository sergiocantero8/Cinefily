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
use Knp\Component\Pager\PaginatorInterface;
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



        $upcomingsFilmsPage = $eventController->getTMDBFilmsUpcoming();


        foreach ($upcomingsFilmsPage as $key => $film):
            if ($key <= static::LIMIT_UPCOMING_FILMS):
                $upcoming_data[] = $eventController->getIMDBFilmByID($film);
            else:
                break;
            endif;
        endforeach;



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
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function renderLogInfo(Request $request, PaginatorInterface $paginator): Response
    {
        # Si el usuario está identificado pero no es administrador no podrá acceder
        if (!$this->getUser() || ($this->getUser() && !\in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        # Obtenemos toda la información del Log
        $logInfoQuery = $this->getDoctrine()->getRepository(LogInfo::class)->createQueryBuilder('L')
            ->getQuery();
        $logResults = null;

        if ($logInfoQuery !== null):
            // Paginar los resultados de la consulta
            $logResults = $paginator->paginate(
            // Consulta Doctrine, no resultados
                $logInfoQuery,
                // Definir el parámetro de la página
                $request->query->getInt('page', 1),
                // Items per page
                15
            );
        endif;

        $data = array();
        foreach ($logResults as $info):
            $data[] = array(
                'id' => $info->getId(),
                'date' => $info->getDate()->format('Y-m-d H:i'),
                'type' => LogInfo::convertTypeLogInfo($info->getType()),
                'info' => $info->getInfo()
            );
        endforeach;


        return $this->render('log.html.twig', array('log_info' => $data, 'results' => $logResults));
    }


    # ------------------------------------------------- METHODS ------------------------------------------------------ #
    private function getTMDB_FilmIDs(): array
    {
        return array(157336, 808, 399566, 24, 615457, 577922, 460465);
    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
