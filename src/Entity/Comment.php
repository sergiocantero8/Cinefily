<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommentRepository::class)
 */
class Comment
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
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=EventData::class, inversedBy="comments")
     * @ORM\JoinColumn(nullable=true)
     */
    private $event;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $tmdb_id;

    
    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #


    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

        
    /**
     * Devuelve el id del comentario
     * 
     * @return int
     * 
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    
    /**
     * Devuelve el texto del comentario
     *
     * @return string
     */
    public function getText(): ?string
    {
        return $this->text;
    }
    
    /**
     * Recibe por parámetro el texto del comentario y lo actualiza
     *
     * @param  string $text
     * @return self
     */
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }
    
    /**
     * Devuelve el usuario que ha comentado(su propietario)
     *
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
    
    /**
     * Recibe por parámetro el nuevo propietario del comentario y lo actualiza
     *
     * @param  string $user
     * @return self
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }


    # ------------------------------------------------ LIFECYCLE ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #

    public function getEvent(): ?EventData
    {
        return $this->event;
    }

    public function setEvent(?EventData $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTime $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getTmdbId(): ?int
    {
        return $this->tmdb_id;
    }

    public function setTmdbId(?int $tmdb_id): self
    {
        $this->tmdb_id = $tmdb_id;

        return $this;
    }
}
