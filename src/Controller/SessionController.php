<?php /** @noinspection MultipleReturnStatementsInspection */

/** @noinspection PhpParamsInspection */

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\EventData;
use App\Entity\LogInfo;
use App\Entity\Room;
use App\Entity\Session;
use App\Entity\User;
use App\Form\AddSessionType;
use DateInterval;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SessionController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const LANGUAGE = 'castellano';
    public const ORIGINAL_LANGUAGE = 'original';
    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- ROUTES ------------------------------------------------------- #


    /**
     * @Route("/admin/session/add", name="add_session")
     * @throws Exception
     */
    public function addSession(Request $request): Response
    {
        # Si el usuario está identificado pero no es administrador no podrá acceder
        if (!$this->getUser() || ($this->getUser() && !\in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        # Obtenemos todos los eventos, cines y idiomas almacenados para mostrarlos en el selector de las sesiones
        $eventsRe = $this->getDoctrine()->getRepository(EventData::class)->findAll();
        $cinemasRe = $this->getDoctrine()->getRepository(Cinema::class)->findAll();
        $languages = array("Castellano" => static::LANGUAGE, "Versión original (subtitulada)" => static::ORIGINAL_LANGUAGE);

        $events = array();
        $cinemas = array();

        foreach ($eventsRe as $event):
            $events[$event->getTitle()] = $event->getID();
        endforeach;

        foreach ($cinemasRe as $cinema):
            $cinemas[$cinema->getName()] = $cinema->getID();
        endforeach;

        # Usamos compact para cuando el key del array coincide con el nombre de la variable
        $form = $this->createForm(AddSessionType::class, NULL,
            array('data' => compact('events', 'cinemas', 'languages'))
        );
        $form->handleRequest($request);


        # Si se envia el formulario
        if ($form->isSubmitted() && $form->isValid()):
            $formData = $form->getData();
            $em = $this->getDoctrine()->getManager();

            # Obtenemos el cine seleccionado, el evento y todas las sesiones y salas de ese cine
            # para asignar una sala a la película
            $cinema = $this->getDoctrine()->getRepository(Cinema::class)->findOneBy(array('id' => $formData['cinema']));
            $event = $this->getDoctrine()->getRepository(EventData::class)->findOneBy(array('id' => $formData['event']));

            $rooms = null;
            $sessions = null;

            if ($cinema !== null):
                $sessions = $this->getDoctrine()->getRepository(Session::class)->findByActiveSessions($cinema);
                $rooms = $this->getDoctrine()->getRepository(Room::class)->findBy(array('cinema' => $cinema));
            endif;

            $now = new DateTime();


            # Obtenemos cuando empieza la sesión y cuando terminaría, le sumamos la duración del evento
            if ($event !== null):
                $schedule_start = $formData['schedule'];
                $schedule_end = new DateTime($schedule_start->format('Y-m-d H:i'));
                $schedule_end->add(new DateInterval(('PT' . $event->getDuration() . 'M')));
            endif;


            # Si la fecha de la sesión es anterior a la actual mostramos un mensaje
            if ($formData['schedule'] < $now):
                $this->addFlash('error', 'La fecha de la sesión es anterior a la fecha actual');
                $info = new LogInfo(LogInfo::TYPE_ERROR, 'Intento fallido de añadir una sesión debido a que la
                                    fecha de la sesión es anterior a la fecha actual');
                $em->persist($info);
                $em->flush();

            # Si son nulos tanto sessión como rooms significa que el cine seleccionado no existe (inusual)
            elseif ($sessions === null && $rooms === null):
                $this->addFlash('error', 'No existe el cine seleccionado');
                $info = new LogInfo(LogInfo::TYPE_ERROR, 'Intento fallido de añadir una sesión debido a que el 
                                    cine seleccionado no existe');
                $em->persist($info);
                $em->flush();
            # Si está vacío significa que no hay ninguna sesión activa para ese cine
            elseif (empty($sessions) && isset($schedule_start, $schedule_end) && $rooms !== null):
                $session = new Session();
                $session->setCinema($cinema);
                $session->setEvent($event);
                $session->setLanguage($formData['language']);
                $session->setSchedule($schedule_start);
                $session->setScheduleEnd($schedule_end);
                # Como es la primera sesión del cine, le asignamos la primera sala
                $session->setRoom($rooms[0]);

                if (!$event->getStatus()):
                    $event->setStatus(true);
                    $em->persist($event);
                endif;

                $this->addFlash('success', '¡La sesión ha sido añadida correctamente!');
                $info = new LogInfo(LogInfo::TYPE_SUCCESS, 'Se ha añadido una nueva sesión del evento ' .
                    $event->getTitle() . ' con fecha de inicio ' . $schedule_start->format('Y-m-d H:i') .
                    ' en la sala ' . $rooms[0]->getNumber());
                $em->persist($session);
                $em->persist($info);
                $em->flush();

                return $this->redirectToRoute('home');
            # Si no está vacío significa que hay sesiones activas en ese cine
            else:
                $assigned = false;
                foreach ($rooms as $room):
                    if (empty($this->getDoctrine()->getRepository(Session::class)->findByActiveSessionsByRoom(
                        $cinema, $room, $schedule_start, $schedule_end))):
                        $session = new Session();
                        $session->setCinema($cinema);
                        $session->setEvent($event);
                        $session->setLanguage($formData['language']);
                        $session->setSchedule($schedule_start);
                        $session->setScheduleEnd($schedule_end);
                        $session->setRoom($room);

                        if (!$event->getStatus()):
                            $event->setStatus(true);
                            $em->persist($event);
                        endif;

                        $assigned = true;
                        $info = new LogInfo(LogInfo::TYPE_SUCCESS, 'Se ha añadido una nueva sesión del evento ' .
                            $event->getTitle() . ' con fecha de inicio ' . $schedule_start->format('Y-m-d H:i') .
                            ' en la sala ' . $room->getNumber());

                        $em->persist($session);
                        $em->persist($info);
                        $em->flush();
                        break;
                    endif;
                endforeach;

                if ($assigned):
                    $this->addFlash('success', '¡La sesión ha sido añadida correctamente!');
                    return $this->redirectToRoute('home');
                else:
                    $message = 'No se ha podido añadir la sesión a 
                    la hora seleccionada porque hay conflictos con otra sesión';
                    $info = new LogInfo(LogInfo::TYPE_WARNING, $message);
                    $em->persist($info);
                    $em->flush();
                    $this->addFlash('error', $message
                    );
                endif;
            endif;
        endif;

        $data = array(
            'form' => $form->createView()
        );

        return $this->render('session/add.html.twig', array('data' => $data));
    }

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
