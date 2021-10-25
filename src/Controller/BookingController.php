<?php /** @noinspection PhpParamsInspection */

namespace App\Controller;

use App\Entity\Coupon;
use App\Entity\LogInfo;
use App\Entity\SeatBooked;
use App\Entity\Session;
use App\Entity\Ticket;
use DateTime;
use Exception;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function count;
use Symfony\Component\Lock\LockFactory;


class BookingController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_CASH = 'cash';

    private const API_QR_GENERATOR_KEY = 'wwVIbVZF0U3w4ZxansmDVYukx0q03gc9JRd5kbI_6CuFlv3kSFct4200fpqaPZFG';
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
        $session = $this->getDoctrine()->getRepository(Session::class)->find($id);

        if ($session !== null):
            $cinema = $session->getCinema();
            $event = $session->getEvent();
            $room = $session->getRoom();
            if ($room !== null):
                $matrixStatusSeats = array();
                for ($row = 1; $row <= $room->getNRows(); $row++):
                    for($number = 1; $number <= $room->getNColumns(); $number++):
                        $seatBooked = $this->getDoctrine()->getRepository(SeatBooked::class)->findBy(
                            array('session' => $session, 'row' => $row, 'number' => $number)
                        );
                        $matrixStatusSeats[$row][$number] = empty($seatBooked);
                    endfor;
                endfor;
            endif;
        endif;


        $data = array(
            'session' => $session,
            'cinema' => $cinema ?? null,
            'event' => $event ?? null,
            'room' => $room ?? null,
            'matrixStatusSeats' => $matrixStatusSeats ?? null
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
        $email = $request->get('email');


        if ($sessionID !== null):
            $session = $this->getDoctrine()->getRepository(Session::class)->find($sessionID);
            if ($session !== null):
                $event = $session->getEvent();
                $room = $session->getRoom();
                $cinema = $session->getCinema();
            endif;
        endif;

        if ($this->getUser()):
            $couponsObject = $this->getDoctrine()->getRepository(Coupon::class)->findBy(array('user' => $this->getUser()));
            $coupons = array();

            if ($couponsObject !== null):
                foreach ($couponsObject as $coupon):
                    $coupons[] = array(
                        'id' => $coupon->getId(), 'code' => $coupon->getCode(), 'discount' => $coupon->getDiscount(),
                        'active' => $coupon->getActive());
                endforeach;
            endif;
        endif;

        if ($s === null || $sessionID === null):
            $template = 'error.html.twig';
        else:
            $template = 'booking/process_booking.html.twig';
        endif;


        $seats = array_filter(explode(',', $s));
        $matrixSeats = $this->getMatrixSeats($seats);
        $qr = $this->generateTicketQR(7);
        $data = array(
            'seats' => $seats,
            'n_seats' => count($seats),
            'event' => $event ?? null,
            'session' => $session ?? null,
            'room' => $room ?? null,
            'cinema' => $cinema ?? null,
            'matrixSeats' => $matrixSeats,
            'email' => $email,
            'qr' => $qr,
            'coupons' => $coupons ?? null
        );


        return $this->render($template, $data);
    }

    /**
     * Ruta para procesar el pago de las entradas que se seleccionaron, se pueden recibir dos métodos de pago: paypal o
     * efectivo. Por paypal se hace la pasarela de pago y se utiliza la API de paypal, en efectivo reserva la entrada y
     * se tendría que pagar en taquilla.
     * @Route("/booking/payment", methods={"GET"},  name="payment_booking")
     * @throws Exception
     */
    public function payment(Request $request, LockFactory $lockFactory, Swift_Mailer $mailer): Response
    {

        # Obtenemos el método de pago, los asientos, el id de la sesión, el precio total y el email al que hay que enviar
        # las entradas
        $s = $request->get('seats');
        $sessionID = (int)$request->get('id_session');
        $method = $request->get('method');
        $price = (float)$request->get('price');
        $email = $request->get('email');
        $couponID = (int)$request->get('id_coupon');


        # Si alguna de las anteriores variables es nula, renderizamos un error
        if ($method === null || $s === null || $sessionID === null):
            $route = 'error_page';
        else:
            $route = 'home';
        endif;

        # Obtenemos la sesión junto con el cine y la sala correspondientes y seguimos con el proceso de reserva
        if ($sessionID !== null):
            $session = $this->getDoctrine()->getRepository(Session::class)->find($sessionID);
            if ($session !== null):
                $room = $session->getRoom();
                $cinema = $session->getCinema();
                $event = $session->getEvent();
                if ($room !== null):
                    # Obtenemos la matriz con los asientos y creamos el cerrojo para que no haya concurrencia a
                    # la hora de reservar el asiento
                    $seats = array_filter(explode(',', $s));
                    $n_seats = count($seats);
                    $matrixSeats = $this->getMatrixSeats($seats);

                    $em = $this->getDoctrine()->getManager();
                    $lock = $lockFactory->createLock('seat-booking');

                    foreach ($matrixSeats as $row => $columns):
                        foreach ($columns as $column):

                                # Obtiene el cerrojo para que no haya concurrencia
                                $lock->acquire(true);

                                $seatBooked = $this->getDoctrine()->getRepository(SeatBooked::class)->findBy(
                                    array('session' => $session, 'row'=>(int)$row, 'number'=> (int)$column, 'room'=> $room->getId())
                                );
                                # Si el asiento está libre
                                if (empty($seatBooked)):

                                    $ticket = new Ticket();
                                    $ticket->setSession($session);


                                    if ($this->getUser()):
                                        $ticket->setUser($this->getUser());
                                    endif;


                                    $ticket->setSaleDate(new DateTime());

                                    if ($couponID !== 0):
                                        $coupon = $this->getDoctrine()->getRepository(Coupon::class)->find($couponID);
                                        if ($coupon !== null):
                                            $coupon->setActive(false);
                                            $ticket->setPrice($price - (($coupon->getDiscount() / 100) * $price));
                                            $em->persist($coupon);
                                            $em->flush();
                                        endif;
                                    else:
                                        $ticket->setPrice($price);
                                    endif;

                                    # Creamos el asiento reservado correspondiente al asiento y a la sesión
                                    $seatBooked = new SeatBooked();
                                    $seatBooked->setSession($session);
                                    $seatBooked->setTicket($ticket);

                                    # Guardamos toda la información relacionado con el ticket
                                    $em->persist($seatBooked);
                                    $em->flush();

                                    $ticket->setSeatBooked($seatBooked);
                                    $em->persist($ticket);
                                    $em->flush();


                                    $message = 'Sus asientos se han reservado correctamente. ';

                                    $logInfo = new LogInfo(LogInfo::TYPE_SUCCESS, 'Se han reservado ' . $n_seats . ' asientos para la session
                                                                        con ID' . $session->getID() . ' y con email asociado ' . $email);
                                    $em->persist($logInfo);
                                    $em->flush();

                                    try {
                                        $ticket->setQrCode($this->generateTicketQR($ticket->getId()));
                                    } catch (ClientExceptionInterface
                                    | RedirectionExceptionInterface |
                                    DecodingExceptionInterface | TransportExceptionInterface | ServerExceptionInterface $e) {
                                        new LogInfo(LogInfo::TYPE_ERROR, 'Error al generar código QR');
                                        $em->persist($logInfo);
                                        $em->flush();
                                        throw new Exception("Error al generar código QR", $e);

                                    }
                                    $em->persist($ticket);
                                    $em->flush();
                                    if ($this->getUser()):
                                        $message .= 'También las tiene disponibles en su perfil, en el apartado Mis entradas';
                                    endif;
                                    $typeMessage = 'success';

                                # Si no estuviera libre creamos un error
                                else:
                                    $message = 'No se ha podido realizar la reserva, debido a que se ha intentaod reservar un asiento
                                    ocupado';
                                    $typeMessage = 'error';
                                    $info = new LogInfo(LogInfo::TYPE_ERROR, 'Se intenta reservar un asiento 
                                    que está ocupado');
                                    $em->persist($info);
                                    $em->flush();
                                endif;

                                # Libera el cerrojo
                                $lock->release();


                        endforeach;
                    endforeach;

                    $emailMessage = (new Swift_Message('Tus entradas'))
                        ->setFrom('cinefily@gmail.com')
                        ->setTo((string)$email)
                        ->setBody(
                            $this->renderView(
                                'emails/buy_ticket.html.twig',
                                ['session' => $session,
                                    'room' => $room,
                                    'cinema' => $cinema,
                                    'event' => $event,
                                    'matrixSeats' => $matrixSeats
                                ]
                            ),
                            'text/html'
                        );


                    if ($mailer->send($emailMessage)):
                        $message .= '  y se le ha enviado al correo las entradas';

                        $logInfo = new LogInfo(LogInfo::TYPE_SUCCESS, 'Se le han enviado las entradas correctamente al email '
                            . $email);
                        $em->persist($logInfo);
                        $em->flush();

                    else:
                        $this->addFlash('error', 'Error al enviarle las entradas al email');

                        $logInfo = new LogInfo(LogInfo::TYPE_ERROR, 'No se ha podido enviarle las entradas al email '
                            . $email);
                        $em->persist($logInfo);
                        $em->flush();

                    endif;


                    $this->addFlash($typeMessage ?? 'error', $message ?? 'No existe la sala');
                endif;
            endif;
        endif;

        return $this->redirectToRoute($route);
    }


    /**
     * Ruta para validar un ticket
     * @Route("/ticket/validate", methods={"GET"},  name="validate_ticket")
     */
    public function validateTicket(Request $request): RedirectResponse
    {

        # Obtenemos el id del ticket
        $id = $request->get('id');

        if ($id !== null):
            $this->getDoctrine()->getRepository(Ticket::class)->find($id);
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

    /**
     *  Devuelve los datos de las películas que se estrenarán proximamente
     * @param int $ticketID
     * @return array|null
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function generateTicketQR(int $ticketID): string
    {
        $result = NULL;

        $url = 'https://api.qr-code-generator.com/v1/create?access-token=' . static::API_QR_GENERATOR_KEY;
        $response = $this->client->request(
            'POST',
            $url,
            [
                'body' => [
                    'frame_name' => 'no-frame',
                    'qr_code_text' => 'ticket/validate?id=' . $ticketID,
                    'image_format' => 'SVG',
                    'qr_code_logo' => 'scan-me-square'
                ]
            ]

        );


        if ($response->getStatusCode() === EventController::SUCCESS_STATUS_CODE):
            $result = $response->getContent();
        endif;

        return $result;
    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
