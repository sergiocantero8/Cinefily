<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SessionRepository::class)
 */
class Session
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
     * @ORM\Column(type="datetime")
     */
    private $schedule;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $language;

    /**
     * @ORM\ManyToOne(targetEntity=EventData::class, inversedBy="sessions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity=Cinema::class, inversedBy="sessions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cinema;

    /**
     * @ORM\ManyToOne(targetEntity=Room::class, inversedBy="sessions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $room;

    /**
     * @ORM\Column(type="datetime")
     */
    private $schedule_end;

    /**
     * @ORM\OneToMany(targetEntity=SeatBooked::class, mappedBy="session", orphanRemoval=true)
     */
    private $seatBookeds;

    public function __construct()
    {
        $this->seatBookeds = new ArrayCollection();
    }

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchedule(): ?\DateTimeInterface
    {
        return $this->schedule;
    }

    public function setSchedule(\DateTimeInterface $schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getEvent(): ?EventData
    {
        return $this->event;
    }

    public function setEvent(?EventData $event): self
    {
        $this->event = $event;

        return $this;
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

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): self
    {
        $this->room = $room;

        return $this;
    }

    # ------------------------------------------------ LIFECYCLE ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #

    public function getScheduleEnd(): ?\DateTimeInterface
    {
        return $this->schedule_end;
    }

    public function setScheduleEnd(\DateTimeInterface $schedule_end): self
    {
        $this->schedule_end = $schedule_end;

        return $this;
    }

    /**
     * @return Collection|SeatBooked[]
     */
    public function getSeatBookeds(): Collection
    {
        return $this->seatBookeds;
    }

    public function addSeatBooked(SeatBooked $seatBooked): self
    {
        if (!$this->seatBookeds->contains($seatBooked)) {
            $this->seatBookeds[] = $seatBooked;
            $seatBooked->setSession($this);
        }

        return $this;
    }

    public function removeSeatBooked(SeatBooked $seatBooked): self
    {
        if ($this->seatBookeds->removeElement($seatBooked)) {
            // set the owning side to null (unless already changed)
            if ($seatBooked->getSession() === $this) {
                $seatBooked->setSession(null);
            }
        }

        return $this;
    }
}
