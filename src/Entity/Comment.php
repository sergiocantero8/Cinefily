<?php

namespace App\Entity;

use App\Repository\CommentRepository;
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
}
