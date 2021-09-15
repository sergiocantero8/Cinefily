<?php

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\Room;
use App\Entity\Seat;
use App\Entity\User;
use App\Form\AddCinemaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function count;

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
     * @Route("/cinema/add", name="add_cinema")
     */
    public function addCinema(Request $request): Response
    {

        // Comprobamos que el usuario está identificado y además tiene privilegios de admnistrador
        if ($this->getUser() && !\in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true)):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        $form = $this->createForm(AddCinemaType::class, null, array());
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()):
            $dataForm = $form->getData();
            $nRooms = $dataForm['n_rooms'];
            $nRows = $dataForm['n_rows'];
            $nColumns = $dataForm['n_columns'];
            if ($nRooms > 0 && $nRooms <= static::MAX_ROOMS && $nRows > 0 && $nRows <= static::MAX_ROWS &&
                $nColumns > 0 && $nColumns <= static::MAX_COLUMNS):

                # Creamos un nuevo cine
                $cinema = new Cinema();

                $cinema->setName($dataForm['name']);
                $cinema->setLocation($dataForm['location']);
                $cinema->setTicketsPrice($dataForm['tickets_price']);

                $em = $this->getDoctrine()->getManager();
                $em->persist($cinema);

                # Con este bucle vamos creando una sala de cine hasta el número de salas que nos ha indicado el usuario
                for ($i = 1; $i <= $nRooms; $i++):
                    $room = new Room();
                    $room->setCinema($cinema);
                    $room->setNRows($nRows);
                    $room->setNColumns($nColumns);
                    $room->setNumber($i);

                    # Guardamos en la base de datos la sala
                    $em->persist($room);

                    # Por cada sala creamos los asientos teniendo en cuenta las filas y asientos que tienen las salas
                    for ($j = 1; $j <= $nRows; $j++):
                        for ($k = 1; $k <= $nColumns; $k++):
                            $seat = new Seat();
                            $seat->setRoom($room);
                            $seat->setRow($j);
                            $seat->setNumber($k);
                            $seat->setStatus(true); #True para indicar que el asiento está libre
                            $em->persist($seat);
                        endfor;
                    endfor;
                endfor;
                $em->flush();
            else:
                $this->addFlash('danger', 'Los número de salas, filas y asientos no pueden ser negativos 
                y tienen que ser iguales o menores que sus máximos permitidos.');
            endif;

        endif;

        $data = array(
            'form' => $form->createView()
        );

        return $this->render('cinema/add.html.twig', $data);
    }

    /**
     * Ruta para mostrar los detalles de todos los cines almacenados en la web
     * @Route("/cinema/all", name="all_cinema")
     */
    public function showAllCinemas(Request $request): Response
    {

        $data = array();
        $cinemas = $this->getDoctrine()->getRepository(Cinema::class)->findAll();

        foreach ($cinemas as $cinema):
            $rooms = $this->getDoctrine()->getRepository(Room::class)->findBy(array('cinema' => $cinema));
            $nSeats=0;

            foreach ($rooms as $room):
                $nSeats+=$room->getNRows() * $room->getNColumns();
            endforeach;

            $data[] = array(
                'id' => $cinema->getID(),
                'name' => $cinema->getName(),
                'location' => $cinema->getLocation(),
                'tickets_price' => $cinema->getTicketsPrice(),
                'n_rooms' => count($rooms),
                'n_seats' => $nSeats
            );
        endforeach;

        return $this->render('cinema/all.html.twig', array('data'=>$data));
    }

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #


}
