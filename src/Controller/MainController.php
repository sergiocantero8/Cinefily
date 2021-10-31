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
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function array_key_exists;
use function in_array;

class MainController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const LIMIT_UPCOMING_FILMS = 5;
    public const LIMIT_FILMS = 6;

    public const ROUTE_HOME = 'home';
    public const ROUTE_LOG_INFO = 'log_page';
    public const ROUTE_CONTACT = 'contact';

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- ROUTES ------------------------------------------------------- #


    /**
     * @Route("/", name="home")
     * @throws Exception
     */
    public function renderHomePage(): Response
    {

        // Obtenemos todos los géneros para eventos
        $genresTypes = EventController::getAllGenresTypes();

        // Y filtramos las películas por su género
        foreach ($genresTypes as $genre):
            $categoryFilms[$genre] = $this->getDoctrine()->getRepository(EventData::class)->findByCategory
            ($genre, static::LIMIT_FILMS);

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
                        'poster_photo' => $film->getPosterPhoto(),
                        'youtube_trailer' => $film->getYoutubeTrailer()
                    );

                endforeach;
            endforeach;
        endif;

        # Obtenemos todas las sesiones programadas para hoy
        $sessionsByEvent = array();
        $now = new DateTime();

        $activeSessions=$this->getDoctrine()->getRepository(Session::class)->getTodayActiveSessions(
            new DateTime(), $now->add(new DateInterval(('P1D'))));
        foreach ($activeSessions as $session):
            if (!array_key_exists($session->getEvent()->getId(), $sessionsByEvent)):
                $event = $session->getEvent();
                $sessionsByEvent[$session->getEvent()->getId()]['event'] = $event;
            endif;
            $sessionsByEvent[$session->getEvent()->getId()][$session->getRoom()->getNumber()][] = $session;
        endforeach;


        return $this->render('home.html.twig', array('films' => $data, 'sessionsByEvent' => $sessionsByEvent ?? null));

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
        if (!$this->getUser() || ($this->getUser() && !in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        # Obtenemos toda la información del Log
        $logInfoQuery = $this->getDoctrine()->getRepository(LogInfo::class)->createQueryBuilder('l')
            ->orderBy('l.date','DESC')->getQuery();
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

    /**
     * @Route("/contact", name="contact")
     * @param Request $request
     * @param Swift_Mailer $mailer
     * @return Response
     */
    public function contact(Request $request, Swift_Mailer $mailer): Response
    {

        $user = $this->getUser();
        $form = $this->createFormBuilder(array('csrf_protection' => FALSE))
            ->setMethod(Request::METHOD_GET)
            ->setAction($this->generateUrl(static::ROUTE_CONTACT))
            ->add('reason', ChoiceType::class, array(
                'label' => 'Motivo',
                'choices' => array('Opinion' => 'Opinión',
                    'Solicitud de sala para evento' => 'Solicitud de sala para evento', 'Mejoras' => 'Mejoras')
            ))
            ->add('email', EmailType::class, array(
                'label' => 'Email',
                'data' => $user !== null ? $user->getUsername() : null
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Descripción',
                'attr' => array(
                    'rows' => 4
                ),
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Enviar',
            ))
            ->getForm();

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()):
            $dataForm = $form->getData();

            $emailMessage = (new Swift_Message($dataForm['reason']))
                ->setFrom('cinefily@gmail.com')
                ->setTo('cinefily@gmail.com')
                ->setSubject($dataForm['reason'])
                ->setBody($dataForm['description'] . ' escrito por ' . $dataForm['email'], 'text/plain');

            if ($mailer->send($emailMessage)):
                $this->addFlash('success', 'Tu mensaje se ha enviado correctamente');
                $this->redirectToRoute('home');
            endif;
        endif;

        return $this->render('contact.html.twig', array('form' => $form->createView()));
    }

    /**
     * @Route("/error", name="page_error")
     * @return Response
     */
    public function renderPageError(): Response
    {
        return $this->render('error.html.twig', array());
    }


    # ------------------------------------------------- METHODS ------------------------------------------------------ #
    private function getTMDB_FilmIDs(): array
    {
        return array(157336, 808, 399566, 24, 615457, 577922, 460465);
    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
