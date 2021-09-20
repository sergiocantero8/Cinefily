<?php

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\EventData;
use App\Entity\Room;
use App\Entity\Session;
use App\Entity\User;
use App\Form\AddSessionType;
use DateInterval;
use DateTime;
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
     * @Route("/session/add", name="add_session")
     */
    public function addSession(Request $request): Response
    {
        # Si el usuario está identificado pero no es administrador no podrá acceder
        if (!$this->getUser() || ($this->getUser() && !\in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        # Obtenemos todos los eventos, cines y idiomas almacenados
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


        if ($form->isSubmitted() && $form->isValid()):
            $formData = $form->getData();


            # Obtenemos el cine seleccionado, el evento y todas las sesiones y salas de ese cine
            # para asignar una sala a la película
            $cinema = $this->getDoctrine()->getRepository(Cinema::class)->findOneBy(array('id'=>$formData['cinema']));
            $event = $this->getDoctrine()->getRepository(EventData::class)->findOneBy(array('id'=>$formData['event']));
            $sessions = $this->getDoctrine()->getRepository(Session::class)->findBy(array('cinema' => $cinema));
            $rooms=$this->getDoctrine()->getRepository(Room::class)->findBy(array('cinema' => $cinema));

            $now = new DateTime();
            $eee = new DateTime();
            $now->add(new DateInterval(('PT' . 90 . 'M')));
           // $eee->format('Y-m-d H:i'),$now->format('Y-m-d H:i')
            # Si la fecha de la sesión es anterior a la actual mostramos un mensaje
            if ($formData['schedule'] < $now):
                $this->addFlash('error', 'La fecha de la sesión es anterior a la fecha actual');
            # Si está vacío significa que no hay ninguna sesión para ese cine
            elseif (empty($sessions)):
                $session = new Session();
                $session->setCinema($cinema);
                $session->setEvent($event);
                $session->setLanguage($formData['language']);
                $session->setSchedule($formData['schedule']);

                # Como es la primera sesión del cine, le asignamos la primera sala
                $session->setRoom($rooms[0]);

                $em = $this->getDoctrine()->getManager();
                $em->persist($session);
                $em->flush();
                $this->addFlash('success', '¡La sesión ha sido añadida correctamente!');
                return $this->redirectToRoute('home');
            else:

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
