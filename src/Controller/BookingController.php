<?php

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\EventData;
use App\Entity\Room;
use App\Entity\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function count;


class BookingController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- ROUTES ------------------------------------------------------- #

    /**
     * Ruta para añadir un cine a través de un formulario. Un cine solo lo puede añadir un usuario con privilegios
     * de administrador
     * @Route("/booking", methods={"GET"},  name="booking_tickets")
     */
    public function bookingTickets(Request $request): Response
    {
        $id = $request->get('session');

        if ($id === null):
            $template = 'error.html.twig';
        else:
            $template = 'booking/room_booking.html.twig';
        endif;

        // Recuperamos todos los datos de la sesión, la sala, el cine y el evento
        $session = $this->getDoctrine()->getRepository(Session::class)->findOneBy(array('id' => $id));

        if ($session !== null):
            $cinema = $this->getDoctrine()->getRepository(Cinema::class)->findOneBy(array('id' => $session->getCinema()));
            $event = $this->getDoctrine()->getRepository(EventData::class)->findOneBy(array('id' => $session->getEvent()));
            $room = $this->getDoctrine()->getRepository(Room::class)->findOneBy(array('id' => $session->getRoom()));
        endif;


        $data= array(
            'session' => $session,
            'cinema' => $cinema ?? null,
            'event' => $event ?? null,
            'room' => $room ?? null
        );

        return $this->render($template, $data);
    }

    /**
     * Ruta para añadir un cine a través de un formulario. Un cine solo lo puede añadir un usuario con privilegios
     * de administrador
     * @Route("/booking/processBooking", methods={"GET"},  name="process_booking")
     */
    public function processBooking(Request $request): Response
    {

        $s = $request->get('seats');

        if ($s === null):
            $template = 'error.html.twig';
        else:
            $template = 'booking/process_booking.html.twig';
        endif;

        $seats= array_filter(explode(",",$s));

        $data=array(
            'seats'=>$seats,
            'n_seats' => count($seats)
        );

        return $this->render($template, $data);
    }
    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
