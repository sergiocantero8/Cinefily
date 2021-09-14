<?php

namespace App\Entity;

use App\Repository\CinemaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CinemaRepository::class)
 */
class Cinema
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
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $location;

    /**
     * @ORM\OneToMany(targetEntity=Session::class, mappedBy="cinema", orphanRemoval=true)
     */
    private $sessions;

    /**
     * @ORM\OneToMany(targetEntity=Room::class, mappedBy="cinema", orphanRemoval=true)
     */
    private $rooms;

    /**
     * @ORM\Column(type="float")
     */
    private $tickets_price;

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #
    
    public function __construct()
    {
        $this->sessions = new ArrayCollection();
        $this->rooms = new ArrayCollection();
    }


    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection|Session[]
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }


    /**
     * @return Collection|Room[]
     */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    # ------------------------------------------------ LIFECYCLE ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    public function addRoom(Room $room): self
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms[] = $room;
            $room->setCinema($this);
        }

        return $this;
    }

    public function removeRoom(Room $room): self
    {
        if ($this->rooms->removeElement($room)) {
            // set the owning side to null (unless already changed)
            if ($room->getCinema() === $this) {
                $room->setCinema(null);
            }
        }

        return $this;
    }

    public function addSession(Session $session): self
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions[] = $session;
            $session->setCinema($this);
        }

        return $this;
    }

    public function removeSession(Session $session): self
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getCinema() === $this) {
                $session->setCinema(null);
            }
        }

        return $this;
    }
    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #

    public function getTicketsPrice(): ?float
    {
        return $this->tickets_price;
    }

    public function setTicketsPrice(float $tickets_price): self
    {
        $this->tickets_price = $tickets_price;

        return $this;
    }

   
    
}
