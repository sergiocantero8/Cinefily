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
     * @ORM\Column(type="blob", nullable=true)
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
     * @ORM\OneToOne(targetEntity=Seat::class, mappedBy="ticket", cascade={"persist", "remove"})
     */
    private $seat;

    /**
     * @ORM\ManyToOne(targetEntity=Session::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $session;


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

    public function setQrCode($qr_code): self
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

    public function getSeat(): ?Seat
    {
        return $this->seat;
    }

    public function setSeat(Seat $seat): self
    {
        // set the owning side of the relation if necessary
        if ($seat->getTicket() !== $this) {
            $seat->setTicket($this);
        }

        $this->seat = $seat;

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


}
