<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const ROLE_ADMIN = 0;
    public const ROLE_USER = 1;
    public const ROLE_MODERATOR = 2;

    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $surname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $email;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $privileges;

    /**
     * @ORM\Column(type="blob", nullable=true)
     */
    protected $photo;

    /**
     * @ORM\Column(type="string", length=14, nullable=true)
     */
    protected $phone_number;

    /**
     * @ORM\OneToMany(targetEntity=Ticket::class, mappedBy="user")
     */
    protected $tickets;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="user", orphanRemoval=true)
     */
    protected $comments;

        
    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #
    
    /**
     * Constructor de User
     *
     * @return void
     */
    public function __construct()
    {

        # La primera vez que se crea un usuario tendrá el rol de usuario
        $this->setPrivileges(self::ROLE_USER);

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
     * @param string $name
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
     * @param string $surname
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
     * @param string $password
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
     * @param string $email
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
     * @param int|null $privileges
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
     * @return File
     */
    public function getPhoto(): File
    {
        return $this->photo;
    }
    
    /**
     * setPhoto
     *
     * @param File $photo
     * @return self
     */
    public function setPhoto(File $photo): self
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
     * @param mixed $phone_number
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
    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        if ($this->privileges === NULL):
            $this->setPrivileges(self::ROLE_USER);
        endif;
    }
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

    /**
     * Devuelve los tipos de roles de usuario disponibles
     * @return array
     */
    public function getRoles():array
    {
        return array(
            static::ROLE_ADMIN,
            static::ROLE_USER,
            static::ROLE_MODERATOR
        );
    }

    public function getSalt():void
    {

    }


    public function eraseCredentials():void
    {

    }

    public function getUsername(): string
    {
        return $this->getEmail();
    }
}
