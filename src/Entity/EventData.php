<?php

namespace App\Entity;

use App\Entity\User;
use App\Repository\EventDataRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use function count;

/**
 * @ORM\Entity(repositoryClass=EventDataRepository::class)
 */
class EventData
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const MAX_CHARACTERS = 111;
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
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $gender;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duration;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $release_date;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $director;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $actors;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\File(maxSize = "1024k",
     *     mimeTypes={"image/jpeg"} ,
     *     mimeTypesMessage = "Please upload a valid Image"
     * )
     */
    private $poster_photo;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $rating;

    /**
     * @ORM\Column(type="boolean")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $age_rating;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="event", orphanRemoval=true)
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity=Session::class, mappedBy="event", orphanRemoval=true)
     */
    private $sessions;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $tag_line;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $backdrop_path;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $youtube_trailer;


    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?array $genders): self
    {
        $stringGenders = '';
        foreach ($genders as $gender):
            if (!empty($stringGenders)):
                $stringGenders .= ', ';
            endif;
            $stringGenders .= $gender;
        endforeach;

        $this->gender = $stringGenders;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->release_date;
    }

    public function setReleaseDate(?\DateTimeInterface $release_date): self
    {
        $this->release_date = $release_date;

        return $this;
    }

    public function getActors(): ?string
    {
        return $this->actors;
    }

    public function setActors(?string $actors): self
    {
        $this->actors = $actors;

        return $this;
    }

    public function getPosterPhoto(): ?string
    {
        return $this->poster_photo;
    }

    public function setPosterPhoto(string $poster_photo): self
    {
        $this->poster_photo = $poster_photo;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAgeRating(): ?string
    {
        return $this->age_rating;
    }

    public function setAgeRating(?string $age_rating): self
    {
        $this->age_rating = $age_rating;

        return $this;
    }

    public function getDirector(): ?string
    {
        return $this->director;
    }

    public function setDirector(?string $director): self
    {
        $this->director = $director;

        return $this;
    }

    public function getTagLine(): ?string
    {
        return $this->tag_line;
    }

    public function setTagLine(?string $tag_line): self
    {
        $this->tag_line = $tag_line;

        return $this;
    }

    public function getBackdropPath(): ?string
    {
        return $this->backdrop_path;
    }

    public function setBackdropPath(?string $backdrop_path): self
    {
        $this->backdrop_path = $backdrop_path;

        return $this;
    }

    public function getYoutubeTrailer(): ?string
    {
        return $this->youtube_trailer;
    }

    public function setYoutubeTrailer(?string $youtube_trailer): self
    {
        $this->youtube_trailer = $youtube_trailer;

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setEvent($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getEvent() === $this) {
                $comment->setEvent(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection|Session[]
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): self
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions[] = $session;
            $session->setEvent($this);
        }

        return $this;
    }

    public function removeSession(Session $session): self
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getEvent() === $this) {
                $session->setEvent(null);
            }
        }

        return $this;
    }


    # ------------------------------------------------ LIFECYCLE ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #

    public static function getShortenSummary(string $overview, ?int $maxCharacters = null): string
    {

        if ($maxCharacters !== null):
            $newOverview = substr(chunk_split($overview, $maxCharacters), 0, $maxCharacters);
        else:
            $newOverview = substr(chunk_split($overview, static::MAX_CHARACTERS), 0, static::MAX_CHARACTERS);
        endif;


        $newOverview .= '...';

        return $newOverview;
    }


}
