<?php

namespace App\Entity;

use App\Repository\SeatBookedRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SeatBookedRepository::class)
 */
class SeatBooked
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Session::class, inversedBy="seatBookeds")
     * @ORM\JoinColumn(nullable=false)
     */
    private $session;

    /**
     * @ORM\ManyToOne(targetEntity=Seat::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $seat;

    /**
     * @ORM\OneToOne(targetEntity=Ticket::class, inversedBy="seatBooked", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $ticket;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getSeat(): ?Seat
    {
        return $this->seat;
    }

    public function setSeat(?Seat $seat): self
    {
        $this->seat = $seat;

        return $this;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

}
