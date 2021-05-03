<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $surname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $privileges;

    /**
     * @ORM\Column(type="blob", nullable=true)
     */
    private $photo;

    /**
     * @ORM\Column(type="string", length=14, nullable=true)
     */
    private $phone_number;

    /**
     * @ORM\OneToMany(targetEntity=Ticket::class, mappedBy="user")
     */
    private $tickets;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="user", orphanRemoval=true)
     */
    private $comments;

        
    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #
    
    /**
     * Constructor de User
     *
     * @return void
     */
    public function __construct()
    {
        $this->tickets = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }


    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #    
    /**
     * Devuelve el id del usuario
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    
    /**
     * Devuelve el nombre del usuario
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }
    
    /**
     * Recibe el nuevo nombre por parámetro y lo actualiza
     *
     * @param  string $name
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
    
    /**
     * Devuelve el apellido del usuario
     *
     * @return string
     */
    public function getSurname(): ?string
    {
        return $this->surname;
    }
    
    /**
     * Recibe el nuevo nombre por parámetro y lo actualiza
     *
     * @param  string $surname
     * @return self
     */
    public function setSurname(?string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }
    
    /**
     * Devuelve la contraseña (codificada)
     *
     * @return string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }
    
    /**
     * Reciba la nueva contraseña por parámetro y la actualiza
     *
     * @param  string $password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
    
    /**
     * Devuelve el email del usuario
     *
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }
    
    /**
     * Recibe el nuevo email por parámetro y lo actualiza
     *
     * @param  string $email
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }
    
    /**
     *  Devuelve los privilegios que tiene el usuario
     *
     * @return int
     */
    public function getPrivileges(): ?int
    {
        return $this->privileges;
    }
    
    /**
     * Recibe un nuevo privilegio por parámetro y lo actualiza
     *
     * @param  int $privileges
     * @return self
     */
    public function setPrivileges(?int $privileges): self
    {
        $this->privileges = $privileges;

        return $this;
    }
    
    /**
     * Devuelve la foto de perfil del usuario
     *
     * @return void
     */
    public function getPhoto()
    {
        return $this->photo;
    }
    
    /**
     * setPhoto
     *
     * @param  mixed $photo
     * @return self
     */
    public function setPhoto($photo): self
    {
        $this->photo = $photo;

        return $this;
    }
    
    /**
     * getPhoneNumber
     *
     * @return string
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }
    
    /**
     * setPhoneNumber
     *
     * @param  mixed $phone_number
     * @return self
     */
    public function setPhoneNumber(?string $phone_number): self
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    /**
     * @return Collection|Ticket[]
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }


    # ------------------------------------------------ LIFECYCLE ----------------------------------------------------- #

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    public function addTicket(Ticket $ticket): self
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets[] = $ticket;
            $ticket->setUser($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): self
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getUser() === $this) {
                $ticket->setUser(null);
            }
        }

        return $this;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }

    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #

    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #




   
}
