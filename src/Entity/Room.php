<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RoomRepository::class)
 */
class Room
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
     * @ORM\Column(type="integer")
     */
    private $n_rows;

    /**
     * @ORM\Column(type="integer")
     */
    private $n_columns;

    /**
     * @ORM\Column(type="integer")
     */
    private $number;

    /**
     * @ORM\OneToMany(targetEntity=Session::class, mappedBy="room", orphanRemoval=true)
     */
    private $sessions;

    /**
     * @ORM\ManyToOne(targetEntity=Cinema::class, inversedBy="rooms")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cinema;

    /**
     * @ORM\OneToMany(targetEntity=Seat::class, mappedBy="room", orphanRemoval=true)
     */
    private $seats;

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
        $this->seats = new ArrayCollection();
    }

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNRows(): ?int
    {
        return $this->n_rows;
    }

    public function setNRows(int $n_rows): self
    {
        $this->n_rows = $n_rows;

        return $this;
    }

    public function getNColumns(): ?int
    {
        return $this->n_columns;
    }

    public function setNColumns(int $n_columns): self
    {
        $this->n_columns = $n_columns;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return Collection|Session[]
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function getCinema(): ?Cinema
    {
        return $this->cinema;
    }

    public function setCinema(?Cinema $cinema): self
    {
        $this->cinema = $cinema;

        return $this;
    }

    /**
     * @return Collection|Seat[]
     */
    public function getSeats(): Collection
    {
        return $this->seats;
    }



    # ------------------------------------------------ LIFECYCLE ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    public function removeSession(Session $session): self
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getRoom() === $this) {
                $session->setRoom(null);
            }
        }

        return $this;
    }

    public function removeSeat(Seat $seat): self
    {
        if ($this->seats->removeElement($seat)) {
            // set the owning side to null (unless already changed)
            if ($seat->getRoom() === $this) {
                $seat->setRoom(null);
            }
        }

        return $this;
    }

    public function addSession(Session $session): self
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions[] = $session;
            $session->setRoom($this);
        }

        return $this;
    }

    public function addSeat(Seat $seat): self
    {
        if (!$this->seats->contains($seat)) {
            $this->seats[] = $seat;
            $seat->setRoom($this);
        }

        return $this;
    }
    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #
}
