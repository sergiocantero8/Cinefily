<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TicketRepository::class)
 */
class Ticket
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $price;


    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $qr_code;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="tickets")
     */
    private $user;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $sale_date;

    /**
     * @ORM\ManyToOne(targetEntity=Session::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $session;

    /**
     * @ORM\OneToOne(targetEntity=SeatBooked::class, mappedBy="ticket", cascade={"persist", "remove"})
     */
    private $seatBooked;


    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }


    public function getQrCode()
    {
        return $this->qr_code;
    }

    public function setQrCode(string $qr_code): string
    {
        $this->qr_code = $qr_code;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }


    public function getSaleDate(): ?\DateTimeInterface
    {
        return $this->sale_date;
    }

    public function setSaleDate(?\DateTimeInterface $sale_date): self
    {
        $this->sale_date = $sale_date;

        return $this;
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

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------ LIFECYCLE ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #

    public function getSeatBooked(): ?SeatBooked
    {
        return $this->seatBooked;
    }

    public function setSeatBooked(SeatBooked $seatBooked): self
    {
        // set the owning side of the relation if necessary
        if ($seatBooked->getTicket() !== $this) {
            $seatBooked->setTicket($this);
        }

        $this->seatBooked = $seatBooked;

        return $this;
    }


}
