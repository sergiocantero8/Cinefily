<?php /** @noinspection GlobalVariableUsageInspection */

/** @noinspection PhpParamsInspection */

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\Room;
use App\Entity\Session;
use App\Entity\User;
use App\Form\AddCinemaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function count;
use function in_array;

class CinemaController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    // Rutas
    public const ROUTE_ADD_CINEMA = 'add_cinema';

    public const MAX_ROOMS = 15;
    public const MAX_ROWS = 20;
    public const MAX_COLUMNS = 30;

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- ROUTES ------------------------------------------------------- #

    /**
     * Ruta para añadir un cine a través de un formulario. Un cine solo lo puede añadir un usuario con privilegios
     * de administrador
     * @Route("/admin/cinema/add", name="add_cinema")
     */
    public function addCinema(Request $request): Response
    {

        // Comprobamos que el usuario está identificado y además tiene privilegios de administrador
        if (!$this->getUser() || ($this->getUser() && !in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        if ($_SERVER['REQUEST_METHOD'] === 'POST'):
            $nRooms = (int)$_POST['n_rooms'];

            if ($nRooms > 0 && $nRooms <= static::MAX_ROOMS):

                # Creamos un nuevo cine
                $cinema = new Cinema();

                $cinema->setName($_POST['name']);
                $cinema->setLocation($_POST['location']);
                $cinema->setTicketsPrice($_POST['price']);

                $em = $this->getDoctrine()->getManager();
                $em->persist($cinema);

                # Con este bucle vamos creando una sala de cine hasta el número de salas que nos ha indicado el usuario
                for ($i = 1; $i <= $nRooms; $i++):
                    $nRows = (int)$_POST['nrows_' . $i];
                    $nColumns = (int)$_POST['nseats_' . $i];

                    if ($nRows > 0 && $nRows <= static::MAX_ROWS && $nColumns > 0 && $nColumns <= static::MAX_COLUMNS):
                        $room = new Room();
                        $room->setCinema($cinema);
                        $room->setNRows($nRows);
                        $room->setNColumns($nColumns);
                        $room->setNumber($i);

                        # Guardamos en la base de datos la sala
                        $em->persist($room);
                    endif;
                endfor;
                $em->flush();
                $this->addFlash('success', 'El cine se ha añadido correctamente');
                $this->redirectToRoute('home');
            else:
                $this->addFlash('danger', 'Los número de salas, filas y asientos no pueden ser negativos 
                y tienen que ser iguales o menores que sus máximos permitidos.');
            endif;

        endif;

        $data = array();

        return $this->render('cinema/add.html.twig', $data);
    }

    /**
     * Ruta para mostrar los detalles de todos los cines almacenados en la web
     * @Route("/cinema/all", name="all_cinema")
     */
    public function showAllCinemas(): Response
    {

        $data = array();
        $cinemas = $this->getDoctrine()->getRepository(Cinema::class)->findAll();

        foreach ($cinemas as $cinema):
            $rooms = $this->getDoctrine()->getRepository(Room::class)->findBy(array('cinema' => $cinema));
            $nSeats = 0;

            foreach ($rooms as $room):
                $nSeats += $room->getNRows() * $room->getNColumns();
            endforeach;

            $nActiveSessions = count($this->getDoctrine()->getRepository(Session::class)->findByActiveSessions($cinema));
            $data[] = array(
                'id' => $cinema->getID(),
                'name' => $cinema->getName(),
                'location' => $cinema->getLocation(),
                'tickets_price' => $cinema->getTicketsPrice(),
                'n_rooms' => count($rooms),
                'n_seats' => $nSeats,
                'n_sessions' => $nActiveSessions
            );
        endforeach;

        return $this->render('cinema/all.html.twig', array('data' => $data));
    }

    /**
     * Ruta para borrar un cine junto con sus salas
     * @Route("/cinema/delete", name="delete_cinema")
     */
    public function deleteCinema(Request $request): Response
    {

        if (!$this->getUser() || ($this->getUser() && !in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        $cinemaID = $request->get('id_cinema');

        if ($cinemaID !== null):
            $cinema = $this->getDoctrine()->getRepository(Cinema::class)->find($cinemaID);
            $em = $this->getDoctrine()->getManager();

            if ($cinema !== null):
                $em->remove($cinema);
                $em->flush();
                $this->addFlash('success', 'El cine se ha eliminado correctamente');
            else:
                $this->addFlash('error', 'No existe el cine con ese ID');
            endif;

        endif;

        return $this->redirectToRoute('home');
    }

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
