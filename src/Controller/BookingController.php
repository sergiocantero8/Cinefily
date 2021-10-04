<?php /** @noinspection PhpParamsInspection */

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\EventData;
use App\Entity\LogInfo;
use App\Entity\Room;
use App\Entity\Seat;
use App\Entity\Session;
use App\Entity\Ticket;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function count;
use Symfony\Component\Lock\LockFactory;


class BookingController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_CASH = 'cash';
    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- ROUTES ------------------------------------------------------- #

    /**
     * Ruta para renderizar la página para reservar de forma interactiva los asientos para el evento
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


        $data = array(
            'session' => $session,
            'cinema' => $cinema ?? null,
            'event' => $event ?? null,
            'room' => $room ?? null
        );

        return $this->render($template, $data);
    }

    /**
     * Ruta para procesar la reserva de los asientos seleccionados
     * @Route("/booking/processBooking", methods={"GET"},  name="process_booking")
     */
    public function processBooking(Request $request): Response
    {

        $s = $request->get('seats');
        $sessionID = (int)$request->get('id_session');

        if ($sessionID !== null):
            $session = $this->getDoctrine()->getRepository(Session::class)->findOneBy(array('id' => $sessionID));
            if ($session !== null):
                $event = $this->getDoctrine()->getRepository(EventData::class)->findOneBy(array('id' => $session->getEvent()));
                $room = $this->getDoctrine()->getRepository(Room::class)->findOneBy(array('id' => $session->getRoom()));
                $cinema = $this->getDoctrine()->getRepository(Cinema::class)->findOneBy(array('id' => $session->getCinema()));
            endif;
        endif;

        if ($s === null || $sessionID === null):
            $template = 'error.html.twig';
        else:
            $template = 'booking/process_booking.html.twig';
        endif;


        $seats = array_filter(explode(',', $s));
        $matrixSeats = $this->getMatrixSeats($seats);

        $data = array(
            'seats' => $seats,
            'n_seats' => count($seats),
            'event' => $event ?? null,
            'session' => $session ?? null,
            'room' => $room ?? null,
            'cinema' => $cinema ?? null,
            'matrixSeats' => $matrixSeats
        );

        return $this->render($template, $data);
    }

    /**
     * Ruta para procesar el pago de las entradas que se seleccionaron, se pueden recibir dos métodos de pago: paypal o
     * efectivo. Por paypal se hace la pasarela de pago y se utiliza la API de paypal, en efectivo reserva la entrada y
     * se tendría que pagar en taquilla.
     * @Route("/booking/payment", methods={"GET"},  name="payment_booking")
     */
    public function payment(Request $request, LockFactory $lockFactory): Response
    {

        # Obtenemos el método de pago, los asientos y el id de la sesión
        $s = $request->get('seats');
        $sessionID = (int)$request->get('id_session');
        $method = $request->get('method');
        $price = (float)$request->get('price');

        if ($method === null || $s === null || $sessionID === null):
            $template = 'error.html.twig';
        else:
            $template = 'home.html.twig';
        endif;

        # Obtenemos la sesión junto con el cine y la sala correspondientes y seguimos con el proceso de reserva
        if ($sessionID !== null):
            $session = $this->getDoctrine()->getRepository(Session::class)->findOneBy(array('id' => $sessionID));
            if ($session !== null):
                $room = $this->getDoctrine()->getRepository(Room::class)->findOneBy(array('id' => $session->getRoom()));

                if ($room !== null):
                    # Obtenemos la matriz con los asientos y creamos el cerrojo para que no haya concurrencia a
                    # la hora de reservar el asiento
                    $seats = array_filter(explode(",", $s));
                    $n_seats = count($seats);
                    $matrixSeats = $this->getMatrixSeats($seats);

                    $em = $this->getDoctrine()->getManager();
                    $lock = $lockFactory->createLock('seat-booking');

                    foreach ($matrixSeats as $row => $columns):
                        foreach ($columns as $column):

                            $seat = $this->getDoctrine()->getRepository(Seat::class)->findOneBy(
                                array('row' => (int)$row, 'number' => (int)$column, 'room' => $room->getId())
                            );

                            if ($seat !== null):
                                # Obtiene el cerrojo para que no haya concurrencia
                                $lock->acquire(true);

                                # Si el asiento está libre
                                if ($seat->getStatus()):
                                    $ticket = new Ticket();
                                    $ticket->setSeat($seat);
                                    $ticket->setSession($session);

                                    if ($this->getUser()):
                                        $ticket->setUser($this->getUser());
                                    endif;

                                    $ticket->setPrice($price);
                                    $ticket->setSaleDate(new DateTime());

                                    # Guardamos toda la información relacionado con el ticket
                                    $em->persist($ticket);
                                    $em->flush();

                                    # Cambiamos el estado del asiento a falso ya que ahora está ocupado
                                    $seat->setStatus(false);
                                    $seat->setTicket($ticket);

                                    $em->persist($seat);
                                    $em->flush();

                                    $message = 'Sus asientos se han reservado correctamente 
                                    y se le ha enviado al correo las entradas.';
                                    if ($this->getUser()):
                                        $message.='También las tiene disponibles en su perfil, en el apartado Mis entradas.';
                                    endif;
                                    $this->addFlash('success', $message);

                                # Si no estuviera libre creamos un error
                                else:
                                    $info = new LogInfo(LogInfo::TYPE_ERROR, 'Se intenta reservar un asiento 
                                    que está ocupado');
                                    $em->persist($info);
                                    $em->flush();
                                endif;

                                # Libera el cerrojo
                                $lock->release();
                            endif;

                        endforeach;
                    endforeach;
                endif;
            endif;
        endif;

        return $this->redirectToRoute('home');
    }
    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    /**
     * @param array $seats
     * @return array
     */
    public function getMatrixSeats(array $seats): array
    {

        $matrixSeats = array();
        foreach ($seats as $seat):
            if (preg_match_all('!\d+!', $seat, $matches)):
                $matrixSeats[(int)$matches[0][0]][] = (int)$matches[0][1];
            endif;
        endforeach;

        return $matrixSeats;
    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
